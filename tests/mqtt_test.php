<?php
/* Test du client MQTT en PHP pur contre un vrai broker.
   Usage : php tests/mqtt_test.php [hote] [port]      (Mosquitto doit tourner) */
require __DIR__ . '/../site/src/mqtt_lite.php';

$host = $argv[1] ?? (getenv('LERNA_MQTT_HOST') ?: 'localhost');
$port = (int) ($argv[2] ?? (getenv('LERNA_MQTT_PORT') ?: 1883));
$fails = 0;
function check(string $name, bool $ok, string $detail = ''): void {
    global $fails;
    echo ($ok ? '  OK     ' : '  ECHEC  ') . $name . ($ok ? '' : "  -> $detail") . "\n";
    if (!$ok) $fails++;
}
echo "Test MQTT sur $host:$port\n";

$sub = new MqttLite($host, $port, 'test-sub-' . mt_rand(1000, 9999), 10);
check('connexion de l\'abonné', $sub->connect(), 'broker injoignable');
if (!$sub->connected()) { echo "\nBroker injoignable.\n"; exit(1); }
check('abonnement', $sub->subscribe(['lerna/test/#']));
usleep(200000);

$topic = 'lerna/test/' . mt_rand(1000, 9999);
check('publication ponctuelle', MqttLite::publishOnce($host, $port, $topic, 'bonjour', false));
$got = [];
$t0 = microtime(true);
while (microtime(true) - $t0 < 2 && !$got) $got = $sub->poll(200);
check('message reçu', count($got) === 1 && $got[0]['topic'] === $topic && $got[0]['payload'] === 'bonjour', json_encode($got));

$ret = 'lerna/test/retenu' . mt_rand(1000, 9999);
check('publication retenue', MqttLite::publishOnce($host, $port, $ret, 'garde', true));
$late = new MqttLite($host, $port, 'test-late-' . mt_rand(1000, 9999), 10);
$late->connect();
$late->subscribe([$ret]);
$got = [];
$t0 = microtime(true);
while (microtime(true) - $t0 < 2 && !$got) $got = $late->poll(200);
check('message retenu reçu par un abonné tardif', count($got) === 1 && $got[0]['payload'] === 'garde' && $got[0]['retain'] === true, json_encode($got));
MqttLite::publishOnce($host, $port, $ret, '', true); // efface le message retenu

// dernier souhait (le broker publie quand le client disparaît sans prévenir)
$willTopic = 'lerna/test/dernier' . mt_rand(1000, 9999);
$sub->subscribe([$willTopic]);
usleep(200000);
$mortel = new MqttLite($host, $port, 'test-mort-' . mt_rand(1000, 9999), 10, ['topic' => $willTopic, 'payload' => 'hors-ligne', 'retain' => false]);
$mortel->connect();
$ref = new ReflectionProperty($mortel, 'sock'); $ref->setAccessible(true);
fclose($ref->getValue($mortel)); // coupure brutale, sans DISCONNECT
$got = [];
$t0 = microtime(true);
while (microtime(true) - $t0 < 3 && !$got) {
    // l'abonné écoute aussi lerna/test/#, on ignore les messages des étapes précédentes
    $got = array_values(array_filter($sub->poll(200), fn($m) => $m['topic'] === $willTopic));
}
// selon le broker, un message peut arriver en double quand deux abonnements se recouvrent (permis par MQTT) : on exige au moins un exemplaire correct
check('dernier souhait reçu après une coupure brutale', count($got) >= 1 && $got[0]['payload'] === 'hors-ligne', json_encode($got));

$sub->disconnect();
echo $fails === 0 ? "\nTout est bon.\n" : "\n$fails test(s) en échec.\n";
exit($fails === 0 ? 0 : 1);
