<?php
/* Test des épreuves (site/src/puzzles.php) : le SQL doit rester en lecture seule quoi que les joueurs tapent,
   le code à trous doit accepter les bonnes réponses et refuser les autres, le CLI des équipements doit résoudre l'énigme réseau,
   le contenu du scénario doit être cohérent. Aucun serveur ni broker nécessaire.
   Usage : php tests/puzzles_test.php */

$dbFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lerna_puzzles_' . getmypid() . '.sqlite';
putenv('LERNA_DB=' . $dbFile);
require __DIR__ . '/../site/src/puzzles.php';

$fails = 0;
function check(string $name, bool $ok, string $detail = ''): void {
    global $fails;
    echo ($ok ? '  OK     ' : '  ECHEC  ') . $name . ($ok ? '' : "  -> $detail") . "\n";
    if (!$ok) $fails++;
}
echo "Test des épreuves\n";

echo "\n-- SQL : ce qui doit marcher --\n";
[$ok, $t, $l] = sql_executer('SELECT nom FROM personnel WHERE id = 4117');
check('SELECT simple', $ok && count($l) === 1 && $l[0][0] === 'A. KOVAC', $t);
[$ok, $t, $l] = sql_executer("select nom from personnel where departement = 'zero-jour';");
check('minuscules et point-virgule final acceptés', $ok && count($l) === 2, $t);
[$ok, $t, $l] = sql_executer("SELECT COUNT(*) AS n FROM personnel");
check('fonction d\'agrégat', $ok && $l[0][0] === 10 && str_contains($t, 'n'), $t);
[$ok, $t, $l] = sql_executer('SELECT p.nom, o.fichier FROM personnel p JOIN ouvertures o ON o.personnel_id = p.id ORDER BY o.heure');
check('JOIN avec ORDER BY', $ok && count($l) === 6 && $l[0][1] === 'Planning_Juin.xlsx', $t);
[$ok, $t, $l] = sql_executer('SELECT * FROM personnel');
check('le tableau affiche les en-têtes et le nombre de lignes', $ok && str_contains($t, 'departement') && str_contains($t, '10 lignes retournées'), $t);
[$ok, $t, $l] = sql_executer("SELECT p.id FROM personnel p JOIN habilitations h ON h.personnel_id = p.id WHERE h.droit = 'URGENCE_PHYSIQUE' AND p.departement = 'ZERO-JOUR'");
check('la solution de l\'épreuve donne bien un seul ID', $ok && count($l) === 1 && $l[0][0] === ID_SUPERVISEUR, $t);
[$ok, $t, $l] = sql_executer("SELECT p.id FROM personnel p JOIN ouvertures o ON o.personnel_id = p.id WHERE o.fichier LIKE '%.exe'");
check('la solution du suspect donne bien un seul ID', $ok && count($l) === 1 && $l[0][0] === ID_SUSPECT, $t);
[$ok, $t, $l] = sql_executer("SELECT personnel_id FROM habilitations WHERE droit = 'urgence_physique'");
check('sans la condition de service, deux personnes ont le droit (le piège de l\'énigme)', $ok && count($l) === 2, $t);

echo "\n-- SQL : ce qui doit être refusé --\n";
$interdits = [
    'DELETE FROM personnel', "UPDATE personnel SET niveau_acces = 9", "INSERT INTO personnel VALUES (1,'x','y',1)", 'DROP TABLE personnel',
    'SELECT 1; DROP TABLE personnel', 'PRAGMA table_info(personnel)', "ATTACH DATABASE 'x.db' AS x",
    'SELECT * FROM sqlite_master', "SELECT load_extension('x')", 'SELECT randomblob(1000000000)',
    'WITH RECURSIVE c(x) AS (SELECT 1 UNION ALL SELECT x+1 FROM c) SELECT * FROM c',
    'SELECT * FROM personnel a JOIN personnel b JOIN personnel c JOIN personnel d JOIN personnel e',
    str_repeat('SELECT 1 ', 100), '', '   ;  ',
];
foreach ($interdits as $q) {
    [$ok] = sql_executer($q);
    check('refusé : ' . (strlen($q) > 50 ? substr($q, 0, 47) . '...' : ($q === '' ? '(vide)' : $q)), $ok === false);
}
[$ok, $t] = sql_executer('SELECT * FROM table_inconnue');
check('une table inconnue donne une explication', !$ok && str_contains($t, 'no such table'), $t);
[$ok, $t] = sql_executer('SELEC nom FROM personnel');
check('une faute de frappe est refusée', $ok === false);
check('la base du jeu n\'a pas été modifiée par ces essais', sql_executer('SELECT COUNT(*) FROM personnel')[2][0][0] === 10);

echo "\n-- Code à trous (12 trous) --\n";
$bonnes = ['4117', '4444', '==', 'False', '!=', '0', 'for', 'and', 'kill', '+', '>=', 'OFF'];
check('le script a 12 trous', substr_count(implode("\n", script_lignes()), '{') === 12 && count(script_reponses()) === 12);
check('le script est long (plus de 25 lignes)', count(script_lignes()) > 25);
check('toutes les bonnes réponses', !in_array(false, script_verifier($bonnes), true));
check('majuscules, espaces et guillemets tolérés', !in_array(false, script_verifier(['  4117 ', '4444', '==', ' false', '!=', '0', 'FOR', 'AND', "'kill'", '+', '>=', '"off"']), true));
$r = script_verifier(['3588', '443', '=', 'True', '==', '1', 'while', 'or', 'stop', '-', '>', 'ON']);
check('toutes les mauvaises réponses sont détectées', !in_array(true, $r, true), json_encode($r));
$r = script_verifier(['4117', '4444', '==', 'False', '!=', '0', 'for', 'and', 'kill', '+', '>=']);
check('un trou vide est faux, les autres restent justes', $r[12] === false && $r[1] === true && $r[11] === true, json_encode($r));
$r = script_verifier(['4117', '4444', '==', 'False', '=', '0', 'for', 'and', 'kill', '+', '>=', 'OFF']);
check('un seul trou faux est signalé précisément', array_keys(array_filter($r, fn($v) => !$v)) === [5], json_encode($r));
$banque = array_map('strtolower', script_banque());
check('la banque de mots contient toutes les bonnes réponses', count(array_diff(array_map('strtolower', $bonnes), $banque)) === 0);
check('la banque contient des pièges (plus de mots que de trous)', count($banque) >= 24);
check('la banque n\'a pas de doublon', count(array_unique($banque)) === count($banque));
check('chaque trou est numéroté de 1 à 12 sans doublon', (function () {
    preg_match_all('/\{(\d+)\}/', implode("\n", script_lignes()), $m);
    $n = array_map('intval', $m[1]); sort($n);
    return $n === range(1, 12);
})());

echo "\n-- Réseau --\n";
check('hôte retrouvé par IP, par nom et par clé', hote_trouve('192.168.1.17') === 'archives' && hote_trouve('SRV-ARCHIVES') === 'archives' && hote_trouve('archives') === 'archives');
check('hôte inconnu', hote_trouve('10.0.0.9') === null);
check('9 équipements, dont un switch et le pirate', count(hotes()) === 9 && isset(hotes()['switch']) && isset(hotes()['pirate']));
check('chaque flux et chaque câble relie des équipements connus', (function () {
    foreach (flux() as $f) if (!isset(hotes()[$f[0]], hotes()[$f[1]])) return false;
    foreach (liens() as $l) if (!isset(hotes()[$l[0]], hotes()[$l[1]])) return false;
    return true;
})());
check('tous les équipements sont reliés au réseau', (function () {
    $vus = [];
    foreach (liens() as $l) { $vus[$l[0]] = true; $vus[$l[1]] = true; }
    return count($vus) === count(hotes());
})());
check('les équipements ne se chevauchent pas sur la carte', (function () {
    $h = array_values(hotes());
    foreach ($h as $i => $a) foreach ($h as $j => $b) if ($i < $j && abs($a['x'] - $b['x']) < 120 && abs($a['y'] - $b['y']) < 70) return false;
    return true;
})());
check('les ports de l\'inventaire du rapport T3 correspondent aux ports réels, sauf le port intrus', (function () {
    $rapport = '';
    foreach (documents() as $d) if ($d['id'] === 'rapport-t3') $rapport = $d['texte'];
    foreach (hotes() as $k => $h) foreach ($h['ports'] as $port => $_) {
        $dansRapport = str_contains($rapport, (string) $port . ' (') || preg_match('/\b' . $port . '\b/', $rapport);
        if ($k === 'archives' && $port === SERVEUR_PIVOT_PORT) { if ($dansRapport) return false; continue; }
        if (!$dansRapport) return false;
    }
    return true;
})());
check('chaque serveur a son bouton', count(array_unique(array_values(BOUTON_SERVEUR))) === 4 && count(BOUTON_SERVEUR) === 4);

echo "\n-- Console des équipements (CLI) --\n";
game_reset('test'); game_login(); game_unlock(S_IP);
check('help liste les commandes', str_contains(reseau_cli('archives', 'help'), 'show ports'));
check('show ports montre le port inconnu', str_contains(reseau_cli('archives', 'show ports'), '4444') && str_contains(reseau_cli('archives', 'show ports'), 'inconnu'));
check('show connections montre le lien vers le pirate', str_contains(reseau_cli('archives', 'show connections'), '192.168.1.42:4444'));
check('un équipement sans port le dit', str_contains(reseau_cli('pc-kovac', 'show ports'), 'Aucun port'));
check('le pirate est inaccessible', str_contains(reseau_cli('pirate', 'help'), 'externe'));
check('commande inconnue', str_contains(reseau_cli('bd', 'reboot'), 'inconnue'));
$avant = game_leak();
check('close d\'un port légitime : pénalité de 3 %', str_contains(reseau_cli('archives', 'close 445'), 'inventaire') && game_leak() === $avant + 3 && game_step() === S_IP);
check('close d\'un port inexistant : aucune pénalité', str_contains(reseau_cli('bd', 'close 9999'), 'Aucun service') && game_leak() === $avant + 3);
check('close 4444 sur une autre machine : refusé', str_contains(reseau_cli('web', 'close 4444'), 'Aucun service') && game_step() === S_IP);
check('close 4444 sur SRV-ARCHIVES résout l\'énigme', str_contains(reseau_cli('archives', 'close 4444'), 'SUCCÈS') && game_step() === S_PIVOT);
check('le relais fermé disparaît des connexions', !str_contains(reseau_cli('archives', 'show connections'), '192.168.1.42'));
game_reset('test');

echo "\n-- Scénario --\n";
check('chaque objectif a 3 indices, du plus général au plus précis', (function () {
    game_reset('test'); game_login();
    foreach (objectifs() as $i => $_) { $h = indices()[$i] ?? []; if (count($h) !== 3 || strlen($h[2]) < strlen('Tapez : auth ')) return false; }
    return true;
})());
check('chaque objectif a un briefing (contexte et piste)', (function () {
    foreach (objectifs() as $i => $_) if (empty(briefing()[$i]['contexte']) || empty(briefing()[$i]['piste'])) return false;
    return true;
})());
check('l\'indice final de l\'ordre cite les vrais boutons', (function () {
    game_reset('test'); game_login(); state_set('ordre', 'routeur,archives,bd,web');
    $dernier = indices()[S_PIVOT][2];
    return str_contains($dernier, 'bouton 4 (ROUTEUR-DGSI), puis bouton 2 (SRV-ARCHIVES), puis bouton 3 (SRV-BD), puis bouton 1 (SRV-WEB)');
})());
check('les mails arrivent dans l\'ordre des étapes', (function () { foreach (mails() as $m) if ($m['etape'] < S_LOGIN || $m['etape'] > S_SCRIPT) return false; return true; })());
check('les identifiants de mail sont uniques', count(array_unique(array_column(mails(), 'id'))) === count(mails()));
check('le mail d\'anniversaire contient le PIN du téléphone', (function () { foreach (mails() as $m) if (str_contains($m['texte'], '07/12')) return true; return false; })());
check('les documents ont un identifiant unique et un dossier connu', (function () {
    $ids = array_column(documents(), 'id');
    if (count(array_unique($ids)) !== count($ids)) return false;
    foreach (documents() as $d) if (!in_array($d['dossier'], ['Documentation', 'Rapports', 'Mes documents', 'Corbeille'], true) || trim($d['texte']) === '') return false;
    return true;
})());
check('la documentation couvre chaque épreuve', (function () {
    $ids = array_column(documents(), 'id');
    return !array_diff(['guide-terminal', 'procedure', 'guide-reseau', 'guide-sql', 'guide-python', 'rapport-t3'], $ids);
})());
check('chaque guide arrive avant l\'épreuve qu\'il explique', (function () {
    $e = array_column(documents(), 'etape', 'id');
    return $e['guide-reseau'] <= S_IP && $e['guide-sql'] <= S_SERVEURS && $e['guide-python'] <= S_ALARME && $e['rapport-t3'] <= S_IP;
})());
check('le guide Python explique les mots du code à trous', (function () {
    $g = ''; foreach (documents() as $d) if ($d['id'] === 'guide-python') $g = $d['texte'];
    foreach (['==', '!=', '>=', 'and', 'for', 'kill', 'False', 'mqtt.publish', 'OFF'] as $mot) if (!str_contains($g, $mot)) return false;
    return true;
})());
check('chaque photo a un titre et une date', (function () { foreach (photos() as $p) if ($p['titre'] === '' || $p['date'] === '') return false; return count(photos()) >= 4; })());
check('aucun accent corrompu dans les textes du scénario', (function () {
    $tout = json_encode([mails(), documents(), photos(), briefing(), indices(), objectifs(), script_lignes()], JSON_UNESCAPED_UNICODE);
    return !preg_match('/Ã[\x{0080}-\x{00BF}\x{2018}-\x{203A}]|Â[«»]/u', $tout);
})());
check('les guides restent courts (moins de 900 caractères chacun)', (function () {
    foreach (documents() as $d) if (str_starts_with($d['id'], 'guide-') && mb_strlen($d['texte']) > 900) return false;
    return true;
})());
check('pénalité d\'erreur 3 %, indice 5 %', PENALITE_ERREUR === 3 && COUT_INDICE === 5);

game_reset('test');
@unlink($dbFile); @unlink($dbFile . '-wal'); @unlink($dbFile . '-shm');
echo $fails === 0 ? "\nTout est bon.\n" : "\n$fails test(s) en échec.\n";
exit($fails === 0 ? 0 : 1);
