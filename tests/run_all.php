<?php
/* Lance les 5 tests l'un après l'autre et affiche un résumé. Code de sortie 0 si tout passe, 1 sinon.
   Usage : php tests/run_all.php [hôte-mqtt] [--detail]

   - game_test et puzzles_test tournent toujours (ni site ni broker nécessaires).
   - mqtt_test et bridge_test tournent si Mosquitto répond. L'hôte est le premier argument, sinon LERNA_MQTT_HOST,
     sinon l'adresse de carte/include/secrets.h, sinon localhost.
   - smoke tourne si le site répond sur http://127.0.0.1:8000 ET qu'aucune partie n'est en cours (phase jeu ou alarme) :
     il remet la partie à zéro, donc on ne le lance jamais au milieu d'une partie. Une partie déjà terminée est déjà
     dans l'historique, la remettre à zéro ne fait rien perdre.
   --detail affiche la sortie complète de chaque test au lieu des seules vérifications en échec. */

$detail = in_array('--detail', $argv, true);
$args = array_values(array_filter(array_slice($argv, 1), fn($a) => $a !== '--detail'));

/* --- où est le broker ? --- */
$hote = $args[0] ?? getenv('LERNA_MQTT_HOST') ?: null;
if (!$hote) {
    $secrets = @file_get_contents(__DIR__ . '/../carte/include/secrets.h');
    if ($secrets && preg_match('/#define\s+MQTT_SERVER_IP\s+"([^"]+)"/', $secrets, $m)) $hote = $m[1];
}
$hote = $hote ?: 'localhost';

function repond(string $hote, int $port): bool
{
    $s = @fsockopen($hote, $port, $errno, $errstr, 1.0);
    if ($s) { fclose($s); return true; }
    return false;
}

/* --- le site tourne-t-il, et une partie est-elle en cours ? --- */
$siteOk = repond('127.0.0.1', 8000);
$phase = null;
if ($siteOk) {
    $r = @file_get_contents('http://127.0.0.1:8000/gm.php', false, stream_context_create(['http' => ['timeout' => 3]]));
    $phase = $r ? (json_decode($r, true)['phase'] ?? null) : null;
}
$brokerOk = repond($hote, 1883);

$tests = [
    ['game_test.php',    'Règles du jeu',                 [],           null],
    ['puzzles_test.php', 'Épreuves, SQL, code à trous',   [],           null],
    ['mqtt_test.php',    'Client MQTT',                   [$hote],      $brokerOk ? null : "Mosquitto ne répond pas sur $hote:1883"],
    ['bridge_test.php',  'Pont carte <-> jeu',            [$hote],      $brokerOk ? null : "Mosquitto ne répond pas sur $hote:1883"],
    ['smoke.php',        'Partie complète via le site',   [],           !$siteOk ? 'le site ne tourne pas (site/bin/start.ps1)'
                                                                          : (in_array($phase, ['jeu', 'alarme'], true) ? "une partie est en cours (phase : $phase), le test la remettrait à zéro" : null)],
];

$echecs = 0; $ignores = 0; $total = 0;
echo "Lancement des tests (broker : $hote)\n\n";
foreach ($tests as [$fichier, $titre, $params, $raison]) {
    if ($raison !== null) { printf("  --  %-30s ignoré : %s\n", $titre, $raison); $ignores++; continue; }
    $proc = proc_open(array_merge([PHP_BINARY, __DIR__ . '/' . $fichier], $params), [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $sortie = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    $code = proc_close($proc);
    $ok = preg_match_all('/^  OK /m', $sortie);
    $ko = preg_match_all('/^  ECHEC /m', $sortie);
    $total += $ok + $ko;
    $bon = $code === 0 && $ko === 0;
    printf("  %s  %-30s %d vérification%s%s\n", $bon ? 'OK' : '!!', $titre, $ok + $ko, $ok + $ko > 1 ? 's' : '', $ko ? ", $ko en échec" : '');
    if (!$bon) {
        $echecs++;
        if (!$detail) {
            foreach (preg_split('/\R/', $sortie) as $l) if (str_starts_with($l, '  ECHEC') || stripos($l, 'fatal') !== false || stripos($l, 'warning') !== false) echo "        $l\n";
            if ($ko === 0) echo "        (code de sortie $code) " . trim(substr($sortie, -300)) . "\n";
        }
    }
    if ($detail) echo "\n" . $sortie . "\n";
}

echo "\n";
if ($echecs) { echo "$echecs test(s) en échec.\n"; exit(1); }
echo "Tout est bon : $total vérifications" . ($ignores ? ", $ignores test(s) ignoré(s)" : '') . ".\n";
exit(0);
