<?php
/* Règles du jeu et état global de la partie (partagé entre le terminal, la carte Arduino et la page Game Master).
   Le terminal (api.php) et les boutons physiques (bridge.php) appellent les mêmes fonctions.
   Le texte du scénario est dans scenario.php. */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mqtt_lite.php';
require_once __DIR__ . '/scenario.php';

const DUREE_PARTIE = 1800;                 // 30 min : la jauge monte seule jusqu'à 100 %
const RAB_MAX      = 900;                  // le game master peut ajouter 15 min au total
const MOT_DE_PASSE = 'max2018';
const IP_CIBLE     = '192.168.1.42';

/* Sujets MQTT. Le préfixe ('lerna') peut être changé avec LERNA_TOPIC_PREFIX : les tests s'en servent
   pour ne pas parler à la vraie carte quand ils tournent sur le même broker. */
$prefixe = getenv('LERNA_TOPIC_PREFIX') ?: 'lerna';
define('TOPIC_ALARME', "$prefixe/alarme");       // ON / OFF
define('TOPIC_ETAT',   "$prefixe/etat");         // attente / jeu / alarme / victoire / defaite
define('TOPIC_JAUGE',  "$prefixe/jauge");        // 0 à 100
define('TOPIC_MSG',    "$prefixe/msg");          // texte à afficher sur la 1re ligne de l'écran (vide = texte par défaut)
define('TOPIC_BOUTON', "$prefixe/bouton/");      // + nom du bouton, envoyé par la carte
define('TOPIC_CARTE',  "$prefixe/carte/etat");   // en-ligne / hors-ligne, envoyé par la carte
define('TOPIC_PONT',   "$prefixe/pont/etat");    // en-ligne / hors-ligne, envoyé par le pont
unset($prefixe);

function lower(string $s): string { return function_exists('mb_strtolower') ? mb_strtolower($s) : strtolower($s); }

function game_step(): int { return (int) state_get('step', '0'); }

/** Note, pour chaque objectif franchi, le nombre de secondes écoulées depuis le début (sert à l'historique). */
function game_marquer_etapes(int $de, int $a): void
{
    $start = (int) state_get('start', '0');
    $t = json_decode(state_get('etapes', '{}'), true) ?: [];
    for ($i = $de; $i <= $a; $i++) if (!isset($t[$i])) $t[$i] = $start ? max(0, time() - $start) : 0;
    state_set('etapes', json_encode($t));
}

function game_unlock(int $n): void
{
    $cur = game_step();
    if ($n <= $cur) return;
    game_marquer_etapes($cur + 1, $n);
    state_set('step', $n);
}

function game_duree(): int { return DUREE_PARTIE + (int) state_get('rab', '0'); }

function game_leak(): int
{
    $frozen = state_get('frozen');
    if ($frozen !== null) return (int) $frozen;
    $start = (int) state_get('start', '0');
    $t = $start ? (time() - $start) / game_duree() * 100 : 0;
    return (int) min(100, floor($t) + (int) state_get('penalty', '0'));
}

/** Secondes restantes avant la fuite totale, si le temps seul faisait monter la jauge. Null hors partie. */
function game_restant(): ?int
{
    $start = (int) state_get('start', '0');
    if (!$start || !in_array(game_phase(), ['jeu', 'alarme'], true)) return null;
    return max(0, $start + game_duree() - time());
}

/** Ajoute des points de fuite. $erreur = false pour un indice (il n'est pas compté comme une erreur). */
function game_penalty(int $points, string $raison = '', bool $erreur = true): void
{
    state_set('penalty', (int) state_get('penalty', '0') + $points);
    if ($erreur) state_set('erreurs', (int) state_get('erreurs', '0') + 1);
    event_add('penalite', "+{$points} %" . ($raison !== '' ? " : $raison" : ''));
}

/** attente → jeu → alarme → victoire, ou défaite si la jauge atteint 100 % avant la fin. */
function game_phase(): string
{
    if (state_get('frozen') !== null) return 'victoire';
    $step = game_step();
    if ($step < S_LOGIN) return 'attente';
    if (game_leak() >= 100) return 'defaite';
    return $step >= S_ALARME ? 'alarme' : 'jeu';
}

/** À appeler régulièrement : note la défaite dans le journal et l'historique, une seule fois. */
function game_tick(): void
{
    if (game_phase() === 'defaite' && state_get('defaite_notee') === null) {
        state_set('defaite_notee', '1');
        state_set('fin', time());
        event_add('defaite', 'la jauge a atteint 100 %');
        game_archiver('defaite');
    }
}

function game_login(): void
{
    state_set('start', time());
    state_set('step', S_LOGIN);
    state_del('etapes', 'ordre');
    game_marquer_etapes(S_LOGIN, S_LOGIN);
    ordre_redemarrage();   // l'ordre de la partie est tiré dès maintenant
    event_add('login', 'début de la partie');
}

function game_victory(string $source): void
{
    state_set('frozen', game_leak());
    state_set('fin', time());
    game_unlock(S_SCRIPT + 1);
    event_add('victoire', "source : {$source}, jauge figée à " . game_leak() . ' %');
    game_archiver('victoire');
}

function game_reset(string $source = 'terminal'): void
{
    if (game_step() >= S_LOGIN) { if (state_get('fin') === null) state_set('fin', time()); game_archiver('abandon'); }
    $cles = ['step', 'penalty', 'start', 'fin', 'frozen', 'defaite_notee', 'rab', 'indices', 'seq', 'tel_essais', 'tel_bloque',
             'tel_deverrouille', 'ordre', 'etapes', 'erreurs', 'archive', 'baie_journal'];
    for ($i = 0; $i <= S_SCRIPT; $i++) $cles[] = "hint_$i";
    state_del(...$cles);
    event_add('reset', "source : {$source}");
}

/** Passe directement à une étape (Game Master, secours). Les objectifs sautés sont considérés comme réussis. */
function game_set_step(int $n): void
{
    $n = max(0, min(S_SCRIPT, $n));
    if ($n >= S_LOGIN && !(int) state_get('start', '0')) { state_set('start', time()); ordre_redemarrage(); }
    if ($n > game_step()) game_marquer_etapes(game_step() + 1, $n);
    state_set('step', $n);
    state_del('seq');
    event_add('etape', "forcée à {$n} par le game master");
}

function game_rab(): bool
{
    $rab = (int) state_get('rab', '0');
    if ($rab >= RAB_MAX) return false;
    state_set('rab', $rab + 300);   // 5 minutes à chaque fois, 15 au maximum
    event_add('rab', '+5 min accordées par le game master');
    return true;
}

/* ---- Double authentification : un code à 6 chiffres qui change toutes les 30 s, comme une application d'authentification ---- */
function game_2fa_code(?int $t = null): string
{
    $secret = getenv('LERNA_2FA_SECRET') ?: 'lerna-kovac-2fa';
    $h = hash_hmac('sha1', pack('J', intdiv($t ?? time(), 30)), $secret, true);
    $o = ord($h[19]) & 15;
    $n = ((ord($h[$o]) & 0x7f) << 24) | (ord($h[$o + 1]) << 16) | (ord($h[$o + 2]) << 8) | ord($h[$o + 3]);
    return str_pad((string) ($n % 1000000), 6, '0', STR_PAD_LEFT);
}

function game_2fa_valide(string $code): bool
{
    $code = preg_replace('/\D/', '', $code);
    foreach ([-1, 0, 1] as $d) if (hash_equals(game_2fa_code(time() + 30 * $d), $code)) return true;
    return false;
}

/* ---- Indices ---- */
/** Donne l'indice suivant de l'objectif en cours (et coûte 5 %). texte = null s'il n'y en a plus. */
function game_hint(): array
{
    $step = game_step();
    $liste = indices()[$step] ?? [];
    $niv = (int) state_get("hint_$step", '0');
    if ($niv >= count($liste)) return ['texte' => null, 'niveau' => $niv, 'total' => count($liste)];
    state_set("hint_$step", $niv + 1);
    state_set('indices', (int) state_get('indices', '0') + 1);
    game_penalty(COUT_INDICE, 'indice ' . ($niv + 1) . '/' . count($liste), false);
    return ['texte' => $liste[$niv], 'niveau' => $niv + 1, 'total' => count($liste)];
}

/* ---- Redémarrage des serveurs (boutons physiques) ---- */

/** L'ordre de redémarrage de la partie : le routeur d'abord, puis les trois autres dans un ordre tiré au hasard. */
function ordre_redemarrage(): array
{
    $s = state_get('ordre');
    if ($s !== null && $s !== '') return explode(',', $s);
    $autres = ['bd', 'archives', 'web'];
    do {
        shuffle($autres);
        $ordre = array_merge(['routeur'], $autres);
        $boutons = array_map('bouton_du_serveur', $ordre);
        $monotone = $boutons === ['4', '3', '2', '1'] || $boutons === ['1', '2', '3', '4'];   // pas de simple balayage de A3 à A0
    } while ($monotone);
    state_set('ordre', implode(',', $ordre));
    return $ordre;
}

/** "X dépend de Y" : Y doit être démarré avant X. Une chaîne (qui impose l'ordre) plus deux flèches de plus pour la carte. */
function dependances(): array
{
    $o = ordre_redemarrage();
    return [[$o[1], $o[0]], [$o[2], $o[1]], [$o[3], $o[2]], [$o[2], $o[0]], [$o[3], $o[1]]];
}

function game_serveurs_redemarres(): array
{
    $seq = state_get('seq', '');
    return $seq === '' ? [] : explode(',', $seq);
}

function baie_journal_ajouter(string $texte, string $type): void
{
    $j = json_decode(state_get('baie_journal', '[]'), true) ?: [];
    $j[] = ['ts' => time(), 'type' => $type, 'texte' => $texte];
    state_set('baie_journal', json_encode(array_slice($j, -14)));
}

function baie_journal(): array { return json_decode(state_get('baie_journal', '[]'), true) ?: []; }

function game_serveur(string $serveur): string
{
    $faits = game_serveurs_redemarres();
    $nom = nom_serveur($serveur);
    $erreur = null;
    if (in_array($serveur, $faits, true)) {
        $erreur = "$nom est déjà en ligne";
    } else {
        $manque = [];
        foreach (dependances() as [$de, $vers]) if ($de === $serveur && !in_array($vers, $faits, true)) $manque[] = nom_serveur($vers);
        if ($manque) $erreur = "$nom dépend de " . implode(' et de ', $manque) . ", qui n'est pas encore démarré";
    }
    if ($erreur !== null) {
        state_del('seq');
        baie_journal_ajouter("ERREUR : $erreur. Séquence annulée, tout est à recommencer.", 'erreur');
        game_penalty(PENALITE_ERREUR, "serveurs : $erreur");
        return 'serveur-faux';
    }
    $faits[] = $serveur;
    state_set('seq', implode(',', $faits));
    event_add('serveur', "{$serveur} redémarré (" . count($faits) . '/4)');
    baie_journal_ajouter("$nom : démarrage... en ligne (" . count($faits) . '/4).', 'ok');
    if (count($faits) === 4) { game_unlock(S_SERVEURS); baie_journal_ajouter('Les 4 serveurs sont en ligne : réseau rétabli.', 'ok'); return 'serveurs-ok'; }
    return 'serveur-ok';
}

/** Un bouton physique a été pressé sur la carte. Renvoie ce qui s'est passé, pour le journal et les tests. */
function game_button(string $name): string
{
    $phase = game_phase();
    $step = game_step();
    event_add('bouton', "{$name} (phase : {$phase})");

    if ($name === 'arret' && $phase === 'alarme') {
        if ($step >= S_SCRIPT) { game_victory('bouton arrêt'); return 'victoire'; }
        game_penalty(PENALITE_ERREUR, 'arrêt refusé : la procédure d\'arrêt est corrompue');
        return 'refuse';
    }
    if ($name === 'demarrage' && in_array($phase, ['victoire', 'defaite'], true)) { game_reset('bouton démarrage'); return 'reset'; }
    if (isset(BOUTON_SERVEUR[$name]) && $phase === 'jeu' && $step === S_PIVOT) return game_serveur(BOUTON_SERVEUR[$name]);
    return 'ignore';
}

/* ---- Historique des parties ---- */

/** Range la partie qui vient de se terminer (victoire, défaite ou abandon) dans l'historique. Une seule fois par partie. */
function game_archiver(string $issue): void
{
    if (state_get('archive') !== null) return;
    $start = (int) state_get('start', '0');
    if (!$start) return;
    $fin = (int) state_get('fin', '0') ?: time();
    if ($issue === 'abandon' && $fin - $start < 20) return;   // un simple essai de quelques secondes n'entre pas dans l'historique
    $indices = [];
    for ($i = 0; $i <= S_SCRIPT; $i++) if ($n = (int) state_get("hint_$i", '0')) $indices[$i] = $n;
    $st = db()->prepare('INSERT INTO parties (debut, fin, issue, duree, jauge, indices, erreurs, penalites, rab, objectif, etapes, indices_detail)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $st->execute([$start, $fin, $issue, max(0, $fin - $start), game_leak(), (int) state_get('indices', '0'), (int) state_get('erreurs', '0'),
        (int) state_get('penalty', '0'), (int) state_get('rab', '0'), min(game_step(), S_SCRIPT + 1),
        state_get('etapes', '{}'), json_encode($indices)]);
    state_set('archive', (string) db()->lastInsertId());
}

function game_historique(int $n = 30): array
{
    $st = db()->prepare('SELECT * FROM parties ORDER BY id DESC LIMIT ?');
    $st->execute([$n]);
    $lignes = $st->fetchAll();
    foreach ($lignes as &$l) { $l['etapes'] = json_decode($l['etapes'], true) ?: []; $l['indices_detail'] = json_decode($l['indices_detail'], true) ?: []; }
    return $lignes;
}

function game_resume_historique(): array
{
    $r = db()->query("SELECT COUNT(*) AS parties,
        SUM(issue = 'victoire') AS victoires, SUM(issue = 'defaite') AS defaites, SUM(issue = 'abandon') AS abandons,
        MIN(CASE WHEN issue = 'victoire' THEN duree END) AS meilleur,
        AVG(CASE WHEN issue = 'victoire' THEN duree END) AS moyenne,
        AVG(indices) AS indices_moyens, AVG(erreurs) AS erreurs_moyennes FROM parties")->fetch();
    foreach (['victoires', 'defaites', 'abandons'] as $k) $r[$k] = (int) $r[$k];
    foreach (['meilleur', 'moyenne'] as $k) $r[$k] = $r[$k] === null ? null : (int) round($r[$k]);
    foreach (['indices_moyens', 'erreurs_moyennes'] as $k) $r[$k] = $r[$k] === null ? null : round((float) $r[$k], 1);
    $r['parties'] = (int) $r['parties'];
    return $r;
}

/** Classement d'une victoire : sa place parmi toutes les victoires, par temps croissant. */
function game_classement(int $duree): array
{
    $meilleurs = (int) db()->query("SELECT COUNT(*) FROM parties WHERE issue = 'victoire' AND duree < " . (int) $duree)->fetchColumn();
    $total = (int) db()->query("SELECT COUNT(*) FROM parties WHERE issue = 'victoire'")->fetchColumn();
    return ['rang' => $meilleurs + 1, 'total' => $total];
}

/** Ce qui s'est passé pendant la partie : sert à l'écran de fin. */
function game_stats(): array
{
    $start = (int) state_get('start', '0');
    $fin = (int) state_get('fin', '0') ?: time();
    $leak = game_leak();
    $phase = game_phase();
    $grade = $phase === 'victoire' ? ($leak < 35 ? 'Agent d\'élite' : ($leak < 60 ? 'Analyste confirmé' : 'Recrue courageuse')) : 'Mission échouée';
    $temps = $start ? max(0, $fin - $start) : 0;
    $s = [
        'temps'     => $temps,
        'indices'   => (int) state_get('indices', '0'),
        'erreurs'   => (int) state_get('erreurs', '0'),
        'penalites' => (int) state_get('penalty', '0'),
        'jauge'     => $leak,
        'grade'     => $grade,
    ];
    if ($phase === 'victoire') $s += game_classement($temps) + ['meilleur_temps' => game_resume_historique()['meilleur']];
    return $s;
}

/** Ce que la carte doit afficher / jouer : sujet MQTT => valeur. */
function game_outputs(): array
{
    $phase = game_phase();
    $msg = '';
    if ($phase === 'jeu' && game_step() === S_PIVOT) $msg = 'Serveurs ' . count(game_serveurs_redemarres()) . '/4';
    return [
        TOPIC_ALARME => in_array($phase, ['alarme', 'defaite'], true) ? 'ON' : 'OFF',
        TOPIC_ETAT   => $phase,
        TOPIC_JAUGE  => (string) game_leak(),
        TOPIC_MSG    => $msg,
    ];
}

/** Publie sur le broker ce qui a changé depuis la dernière fois (messages retenus : la carte les reçoit même si elle se reconnecte). */
function game_publish(MqttLite $mqtt, bool $force = false): int
{
    $last = $force ? [] : (json_decode(state_get('publie', '{}'), true) ?: []);
    $now = game_outputs();
    $sent = 0;
    foreach ($now as $topic => $value) {
        if (($last[$topic] ?? null) === $value) continue;
        if (!$mqtt->publish($topic, $value, true)) return $sent; // broker perdu : on réessaiera, rien n'est noté comme envoyé
        $last[$topic] = $value;
        $sent++;
    }
    if ($sent) state_set('publie', json_encode($last));
    return $sent;
}
