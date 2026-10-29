<?php
/* API de la page téléphone (telephone.html) : déverrouillage par PIN, puis code 2FA et SMS.
   POST {"pin": "0712"}. Trois erreurs de suite verrouillent le téléphone 15 secondes. */
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../src/game.php';

try {
    $in = json_decode(file_get_contents('php://input'), true) ?: [];
    $pin = preg_replace('/\D/', '', (string) ($in['pin'] ?? ''));

    $bloque = (int) state_get('tel_bloque', '0') - time();
    if ($bloque > 0) {
        echo json_encode(['ok' => false, 'bloque' => $bloque], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!hash_equals(PIN_TELEPHONE, $pin)) {
        $essais = (int) state_get('tel_essais', '0') + 1;
        state_set('tel_essais', $essais);
        event_add('telephone', 'mauvais PIN');
        if ($essais >= 3) { state_set('tel_bloque', time() + 15); state_set('tel_essais', 0); }
        echo json_encode(['ok' => false, 'essais_restants' => max(0, 3 - $essais), 'bloque' => $essais >= 3 ? 15 : 0], JSON_UNESCAPED_UNICODE);
        exit;
    }

    state_set('tel_essais', 0);
    if (state_get('tel_deverrouille') === null) { state_set('tel_deverrouille', '1'); event_add('telephone', 'téléphone déverrouillé'); }
    echo json_encode([
        'ok'      => true,
        'code'    => game_2fa_code(),
        'expire'  => 30 - (time() % 30),
        'sms'     => [
            ['de' => 'DGSI Sécurité', 'heure' => '09:17', 'texte' => "ALERTE : connexion suspecte détectée sur votre compte. Si ce n'est pas vous, contactez la cellule Zéro-Jour."],
            ['de' => 'K. Diallo', 'heure' => '09:26', 'texte' => "Adrien, si LERNA déconne : cherche qui a ouvert le PDF bizarre. Gomez a tout mis dans la base."],
            ['de' => 'Maman', 'heure' => '08:03', 'texte' => "N'oublie pas de manger ce midi !"],
        ],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'erreur' => 'Erreur interne.'], JSON_UNESCAPED_UNICODE);
    error_log('[lerna tel] ' . $e->getMessage());
}
