<?php
/* Le pont : relie la carte Arduino (via Mosquitto) à la partie.
   - écoute les boutons physiques (lerna/bouton/...) et l'état de la carte (lerna/carte/etat)
   - publie l'alarme, la phase et la jauge dès que la partie change
   Se lance dans un terminal à part :  php site/bin/bridge.php   (start.ps1 le fait tout seul)
   Variables : LERNA_MQTT_HOST (défaut localhost), LERNA_MQTT_PORT (défaut 1883), LERNA_DB. */

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../src/game.php';

$host = getenv('LERNA_MQTT_HOST') ?: '127.0.0.1';
$port = (int) (getenv('LERNA_MQTT_PORT') ?: 1884);
$maxSeconds = (int) (getenv('LERNA_BRIDGE_MAX_SECONDS') ?: 0);   // pour les tests : s'arrête tout seul
$started = time();

function log_line(string $s): void { echo '[' . date('H:i:s') . '] ' . $s . "\n"; }

/* Un seul pont à la fois : deux ponts avec le même identifiant MQTT s'éjecteraient l'un l'autre en boucle. */
if (time() - (int) state_get('pont', '0') < 4) {
    log_line('Un autre pont tourne déjà sur cette base, arrêt.');
    exit(1);
}

$mqtt = new MqttLite($host, $port, 'lerna-pont', 30, ['topic' => TOPIC_PONT, 'payload' => 'hors-ligne', 'retain' => true]);
$connected = false;
log_line("Pont LERNA : broker $host:$port");

while (true) {
    if ($maxSeconds && time() - $started >= $maxSeconds) break;

    if (!$mqtt->connected()) {
        if ($connected) { log_line('Broker perdu, nouvelle tentative...'); $connected = false; sleep(2); }
        if (!$mqtt->connect(2.0)) { sleep(2); continue; }
        $mqtt->subscribe([TOPIC_BOUTON . '+', TOPIC_CARTE]);
        $mqtt->publish(TOPIC_PONT, 'en-ligne', true);
        game_publish($mqtt, true);   // tout republier : le broker a pu redémarrer
        $connected = true;
        log_line('Connecté au broker.');
    }

    foreach ($mqtt->poll(250) as $m) {
        if (str_starts_with($m['topic'], TOPIC_BOUTON)) {
            if ($m['retain'] || $m['payload'] !== '1') continue;   // un vieux message retenu, ou un message vide, n'est pas un appui
            $name = substr($m['topic'], strlen(TOPIC_BOUTON));
            if (!preg_match('/^[a-z0-9_]{1,20}$/', $name)) continue;
            $res = game_button($name);
            log_line("Bouton $name -> $res");
        } elseif ($m['topic'] === TOPIC_CARTE) {
            $etat = $m['payload'] === 'en-ligne' ? 'en-ligne' : 'hors-ligne';
            if (state_get('carte') !== $etat) { state_set('carte', $etat); event_add('carte', $etat); log_line("Carte $etat"); }
        }
    }

    try {
        game_tick();
        if ($mqtt->connected()) game_publish($mqtt);
        state_set('pont', time());   // battement de coeur, la page Game Master l'affiche
    } catch (Throwable $e) {
        log_line('Erreur : ' . $e->getMessage());
        sleep(1);
    }
}
$mqtt->disconnect();
log_line('Pont arrêté.');
