# Terminal L.E.R.N.A (site web)

Le poste de travail de Kovac : connexion en deux étapes, bureau avec fenêtres (terminal, messagerie, documents, photos, navigateur, calculatrice, carte réseau, baie serveurs, éditeur de code), panneau Mission, jauge de fuite et chrono. Le navigateur envoie les actions à `public/api.php` (PHP). L'état de la partie est gardé dans une base SQLite, partagée par le bureau, le téléphone, la carte Arduino et la page Game Master. Un petit programme, le pont (`bin/bridge.php`), relie la carte à la partie par MQTT. L'historique des parties est gardé dans la même base.

```
bureau (index.html)  --->  api.php  --->  base SQLite  <---  bridge.php  <--->  Mosquitto  <--->  carte Arduino
téléphone            --->  telephone.php --->  (état + historique)               (boutons, alarme, jauge, écran)
page Game Master     --->  gm.php    --->
```

## Lancer

1. Installer PHP 8 ou plus avec les extensions `pdo_sqlite` et `mbstring` (à activer dans `php.ini` en retirant le `;` devant la ligne). Vérifier avec `php -v`
2. Depuis la racine du projet : `powershell -ExecutionPolicy Bypass -File site\bin\start.ps1`
   - `-Broker` lance aussi Mosquitto avec le fichier du projet
   - le site est ouvert par défaut aux autres appareils du Wi-Fi (téléphone, page Game Master) et le script affiche les adresses à taper ; `-Local` le réserve à ce PC
   - le script vérifie PHP, lance le pont dans une fenêtre réduite, puis le site
3. Ouvrir http://127.0.0.1:8000
4. Mot de passe de départ : `max2018` (le cadre photo sur le bureau)

Sans `start.ps1` : `php site/bin/bridge.php` dans un terminal, `php -S 127.0.0.1:8000 -t site/public` dans un autre. Seul `site/public` est servi : la base et le code ne sont pas téléchargeables.

Si le code PHP change, il faut relancer le pont (le site, lui, relit les fichiers à chaque requête).

## Le déroulé : 30 minutes, 10 objectifs

La partie dure 30 minutes. La jauge de fuite monte toute seule jusqu'à 100 % (défaite). **Chaque erreur coûte 3 %, chaque indice 5 %.** Le Game Master peut ajouter 15 minutes en tout.

| # | Objectif | Ce qu'il faut faire | Durée prévue |
| :-: | :--- | :--- | :-: |
| 1 | Mot de passe | Le trouver sur le cadre photo du bureau, le saisir sur l'écran de connexion | 2 min |
| 2 | Double authentification | Ouvrir la page téléphone, trouver le code PIN (la date dans le mail d'anniversaire de Léa, ou sur le gâteau en photo), lire le code à 6 chiffres qui change toutes les 30 s | 4 min |
| 3 | IP du pirate | `show logs`, repérer la connexion suspecte, `cibler 192.168.1.42` | 3 min |
| 4 | Relais du pirate | Comparer les ports de la carte réseau avec le Rapport_T3.pdf, repérer le port 4444 sur SRV-ARCHIVES, le fermer (`bloquer 192.168.1.17 4444` ou `close 4444` dans le CLI de la machine) | 4 min |
| 5 | Serveurs (**boutons physiques**) | Redémarrer les 4 serveurs dans l'ordre de leurs dépendances, avec les boutons de la baie. **L'ordre est tiré au hasard à chaque partie** | 4 min |
| 6 | Suspect (SQL) | Écrire une requête avec JOIN pour trouver qui a ouvert le fichier `.pdf.exe` : `suspect 5002` | 4 min |
| 7 | Superviseur (SQL) | Croiser `personnel` et `habilitations` : `auth 4117` | 3 min |
| 8 | Script d'urgence | `run urgence.sh` : l'alarme se déclenche (sirène, LED rouge) | 1 min |
| 9 | Code à trous | Dans l'éditeur urgence.sh, remplir **12 trous** (Python et MQTT) pour réparer la procédure d'arrêt | 5 min |
| 10 | Kill switch (**bouton physique**) | Appuyer sur le bouton ARRÊT : victoire. Avant l'objectif 9, le bouton est refusé (+3 %) | 1 min |

Les commandes utiles : `aide`, `statut` (objectifs, contexte et piste), `indice`, `ls`, `cat`, `ping`, `clear`.

### Comment le jeu guide les joueurs

- **Panneau Mission** (en haut à droite) : l'objectif en cours, son contexte, où chercher, et des raccourcis vers les bonnes applications.
- **Documents** : Guide du terminal, Procédure d'incident, Rapport T3, puis, au fil de l'enquête, les guides Réseau, SQL et Python. Les nouveaux documents sont signalés par une notification.
- **Messagerie** : des mails arrivent au bon moment (audit, Lefebvre, Gomez, Diallo) avec des pistes.
- **Photos** : le tableau blanc de la réunion de crise, la baie avec ses boutons numérotés, un post-it, le gâteau.
- **Navigateur** : l'intranet avec un wiki et le ticket d'incident, qui coche les objectifs au fur et à mesure.
- **Erreurs expliquées** : la baie dit quel serveur manquait, le SQL explique ses erreurs sans pénalité, l'éditeur à trous indique quels trous sont faux.
- **Indices** : 3 par objectif, du plus général (« où chercher ») au plus précis (la commande exacte).

### Ce qu'il faut préparer dans la salle

- Un téléphone ou une tablette ouverts sur `http://[IP du PC]:8000/telephone.html` (l'adresse est affichée par `start.ps1` et en haut de la page Game Master)
- Le cadre photo avec « Max2018 » (prénom d'un enfant et année de naissance)
- La baie : les 4 boutons serveur **numérotés de 1 à 4** (le numéro est affiché dans l'application Baie serveurs et sur la photo de la baie), le bouton ARRÊT bien visible, le bouton DÉMARRAGE
- Le coffre fermé sur le servo (il s'ouvre à la victoire)

Les boutons serveur de la carte sont `serveur1` à `serveur4`, sur les prises A0 à A3 : 1 = SRV-WEB, 2 = SRV-ARCHIVES, 3 = SRV-BD, 4 = ROUTEUR-DGSI. Le bouton ARRÊT est sur D1 et le bouton DÉMARRAGE sur D0. L'ordre de pression change à chaque partie (jamais un simple balayage de 4 à 1) : le Game Master le voit sur sa page.

## Page Game Master

http://127.0.0.1:8000/gm.html : phase, temps restant, jauge, état de la carte, du pont et du téléphone, liste des objectifs réussis, nombre d'indices utilisés, ordre de redémarrage de la partie, journal de la partie et **historique des parties**. Les actions sont protégées par un code (`lerna` par défaut, à changer avec la variable `LERNA_GM_PIN`) :

- **+5 min (rab)** : trois fois au maximum
- **Valider la victoire**
- **Nouvelle partie**
- **Aller à une étape** : saute directement à un objectif (secours, ou pour tester la suite)
- **Simuler un bouton** : comme si on avait appuyé sur la carte, si le matériel ne répond pas

Une partie terminée se relance avec le bouton DÉMARRAGE de la baie.

### Historique des parties

Chaque partie terminée (victoire, défaite, ou abandon de plus de 20 secondes) est enregistrée dans la base : date, issue, **temps mis**, jauge finale, **indices utilisés** (et à quel objectif), erreurs, pénalités, rab, objectif atteint, et le temps passé sur chaque objectif. L'historique survit aux remises à zéro. La page Game Master en affiche un résumé (record, temps moyen, indices et erreurs moyens) et permet de l'**exporter en CSV** (http://127.0.0.1:8000/gm.php?export=csv, s'ouvre dans Excel). L'écran de victoire montre le classement de l'équipe parmi les parties gagnées.

## Les épreuves en détail

- **Double authentification.** Le code est calculé comme dans une vraie application d'authentification (HMAC-SHA1 de l'heure, 6 chiffres, 30 secondes, une fenêtre de tolérance). Le téléphone est verrouillé par un PIN : trois erreurs de suite le bloquent 15 secondes. Le secret se change avec `LERNA_2FA_SECRET`.
- **Réseau.** La carte réseau imite un simulateur de réseau : menus, grille, routeur, switch, serveurs, postes, nuage « Internet », voyants verts sur les câbles. On clique sur un équipement : onglet **Config** (adresse, ports en écoute) et onglet **CLI** (`show ports`, `show connections`, `ping`, `close [port]`). Le mode **Simulation** fait passer des paquets sur les câbles (les paquets rouges vont vers le pirate). Après la coupure du relais, des flèches jaunes montrent « X dépend de Y ». Les commandes `scan` et `netstat` du terminal donnent les mêmes informations en texte.
- **Baie serveurs.** Une application de supervision : 4 serveurs avec leur numéro de bouton, voyant, animation de démarrage et journal. Une erreur de séquence (un serveur lancé avant ceux dont il dépend) secoue la baie, explique ce qui manquait et remet tout à zéro (+3 %). L'écran de la carte affiche « Serveurs 2/4 ».
- **SQL.** Une vraie base SQLite en mémoire, en lecture seule : seuls les `SELECT` passent (pas de `;`, de `PRAGMA`, de `ATTACH`, de récursion, ni plus de 3 `JOIN`). Les comparaisons de texte ignorent la casse. Une erreur de syntaxe est expliquée sans pénalité. Piège : deux personnes ont l'habilitation d'urgence, il faut aussi filtrer par service.
- **Code à trous.** Un script Python de 30 lignes avec 12 trous et une banque de 29 mots (dont des pièges) : l'ID et le port viennent de l'enquête, le reste est de la logique (comparaisons, `and`, boucle, compteur, `kill()`) et du MQTT (`OFF`). Chaque test dit quels trous sont justes ; un test raté coûte 3 %. Les réponses sont comparées sans tenir compte des majuscules, des espaces ni des guillemets.

## Tester

Cinq tests, depuis la racine du projet, dans un autre terminal que le serveur. **Pour tous les lancer d'un coup : `php tests/run_all.php`**. Le lanceur saute tout seul les tests qui ont besoin de Mosquitto ou du site s'ils ne tournent pas, et ne lance jamais `smoke.php` pendant une partie (il la remettrait à zéro). `--detail` affiche la sortie complète.

| Commande | Ce qu'il vérifie | A besoin de |
| :--- | :--- | :--- |
| `php tests/game_test.php` | Les règles du jeu (phases, jauge, 2FA, indices, ordre aléatoire des serveurs, boutons, défaite, rab, historique et classement) | rien |
| `php tests/puzzles_test.php` | Les épreuves : garde-fous du SQL, code à trous, CLI des équipements, cohérence du réseau, des documents et des indices | rien |
| `php tests/smoke.php` | Toute la partie, via le site (terminal, bureau, documents, baie, téléphone, boutons simulés, historique) | le site lancé |
| `php tests/mqtt_test.php [hôte]` | Le client MQTT en PHP (messages, message retenu, dernier souhait) | Mosquitto |
| `php tests/bridge_test.php [hôte]` | Le pont avec un faux Arduino : boutons, alarme, phase, jauge | Mosquitto |

Chaque test affiche OK ou ECHEC pour chaque vérification et rend un code de sortie non nul en cas d'échec. `smoke.php` remet la partie à zéro au début et à la fin, déclenche brièvement l'alarme de la vraie carte si elle est branchée, et **efface de l'historique les parties qu'il a lui-même créées**. `bridge_test.php` utilise le préfixe `lernatest/` et ne parle pas à la vraie carte.

## MQTT

Le pont est le seul à publier l'état de la partie. Les messages sont retenus : une carte qui se reconnecte retrouve tout de suite l'état.

| Sujet | Sens | Contenu |
| :--- | :--- | :--- |
| `lerna/alarme` | site → carte | `ON` ou `OFF` |
| `lerna/etat` | site → carte | `attente`, `jeu`, `alarme`, `victoire`, `defaite` |
| `lerna/jauge` | site → carte | 0 à 100 |
| `lerna/msg` | site → carte | Texte de la 1re ligne de l'écran pendant le jeu (« Serveurs 2/4 »), vide = « Mission en cours » |
| `lerna/bouton/<nom>` | carte → site | `1` à chaque appui. Noms : `demarrage`, `arret`, `serveur1` à `serveur4` |
| `lerna/carte/etat` | carte → site | `en-ligne` ou `hors-ligne` (dernier souhait du broker) |
| `lerna/pont/etat` | pont → tous | `en-ligne` ou `hors-ligne` |

Le site parle MQTT avec son propre client (`src/mqtt_lite.php`, PHP pur) : `mosquitto_pub` n'est plus nécessaire.

- Installer Mosquitto : `winget install EclipseFoundation.Mosquitto`
- Le lancer avec le fichier du projet, en ajoutant `-Broker` à `start.ps1`. Sans ce fichier de configuration, Mosquitto refuse la carte, qui se connecte par Wi-Fi.
- À la première ouverture, Windows demande d'autoriser Mosquitto dans le pare-feu : accepter pour le réseau privé.
- Attention, le service Windows « mosquitto » créé par l'installation démarre tout seul et n'écoute que sur `localhost`. Il ne sert pas à ce projet. `start.ps1` fait parler le pont au broker de l'adresse `MQTT_SERVER_IP` de `carte/include/secrets.h`, la même que celle de la carte. Si l'IP du PC change (partage de connexion), la corriger dans `secrets.h` puis relancer `start.ps1`. On peut aussi forcer l'adresse avec la variable `LERNA_MQTT_HOST`.
- Sans broker, le site fonctionne mais la carte ne reçoit rien. Le pont réessaie toutes les 2 secondes.
- La carte est décrite dans `carte/README.md`.

## Où est quoi

| Fichier | Rôle |
| :--- | :--- |
| `public/index.html`, `public/css/bureau.css`, `public/js/app.js` | Le bureau : connexion, fenêtres, panneau Mission, terminal, messagerie, notifications, alarme, écrans de fin |
| `public/css/apps.css`, `public/js/apps/*.js` | Une application par fichier : `documents`, `photos`, `navigateur`, `calculatrice`, `baie`, `reseau`, `editeur` |
| `public/api.php` | Terminal et applications du bureau |
| `public/telephone.html`, `public/telephone.php` | Le téléphone de Kovac (PIN, code 2FA, SMS) |
| `public/gm.html`, `public/gm.php` | La page Game Master et l'historique |
| `bin/bridge.php` | Le pont carte ↔ partie |
| `bin/start.ps1` | Le lanceur : pont, site, et si besoin Mosquitto |
| `src/scenario.php` | Tout le texte du jeu : mails, documents, photos, réseau, base de données, script à trous, briefing, indices |
| `src/game.php` | Les règles : étapes, jauge, phases, 2FA, boutons, ordre des serveurs, historique |
| `src/puzzles.php` | Exécuteur SQL, outils réseau et CLI, vérification du code à trous |
| `src/db.php`, `src/mqtt_lite.php` | Base SQLite, client MQTT |
| `data/` | La base SQLite (créée au premier lancement, ignorée par git) |

Seul `public/` est servi au navigateur : la base, les règles et les outils ne sont pas téléchargeables.
Pour changer un mail, un document, un indice, une réponse ou le décor : tout est dans `src/scenario.php`. Les dessins des photos sont dans `public/js/apps/photos.js` (`SCENES`).

## Pas encore fait

- Le fond d'écran du bureau est un dégradé : on peut y mettre une vraie image.
- Les vidéos d'indices et de fin.
- Les mesures d'éco-conception (consommation de la carte, poids des pages).
