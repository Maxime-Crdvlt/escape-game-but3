<?php
/* Test de fumée : joue toute la partie contre le site (terminal, applications du bureau, téléphone, boutons simulés).
   Usage :  php tests/smoke.php [url]      (le serveur doit tourner, voir site/README.md)
   Par défaut l'URL est http://127.0.0.1:8000. Code de sortie 0 si tout passe, 1 sinon.
   Attention : il remet la partie à zéro au début et à la fin, déclenche brièvement l'alarme de la vraie carte,
   et efface de l'historique les parties qu'il a lui-même créées. */

require __DIR__ . '/../site/src/scenario.php';

$racine = rtrim($argv[1] ?? 'http://127.0.0.1:8000', '/');
$pin = getenv('LERNA_GM_PIN') ?: 'lerna';
$fails = 0;

function post(string $url, array $data): array {
    $ctx = stream_context_create(['http' => [
        'method' => 'POST', 'header' => "Content-Type: application/json\r\n",
        'content' => json_encode($data), 'ignore_errors' => true, 'timeout' => 10,
    ]]);
    $body = @file_get_contents($url, false, $ctx);
    $data = json_decode((string) $body, true);
    if (!is_array($data)) { fwrite(STDERR, "Réponse invalide de $url (serveur lancé ?) : " . substr((string) $body, 0, 200) . "\n"); exit(1); }
    return $data;
}
function lire(string $url): array {
    $data = json_decode((string) @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 10]])), true);
    if (!is_array($data)) { fwrite(STDERR, "Réponse invalide de $url\n"); exit(1); }
    return $data;
}
function call(string $command): array { global $racine; return post("$racine/api.php", ['command' => $command]); }
function act(string $action, array $extra = []): array { global $racine; return post("$racine/api.php", ['action' => $action] + $extra); }
function gm(string $action, array $extra = []): array { global $racine, $pin; return post("$racine/gm.php", ['action' => $action, 'pin' => $pin] + $extra); }
function tel(string $pinTel): array { global $racine; return post("$racine/telephone.php", ['pin' => $pinTel]); }

function check(string $name, bool $ok, string $detail = ''): void {
    global $fails;
    echo ($ok ? '  OK     ' : '  ECHEC  ') . $name . ($ok ? '' : "  -> $detail") . "\n";
    if (!$ok) $fails++;
}
function ok(array $r): bool { return ($r['status'] ?? '') === 'success-msg'; }

echo "Test de la partie complète sur $racine\n";
$dernierId = (int) (lire("$racine/gm.php?historique=1")['parties'][0]['id'] ?? 0);

echo "\n-- Départ --\n";
$r = call('reset');
check('reset de départ', ($r['reset'] ?? false) === true);
$r = call('aide');
check('aide avant connexion', str_contains($r['message'], 'login'), $r['message']);
$r = call('show logs');
check('show logs refusé avant connexion', $r['status'] === 'error-msg' && str_contains($r['message'], 'verrouillée'), $r['message']);
check('le briefing est donné dès le départ', $r['mission']['n'] === 1 && str_contains($r['mission']['piste'], 'cadre photo'), json_encode($r['mission'] ?? null));
$r = call('indice');
check('un indice est possible dès le départ (cadre photo) et coûte 5 %', str_contains($r['message'], 'cadre photo') && $r['leak'] >= 5 && $r['leak'] <= 6, $r['message']);
$r = call('login mauvais');
check('mauvais mot de passe refusé', $r['status'] === 'error-msg' && $r['pass_ok'] === false, $r['message']);

echo "\n-- Double authentification --\n";
$r = call('LOGIN Max2018');
check('login Max2018 : mot de passe accepté mais pas encore connecté', ok($r) && $r['pass_ok'] === true && $r['logged'] === false, $r['message']);
$r = call('show logs');
check('le terminal reste verrouillé sans le code', $r['status'] === 'error-msg' && str_contains($r['message'], '2fa'), $r['message']);
$t = tel('1234');
check('téléphone : mauvais PIN refusé', ($t['ok'] ?? true) === false && ($t['essais_restants'] ?? -1) === 2, json_encode($t));
$t = tel(PIN_TELEPHONE);
check('téléphone : bon PIN, code à 6 chiffres', ($t['ok'] ?? false) === true && preg_match('/^\d{6}$/', $t['code'] ?? '') === 1 && count($t['sms']) >= 2, json_encode($t));
$base = call('__state')['leak'];
$r = call('2fa 000000');
check('mauvais code 2FA refusé (+3 %)', $r['status'] === 'error-msg' && $r['logged'] === false && $r['leak'] - $base === 3, $r['message'] . ' / ' . $r['leak']);
$r = call('2fa ' . $t['code']);
check('bon code 2FA : connecté', ok($r) && $r['logged'] === true && $r['step'] === S_2FA, $r['message']);

echo "\n-- Documentation, messagerie, photos --\n";
$r = act('documents');
$titres = array_column($r['documents'], 'titre');
check('documents : guide du terminal, procédure et rapport T3 sont là', !array_diff(['Guide_du_terminal.txt', 'Procedure_incident.pdf', 'Rapport_T3.pdf'], $titres), implode(', ', $titres));
check('documents : le guide réseau n\'est pas encore débloqué', !in_array('Guide_reseau.txt', $titres, true));
check('documents : la corbeille contient le fichier piégé', count(array_filter($r['documents'], fn($d) => $d['dossier'] === 'Corbeille' && str_contains($d['titre'], '.pdf.exe'))) === 1);
check('photos : le tableau blanc est visible, pas encore la baie', count(array_filter($r['photos'], fn($p) => $p['id'] === 'tableau')) === 1 && count(array_filter($r['photos'], fn($p) => $p['id'] === 'baie')) === 0);
$r = act('mails');
check('messagerie : le mail d\'accueil de la DSI est arrivé', count(array_filter($r['mails'], fn($m) => str_contains($m['sujet'], 'Bienvenue'))) === 1, json_encode(array_column($r['mails'], 'sujet')));

echo "\n-- Réseau --\n";
$r = call('show logs');
check('show logs montre l\'IP suspecte', str_contains($r['message'], '192.168.1.42'), $r['message']);
$r = call('scan');
check('scan verrouillé avant cibler', $r['status'] === 'error-msg', $r['message']);
$r = act('reseau');
check('carte réseau verrouillée avant cibler', isset($r['erreur']), json_encode($r));
$base = $r['leak'] ?? call('__state')['leak'];
$r = call('cibler 10.0.0.1');
check('mauvaise IP : +3 % de fuite', $r['status'] === 'error-msg' && $r['leak'] - $base === 3, json_encode($r['leak']));
$r = call('cibler 192.168.1.42');
check('cibler la bonne IP', ok($r) && $r['step'] === S_IP, $r['message']);
$r = act('documents');
check('le guide réseau est débloqué', in_array('Guide_reseau.txt', array_column($r['documents'], 'titre'), true));
$r = call('scan');
check('scan : SRV-ARCHIVES avec son port inconnu, et le switch', str_contains($r['message'], '192.168.1.17') && str_contains($r['message'], '4444') && str_contains($r['message'], 'SW-CORE'), $r['message']);
$r = call('netstat');
check('netstat : connexion vers le pirate', str_contains($r['message'], '192.168.1.42:4444'), $r['message']);
$r = act('reseau');
check('carte réseau : 9 équipements, 8 câbles, 5 flux, pas de dépendances', count($r['hotes'] ?? []) === 9 && count($r['liens'] ?? []) === 8 && count($r['flux'] ?? []) === 5 && ($r['dependances'] ?? null) === [], json_encode(array_map('count', array_filter($r, 'is_array'))));
$r = act('cli', ['hote' => 'archives', 'ligne' => 'show ports']);
check('CLI : SRV-ARCHIVES liste ses ports', str_contains($r['sortie'], '445') && str_contains($r['sortie'], '4444'), $r['sortie'] ?? '');
$base = $r['leak'];
$r = act('cli', ['hote' => 'web', 'ligne' => 'close 443']);
check('CLI : fermer un port légitime coûte 3 %', str_contains($r['sortie'], 'inventaire') && $r['leak'] - $base === 3, $r['sortie']);
$r = call('bloquer 192.168.1.10 443');
check('terminal : mauvais blocage refusé', $r['status'] === 'error-msg', $r['message']);
$r = call('bloquer srv-archives 4444');
check('terminal : bloquer le relais (par son nom)', ok($r) && $r['step'] === S_PIVOT, $r['message']);
$r = act('reseau');
check('carte réseau : tunnel coupé, 5 dépendances visibles, serveurs corrompus', count($r['dependances']) === 5 && count($r['flux']) === 4 && count(array_filter($r['hotes'], fn($h) => $h['corrompu'])) === 4, json_encode($r['dependances'] ?? null));

echo "\n-- Baie serveurs (boutons physiques simulés, ordre aléatoire) --\n";
$r = act('mails');
check('messagerie : le mail de Lefebvre est arrivé', count(array_filter($r['mails'], fn($m) => str_contains($m['sujet'], 'Rappel : redémarrer'))) === 1);
$r = act('documents');
check('photos : la baie est maintenant visible', count(array_filter($r['photos'], fn($p) => $p['id'] === 'baie')) === 1);
$r = call('bd');
check('bd refusée avant les serveurs', $r['status'] === 'error-msg', $r['message']);
$etat = lire("$racine/gm.php");
$ordre = $etat['ordre'];
check('la partie a un ordre de redémarrage : le routeur d\'abord, jamais A3 A2 A1 A0', count($ordre) === 4 && $ordre[0]['id'] === 'routeur' && array_column($ordre, 'bouton') !== ['4', '3', '2', '1'], json_encode(array_column($ordre, 'bouton')));
$baie = act('baie');
check('baie : 4 serveurs hors ligne, numérotés de 1 à 4', count($baie['serveurs']) === 4 && array_column($baie['serveurs'], 'bouton') === ['1', '2', '3', '4'] && !array_filter($baie['serveurs'], fn($s) => $s['en_ligne']));
$base = $etat['leak'];
$r = gm('bouton', ['nom' => 'serveur' . $ordre[1]['bouton']]);   // le deuxième avant le premier
check('mauvais serveur en premier : séquence annulée, +3 %', $r['serveurs'] === [] && $r['leak'] - $base >= 3, json_encode($r['serveurs']));
$baie = act('baie');
$derniere = end($baie['journal']);
check('baie : le journal explique l\'erreur', $derniere['type'] === 'erreur' && str_contains($derniere['texte'], 'dépend de') && str_contains($derniere['texte'], 'ROUTEUR-DGSI'), json_encode($derniere));
for ($i = 0; $i < 3; $i++) $r = gm('bouton', ['nom' => 'serveur' . $ordre[$i]['bouton']]);
check('3 serveurs bien redémarrés, dans l\'ordre tiré', $r['serveurs'] === array_slice(array_column($ordre, 'id'), 0, 3) && $r['step'] === S_PIVOT, json_encode($r['serveurs']));
$baie = act('baie');
check('baie : 3 serveurs en ligne', count(array_filter($baie['serveurs'], fn($s) => $s['en_ligne'])) === 3 && $baie['termine'] === false);
$r = gm('bouton', ['nom' => 'serveur' . $ordre[3]['bouton']]);
check('4e serveur : on passe à la base de données', $r['step'] === S_SERVEURS, (string) $r['step']);

echo "\n-- Base de données --\n";
$r = act('documents');
check('le guide SQL est débloqué', in_array('Guide_SQL.txt', array_column($r['documents'], 'titre'), true));
$r = call('bd');
check('bd décrit les tables', str_contains($r['message'], 'personnel') && str_contains($r['message'], 'habilitations'), $r['message']);
$r = call("sql SELECT nom FROM personnel WHERE departement = 'ZERO-JOUR'");
check('sql : une requête simple marche', $r['status'] === 'sys-msg' && str_contains($r['message'], 'A. KOVAC') && str_contains($r['message'], '2 lignes'), $r['message']);
$r = call('sql DELETE FROM personnel');
check('sql : seule la lecture est permise', $r['status'] === 'error-msg' && str_contains($r['message'], 'SELECT'), $r['message']);
$r = call('sql SELECT * FROM inconnue');
check('sql : une erreur est expliquée', $r['status'] === 'error-msg' && str_contains($r['message'], 'ERREUR SQL'), $r['message']);
$r = call("sql SELECT p.id, p.nom FROM personnel p JOIN ouvertures o ON o.personnel_id = p.id WHERE o.fichier LIKE '%.exe'");
check('sql : la jointure trouve le suspect', str_contains($r['message'], '5002') && str_contains($r['message'], 'L. MARTIN'), $r['message']);
$r = call('auth 4117');
check('auth avant suspect refusé', $r['status'] === 'error-msg', $r['message']);
$r = call('suspect 1042');
check('mauvais suspect refusé', $r['status'] === 'error-msg', $r['message']);
$r = call('suspect 5002');
check('bon suspect', ok($r) && $r['step'] === S_SUSPECT, $r['message']);
$r = call("sql SELECT p.id, p.nom FROM personnel p JOIN habilitations h ON h.personnel_id = p.id WHERE h.droit = 'URGENCE_PHYSIQUE' AND p.departement = 'ZERO-JOUR'");
check('sql : le superviseur est unique', str_contains($r['message'], '4117') && str_contains($r['message'], '1 ligne'), $r['message']);
$r = call('auth 3588');
check('mauvais superviseur refusé (le directeur n\'est pas de ZERO-JOUR)', $r['status'] === 'error-msg', $r['message']);
$r = call('auth 4117');
check('bon superviseur', ok($r) && $r['step'] === S_AUTH, $r['message']);

echo "\n-- Alarme et code à trous --\n";
$r = act('script');
check('éditeur verrouillé avant le script d\'urgence', isset($r['erreur']), json_encode($r));
$r = call('run urgence.sh');
check('run urgence.sh déclenche l\'alarme', str_contains($r['message'], 'ALARME') && $r['phase'] === 'alarme' && $r['step'] === S_ALARME, $r['message']);
$r = act('documents');
check('le guide Python est débloqué', in_array('Guide_Python.txt', array_column($r['documents'], 'titre'), true));
$r = act('script');
check('éditeur : 12 trous et une banque de mots', count($r['lignes'] ?? []) > 25 && str_contains(implode("\n", $r['lignes']), '{12}') && in_array('kill', $r['banque'] ?? []), json_encode($r));
$base = $r['leak'] ?? call('__state')['leak'];
$r = gm('bouton', ['nom' => 'arret']);
check('arrêt refusé tant que le script est corrompu (+3 %)', $r['phase'] === 'alarme' && $r['step'] === S_ALARME && $r['leak'] - $base >= 3, json_encode([$r['phase'], $r['step']]));
$bonnes = ['4117', '4444', '==', 'False', '!=', '0', 'for', 'and', 'kill', '+', '>=', 'OFF'];
$fausses = $bonnes; $fausses[4] = '==';
$base = call('__state')['leak'];
$r = act('script_verifier', ['reponses' => $fausses]);
check('script : un trou faux est signalé, +3 %', $r['ok'] === false && $r['trous'][5] === false && $r['trous'][1] === true && $r['leak'] - $base === 3, json_encode($r['trous'] ?? $r));
$bonnes[11] = '"OFF"';
$r = act('script_verifier', ['reponses' => $bonnes]);
check('script : tous les trous justes (guillemets tolérés)', $r['ok'] === true && $r['step'] === S_SCRIPT, json_encode($r['trous'] ?? $r));

echo "\n-- Victoire et historique --\n";
$r = gm('bouton', ['nom' => 'arret']);
check('bouton arrêt : victoire', $r['phase'] === 'victoire', $r['phase']);
$r = call('__state');
check('l\'état annonce la victoire avec les statistiques et le rang', $r['phase'] === 'victoire' && isset($r['stats']['grade']) && $r['stats']['indices'] === 1 && $r['stats']['erreurs'] >= 7 && $r['stats']['rang'] >= 1, json_encode($r['stats'] ?? $r));
$r = call('show logs');
check('partie terminée : plus de commandes de jeu', str_contains($r['message'], 'terminée'), $r['message']);
$h = lire("$racine/gm.php?historique=1");
$p = $h['parties'][0] ?? [];
check('l\'historique a enregistré la partie (victoire, indices, erreurs, étapes)', ($p['id'] ?? 0) > $dernierId && $p['issue'] === 'victoire' && $p['indices'] === 1 && $p['erreurs'] >= 7 && count($p['etapes']) >= 9, json_encode($p));
check('le résumé de l\'historique est cohérent', $h['resume']['parties'] >= 1 && $h['resume']['victoires'] >= 1 && $h['resume']['meilleur'] !== null, json_encode($h['resume']));
$csv = (string) file_get_contents("$racine/gm.php?export=csv");
check('l\'export CSV contient la partie', str_contains($csv, 'victoire') && str_contains($csv, 'duree_s'));

echo "\n-- Divers --\n";
$r = call('reset');
check('reset final', ($r['reset'] ?? false) === true && $r['phase'] === 'attente');
$r = call('login max2018');
$r = call('commande_inconnue');
check('commande inconnue refusée proprement', $r['status'] === 'error-msg', $r['message']);
$r = call('reset');
gm('purger_apres', ['id' => $dernierId]);   // on ne laisse pas les parties de test dans l'historique
check('nettoyage : l\'historique est revenu comme avant', (int) (lire("$racine/gm.php?historique=1")['parties'][0]['id'] ?? 0) === $dernierId);

echo $fails === 0 ? "\nTout est bon.\n" : "\n$fails test(s) en échec.\n";
exit($fails === 0 ? 0 : 1);
