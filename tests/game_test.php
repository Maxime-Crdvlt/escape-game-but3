<?php
/* Test de la logique du jeu (site/src/game.php) sur une base temporaire. Aucun serveur ni broker nécessaire.
   Usage : php tests/game_test.php */

$dbFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'lerna_test_' . getmypid() . '.sqlite';
putenv('LERNA_DB=' . $dbFile);
require __DIR__ . '/../site/src/game.php';

$fails = 0;
function check(string $name, bool $ok, string $detail = ''): void {
    global $fails;
    echo ($ok ? '  OK     ' : '  ECHEC  ') . $name . ($ok ? '' : "  -> $detail") . "\n";
    if (!$ok) $fails++;
}
echo "Test de la logique du jeu\n";

/* ---- Départ ---- */
game_reset('test');
check('phase de départ : attente', game_phase() === 'attente', game_phase());
check('jauge à 0 avant la connexion', game_leak() === 0);
check('alarme éteinte au départ', game_outputs()[TOPIC_ALARME] === 'OFF');
check('pas de chrono avant la partie', game_restant() === null);

game_login();
check('phase jeu après login', game_phase() === 'jeu' && game_step() === S_LOGIN, game_phase());
check('le chrono part de 30 min', abs(game_restant() - 1800) <= 2, (string) game_restant());

/* ---- Pénalités et temps ---- */
game_penalty(5);
check('pénalité ajoutée à la jauge', game_leak() === 5, (string) game_leak());
state_set('start', time() - 900);
check('jauge à 55 % après 15 min + 5 de pénalité', game_leak() === 55, (string) game_leak());
state_set('start', time());
state_del('penalty');

/* ---- Double authentification ---- */
$code = game_2fa_code();
check('le code 2FA a 6 chiffres', preg_match('/^\d{6}$/', $code) === 1, $code);
check('le code 2FA actuel est accepté', game_2fa_valide($code));
check('le code est accepté avec des espaces', game_2fa_valide(substr($code, 0, 3) . ' ' . substr($code, 3)));
check('le code de la fenêtre précédente est encore accepté', game_2fa_valide(game_2fa_code(time() - 30)));
check('un code vieux de 2 minutes est refusé', !game_2fa_valide(game_2fa_code(time() - 120)));
check('un mauvais code est refusé', !game_2fa_valide($code === '000000' ? '111111' : '000000'));
check('le code change d\'une fenêtre à l\'autre', game_2fa_code(1000000) !== game_2fa_code(1000030));

/* ---- Indices ---- */
game_unlock(S_2FA);
$niveaux = count(indices()[S_2FA]);
$h1 = game_hint();
check('un indice coûte 5 %', $h1['texte'] !== null && $h1['niveau'] === 1 && game_leak() >= 5, json_encode($h1));
game_hint(); $h3 = game_hint();
check('le 3e indice est le dernier', $h3['niveau'] === $niveaux, json_encode($h3));
$h4 = game_hint();
check('plus d\'indice au-delà', $h4['texte'] === null);
check('les indices sont comptés', (int) state_get('indices') === $niveaux, (string) state_get('indices'));
state_del('penalty');

/* ---- Les étapes ne redescendent pas ---- */
game_unlock(S_IP);
game_unlock(S_2FA);
check('une étape ne redescend pas', game_step() === S_IP, (string) game_step());
check('boutons serveur sans effet avant le pivot', game_button('serveur4') === 'ignore');

/* ---- Redémarrage des serveurs ---- */
game_unlock(S_PIVOT);
state_set('ordre', 'routeur,bd,archives,web');   // ordre fixé pour le test (en vrai il est tiré au hasard)
check('message "Serveurs 0/4" envoyé à la carte', game_outputs()[TOPIC_MSG] === 'Serveurs 0/4', game_outputs()[TOPIC_MSG]);
check('mauvais serveur en premier : refusé', game_button('serveur1') === 'serveur-faux' && game_leak() === PENALITE_ERREUR, (string) game_leak());
check('routeur (serveur4) en premier', game_button('serveur4') === 'serveur-ok');
check('progression envoyée à la carte', game_outputs()[TOPIC_MSG] === 'Serveurs 1/4');
check('une erreur au milieu remet tout à zéro', game_button('serveur1') === 'serveur-faux' && game_serveurs_redemarres() === []);
foreach (['serveur4', 'serveur3', 'serveur2'] as $b) game_button($b);
check('3 bons serveurs de suite : pas encore fini', game_step() === S_PIVOT && count(game_serveurs_redemarres()) === 3);
check('4e serveur : étape suivante', game_button('serveur1') === 'serveurs-ok' && game_step() === S_SERVEURS, (string) game_step());
check('le message de la carte disparaît', game_outputs()[TOPIC_MSG] === '');
check('boutons serveur sans effet ensuite', game_button('serveur2') === 'ignore' && game_step() === S_SERVEURS);
state_del('penalty');

/* ---- Alarme et kill switch ---- */
check('bouton arrêt ignoré avant l\'alarme', game_button('arret') === 'ignore' && game_phase() === 'jeu');
game_unlock(S_ALARME);
check('phase alarme à l\'étape 8', game_phase() === 'alarme', game_phase());
check('alarme allumée', game_outputs()[TOPIC_ALARME] === 'ON');
check('bouton démarrage ignoré pendant la partie', game_button('demarrage') === 'ignore' && game_phase() === 'alarme');
$avant = game_leak();
check('arrêt refusé tant que le script n\'est pas réparé (+3 %)', game_button('arret') === 'refuse' && game_phase() === 'alarme' && game_leak() === $avant + PENALITE_ERREUR, (string) game_leak());
game_unlock(S_SCRIPT);
check('arrêt accepté une fois le script réparé : victoire', game_button('arret') === 'victoire' && game_phase() === 'victoire');
$figee = game_leak();
state_set('start', time() - 1700);
check('jauge figée après victoire', game_leak() === $figee, game_leak() . ' au lieu de ' . $figee);
check('alarme éteinte après victoire', game_outputs()[TOPIC_ALARME] === 'OFF');
check('état publié : victoire', game_outputs()[TOPIC_ETAT] === 'victoire');
$s = game_stats();
check('statistiques de fin cohérentes', $s['jauge'] === $figee && $s['indices'] === $niveaux && $s['grade'] !== '' && $s['temps'] >= 0, json_encode($s));
check('bouton démarrage après victoire : nouvelle partie', game_button('demarrage') === 'reset' && game_phase() === 'attente' && game_step() === 0);
state_set('tel_deverrouille', '1'); game_reset('test'); game_login(); game_set_step(S_SCRIPT); game_victory('test'); game_button('demarrage');
check('la remise à zéro efface tout', game_leak() === 0 && game_serveurs_redemarres() === [] && (int) state_get('indices', '0') === 0 && state_get('tel_deverrouille') === null);

/* ---- Défaite ---- */
game_login();
state_set('start', time() - DUREE_PARTIE - 10);
check('jauge à 100 % : défaite', game_leak() === 100 && game_phase() === 'defaite', game_phase());
check('alarme allumée en cas de défaite', game_outputs()[TOPIC_ALARME] === 'ON');
check('bouton arrêt ne sauve pas une défaite', game_button('arret') === 'ignore' && game_phase() === 'defaite');
game_tick(); game_tick();
$n = count(array_filter(events_last(100), fn($e) => $e['type'] === 'defaite'));
check('défaite notée une seule fois dans le journal', $n === 1, (string) $n);
check('grade de défaite', game_stats()['grade'] === 'Mission échouée');
check('bouton démarrage après défaite : nouvelle partie', game_button('demarrage') === 'reset' && game_phase() === 'attente');

/* ---- Game Master ---- */
game_login();
check('rab : +5 min', game_rab() && game_duree() === DUREE_PARTIE + 300);
game_rab(); game_rab();
check('rab limité à 15 min', !game_rab() && game_duree() === DUREE_PARTIE + RAB_MAX, (string) game_duree());
state_set('start', time() - 900);
check('le rab ralentit la jauge', game_leak() < 50, (string) game_leak());
game_set_step(S_SERVEURS);
check('étape forcée par le game master', game_step() === S_SERVEURS);
game_set_step(99);
check('étape forcée bornée', game_step() === S_SCRIPT);
game_victory('test');
check('victoire forcée par le game master', game_phase() === 'victoire');

/* ---- Ordre de redémarrage aléatoire et journal de la baie ---- */
$ordres = [];
for ($i = 0; $i < 60; $i++) { game_reset('test'); game_login(); $ordres[implode(',', ordre_redemarrage())] = true; }
check('l\'ordre est tiré au hasard (5 possibilités, le routeur toujours en premier)', count($ordres) >= 4 && count(array_filter(array_keys($ordres), fn($o) => !str_starts_with($o, 'routeur,'))) === 0, count($ordres) . ' ordres');
check('chaque ordre contient les 4 serveurs', count(array_filter(array_keys($ordres), fn($o) => count(array_unique(explode(',', $o))) !== 4)) === 0);
$ok = true;
foreach (array_keys($ordres) as $o) {
    state_set('ordre', $o); $pos = array_flip(explode(',', $o));
    foreach (dependances() as [$de, $vers]) if ($pos[$vers] > $pos[$de]) $ok = false;
}
check('les dépendances de la carte respectent toujours l\'ordre', $ok);
$premier = array_key_first($ordres);
state_set('ordre', 'routeur,archives,web,bd'); game_set_step(S_PIVOT);
check('dépendances : web dépend de archives, bd dépend de web', in_array(['web', 'archives'], dependances(), true) && in_array(['bd', 'web'], dependances(), true));
game_button('serveur3');   // BD avant son tour
$j = baie_journal();
check('le journal de la baie explique ce qui manque', str_contains(end($j)['texte'], 'SRV-BD dépend de') && str_contains(end($j)['texte'], 'SRV-WEB') && end($j)['type'] === 'erreur', json_encode(end($j)));
game_button('serveur4'); game_button('serveur2'); game_button('serveur1'); $r = game_button('serveur3');
check('séquence dans un ordre aléatoire valide', $r === 'serveurs-ok' && game_step() === S_SERVEURS, $r . ' / étape ' . game_step());
check('les erreurs sont comptées séparément des indices', (int) state_get('erreurs') === 1 && (int) state_get('indices', '0') === 0);
check('le bouton d\'un serveur est mémorisé', bouton_du_serveur('routeur') === '4' && bouton_du_serveur('web') === '1');
check('jamais un simple balayage des boutons de A3 à A0', (function () use ($ordres) {
    foreach (array_keys($ordres) as $o) { $b = array_map('bouton_du_serveur', explode(',', $o)); if ($b === ['4', '3', '2', '1']) return false; }
    return true;
})());

/* ---- Historique des parties ---- */
game_reset('test');
$avant = (int) db()->query('SELECT COUNT(*) FROM parties')->fetchColumn();
game_login(); game_reset('test');
check('un abandon de quelques secondes n\'entre pas dans l\'historique', (int) db()->query('SELECT COUNT(*) FROM parties')->fetchColumn() === $avant);

game_login(); state_set('start', time() - 1000);
game_unlock(S_2FA); game_penalty(PENALITE_ERREUR, 'test'); game_hint();
game_set_step(S_SCRIPT); game_victory('test');
$h = game_historique(1)[0];
check('une victoire est archivée', $h['issue'] === 'victoire' && $h['duree'] >= 1000 && $h['duree'] < 1010 && $h['indices'] === 1 && $h['erreurs'] === 1, json_encode($h));
check('le détail par objectif est conservé', isset($h['etapes'][S_2FA]) && $h['indices_detail'][S_2FA] === 1, json_encode([$h['etapes'], $h['indices_detail']]));
$n = (int) db()->query('SELECT COUNT(*) FROM parties')->fetchColumn();
game_victory('encore'); game_reset('test');
check('une partie n\'est archivée qu\'une fois', (int) db()->query('SELECT COUNT(*) FROM parties')->fetchColumn() === $n);
check('l\'historique survit à la remise à zéro', game_phase() === 'attente' && $n > $avant);

game_login(); state_set('start', time() - DUREE_PARTIE - 5); game_tick();
check('une défaite est archivée', game_historique(1)[0]['issue'] === 'defaite');
game_reset('test');
game_login(); state_set('start', time() - 300); game_set_step(S_PIVOT); game_reset('test');
$h = game_historique(1)[0];
check('un abandon notable est archivé avec l\'objectif atteint', $h['issue'] === 'abandon' && $h['objectif'] === S_PIVOT && $h['duree'] >= 300, json_encode($h));

db()->exec('DELETE FROM parties');
foreach ([600, 900, 1200] as $d) db()->prepare("INSERT INTO parties (debut, fin, issue, duree, jauge, indices, erreurs, penalites, rab, objectif) VALUES (1, 2, 'victoire', ?, 40, 2, 3, 6, 0, 10)")->execute([$d]);
db()->exec("INSERT INTO parties (debut, fin, issue, duree, jauge, indices, erreurs, penalites, rab, objectif) VALUES (1, 2, 'defaite', 1800, 100, 5, 9, 20, 0, 6)");
check('classement : 1er, 2e et dernier', game_classement(500)['rang'] === 1 && game_classement(1000)['rang'] === 3 && game_classement(2000)['rang'] === 4 && game_classement(500)['total'] === 3);
$r = game_resume_historique();
check('résumé de l\'historique', $r['parties'] === 4 && $r['victoires'] === 3 && $r['defaites'] === 1 && $r['meilleur'] === 600 && $r['moyenne'] === 900, json_encode($r));
game_reset('test');

/* ---- Journal ---- */
check('journal rempli', count(events_last(100)) > 10);
check('le journal est limité à 500 lignes', (function () { for ($i = 0; $i < 520; $i++) event_add('bruit'); return count(events_last(1000)) <= 500; })());

@unlink($dbFile); @unlink($dbFile . '-wal'); @unlink($dbFile . '-shm');
echo $fails === 0 ? "\nTout est bon.\n" : "\n$fails test(s) en échec.\n";
exit($fails === 0 ? 0 : 1);
