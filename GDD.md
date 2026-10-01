# GDD Programme L.E.R.N.A. - Equipe AIR

**Document de Conception de Jeu (Game Design Document)**

| Information | Détails |
| :--- | :--- |
| **Groupe / Équipe** | Tanim VEER, Jean-François DENA, Maxime COURDAVAULT, Ethan MEBALEY KAHEL, Ilan NGUELET |
| **Version** | 1 - Draft Initial |
| **Date de mise à jour** | 01/10/2026 |

---

## 1. VISION & CONCEPT (Le "Pitch")

*   **High Concept (Le Pitch) :** Les joueurs sont des stagiaires d'un service de cyberdéfense. Ils ont 30 minutes pour arrêter LERNA, un programme de surveillance détourné par un pirate, qui va publier les données personnelles de millions de citoyens
*   **Genre :** Escape game avec une partie physique et une partie numérique, sur le thème de la gestion d'une crise cyber
*   **Public Cible :** Les visiteurs des JPO, par groupes de 4 ou 5. Ce sont surtout des lycéens de tout niveau, et leurs parents. Il n'y a pas besoin de connaissances techniques : une fiche avec les commandes est fournie, et des indices sont disponibles à tout moment
*   **La Proposition de Valeur :** Les épreuves reprennent des compétences du BUT Informatique (sécurité, système, données, réseau, développement), et les objets de la salle servent à résoudre les épreuves sur l'ordinateur. Les joueurs ressentent de l'urgence grâce à une jauge de fuite qui monte à l'écran, et ils sont fiers à la fin d'avoir réussi des manipulations d'informaticien. Ils repartent aussi avec des réflexes concrets pour se protéger (double authentification, faux mails, mots de passe), grâce à des vidéos de prévention

---

## 2. LE GAMEPLAY (Les Règles du Jeu)

### 2.1 La Boucle de Gameplay (Core Loop)
Les joueurs entrent dans le bureau d'Adrien Kovac, après une vidéo qui explique la crise. Ils fouillent la salle, trouvent un objet ou un document, s'en servent pour agir dans le terminal et obtiennent une information technique (un mot de passe, une adresse IP, un numéro de port) qui ouvre l'étape suivante. Une courte vidéo donne la suite de l'histoire, et la partie se termine par une vidéo de prévention

### 2.2 Mécaniques de Jeu
*   **Actions directes :** appuyer sur le bouton de démarrage du bureau, taper des commandes au clavier dans le terminal (voir le profil d'une personne, retrouver un mot de passe, consulter les logs, cibler une session, interroger une base de données, déchiffrer un fichier, lister le réseau, supprimer des fichiers en double), fouiller le bureau et lire des documents imprimés, lire le code de double authentification sur le téléphone de travail, superposer des feuilles transparentes, ouvrir un cadenas à 4 chiffres, brancher une clé USB, corriger un petit programme, couper un interrupteur et appuyer sur le bouton du faux serveur
*   **Systèmes de jeu :**
    *   Un chrono de 30 minutes, en pause pendant les vidéos
    *   Une jauge de fuite qui monte avec le temps et à chaque erreur. Si elle atteint 100 %, la partie est perdue. Elle s'affiche en pourcentage écrit et pas seulement en couleur
    *   Des indices, demandés avec une commande dans le terminal ou envoyés par le game master. Chaque indice demandé fait monter la jauge de 5 %
    *   Un indice automatique et gratuit : si une équipe reste plus de 7 minutes sur la même épreuve, le terminal propose un indice sans pénalité
    *   Des retours lumineux et sonores sur le faux serveur (LED, buzzer à volume réglable, écran LCD)
    *   Des statistiques anonymes (temps par épreuve, indices utilisés) pour ajuster la difficulté
    *   Un mode secours : une commande secrète du game master simule une action physique si le matériel tombe en panne

### 2.3 Structure & Progression
*   **Le Flow :** Les 4 ou 5 joueurs jouent en équipe. Le game master arme la partie depuis sa page, puis les joueurs appuient sur le bouton de démarrage du bureau : le chrono et la vidéo 1 se lancent. Deux joueurs s'occupent du poste de travail pendant que les autres fouillent la salle (cadre photo, e-mail imprimé, feuilles transparentes, trois cartes cachées avec les modèles de requêtes), puis tout le monde se retrouve au terminal. Ensuite la progression est linéaire et suit l'histoire, en cinq épreuves. Les temps indiqués sont ceux d'un groupe qui avance sans blocage
    1.  Accès à la session de Kovac et double authentification (5 min) : la Cellule a verrouillé la session de Kovac par précaution. Le cadre photo posé sur le bureau donne le prénom de son chien ("Mon fidèle Max"). Au terminal, la commande `profil kovac` affiche son faux profil public : on y lit "Max fête ses 7 ans" dans un message de 2025, donc l'année 2018, parmi des pièges (prénom de sa fille, sa ville, un autre anniversaire). L'écran de connexion montre l'empreinte (hash) du mot de passe. Les joueurs lancent `craquer` avec les mots retenus, ce qui retrouve `Max2018`, puis se connectent avec `connexion kovac Max2018`. Un faux SMS arrive sur le téléphone de travail avec un code valable 30 secondes, que les joueurs tapent dans le terminal. Après 3 erreurs, le compte se bloque 30 secondes. Compétence travaillée : sécurité, authentification et recherche d'informations publiques
    2.  Origine de la fuite (5 min) : le terminal affiche une alerte avec l'heure de la fuite. Les joueurs interrogent la base des logs avec la commande `logs` et des filtres (serveur, heure, volume). Ils lisent aussi un e-mail "RH" imprimé, repèrent l'adresse suspecte de l'expéditeur et l'heure à laquelle Kovac l'a reçu (9h12). En cherchant dans les logs une session inconnue ouverte quelques minutes après, ils trouvent la session compromise et la ciblent avec la commande `cibler`. Le terminal donne alors l'adresse IP du poste infecté. La vidéo 2 reconstitue l'attaque et montre les signes d'un mail piégé. Compétence travaillée : manipulation de données et phishing
    3.  Le message d'HYDRE (5 min) : LERNA chiffre tout ce qu'il archive, y compris le message laissé par le pirate, et range les clés dans une table à côté, ce qui est une mauvaise pratique. Les joueurs ouvrent la base d'archives avec la commande `bd`. Avec des requêtes SQL simplifiées, dont les modèles sont écrits sur les trois cartes cachées, ils retrouvent le message chiffré d'HYDRE (`SELECT * FROM archives WHERE auteur = 'hydre'`), dont l'en-tête indique un chiffre de César et la clé K7. Une seconde requête sur la table `cles` donne la valeur de K7. Ils lisent `man dechiffre` pour connaître les paramètres, puis lancent `dechiffre`. Le message déchiffré est une demande de rançon qui se moque d'eux et donne le port de sortie de la fuite (4 chiffres), qui est le code du cadenas d'une boîte. Compétence travaillée : base de données, chiffrement et gestion des clés
    4.  Le code d'arrêt (5 min) : la boîte contient une clé USB rouge marquée "URGENCE" : Kovac avait préparé un arrêt d'urgence. Elle contient un script de dix lignes qui ne fonctionne pas. Une valeur est manquante (elle se lit en superposant deux feuilles transparentes trouvées dans la poubelle) et une condition est inversée. Les joueurs corrigent le script dans le terminal, guidés par les messages d'erreur et les tests. Quand les tests passent, ils pensent avoir gagné, mais HYDRE active sa porte dérobée et l'alarme se déclenche (LED rouge et buzzer). La vidéo 3 explique comment isoler une machine infectée. Compétence travaillée : développement et tests
    5.  L'isolement et le nettoyage (4 min) : les joueurs lancent la commande `reseau`, qui liste les serveurs avec leur adresse IP, et retrouvent celui de l'épreuve 2. Sur le faux serveur, quatre interrupteurs (SRV-01 à SRV-04) coupent la liaison réseau de chaque serveur. Les joueurs coupent celui du serveur infecté, puis appuient sur le gros bouton rouge, ce qui arrête l'alarme. La jauge continue de monter. Il reste à nettoyer : HYDRE a copié les archives en de nombreux exemplaires (les têtes de l'hydre). La commande `doublons` liste les fichiers dont l'empreinte est identique. Dans chaque groupe, les joueurs gardent le plus ancien (l'original) et suppriment les copies avec `purger` suivi des identifiants. Supprimer un original compte comme une erreur. Chaque mauvaise coupure fait monter la jauge de 10 %, et appuyer sur le bouton avant d'avoir coupé le bon interrupteur compte aussi comme une erreur. Compétence travaillée : réseau, gestion d'incident, empreintes de fichiers et protection des données

    Le chrono est en pause pendant les vidéos, qui ajoutent environ 3 minutes de visionnage. Les cinq épreuves prennent environ 24 minutes, ce qui laisse environ 6 minutes de marge sur les 30
*   **Difficulté :** Elle est progressive. Les épreuves 1 à 3 demandent surtout de chercher, de lire et de taper des commandes données par la fiche. L'épreuve 4 demande de comprendre un code court. L'épreuve 5 demande de rassembler plusieurs informations sous la pression de la jauge, et de repérer les originaux parmi les copies. Le nombre de lignes de logs et le seuil de l'indice automatique permettent de régler la difficulté sans toucher au matériel
*   **Conditions de Victoire & Défaite :** Les joueurs gagnent s'ils isolent le bon serveur, appuient sur le bouton et suppriment toutes les copies avant la fin du chrono et avant que la jauge atteigne 100 %. Ils perdent si la jauge est pleine (fuite des données) ou si le temps est écoulé, et une vidéo montre alors les conséquences de la fuite

---

## 3. SCÉNARIO & THÉMATIQUE (L'Univers)

### 3.1 L'Intrigue & l'Univers
L'histoire se passe de nos jours en France, dans un service de cyberdéfense fictif appelé la Cellule Zéro-Jour. Il utilise L.E.R.N.A. (Logiciel d'Exfiltration et de Régulation du Numérique et des Archives), un outil de surveillance qui garde en archive les données de millions de citoyens, au cas où. Adrien Kovac, un ingénieur du service, est parti en congés. Avant de partir, il a ouvert un faux mail des ressources humaines, et un pirate surnommé HYDRE a pris le contrôle de sa session. HYDRE exige une rançon et menace de rendre publiques toutes les données au bout de 30 minutes. Son nom vient de l'hydre de Lerne, dont chaque tête coupée repousse : il a copié les archives en de nombreux exemplaires

Le Commandant Riad explique la situation aux joueurs dans une vidéo : ils sont les seuls stagiaires disponibles, et ils doivent entrer dans le bureau sombre et fermé de Kovac et arrêter LERNA avant la publication. Au début, tout le monde soupçonne Kovac. En avançant, les joueurs découvrent qu'il est victime d'un mail piégé, et que la clé "URGENCE" prouve qu'il avait prévu un arrêt d'urgence

L'histoire est racontée par cinq vidéos courtes du Commandant : le briefing au départ, la reconstitution de l'attaque après l'épreuve 2, la méthode pour isoler une machine infectée après l'épreuve 4, puis la fin gagnée et la fin perdue. À la fin gagnée, les logs gardés par les joueurs permettent de remonter jusqu'à HYDRE, et le Commandant conclut que la vraie faille était de tout garder. À la fin perdue, HYDRE publie les données et la vidéo montre les conséquences

### 3.2 Intégration des Enjeux (Éthique / Durable)
Le sujet central est l'hygiène numérique, la protection de la vie privée et l'éthique de la donnée, avec une dimension de sobriété numérique. Il apparaît à quatre endroits
*   **Les conséquences d'une fuite :** la vidéo de fin rappelle ce qui arrive quand des informations privées (adresses, données bancaires, historiques) sont publiées : usurpation d'identité, fraude, chantage, doxxing. Les chiffres cités sur le coût d'une fuite viennent de sources officielles (CNIL, ANSSI), vérifiées avant le tournage
*   **Le facteur humain :** la plupart des failles viennent d'une erreur humaine (mot de passe devinable, faux mail, clé rangée à côté du fichier chiffré). Le jeu fait vivre ces erreurs aux joueurs et leur montre les bons réflexes (double authentification, repérer un mail frauduleux, isoler une machine infectée)
*   **L'accumulation de données :** LERNA garde tout, au cas où. Plus on stocke de données, plus la fuite est grave et plus on consomme d'énergie pour les conserver. La donnée la plus sûre et la plus sobre est celle qu'on ne stocke pas, et celui qui collecte des données a le devoir de les protéger. Cette idée est jouée dans la dernière épreuve : les joueurs retrouvent les fichiers en double grâce à leur empreinte et suppriment les copies inutiles sans toucher aux originaux
*   **Nos choix de conception :**
    *   Matériel : réemploi en priorité (IUT, anciens kits Arduino, objets personnels, ancien téléphone, ancien PC). Le montage est démontable (pas de soudure définitive) pour réutiliser les composants après la JPO
    *   Logiciel : tout est hébergé en local, sans cloud ni streaming. Les vidéos sont courtes et compressées en 720p. Les mises à jour de la jauge passent par WebSocket et MQTT, qui envoient des messages seulement quand quelque chose change, au lieu d'interroger le serveur en boucle. Les écrans se mettent en veille entre deux parties
    *   Données : aucune donnée personnelle n'est collectée sur les visiteurs. Les statistiques sont anonymes et supprimées après la JPO. Les logs, le profil et les archives du jeu sont entièrement fictifs
    *   Mesure : nous mesurons nos pages avec EcoIndex (objectif : note B ou mieux) à chaque jalon, nous mesurons la consommation électrique d'une partie complète avec un wattmètre, et nous relisons une grille de bonnes pratiques du RGESN (référentiel d'écoconception des services numériques) à la fin de chaque sprint

---

## 4. SPÉCIFICATIONS TECHNIQUES (L'Ingénierie)

### 4.1 Stack Technologique
*   **Software :** Frontend Angular et TailwindCSS, backend NestJS (TypeScript), base de données PostgreSQL, broker MQTT Mosquitto, programme de l'ESP32 en C++, tests avec Jest, intégration continue avec GitHub Actions, déploiement avec Docker Compose
*   **Hardware / IoT :** un ESP32 connecté en Wi-Fi au réseau local, qui communique avec le backend en MQTT
    *   Capteurs : quatre interrupteurs à levier et deux gros boutons-poussoirs, un de démarrage et un rouge pour le faux serveur (GPIO)
    *   Actionneurs : LED rouge et verte, buzzer (GPIO), écran LCD 16x2 (I2C)
    *   Sans électronique : poste de travail (PC récupéré), ancien téléphone (affiche une page web), boîte fermée par un cadenas à 4 chiffres, clé USB, feuilles transparentes, documents imprimés
    *   Budget estimé : environ 57 € pour un plafond de 200 € (2 ESP32 dont un de rechange, interrupteurs, deux boutons, LED et buzzer, écran LCD, breadboard, boîte et cadenas, feuilles transparentes, impressions), moins avec la récupération

### 4.2 Architecture du Système
Tout fonctionne sur un réseau local, sans Internet, pour que le jeu reste stable le jour des JPO. Le backend NestJS contient toute la logique du jeu et suit l'avancement avec une machine à états (une étape par épreuve). Il enregistre l'état de la partie dans PostgreSQL (tables principales : sessions, événements de jeu horodatés, logs fictifs, archives et clés fictives, indices utilisés), donc une panne du backend ne fait pas perdre la partie. Le terminal du poste de travail envoie les commandes des joueurs au backend par une API REST, et reçoit la jauge, le chrono et les vidéos en temps réel par WebSocket. Le téléphone utilise le même WebSocket pour afficher le faux SMS. Le terminal est simulé (système de fichiers virtuel, liste fixe de commandes, deux ou trois modèles de requêtes SQL acceptés), et le script de l'épreuve 4 est vérifié par comparaison avec une solution attendue, sans jamais être exécuté

L'ESP32 et le backend communiquent en MQTT, en passant par le broker Mosquitto. L'ESP32 publie ce qu'il lit (le bouton de démarrage, la position des interrupteurs, le bouton rouge) et reçoit des ordres (allumer les LED, faire sonner le buzzer, afficher un texte sur l'écran LCD). Par exemple, au démarrage, le bouton envoie un message au backend, qui lance le chrono. Quand les tests de l'épreuve 4 passent, le backend publie un message alarme/ON et l'ESP32 allume les LED rouges et le buzzer. À l'épreuve 5, quand les joueurs ont coupé le bon interrupteur puis appuyé sur le bouton rouge, le backend publie alarme/OFF. La jauge continue jusqu'à la suppression de toutes les copies

### 4.3 Interface & Expérience Utilisateur (UI/UX)
*   **Interface :**
    *   Le terminal des joueurs a un design de console de hacker (fond noir, texte vert, police rétro). Il accepte les majuscules et les synonymes, et affiche des messages d'erreur clairs qui disent quoi essayer. La jauge et le chrono restent toujours visibles, et les vidéos s'affichent en plein écran
    *   Le texte est lisible à 2 mètres, avec un contraste élevé. La jauge s'affiche aussi en pourcentage écrit pour ne pas dépendre de la couleur
    *   Le téléphone de travail affiche une page web en plein écran, dont l'écran ne se met pas en veille. Quand le mot de passe est correct, elle affiche un faux SMS avec le code de double authentification
    *   Le game master a une page sur son téléphone, protégée par un code, avec des boutons pour armer une nouvelle partie, voir l'état de la partie, envoyer un indice, ajouter ou retirer du temps, valider une épreuve et activer le mode secours
    *   Le faux serveur a un écran LCD qui affiche le pourcentage de fuite
*   **Interaction Physique :** les joueurs appuient sur le bouton de démarrage, tapent les commandes sur un vrai clavier, prennent le téléphone en main, superposent les feuilles transparentes devant la lumière, ouvrent le cadenas de la boîte, branchent la clé USB, coupent un interrupteur et frappent le bouton rouge du faux serveur

---

## 5. PLAN DE PRODUCTION (La Roadmap)

### 5.1 Liste des Fonctionnalités (Feature List)
*   **Must-Have (Critique) :**
    *   Le bouton de démarrage de bout en bout : ESP32, MQTT, backend, base de données et terminal
    *   L'épreuve 1 jusqu'à la connexion (profil, craquer, connexion)
    *   Le terminal avec un parseur de commandes tolérant
    *   La jauge de fuite et le chrono
    *   La page du game master avec le bouton "nouvelle partie" et le mode secours
    *   Les tests Jest et l'intégration continue, qui valident chaque sprint, avec un simulateur de messages MQTT pour tester sans le matériel
    *   La documentation de base (README, schéma d'architecture, API décrite avec OpenAPI)
*   **Should-Have (Important) :**
    *   Les épreuves 2 à 5 avec le matériel (cadenas, feuilles transparentes, interrupteurs, boutons, LED, buzzer, écran LCD) et leurs commandes (`logs`, `cibler`, `bd`, `dechiffre`, `reseau`, `doublons`, `purger`)
    *   Le téléphone avec le faux SMS de double authentification
    *   Les cinq vidéos du Commandant
    *   Les indices (demandés et automatiques) et le reste de la page du game master
    *   Les statistiques simples des parties
    *   Les codes générés à chaque partie (port, adresse IP, session compromise)
    *   La mesure de la consommation électrique d'une partie et le suivi EcoIndex et RGESN
*   **Nice-to-Have (Bonus) :**
    *   Des variantes du scénario (photo, profil, mot de passe) choisies par le game master
    *   Un mode de difficulté (débutant ou expert)
    *   Une version en anglais
    *   Un écran mural qui reprend la jauge et le chrono
    *   Un deuxième poste (même page du terminal sur un autre appareil) si les tests montrent que des joueurs restent inactifs

### 5.2 Jalons de Validation (Milestones)

| Jalon | Date | Livrable |
| :--- | :--- | :--- |
| Jalon 1 | 01/10/2026 | GDD, puis clarification du besoin avec les référents |
| Chaque sprint | Toutes les 2 semaines | Démonstration aux référents, tests Jest et intégration continue qui valident le sprint |
| Jalon 2 | Mi-novembre 2026 | MVP : le bouton de démarrage de bout en bout, l'épreuve 1 jusqu'à la connexion, le terminal, la jauge, le mode secours, les tests et l'intégration continue |
| Jalon 3 | Mi-janvier 2027 | Version bêta intégrée avec les cinq épreuves et le matériel, au moins trois sessions de test avec des joueurs extérieurs à l'équipe, rapport d'équipe |
| Jalon 4 | Mi-février 2027 | Produit final, remise en place du jeu en moins de 2 minutes, exposition aux JPO |

---

## 6. ANALYSE DES RISQUES

| Risque identifié | Impact (H/M/L) | Stratégie d'atténuation |
| :--- | :--- | :--- |
| Panne ou casse du matériel (IoT) | H | Mode secours : le game master tape une commande secrète qui simule l'action physique. ESP32 de rechange déjà programmé |
| Joueurs bloqués sur la syntaxe du terminal | H | Parseur permissif (synonymes, majuscules ignorées), fiche de commandes bien visible, indice automatique après 7 minutes |
| Retard de développement ou d'intégration | H | Faire d'abord tourner le MVP puis ajouter les épreuves une par une. Le matériel est intégré après le jeu sur ordinateur |
| Panne de réseau ou de Wi-Fi | H | Tout héberger en local, avec notre propre routeur |
| Stack plus ambitieuse que prévu, niveaux inégaux dans l'équipe | M | Répartition par spécialité, binômes sur les points sensibles, fonctionnalités Should-Have ajoutées une par une |
| Partie plus longue ou plus courte que 30 minutes | M | Minutage de chaque épreuve vérifié lors des sessions de test, réglage de la difficulté avec les statistiques et le game master |
| Un groupe donne la solution au suivant | M | Codes générés à chaque partie, variantes du scénario changées par le game master |
| Vol, perte ou usure des objets de fouille, téléphone qui se met en veille | L | Documents imprimés en plusieurs exemplaires, téléphone branché sur secteur avec la mise en veille désactivée |
