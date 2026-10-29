<?php
ini_set('display_errors', '0'); // une erreur PHP ne doit jamais s'afficher dans le JSON renvoyé au terminal
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../src/puzzles.php';

/* La partie est globale (base SQLite) : le terminal, la carte Arduino et la page Game Master voient la même chose.
   L'alarme et la jauge physiques sont publiées sur MQTT par bridge.php, pas par ce fichier.
   Deux façons de l'appeler : {"command": "..."} pour le terminal, {"action": "..."} pour les applications du bureau. */

set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error-msg', 'message' => 'Erreur interne du serveur de jeu.', 'leak' => 0,
        'prompt' => 'guest@dgsi-zerojour:~$', 'logged' => false], JSON_UNESCAPED_UNICODE);
    error_log('[lerna] ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
});

/** Les informations que le navigateur affiche en permanence (jauge, chrono, carte réseau, écran de fin...). */
function etat(): array
{
    $step = game_step();
    $phase = game_phase();
    $nbMails = count(array_filter(mails(), fn($m) => $m['etape'] <= $step));
    $etat = [
        'leak' => game_leak(), 'phase' => $phase, 'step' => $step,
        'prompt' => $step >= S_2FA ? 'kovac@lerna:~$' : 'guest@dgsi-zerojour:~$',
        'logged' => $step >= S_2FA, 'pass_ok' => $step >= S_LOGIN,
        'restant' => game_restant(), 'nb_mails' => $step >= S_2FA ? $nbMails : 0,
        'serveurs' => $step >= S_SERVEURS ? ordre_redemarrage() : game_serveurs_redemarres(),
        'indices' => (int) state_get('indices', '0'),
        'mission' => ['n' => min($step, S_SCRIPT) + 1, 'total' => S_SCRIPT + 1, 'titre' => objectifs()[min($step, S_SCRIPT)]] + briefing()[min($step, S_SCRIPT)],
        'nb_docs' => $step >= S_2FA ? count(array_filter(documents(), fn($d) => $d['etape'] <= $step)) + count(array_filter(photos(), fn($p) => $p['etape'] <= $step)) : 0,
    ];
    if (in_array($phase, ['victoire', 'defaite'], true)) $etat['stats'] = game_stats();
    return $etat;
}

function reply($msg, $status = 'sys-msg', $extra = []) {
    echo json_encode(array_merge(['status' => $status, 'message' => $msg], etat(), $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

function refuse(string $msg, int $code = 403): void
{
    http_response_code($code);
    echo json_encode(['erreur' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

game_tick();
$in = json_decode(file_get_contents('php://input'), true) ?: [];

/* ================= Applications du bureau ================= */
if (isset($in['action'])) {
    $step = game_step();
    if ($step < S_2FA && $in['action'] !== 'etat') refuse('Session verrouillée.');
    switch ($in['action']) {
        case 'etat':
            echo json_encode(etat(), JSON_UNESCAPED_UNICODE);
            break;

        case 'mails':
            $liste = [];
            foreach (mails() as $m) if ($m['etape'] <= $step) { unset($m['etape']); $liste[] = $m; }
            usort($liste, fn($a, $b) => strcmp($b['heure'], $a['heure']));
            echo json_encode(['mails' => $liste], JSON_UNESCAPED_UNICODE);
            break;

        case 'reseau':
            if ($step < S_IP) refuse('Module réseau verrouillé : isolez d\'abord la menace.');
            $hotes = [];
            $faits = $step >= S_SERVEURS ? ordre_redemarrage() : game_serveurs_redemarres();
            foreach (hotes() as $k => $h) {
                $h['id'] = $k;
                $h['redemarre'] = in_array($k, $faits, true);
                $h['corrompu'] = in_array($k, BOUTON_SERVEUR, true) && $step >= S_PIVOT && $step < S_SERVEURS && !$h['redemarre'];
                $h['bloque'] = $k === 'pirate';
                $h['relais'] = $k === 'archives' && $step < S_PIVOT;   // tant que le pivot n'est pas coupé
                $hotes[] = $h;
            }
            $flux = array_map(fn($f) => ['de' => $f[0], 'vers' => $f[1], 'port' => $f[2], 'texte' => $f[3]], flux());
            if ($step >= S_PIVOT) $flux = array_values(array_filter($flux, fn($f) => $f['vers'] !== 'pirate'));   // le tunnel est coupé
            echo json_encode(['hotes' => $hotes, 'flux' => $flux,
                'liens' => array_map(fn($l) => ['de' => $l[0], 'vers' => $l[1]], liens()),
                'dependances' => $step >= S_PIVOT ? array_map(fn($d) => ['de' => $d[0], 'vers' => $d[1]], dependances()) : []], JSON_UNESCAPED_UNICODE);
            break;

        case 'cli':   // la console d'un équipement de la carte réseau
            if ($step < S_IP) refuse('Module réseau verrouillé.');
            $sortie = reseau_cli((string) ($in['hote'] ?? ''), (string) ($in['ligne'] ?? ''));
            echo json_encode(['sortie' => $sortie] + etat(), JSON_UNESCAPED_UNICODE);
            break;

        case 'baie':   // l'application Baie serveurs
            if ($step < S_PIVOT) refuse('Supervision indisponible : le relais du pirate bloque la baie.');
            $faits = $step >= S_SERVEURS ? ordre_redemarrage() : game_serveurs_redemarres();
            $serveurs = [];
            foreach (BOUTON_SERVEUR as $bouton => $id) {
                $serveurs[] = ['id' => $id, 'nom' => nom_serveur($id), 'ip' => hotes()[$id]['ip'], 'bouton' => bouton_du_serveur($id), 'en_ligne' => in_array($id, $faits, true)];
            }
            usort($serveurs, fn($a, $b) => (int) $a['bouton'] <=> (int) $b['bouton']);
            echo json_encode(['serveurs' => $serveurs, 'journal' => baie_journal(), 'termine' => $step >= S_SERVEURS,
                'dependances' => array_map(fn($d) => ['de' => nom_serveur($d[0]), 'vers' => nom_serveur($d[1])], dependances())], JSON_UNESCAPED_UNICODE);
            break;

        case 'documents':   // explorateur de fichiers, navigateur intranet
            $docs = [];
            foreach (documents() as $d) if ($d['etape'] <= $step) { unset($d['etape']); $docs[] = $d; }
            $photos = [];
            foreach (photos() as $p) if ($p['etape'] <= $step) { unset($p['etape']); $photos[] = $p; }
            echo json_encode(['documents' => $docs, 'photos' => $photos], JSON_UNESCAPED_UNICODE);
            break;

        case 'script':
            if ($step < S_ALARME) refuse('Fichier verrouillé : il faut d\'abord lancer le script d\'urgence.');
            echo json_encode(['lignes' => script_lignes(), 'banque' => script_banque(), 'reussi' => $step >= S_SCRIPT], JSON_UNESCAPED_UNICODE);
            break;

        case 'script_verifier':
            if ($step < S_ALARME) refuse('Fichier verrouillé.');
            $res = script_verifier((array) ($in['reponses'] ?? []));
            $ok = !in_array(false, $res, true);
            if ($ok && $step === S_ALARME) { game_unlock(S_SCRIPT); event_add('script', 'procédure d\'arrêt réparée, kill switch armé'); }
            if (!$ok) game_penalty(PENALITE_ERREUR, 'script à trous : ' . count(array_filter($res)) . '/' . count($res) . ' trous justes');
            echo json_encode(['trous' => $res, 'ok' => $ok] + etat(), JSON_UNESCAPED_UNICODE);
            break;

        case 'indice':
            $h = game_hint();
            echo json_encode($h + etat(), JSON_UNESCAPED_UNICODE);
            break;

        default:
            refuse('Action inconnue.', 400);
    }
    exit;
}

/* ================= Terminal ================= */
$raw = trim($in['command'] ?? '');
if ($raw === '') reply('');

$parts = preg_split('/\s+/', lower($raw));
$cmd = $parts[0];
$arg = $parts[1] ?? '';
$arg2 = $parts[2] ?? '';

if ($cmd === '__state') reply('');

if ($cmd === 'reset') {
    game_reset('terminal');
    echo json_encode(['status' => 'sys-msg', 'message' => 'Système réinitialisé.', 'reset' => true] + etat());
    exit;
}

/* Commande secrète Game Master : simule le bouton physique (mode secours) */
if ($cmd === 'override' && $arg === 'port404') {
    game_victory('terminal');
    reply("[GM] Kill switch simulé. Alarme coupée, jauge figée.\n*** LERNA NEUTRALISÉ : VICTOIRE ***", 'success-msg');
}

if (game_phase() === 'victoire') reply('LERNA NEUTRALISÉ : la partie est terminée.', 'success-msg');
if (game_leak() >= 100) reply('GAME OVER : FUITE DE DONNÉES MASSIVE EN COURS. SYSTÈME VERROUILLÉ.', 'error-msg');

$step = game_step();
$avant_terminal = ['aide', 'login', '2fa', 'indice'];
if ($step < S_2FA && !in_array($cmd, $avant_terminal)) {
    reply($step < S_LOGIN ? "Session verrouillée. Utilisez : login [mot_de_passe]"
                          : "Double authentification requise. Utilisez : 2fa [code à 6 chiffres du téléphone]", 'error-msg');
}

switch ($cmd) {
    case 'aide':
        $l = ['aide', 'login [mdp]'];
        if ($step >= S_LOGIN) $l[] = '2fa [code]';
        if ($step >= S_2FA) array_push($l, 'statut', 'indice', 'show logs', 'cibler [ip]', 'ls', 'cat [fichier]', 'ping [ip]');
        if ($step >= S_IP) array_push($l, 'scan', 'netstat', 'bloquer [ip] [port]');
        if ($step >= S_SERVEURS) array_push($l, 'bd', 'sql [requête]', 'suspect [id]', 'auth [id]');
        if ($step >= S_AUTH) $l[] = 'run [fichier]';
        reply('Commandes : ' . implode(', ', $l) . ', clear, reset' .
            ($step >= S_2FA ? "\n'statut' montre où vous en êtes. 'indice' donne un coup de pouce (coûte " . COUT_INDICE . " % de fuite)." : ''));

    case 'login':
        if ($step >= S_LOGIN) reply('Session déjà ouverte.');
        if ($arg === MOT_DE_PASSE) {
            game_login();
            reply("Mot de passe correct.\nDOUBLE AUTHENTIFICATION REQUISE : un code à 6 chiffres a été envoyé au téléphone de Kovac.\nTapez : 2fa [code]", 'success-msg');
        }
        reply('Mot de passe incorrect.', 'error-msg');

    case '2fa':
        if ($step >= S_2FA) reply('Déjà authentifié.');
        if (game_2fa_valide($arg)) {
            game_unlock(S_2FA);
            event_add('2fa', 'code du téléphone accepté');
            reply("Code accepté. Authentification racine OK.\nALERTE : Anomalie réseau détectée. Exécutez 'show logs'.", 'success-msg');
        }
        game_penalty(PENALITE_ERREUR, 'mauvais code 2FA');
        reply('Code incorrect ou expiré (il change toutes les 30 secondes). +3% de fuite.', 'error-msg');

    case 'statut':
        // l'objectif n est réussi quand l'étape dépasse n ; l'objectif en cours est celui qui porte le numéro de l'étape
        $out = ['OBJECTIFS DE LA MISSION', ''];
        foreach (objectifs() as $i => $titre) {
            $out[] = ($i < $step ? '  [x] ' : ($i === $step ? '  [>] ' : '  [ ] ')) . ($i <= $step ? $titre : '???');
        }
        $b = briefing()[min($step, S_SCRIPT)];
        $out[] = '';
        $out[] = 'CONTEXTE : ' . $b['contexte'];
        $out[] = 'PISTE    : ' . $b['piste'];
        $out[] = '';
        $out[] = 'Fuite : ' . game_leak() . ' %   Indices utilisés : ' . (int) state_get('indices', '0');
        reply(implode("\n", $out));

    case 'indice':
        $h = game_hint();
        if ($h['texte'] === null) reply("Plus d'indice pour cet objectif : vous avez tout ce qu'il faut.", 'error-msg');
        reply("INDICE {$h['niveau']}/{$h['total']} (+" . COUT_INDICE . " % de fuite)\n> {$h['texte']}", 'sys-msg');

    case 'ls':
        reply("notes.txt\nrapport_t3.pdf\nurgence.sh");

    case 'cat':
        if ($arg === 'notes.txt') reply("- changer mon mot de passe (encore trop facile à deviner)\n- dentiste jeudi\n- ne pas oublier le gâteau de Léa");
        if ($arg === 'rapport_t3.pdf') reply("[Fichier binaire : impossible à afficher dans le terminal.]", 'error-msg');
        if ($arg === 'urgence.sh') {
            reply($step >= S_ALARME ? "Ouvrez urgence.sh avec l'éditeur de code (icône sur le bureau) pour le réparer."
                                     : "Accès refusé : le script d'urgence est protégé.", $step >= S_ALARME ? 'sys-msg' : 'error-msg');
        }
        reply($arg === '' ? 'Usage : cat [fichier]' : "Fichier introuvable : $arg", 'error-msg');

    case 'whoami':
        reply('dgsi\\a.kovac (cellule Zéro-Jour, niveau 5)');

    case 'date':
        reply(date('d/m/Y H:i:s'));

    case 'ping':
        $k = hote_trouve($arg);
        if ($k === null) reply("Hôte introuvable : " . ($arg ?: '(vide)'), 'error-msg');
        if ($k === 'pirate' && $step >= S_IP) reply("Destination inaccessible : bloquée par le pare-feu.", 'error-msg');
        reply('Réponse de ' . hotes()[$k]['ip'] . " : temps<1ms (" . hotes()[$k]['nom'] . ')');

    case 'sudo':
        reply("a.kovac n'est pas dans le fichier sudoers. Cet incident sera signalé.", 'error-msg');

    case 'exit':
        reply("Il n'y a pas de sortie. Il y a une fuite à colmater.", 'error-msg');

    case 'show':
        if ($arg === 'logs' || $arg === 'log') {
            reply("EXTRACTION DES LOGS SYSTÈME - 24H GLISSANTES...
[08:50:12] [INFO] SYSTEM_BOOT séquence initiée avec succès.
[08:50:15] [INFO] KERNEL modules chargés (eth0, wlan0, crypt).
[08:52:04] [NETWORK] Interface eth0 UP - Link 1000Mbps.
[08:55:10] [DB] Connexion au cluster principal établie.
[09:00:00] [CRON] Nettoyage des fichiers temporaires terminé.
[09:01:22] [AUTH] SUCCESS (User: A_Kovac, Local)
[09:02:14] [APP] L.E.R.N.A daemon started. PID 4402.
[09:05:44] [DB_SYNC] Synchronisation des tables secondaires OK.
[09:08:11] [INFO] Mise à jour des signatures antivirus...
[09:12:00] [MAIL] Réception d'un courriel (Expéditeur: rh-dgsi-interne@gmail.com)
[09:12:05] [APP] Lancement du processus d'ouverture de pièce jointe (PDF_Reader)
[09:12:06] [WARN] Exécution de macro non signée bloquée par la politique locale.
[09:12:15] [WARN] Contournement des privilèges détecté sur PID 4402.
[09:12:40] [SYS] SRV-ARCHIVES : un nouveau service a été lancé par PID 4402.
[09:15:27] [AUTH] CRITICAL WARNING: Authentification à distance (User: SYSTEM_OVERRIDE, IP: 192.168.1.42)
[09:15:28] [NETWORK] Début de transfert massif de paquets sortants (Port 443).
[09:16:00] [SYS] CPU Load > 95%.
[09:20:10] [ERR] Impossible de contacter le serveur de logs distant (TimeOut).
--- FIN DE L'EXTRACTION ---
Recherchez l'IP suspecte et tapez 'cibler [IP]'");
        }
        reply("Usage : show logs", 'error-msg');

    case 'cibler':
        if ($step >= S_IP) reply('Cette IP est déjà bloquée.');
        if ($arg === IP_CIBLE) {
            game_unlock(S_IP);
            event_add('cible', 'IP ' . IP_CIBLE . ' bloquée');
            reply("[SUCCÈS] IP " . IP_CIBLE . " bloquée au niveau du pare-feu.\nMais le pirate garde un accès : un relais est resté dans le réseau interne.\nOutils déverrouillés : 'scan', 'netstat', 'bloquer [ip] [port]' et l'application Carte réseau.", 'success-msg');
        }
        game_penalty(PENALITE_ERREUR, 'mauvaise IP');
        reply('Cible introuvable ou requête mal formatée. +3% de fuite.', 'error-msg');

    case 'scan':
        if ($step < S_IP) reply("Outil indisponible : isolez d'abord la menace.", 'error-msg');
        reply(reseau_scan());

    case 'netstat':
        if ($step < S_IP) reply("Outil indisponible : isolez d'abord la menace.", 'error-msg');
        reply(reseau_netstat());

    case 'bloquer':
        if ($step < S_IP) reply("Outil indisponible : isolez d'abord la menace.", 'error-msg');
        if ($step >= S_PIVOT) reply('Le relais du pirate est déjà coupé.');
        if ($arg === '' || $arg2 === '') reply('Usage : bloquer [ip] [port]', 'error-msg');
        if (hote_trouve($arg) === 'archives' && (int) $arg2 === SERVEUR_PIVOT_PORT) {
            game_unlock(S_PIVOT);
            event_add('pivot', 'relais ' . SERVEUR_PIVOT_IP . ':' . SERVEUR_PIVOT_PORT . ' coupé');
            reply("[SUCCÈS] Port " . SERVEUR_PIVOT_PORT . " fermé sur SRV-ARCHIVES. Le tunnel du pirate est coupé.\nMais le relais a corrompu les 4 serveurs : ils doivent être redémarrés avec les boutons physiques de la baie, dans le bon ordre.\nUn mail de M. Lefebvre vient d'arriver.", 'success-msg');
        }
        game_penalty(PENALITE_ERREUR, 'mauvais blocage');
        reply("Ce blocage n'a aucun effet sur le pirate (ou le port/l'hôte est introuvable). +3% de fuite.", 'error-msg');

    case 'bd':
        if ($step < S_SERVEURS) reply("ERREUR : Accès refusé par le protocole de sécurité. Redémarrez d'abord les serveurs.", 'error-msg');
        reply(sql_description());

    case 'sql':
        if ($step < S_SERVEURS) reply("ERREUR : Accès refusé par le protocole de sécurité. Redémarrez d'abord les serveurs.", 'error-msg');
        [$ok, $texte] = sql_executer(preg_replace('/^\s*sql\s*/i', '', $raw));
        reply($texte, $ok ? 'sys-msg' : 'error-msg');

    case 'suspect':
        if ($step < S_SERVEURS) reply("Commande indisponible.", 'error-msg');
        if ($step >= S_SUSPECT) reply('Le suspect est déjà identifié : L. MARTIN (ARCHIVES).');
        if ((int) $arg === ID_SUSPECT) {
            game_unlock(S_SUSPECT);
            event_add('suspect', 'L. MARTIN identifié');
            reply("[SUCCÈS] L. MARTIN (ID " . ID_SUSPECT . ") a bien ouvert Politique_RH_2026.pdf.exe à 09:12.\nCe n'est pas un complice : il a été piégé par un faux mail des RH.\nProchaine étape : le superviseur d'urgence de la cellule ZERO-JOUR (commande 'auth [id]').", 'success-msg');
        }
        game_penalty(PENALITE_ERREUR, 'mauvais suspect');
        reply("Cet employé n'a pas ouvert le fichier piégé. +3% de fuite.", 'error-msg');

    case 'auth':
        if ($step < S_SERVEURS) reply("Commande indisponible.", 'error-msg');
        if ($step < S_SUSPECT) reply("Identifiez d'abord l'employé piégé (commande 'suspect [id]').", 'error-msg');
        if ($step >= S_AUTH) reply('Superviseur déjà authentifié : A. KOVAC.');
        if ((int) $arg === ID_SUPERVISEUR) {
            game_unlock(S_AUTH);
            event_add('auth', 'superviseur d\'urgence authentifié');
            reply("[SUCCÈS] ID " . ID_SUPERVISEUR . " accepté : A. KOVAC, superviseur d'urgence.\nLe module d'arrêt est déverrouillé. Tapez 'run urgence.sh'.", 'success-msg');
        }
        game_penalty(PENALITE_ERREUR, 'mauvais ID superviseur');
        reply("Cet ID n'a pas l'habilitation URGENCE_PHYSIQUE dans la cellule ZERO-JOUR. +3% de fuite.", 'error-msg');

    case 'run':
        if ($step < S_AUTH) reply('Fichier indisponible ou droits insuffisants.', 'error-msg');
        if ($arg === 'urgence.sh') {
            if ($step < S_ALARME) { game_unlock(S_ALARME); event_add('alarme', 'urgence.sh lancé'); }
            reply("EXÉCUTION DU SCRIPT...
[||||||||||          ] 50%
ERREUR CRITIQUE : Une porte dérobée (backdoor) a été détectée dans les lignes de code !
Le pirate a corrompu la procédure d'arrêt.
> VERROUILLAGE PHYSIQUE DU SYSTÈME L.E.R.N.A ACTIVÉ.
> PROTOCOLE D'ALARME GÉNÉRALE ENCLENCHÉ.

Ouvrez urgence.sh dans l'éditeur de code (bureau) et réparez la procédure.
Tant qu'elle est corrompue, le bouton ARRÊT de la baie ne sert à rien.", 'error-msg');
        }
        reply('Fichier introuvable.', 'error-msg');

    default:
        reply("Commande inconnue : '" . $cmd . "'. Tapez 'aide'.", 'error-msg');
}
