<?php
/* Test du pont (site/bin/bridge.php) avec un vrai broker : un faux "Arduino" appuie sur des boutons,
   on vérifie que la partie réagit et que l'alarme, la phase et la jauge sont publiées.
   Usage : php tests/bridge_test.php [hote] [port]      (Mosquitto doit tourner) */

require __DIR__ . '/../site/src/mqtt_lite.php';

$host = $argv[1] ?? (getenv('LERNA_MQTT_HOST') ?: 'localhost');
$port = (int) ($argv[2] ?? (getenv('LERNA_MQTT_PORT') ?: 1883));
$dbFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lerna_bridge_' . getmypid() . '.sqlite';
putenv('LERNA_DB=' . $dbFile);
putenv('LERNA_TOPIC_PREFIX=lernatest');   // ne pas parler à la vraie carte si le broker est partagé
require __DIR__ . '/../site/src/game.php';

$fails = 0;
function check(string $name, bool $ok, string $detail = ''): void {
    global $fails;
    echo ($ok ? '  OK     ' : '  ECHEC  ') . $name . ($ok ? '' : "  -> $detail") . "\n";
    if (!$ok) $fails++;
}
/** Attend (jusqu'à $sec s) qu'un message corresponde à $topic / $payload. */
function attendre(MqttLite $c, string $topic, string $payload, float $sec = 6.0): bool {
    static $recus = [];
    $t0 = microtime(true);
    while (true) {
        foreach ($recus as $i => $m) {
            if ($m['topic'] === $topic && $m['payload'] === $payload) {
                unset($recus[$i]);
                return true;
            }
        }
        if (microtime(true) - $t0 >= $sec) return false;
        foreach ($c->poll(150) as $m) $recus[] = $m;
    }
}

echo "Test du pont sur $host:$port\n";
$arduino = new MqttLite($host, $port, 'faux-arduino-' . mt_rand(1000, 9999), 10);
if (!$arduino->connect()) { echo "Broker injoignable.\n"; exit(1); }
$arduino->subscribe([TOPIC_ALARME, TOPIC_ETAT, TOPIC_JAUGE, TOPIC_PONT]);

game_reset('test');
game_login(); game_unlock(S_SCRIPT);   // la partie est en alarme, le script est réparé : le bouton arrêt gagne

/** Lance le pont (sans passer par cmd.exe, pour pouvoir l'arrêter proprement). */
function lancer_pont(string $dbFile, string $host, int $port, string $sortie) {
    $env = ['LERNA_DB' => $dbFile, 'LERNA_MQTT_HOST' => $host, 'LERNA_MQTT_PORT' => (string) $port, 'LERNA_BRIDGE_MAX_SECONDS' => '30'];
    return proc_open([PHP_BINARY, __DIR__ . '/../site/bin/bridge.php'],
        [0 => ['pipe', 'r'], 1 => ['file', $sortie, 'w'], 2 => ['file', $sortie, 'a']], $pipes, null, array_merge(getenv(), $env));
}
$sortie = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lerna_bridge_out.txt';
$proc = lancer_pont($dbFile, $host, $port, $sortie);
check('le pont démarre', is_resource($proc));

check('le pont annonce qu\'il est en ligne', attendre($arduino, TOPIC_PONT, 'en-ligne'));
check('alarme publiée : ON', attendre($arduino, TOPIC_ALARME, 'ON'));
check('phase publiée : alarme', attendre($arduino, TOPIC_ETAT, 'alarme'));

$arduino->publish(TOPIC_CARTE, 'en-ligne', false);
$arduino->publish(TOPIC_BOUTON . 'serveur2', '1');
$arduino->publish(TOPIC_BOUTON . 'arret', '1');
check('appui sur arrêt : alarme OFF publiée', attendre($arduino, TOPIC_ALARME, 'OFF'));
check('appui sur arrêt : phase victoire publiée', attendre($arduino, TOPIC_ETAT, 'victoire'));

// on regarde la base côté test (le pont a écrit dedans)
db()->exec('PRAGMA wal_checkpoint');
check('la partie est en victoire dans la base', game_phase() === 'victoire', game_phase());
check('la carte est notée en ligne', state_get('carte') === 'en-ligne', (string) state_get('carte'));
$types = array_column(events_last(50), 'type');
check('les appuis sont dans le journal', in_array('bouton', $types, true) && in_array('victoire', $types, true) && in_array('carte', $types, true), implode(',', $types));
// Le pont écrit son battement à la fin de chaque tour de boucle : on lui laisse jusqu'à 3 s.
$t0 = microtime(true);
while (abs((int) state_get('pont', '0') - time()) > 3 && microtime(true) - $t0 < 3) usleep(100000);
check('le pont écrit son battement de coeur', abs((int) state_get('pont', '0') - time()) <= 3, 'pont=' . state_get('pont') . ' heure=' . time());

// nouvelle partie avec le bouton démarrage
$arduino->publish(TOPIC_BOUTON . 'demarrage', '1');
check('appui sur démarrage : phase attente publiée', attendre($arduino, TOPIC_ETAT, 'attente'));

// un message vide (effacement d'un message retenu) n'est pas un appui
game_login(); game_unlock(S_SCRIPT);
$arduino->publish(TOPIC_BOUTON . 'arret', '', false);
sleep(1);
check('un message vide n\'est pas un appui (la partie reste en alarme)', game_phase() === 'alarme', game_phase());

// un second pont est refusé tant que le premier tourne
$second = lancer_pont($dbFile, $host, $port, $sortie . '.second');
sleep(2);
$st = proc_get_status($second);
check('un second pont refuse de démarrer', $st['running'] === false, 'toujours en marche');
proc_close($second);

// un vieux message retenu (laissé par une carte) ne doit pas déclencher la victoire au redémarrage du pont
proc_terminate($proc); proc_close($proc);
sleep(4);   // le battement de coeur du premier pont doit expirer
MqttLite::publishOnce($host, $port, TOPIC_BOUTON . 'arret', '1', true);
$proc = lancer_pont($dbFile, $host, $port, $sortie);
attendre($arduino, TOPIC_PONT, 'en-ligne');
sleep(1);
check('un message retenu n\'est pas un appui (la partie reste en alarme)', game_phase() === 'alarme', game_phase());
MqttLite::publishOnce($host, $port, TOPIC_BOUTON . 'arret', '', true); // nettoie le retenu
proc_terminate($proc); proc_close($proc);
$arduino->disconnect();
unset($GLOBALS['pdo']);
@unlink($dbFile); @unlink($dbFile . '-wal'); @unlink($dbFile . '-shm');
echo $fails === 0 ? "\nTout est bon.\n" : "\n$fails test(s) en échec.\n";
exit($fails === 0 ? 0 : 1);
