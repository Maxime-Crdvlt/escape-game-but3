<?php
/* API de la page Game Master (gm.html).
   GET  : état de la partie, de la carte et du pont, plus le journal. Lecture seule, sans code.
   POST : {"action": ..., "pin": "..."}. Le code se change avec LERNA_GM_PIN.
     reset | victoire | rab (+5 min) | etape (+ "n") | bouton (+ "nom" : simule un bouton de la carte) | alarme
     purger_apres (+ "id") | effacer_historique
   GET ?historique=1 : l'historique des parties, GET ?export=csv : le même en fichier CSV. */
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../src/game.php';

const GM_PIN_DEFAUT = 'lerna';
const BOUTONS_VALIDES = ['demarrage', 'arret', 'serveur1', 'serveur2', 'serveur3', 'serveur4'];

/** L'adresse du PC sur le réseau local (celle qu'il faut taper sur le téléphone). Aucun paquet n'est envoyé : on demande
    seulement à Windows par quelle adresse il sortirait vers Internet. */
function ip_lan(): ?string
{
    $s = @stream_socket_client('udp://8.8.8.8:53', $errno, $errstr, 1);
    if (!$s) return null;
    $nom = stream_socket_get_name($s, false);
    fclose($s);
    $ip = $nom ? explode(':', $nom)[0] : null;
    return $ip && $ip !== '0.0.0.0' ? $ip : null;
}

function gm_state(): array
{
    game_tick();
    $step = game_step();
    $obj = objectifs();
    return [
        'phase'    => game_phase(),
        'leak'     => game_leak(),
        'step'     => $step,
        'objectif' => $obj[$step] ?? 'Partie terminée',
        'objectifs' => $obj,
        'restant'  => game_restant(),
        'indices'  => (int) state_get('indices', '0'),
        'rab'      => (int) state_get('rab', '0'),
        'serveurs' => game_serveurs_redemarres(),
        'ordre'    => game_step() >= S_LOGIN ? array_map(fn($id) => ['id' => $id, 'nom' => nom_serveur($id), 'bouton' => bouton_du_serveur($id)], ordre_redemarrage()) : [],
        'carte'    => state_get('carte', 'inconnue'),
        'pont'     => time() - (int) state_get('pont', '0') < 5,
        'tel'      => state_get('tel_deverrouille') !== null,
        'ip_lan'   => ip_lan(),
        'port'     => (int) ($_SERVER['SERVER_PORT'] ?? 8000),
        'events'   => events_last(30),
    ];
}

try {
    /* Historique des parties : ?historique=1 (JSON) ou ?export=csv (fichier pour un tableur) */
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['historique'])) {
        echo json_encode(['parties' => game_historique(40), 'resume' => game_resume_historique(), 'objectifs' => objectifs()], JSON_UNESCAPED_UNICODE);
        exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['export'] ?? '') === 'csv') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="historique_lerna.csv"');
        echo "\xEF\xBB\xBF" . "id;date;issue;duree_s;jauge_finale;indices;erreurs;penalites;rab_s;objectif_atteint\n";
        foreach (array_reverse(game_historique(1000)) as $p) {
            echo implode(';', [$p['id'], date('Y-m-d H:i:s', $p['debut']), $p['issue'], $p['duree'], $p['jauge'], $p['indices'], $p['erreurs'], $p['penalites'], $p['rab'], $p['objectif']]) . "\n";
        }
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $in = json_decode(file_get_contents('php://input'), true) ?: [];
        $pin = getenv('LERNA_GM_PIN') ?: GM_PIN_DEFAUT;
        if (!hash_equals($pin, (string) ($in['pin'] ?? ''))) {
            http_response_code(403);
            echo json_encode(['error' => 'Code Game Master incorrect.'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        switch ($in['action'] ?? '') {
            case 'reset':
                game_reset('game master');
                break;
            case 'alarme':      // saute directement à l'alarme, pour tester les boutons de la carte
                game_set_step(S_ALARME);
                event_add('alarme', 'forcée par le game master');
                break;
            case 'victoire':
                game_victory('game master');
                break;
            case 'rab':
                if (!game_rab()) { http_response_code(409); echo json_encode(['error' => 'Le rab maximum (15 min) est déjà accordé.'], JSON_UNESCAPED_UNICODE); exit; }
                break;
            case 'etape':
                game_set_step((int) ($in['n'] ?? 0));
                break;
            case 'bouton':      // comme si on avait appuyé sur la carte (secours si le matériel ne répond pas)
                $nom = (string) ($in['nom'] ?? '');
                if (!in_array($nom, BOUTONS_VALIDES, true)) { http_response_code(400); echo json_encode(['error' => 'Bouton inconnu.'], JSON_UNESCAPED_UNICODE); exit; }
                event_add('gm', "bouton simulé : $nom");
                game_button($nom);
                break;
            case 'purger_apres':   // efface les parties de l'historique plus récentes que l'id donné (nettoyage après des essais)
                db()->prepare('DELETE FROM parties WHERE id > ?')->execute([(int) ($in['id'] ?? 0)]);
                break;
            case 'effacer_historique':
                db()->exec('DELETE FROM parties');
                break;
            default:
                http_response_code(400);
                echo json_encode(['error' => 'Action inconnue.'], JSON_UNESCAPED_UNICODE);
                exit;
        }
    }
    echo json_encode(gm_state(), JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur interne.'], JSON_UNESCAPED_UNICODE);
    error_log('[lerna gm] ' . $e->getMessage());
}
