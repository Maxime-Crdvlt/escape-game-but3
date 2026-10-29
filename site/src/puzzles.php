<?php
/* Les épreuves : exécuteur SQL en lecture seule, outils réseau, vérification du code à trous. */

require_once __DIR__ . '/game.php';

/* ---- SQL ---- */

/** Exécute une requête SELECT sur une base du personnel créée en mémoire. Renvoie [ok, texte, lignes]. */
function sql_executer(string $requete): array
{
    $q = trim($requete);
    $q = rtrim($q, "; \t\r\n");
    if ($q === '') return [false, "Requête vide. Exemple : sql SELECT nom FROM personnel", []];
    if (!preg_match('/^select\b/i', $q)) return [false, "Seules les requêtes SELECT sont autorisées : la base du personnel est en lecture seule.", []];
    if (strpos($q, ';') !== false) return [false, "Une seule requête à la fois.", []];
    if (strlen($q) > 400 || preg_match_all('/\bjoin\b/i', $q) > 3) return [false, "Requête trop longue ou trop compliquée.", []];
    if (preg_match('/\b(attach|pragma|load_extension|recursive|randomblob|zeroblob|sqlite_\w+)\b/i', $q)) {
        return [false, "Mot interdit dans la requête.", []];
    }
    try {
        $pdo = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_NUM]);
        $pdo->exec(sql_seed());
        $pdo->exec('PRAGMA query_only = ON');
        $st = $pdo->query($q);
        $noms = [];
        for ($i = 0; $i < $st->columnCount(); $i++) $noms[] = $st->getColumnMeta($i)['name'];
        $lignes = $st->fetchAll();
    } catch (PDOException $e) {
        $msg = preg_replace('/^SQLSTATE\[[^\]]*\]:?\s*(General error: \d+\s*)?/', '', $e->getMessage());
        return [false, "ERREUR SQL : " . $msg . "\nAstuce : tapez 'bd' pour revoir les tables et leurs colonnes.", []];
    }
    return [true, sql_tableau($noms, $lignes), $lignes];
}

function sql_tableau(array $noms, array $lignes): string
{
    $max = 25;
    $affichees = array_slice($lignes, 0, $max);
    $larg = array_map('mb_strlen', $noms);
    foreach ($affichees as $l) foreach ($l as $i => $v) $larg[$i] = max($larg[$i], mb_strlen((string) $v));
    $ligne = fn(array $cells) => '| ' . implode(' | ', array_map(fn($c, $w) => $c . str_repeat(' ', $w - mb_strlen($c)), array_map('strval', $cells), $larg)) . ' |';
    $sep = '|' . implode('|', array_map(fn($w) => str_repeat('-', $w + 2), $larg)) . '|';
    $out = [$ligne($noms), $sep];
    foreach ($affichees as $l) $out[] = $ligne($l);
    $n = count($lignes);
    $out[] = '';
    $out[] = $n . ($n > 1 ? ' lignes' : ' ligne') . ' retournée' . ($n > 1 ? 's' : '') . ($n > $max ? " (les $max premières sont affichées)" : '') . '.';
    return implode("\n", $out);
}

function sql_description(): string
{
    $out = ["CONNEXION À LA BASE DE DONNÉES DU PERSONNEL... OK (lecture seule)", '', 'Tables disponibles :'];
    foreach (sql_schema() as $table => $colonnes) $out[] = sprintf("  %-14s (%s)", $table, implode(', ', $colonnes));
    $out[] = '';
    $out[] = "Utilisation : sql SELECT ... FROM ...";
    $out[] = "Exemple     : sql SELECT nom, departement FROM personnel WHERE niveau_acces = 5";
    $out[] = '';
    $out[] = "Deux questions à résoudre :";
    $out[] = "  1. Quel employé a ouvert le fichier piégé ?  Une fois trouvé : suspect [id]";
    $out[] = "  2. Qui est le superviseur d'urgence de la cellule ZERO-JOUR ?  Une fois trouvé : auth [id]";
    return implode("\n", $out);
}

/* ---- Réseau ---- */

function hote_trouve(string $cle): ?string
{
    $cle = lower(trim($cle));
    foreach (hotes() as $k => $h) {
        if ($cle === $h['ip'] || $cle === lower($h['nom']) || $cle === $k) return $k;
    }
    return null;
}

function reseau_scan(): string
{
    $out = ["SCAN DU RÉSEAU 192.168.1.0/24 ... terminé.", '', sprintf('%-15s %-14s %s', 'ADRESSE IP', 'NOM', 'PORTS OUVERTS')];
    foreach (hotes() as $k => $h) {
        if ($k === 'pirate' && game_step() < S_IP) continue;   // le pirate n'est connu qu'après le repérage de son IP
        $ports = $h['ports'] ? implode(', ', array_map(fn($p, $s) => "$p ($s)", array_keys($h['ports']), $h['ports'])) : '-';
        if ($k === 'pirate') { $h['nom'] = 'PIRATE (bloqué)'; $ports = '-'; }
        $out[] = sprintf('%-15s %-14s %s', $h['ip'], $h['nom'], $ports);
    }
    $out[] = '';
    $out[] = "Un port 'inconnu' n'a rien à faire sur un serveur sain.";
    return implode("\n", $out);
}

function reseau_netstat(): string
{
    $h = hotes();
    $out = ["CONNEXIONS ACTIVES SUR LE RÉSEAU", '', sprintf('%-22s %-24s %s', 'DE', 'VERS', 'ÉTAT')];
    foreach (flux() as [$de, $vers, $port]) {
        $out[] = sprintf('%-22s %-24s %s', $h[$de]['nom'] . ' (' . $h[$de]['ip'] . ')', $h[$vers]['ip'] . ':' . $port, 'ÉTABLIE');
    }
    $out[] = '';
    $out[] = "Utilisation : bloquer [ip] [port]";
    return implode("\n", $out);
}

/** La console (onglet CLI) d'un équipement de la carte réseau. Renvoie le texte affiché. */
function reseau_cli(string $id, string $ligne): string
{
    $h = hotes()[$id] ?? null;
    if ($h === null) return 'Équipement inconnu.';
    if ($id === 'pirate') return "Équipement externe : aucun accès.";
    $p = preg_split('/\s+/', lower(trim($ligne)));
    $c = $p[0];
    $step = game_step();
    switch ($c) {
        case '':
            return '';
        case 'help':
        case '?':
            return "Commandes :\n  help                 cette aide\n  show ports           ports ouverts de cet équipement\n  show connections     connexions de cet équipement\n  ping [ip]            teste une machine\n  close [port]         ferme un port\n  clear                efface l'écran";
        case 'show':
            if (($p[1] ?? '') === 'ports') {
                if (!$h['ports']) return 'Aucun port ouvert.';
                $out = ['PORT   SERVICE'];
                foreach ($h['ports'] as $port => $service) $out[] = sprintf('%-6d %s', $port, $service);
                return implode("\n", $out);
            }
            if (($p[1] ?? '') === 'connections') {
                $hs = hotes();
                $out = [];
                foreach (flux() as [$de, $vers, $port]) {
                    if (($de !== $id && $vers !== $id) || ($vers === 'pirate' && $step >= S_PIVOT)) continue;
                    $out[] = sprintf('%s:%d -> %s:%d   ÉTABLIE', $hs[$de]['ip'], $port, $hs[$vers]['ip'], $port);
                }
                return $out ? "DE                  VERS               ÉTAT\n" . implode("\n", $out) : 'Aucune connexion active.';
            }
            return "Usage : show ports | show connections";
        case 'ping':
            $k = hote_trouve($p[1] ?? '');
            if ($k === null) return 'Usage : ping [ip]';
            if ($k === 'pirate' && $step >= S_IP) return "Destination inaccessible : bloquée par le pare-feu.";
            return 'Réponse de ' . hotes()[$k]['ip'] . ' : temps<1ms';
        case 'close':
        case 'bloquer':
            $port = (int) ($p[1] ?? 0);
            if ($port <= 0) return 'Usage : close [port]';
            if (!isset($h['ports'][$port])) return "Aucun service n'écoute sur le port $port de {$h['nom']}.";
            if ($id === 'archives' && $port === SERVEUR_PIVOT_PORT) {
                if ($step >= S_PIVOT) return "Le port $port est déjà fermé.";
                if ($step < S_IP) return "Accès refusé.";
                game_unlock(S_PIVOT);
                event_add('pivot', 'relais ' . SERVEUR_PIVOT_IP . ':' . SERVEUR_PIVOT_PORT . ' coupé (CLI)');
                return "[SUCCÈS] Port $port fermé sur {$h['nom']}. Le tunnel du pirate est coupé.\nLes 4 serveurs sont corrompus : redémarrez-les avec les boutons de la baie (application Baie serveurs).";
            }
            if ($step >= S_PIVOT) return "Port $port fermé.";
            game_penalty(PENALITE_ERREUR, "port légitime fermé : $port sur {$h['nom']}");
            return "Port $port fermé : le service « {$h['ports'][$port]} » est interrompu.\nCe port figure dans l'inventaire des ports autorisés : ce n'était pas le bon. +" . PENALITE_ERREUR . "% de fuite.";
    }
    return "Commande inconnue : '$c'. Tapez help.";
}

/* ---- Code à trous ---- */

function script_normaliser(string $r): string
{
    return lower(trim(trim($r), "\"' \t"));
}

/** Compare les 6 réponses. Renvoie [trou => vrai/faux]. */
function script_verifier(array $reponses): array
{
    $res = [];
    foreach (script_reponses() as $n => $acceptees) {
        $res[$n] = in_array(script_normaliser((string) ($reponses[$n - 1] ?? $reponses[(string) $n] ?? '')), $acceptees, true);
    }
    return $res;
}
