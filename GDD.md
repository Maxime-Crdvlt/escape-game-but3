# GDD Programme L.E.R.N.A. - Equipe AIR

**Document de Conception de Jeu (Game Design Document)**

| Information | Détails |
| :--- | :--- |
| **Groupe / Équipe** | Jean-Francois DENA, Tanim VEER, Maxime COURDAVAULT, Ethan MEBALEY KAHEL, Ilan NGUELET |
| **Version** | 1.4 |
| **Date de mise à jour** | 01/10/2026 |

---

## 1. VISION & CONCEPT (Le "Pitch")

*   **High Concept (Le Pitch) :** Les joueurs sont des stagiaires d’un service de cyberdéfense de la DGSI. Ils ont 30 minutes pour arrêter LERNA, un programme de surveillance détourné qui va publier les données personnelles de millions de citoyens.
*   **Genre :** Escape Game simulation d'une crise de cybersécurité.
*   **Public Cible :** Les visiteurs des JPO, par groupes de 4 ou 5. Ce sont surtout des lycéens de tout niveau, et leurs parents. Il n'y a pas besoin de connaissances techniques : une fiche avec les commandes est fournie, et des indices sont disponibles à tout moment.
*   **La Proposition de Valeur :** Les épreuves reprennent des compétences du BUT Informatique (sécurité, système, données, réseau, développement). Les joueurs ressentent de l'urgence grâce à une jauge de fuite qui monte à l'écran, et ils sont fiers à la fin d'avoir réussi des manipulations d'informaticien. Ils repartent aussi avec des réflexes concrets pour se protéger (double authentification, faux mails, mots de passe), grâce à des vidéos de prévention.

---

## 2. LE GAMEPLAY (Les Règles du Jeu)

### 2.1 La Boucle de Gameplay (Core Loop)
Les joueurs se trouveront dans un salle. Ils seront introduits dans le jeu par une vidéo explicative. La partie sera divisée en 5 épreuves, à chaque épreuve des indications seront données sur le terminal pour aider les joueurs à avancer et les orienter. Enfin, les joueurs termineront par une vidéo de fin.
Dans chaque épreuve, les joueurs pourront explorer la salle, trouver des indices et faire des actions sur le terminal pour avancer.

### 2.2 Mécaniques de Jeu
*   **Actions directes :** appuyer sur le bouton de démarrage du bureau, taper des commandes au clavier dans le terminal (se connecter, consulter les logs, cibler une session, interroger une base de données, lister le réseau), fouiller le bureau et lire des documents imprimés, lire le code de double authentification sur le téléphone de travail, décoder un code avec le manuel de cryptographie, ouvrir un cadenas à 4 chiffres, superposer des feuilles transparentes, brancher une clé USB, corriger un petit programme, couper un interrupteur et appuyer sur le bouton du faux serveur.
*   **Systèmes de jeu :**
    *   Un chrono de 30 minutes, en pause pendant les vidéos représenté par une jauge de fuite qui monte avec le temps et à chaque erreur. Si elle atteint 100 %, la partie est perdue. Elle s'affiche en pourcentage écrit et pas seulement en couleur.
    *   Des indices, demandés avec une commande dans le terminal ou envoyés par le game master. Chaque indice demandé fait monter la jauge de 5 %.
    *   Des retours lumineux et sonores sur le faux serveur (LED, buzzer à volume réglable, écran LCD).
    *   Un mode secours : une commande secrète du game master simule une action physique si le matériel tombe en panne.
    *   Plusieurs objets dans la pièce (livre cryptographie, post-it, etc…
    *   Ancien téléphone : téléphone de travail d’Adrien Kovac.
    *   Poste de travail : dans lequel les joueurs pourront interagir.

### 2.3 Structure & Progression
*   **Le Flow :** Les 4 ou 5 joueurs jouent en équipe. Le game master arme la partie depuis sa page, puis les joueurs appuient sur le bouton de démarrage du bureau : le chrono et la vidéo 1 se lancent. La progression est linéaire et suit l'histoire, en cinq épreuves. Les temps indiqués sont ceux d'un groupe qui avance sans blocage.
    1.  Accès à la session de Kovac et double authentification (3 min) : la Cellule a verrouillé la session du poste de travail par précaution. Au départ, le terminal ne connaît que les commandes `aide` et `login`, et les autres se débloquent au fil des épreuves. Le cadre photo posé sur le bureau porte la légende "Mon fidèle Max, né en 2018", ce qui donne le mot de passe de la session. Les joueurs se connectent avec `login Max2018`. Un faux SMS arrive ensuite sur le téléphone de travail avec un code valable 30 secondes, que les joueurs tapent dans le terminal. Après 3 erreurs, le compte se bloque 30 secondes.
    2.  Origine de la fuite (5 min) : le terminal affiche une alerte : "Fuite de données détectée. Identifiez le point d'entrée". Les joueurs filtrent les logs avec la commande `show logs` (serveur, heure, volume). Ils lisent aussi un e-mail "RH" imprimé, repèrent l'adresse suspecte de l'expéditeur et l'heure à laquelle Kovac l'a reçu (9h12). En cherchant dans les logs une session inconnue ouverte quelques minutes après, ils trouvent la session compromise et la ciblent avec la commande `cibler`. Le terminal donne alors l'adresse IP du poste infecté et l'isole.
    3.  La base de données et le coffre (5 min) : la commande `cibler` débloque la commande `bd`, qui ouvre une fausse interface de base de données. Avec une requête simple dont le modèle est écrit sur la fiche de commandes (`SELECT * FROM notes WHERE auteur = 'kovac'`), les joueurs lisent l'ID de Kovac dans une base interne du DGSI. Kovac codait ses code avec un chiffrement très simple, et un manuel de cryptographie basique posé sur l'étagère donne la table : chaque lettre de A à J correspond à un chiffre de 0 à 9. Les joueurs décodent à la main (E=4, B=1, B=1, H=7, donc 4117) et tapent ce code sur le cadenas de la boîte.
    4.  Le code d'arrêt (5 min) : la boîte contient une clé USB rouge marquée "URGENCE" : Kovac avait préparé un arrêt d'urgence. Elle contient un script de dix lignes qui ne fonctionne pas. Une valeur est manquante (elle se lit en superposant deux feuilles transparentes trouvées dans la poubelle) et une condition est inversée. Les joueurs corrigent le script dans le terminal, guidés par les messages d'erreur et les tests. Quand les tests passent, ils pensent avoir gagné, mais une porte dérobée du hacker s'active et l'alarme se déclenche (LED rouge et buzzer).
    5.  Le kill switch (4 min) : l'alarme débloque la commande `reseau`, qui liste les serveurs avec leur adresse IP. Les joueurs retrouvent celui de l'épreuve 2. Sur le faux serveur, quatre interrupteurs (SRV-01 à SRV-04) coupent la liaison réseau de chaque serveur. Les joueurs coupent celui du serveur infecté, puis appuient sur le gros bouton rouge : l'alarme s'arrête, la jauge se fige et l'écran de victoire s'affiche. Chaque mauvaise coupure fait monter la jauge de 10 %, et appuyer sur le bouton avant d'avoir coupé le bon interrupteur compte aussi comme une erreur.

    Le chrono est en pause pendant les vidéos. Les cinq épreuves prennent environ 22 minutes, ce qui laisse environ 8 minutes de marge sur les 30.
*   **Difficulté :** Elle est progressive. Les épreuves 1 à 3 demandent surtout de chercher, de lire et de taper des commandes données par la fiche. L'épreuve 4 demande de comprendre un code court. L'épreuve 5 demande de rassembler plusieurs informations (l'adresse IP et la liste du réseau) sous la pression de la jauge. Le nombre de lignes de logs et le seuil de l'indice automatique permettent de régler la difficulté sans toucher au matériel.
*   **Conditions de Victoire & Défaite :** Les joueurs gagnent s'ils coupent le bon interrupteur et appuient sur le bouton rouge avant la fin du chrono et avant que la jauge atteigne 100 %. Ils perdent si la jauge est pleine (fuite des données) ou si le temps est écoulé, et une vidéo montre alors les conséquences de la fuite.

---

## 3. SCÉNARIO & THÉMATIQUE (L'Univers)

### 3.1 L'Intrigue & l'Univers
L'histoire se passe de nos jours en France, dans un service de cyberdéfense de la DGSI appelé la Cellule Zéro-Jour. Adrien Kovac, un ingénieur du service, est parti en vacances, mais son programme L.E.R.N.A. (Logiciel d'Exfiltration et de Régulation du Numérique et des Archives), qui sert normalement à la surveillance de masse, a été compromis. Avant de partir, Kovac a ouvert un faux mail des ressources humaines, et un hacker a pris le contrôle de sa session. Le hacker menace de rendre publiques toutes les données si on ne lui donne pas ce qu'il veut (ransomware).
Le Commandant Riad explique la situation aux joueurs dans une vidéo : ils doivent arrêter L.E.R.N.A.. Les joueurs entrent dans le bureau sombre et fermé d'Adrien Kovac pour résoudre la crise, et découvrent en chemin que Kovac est victime d'un mail piégé.

### 3.2 Intégration des Enjeux (Éthique / Durable)
L’enjeu central du jeu est l’hygiène numérique, la protection de la vie privée et l’éthique de la donnée. Cet enjeu est intégré à l’expérience à travers quatre axes :
- L’impact de la divulgation des données privées : L’Escape Game met en lumière les conséquences dramatiques d’une fuite massive (data leak). Le but est de faire comprendre aux joueurs (particulièrement aux lycéens) ce qui se passe concrètement lorsque des informations intimes (adresses, données bancaires, historiques) tombent dans le domaine public : usurpation d’identité, fraudes, chantage, ou doxxing. Cela rend la menace du jeu très personnelle pour eux.
- La cybersécurité préventive (Le facteur humain) : Le jeu détourne le cliché du “super hacker” pour montrer que la majorité des failles informatiques sont en réalité humaines (mots de passe sur post-it, ingénierie sociale/phishing, clés USB trouvées, documents non broyés).
- L’éthique de la surveillance de masse : En filigrane, le jeu questionne la légitimité d’un programme de surveillance comme LERNA. Il démontre que la centralisation extrême de données, même pensée pour la sécurité nationale, devient une arme de destruction massive lorsqu’elle est compromise. La morale est que collecter de la donnée impose un devoir de sécurisation absolu.
- Sensibilisation concrète : Le jeu se termine par une vidéo de prévention qui ne fait pas que faire peur, mais qui donne aux joueurs des réflexes applicables au quotidien pour se protéger (activer l’A2F, repérer un e-mail frauduleux, isoler du réseau une machine infectée).

---

## 4. SPÉCIFICATIONS TECHNIQUES (L'Ingénierie)

### 4.1 Stack Technologique
- **Software :** Langages, Frameworks (Frontend/Backend), Base de données.
HTML5 / CSS3 / JavaScript / PHP / JSON / C / C++
- **Hardware / IoT :** Liste des composants physiques (Microcontrôleurs, capteurs, actionneurs)
Carte ESP32 / LEDs (rouge et verte) / Buzzer piézoélectrique sonore / écran LCD / Bouton-poussoir / Clé USB / Poste de travail / Téléphone
- **Connexion :**
Connexion entre le poste de travail, le téléphone et le faux serveur via le protocole MQTT

### 4.2 Architecture du Système
L’architecture repose sur un réseau local (LAN) pour garantir la stabilité le jour des JPO (aucune dépendance à Internet). Le système est conçu pour être simple et robuste :
- Le Frontend (Poste de travail - HTML/CSS/JS) :
C’est l’interface visuelle du terminal. Le JavaScript capte ce que le joueur tape au clavier. Pour vérifier si une commande est bonne ou pour actualiser la jauge, le JavaScript utilise la fonction native fetch() pour interroger le PHP de manière transparente (sans recharger la page).
- Le Backend (Serveur Web - PHP / JSON) :
C’est le cerveau du jeu. Des scripts PHP très simples reçoivent les commandes du joueur. L’état de la partie (temps restant, erreurs, avancement) est tout simplement stocké et lu dans des petits fichiers JSON. C’est léger, efficace et très facile à débugger.
- La Communication IoT (Broker MQTT) :
    - Un serveur MQTT local (ex: Mosquitto) permet au PC et à la carte ESP32 de se parler avec des messages très courts (des “topics”).
    - Du Web vers l’IoT : Quand les joueurs activent le code à l’Épreuve 4, le système publie un message alarme/ON. La carte ESP32 (en C/C++) le lit et allume instantanément les LEDs rouges et le buzzer.
    - De l’IoT vers le Web : À l’Épreuve 5, quand le joueur appuie sur le gros bouton (Kill Switch), l’ESP32 publie le message alarme/OFF. Le terminal web capte ce message, stoppe la jauge, et affiche l’écran de victoire.

### 4.3 Interface & Expérience Utilisateur (UI/UX)
- **Interface :**
    - Le Terminal Joueur : Design épuré façon “console de hacker” (fond noir, texte vert, police d’écriture d’ordinateur rétro). L’écran n’affiche que l’historique des commandes et les retours du système.
    - La Jauge de Fuite : Une grosse barre de progression très visible sur le serveur physique (écran LCD). Elle attire l’œil en changeant de couleur selon l’urgence (Vert au début, puis Orange, puis Rouge clignotant à la fin).
    - Le Dashboard Game Master : Une simple page web cachée (pour l’équipe AIR) avec des boutons basiques pour ajouter/retirer du temps à la jauge ou envoyer un indice texte qui apparaîtra sur le terminal des joueurs.
    - Le Téléphone (Système A2F) : Le téléphone est connecté au Wi-Fi local. Il affiche une simple page web en plein écran. Quand le joueur tape le mot de passe sur le PC, la page web du téléphone s’actualise toute seule et affiche un faux SMS d’alerte avec le code A2F.
- **Interaction Physique :**
    - Le Clavier d’ordinateur : Outil d’investigation principal. Devoir taper manuellement les commandes (sans souris) donne le sentiment gratifiant d’être un “hacker”.
    - L’Écran tactile du téléphone (A2F) : Le joueur doit prendre un vrai smartphone, taper le code PIN sur l’écran tactile et lire le faux SMS pour valider l’étape de double authentification.
    - Le Bouton du faux serveur : Lors de l’épreuve finale, la résolution ne se fait pas au clavier, mais par une action physique brute : frapper un bouton-poussoir d’arrêt d’urgence. Cela coupe l’alarme et procure une satisfaction immédiate.
    - Les objets physiques de bureau : Fouille classique pour trouver des indices (post-it sous le clavier, manuel) et manipulation spatiale (superposer des feuilles calques transparentes à la lumière pour lire le code de la clé USB).

---

## 5. PLAN DE PRODUCTION (La Roadmap)

### 5.1 Liste des Fonctionnalités (Feature List)
- **Must-Have (Critique) :** Fonctionnalités indispensables pour le MVP.
    - Epreuve 1 : Possibilité de se connecter (via le post-it) au terminal sans A2F
    - Epreuve 2 : Décryptage des logs sans rechercher la raison
    - Epreuve 3 : Epreuve 3 complète
    - Epreuve 4 : Clé USB avec le code pour arrêter LERNA (code incomplet classique à compléter avec des bases en développement)
    - Interface web du terminal avec un parseur de commandes basique (login, show logs, cibler).
    - Logique de la Jauge de fuite (timer + augmentation en cas d’erreur/aide).
- **Should-Have (Important) :** Fonctionnalités qui enrichissent l’expérience.
    - Vidéo introductive
    - Epreuve 1 : Ajout de l’A2F
    - Epreuve 2 : Ajout de la raison de la connexion (trouver pourquoi Adrien s’est connecté avant d’exporter la BDD)
    - Epreuve 4 : Ajout des feuilles transparentes pour trouver le bout de code manquant
    - Epreuve 5 : Enrichit le scénario
    - Interface Maître du Jeu (Game Master) sur un autre écran pour déclencher des indices à distance et ajuster la jauge si les joueurs bloquent.
    - Serveur IoT avec retours lumineux (LEDs) et sonores (Buzzer).
- **Nice-to-Have (Bonus) :** Fonctionnalités pour l’exposition JPO si le temps le permet.
    - Version anglaise
    - Système renouvelable (codes qui changent, commandes qui changent, etc…)
    - Vidéo de sensibilisation à la fin

### 5.2 Jalons de Validation (Milestones)
| Jalon | Date | Livrable |
| -- | -- | -- |
| Jalon 1 |	01 Octobre 2026 | GDD |
| Jalon 2 | mi-novembre 2026 | MVP |
| Jalon 3 | mi-janvier 2027 | BETA |
| Jalon 4 | mi-février 2027 | Produit final |

---

## 6. ANALYSE DES RISQUES
| Risque identifié | Impact (H/M/L) | Stratégie d’atténuation |
| -- | -- | -- |
| Casse ou défaillance du matériel physique (IoT) | H | Mode “Software-only” de secours : Prévoir une commande secrète (ex: override port404) que le Game Master peut taper pour simuler l’action physique et débloquer les joueurs sans le matériel. |
| Blocage sur la syntaxe du terminal | H | Tolérance logicielle et UX : Coder un parseur de commandes permissif (accepter les synonymes et ignorer la casse/les majuscules). Fournir un “Manuel des commandes” très visuel sur le bureau. |
| Retard de développement ou d’intégration | H | Application stricte du MVP (Must-Have) : Développer et valider l’Escape Game 100% sur ordinateur d’abord (Jalon 2). L’IoT et le matériel physique ne seront intégrés qu’ensuite en bonus (Jalon 3). |
| Panne de réseau ou de Wi-Fi | H | Faire tourner tout le jeu en local (localhost) sans dépendre d’Internet. |
| Gestion du temps de jeu défaillante | M | Contrôle via le Game Master (GM) : Le dashboard du GM (Should-Have) permettra d’augmenter ou de baisser manuellement la jauge de fuite, et d’envoyer des indices sur le terminal pour lisser la durée à ~20 min. |
| Vol, perte ou usure des objets “fouille” | L | Kit de rechange : Prévoir les documents imprimés en triple exemplaire (calques, post-it, mails). Laisser le téléphone branché sur secteur en permanence sur le bureau. |
