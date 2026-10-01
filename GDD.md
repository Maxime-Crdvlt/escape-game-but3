# 🧩 GDD Programme L.E.R.N.A. - Equipe AIR
**Document de Conception de Jeu (Game Design Document)**

| Information | Détails |
| :--- | :--- |
| **Groupe / Équipe** | Jean-Francois DENA, Tanim VEER, Maxime COURDAVAULT, Ethan MEBALEY KAHEL, Ilan NGUELET |
| **Version** | Draft Initial |
| **Date de mise à jour** | 01/10/2026 |

---

## 1. VISION & CONCEPT (Le "Pitch")

*   **High Concept (Le Pitch) :** Les joueurs sont des stagiaires d'un service de cyberdéfense de la DGSI. Ils ont 30 minutes pour arrêter LERNA, un programme détourné qui va publier les données personnelles de millions de citoyens.
*   **Genre :** Escape Game simulation d'une crise.
*   **Public Cible :** Les visiteurs des JPO, par groupes de 2 ou 3. Ce sont surtout des lycéens, tout niveau, et leurs parents. Il n'y a pas besoin de connaissances techniques particulière, on fournit une fiche avec les commandes et des indices.
*   **La Proposition de Valeur :** On veut que les joueurs ressentent de l'urgence, grâce à une jauge de fuite qui monte à l'écran, et qu'ils soient fiers à la fin d'avoir réussi des manipulations d'informaticien mais aussi qu’ils aient été sensibilisés sur comment se protéger des fuites de données via des vidéos préventives qui expliqueraient les conséquences de cette fuite.

---

## 2. LE GAMEPLAY (Les Règles du Jeu)

### 2.1 La Boucle de Gameplay (Core Loop)
Les joueurs se trouveront dans un salle. Ils seront introduits dans le jeu par une vidéo explicative. La partie sera divisée en 5 épreuves, à chaque épreuve des indications seront données sur le terminal pour aider les joueurs. Enfin, les joueurs termineront par une vidéo de fin.
Dans chaque épreuve, les joueurs pourront explorer la salle, trouver des indices et faire des actions sur le terminal pour avancer.

### 2.2 Mécaniques de Jeu
Quelles sont les actions que le joueur peut effectuer ? 
- **Actions directes :**
  - Allumer le poste de travail
  - Taper dans le terminal via un clavier
  - Fouiller la salle, lire et ouvrir des objets, etc...
  - Taper et fouiller un téléphone de travail
  - Puzzle visuel : Superposer des objets trouvés (feuilles transparentes)
  - Appuyer sur un bouton (faux serveur physique)
- **Systèmes de jeu :** 
  - Un chrono de 30 minutes => représenté par une jauge de fuite qui monte avec le temps et les erreurs
  - Poste de travail : dans lequel les joueurs pourront intéragir
  - Faux serveur : des retours lumineux et sonores (LED et buzzer et écran LCD)
  - Ancien téléphone : téléphone de travail d'Adrien Kovac
  - Des indices affichés sur le terminal, envoyés par le game master. Chaque indice fait monter la jauge de 5 % (demandés via un commande)
  - Plusieurs objets dans la pièce (livre cryptographie, post-it, etc...

### 2.3 Structure & Progression
- **Le Flow :** Le joueur progresse de manière linéaire, il suit une narration.
- **Difficulté :** Progressive.
- **Conditions de Victoire & Défaite :**
  - Le joueur perd une fois la jauge pleine (fuite des données)
  - Erreur fatale : dernière épreuve, si le joueur appuie sur le mauvais bouton, la partie est perdue
  - Sinon le joueur gagne

### 2.4 Epreuves
- **Epreuve 1 :** L'Accès au Terminal & L'A2F (La faille humaine)
  - L'objectif : Allumer l'écran de contrôle, trouver le mot de passe, puis passer la double authentification.
  - Contexte / Matériel : Le terminal est verrouillé sur un écran de connexion. Dans la salle (le bureau de Kovac), il y a des objets classiques, un cadre photo d'un chien ("Mon fidèle Max, né en 2018") et un smartphone professionnel éteint.
- **Epreuve 2 :** Identifier l'origine de la fuite (L'Enquête & Le Phishing)
  - L'objectif : Comprendre comment le virus est entré et cibler la session compromise.
  - Contexte / Matériel : Le PC affiche une alerte : "Fuite de es détectée à 14:03. Identifiez le point d'entrée." Des documents sont éparpillés sur le bureau, dont une impression d'un e-mail RH.
- **Epreuve 3 :** La Base de données (Le Déchiffrement & Le Coffre)
  - L'objectif : Trouver le code du coffre-fort physique pour y récupérer une clé USB d'urgence.
  - Contexte / Matériel : La commande précédente déverrouille une fausse interface de Base de Données. Un coffre-fort à code (4 chiffres) est présent dans la salle. Un "Manuel de Cryptographie basique" (ou une simple note manuscrite) traîne sur une étagère.
- **Epreuve 4 :** Stopper le programme (Code & Puzzle visuel)
  - L'objectif : Injecter un "kill-code" (code d'arrêt) pour stopper la fuite.
  - Contexte / Matériel : Le coffre contient une Clé USB rouge marquée "URGENCE". Dans la poubelle de la salle se trouvent des morceaux de papier calque transparent.
- **Epreuve 5 :** Le Twist Final (Le "Kill Switch" physique)
  - L'objectif : Créer une apothéose stressante et physique pour terminer la partie.
  - Contexte / Matériel : Dès que le code de l'épreuve 4 est validé, les joueurs pensent avoir gagné, mais l'alarme se déclenche ! Le faux serveur (ou une boîte électrique factice dans la salle) est relié à plusieurs gros câbles numérotés. Une affiche de prévention sécurité est au mur depuis le début du jeu.

---

## 3. SCÉNARIO & THÉMATIQUE (L'Univers)

### 3.1 L'Intrigue & l'Univers
L’histoire se passe dans nos temps moderne en France, dans un service de cyberdéfense de la DGSI appelé la Cellule Zéro-Jour. Adrien Kovac, un ingénieur du service, est parti en vacances mais son programme L.E.R.N.A. (Logiciel d'Exfiltration et de Régulation du Numérique et des Archives) : qui sert normalement à la DGSI pour de la surveillance de masse a été compromis... Un hacker menace de rendre publique toutes ces données si il n'obtient ce qu'il veut (ransomware). Le Commandant Riad explique la situation aux joueurs dans une vidéo : Ils doivent arrêter LERNA. Les joueurs entreront dans le bureau sombre et confiné d'Adrien Kovac pour résoudre la crise.

### 3.2 Intégration des Enjeux (Éthique / Durable)
L'enjeu central du jeu est l'hygiène numérique, la protection de la vie privée et l'éthique de la donnée. Cet enjeu est intégré à l'expérience à travers quatre axes :
- L'impact de la divulgation des données privées : L'Escape Game met en lumière les conséquences dramatiques d'une fuite massive (data leak). Le but est de faire comprendre aux joueurs (particulièrement aux lycéens) ce qui se passe concrètement lorsque des informations intimes (adresses, données bancaires, historiques) tombent dans le domaine public : usurpation d'identité, fraudes, chantage, ou doxxing. Cela rend la menace du jeu très personnelle pour eux.
- La cybersécurité préventive (Le facteur humain) : Le jeu détourne le cliché du "super hacker" pour montrer que la majorité des failles informatiques sont en réalité humaines (mots de passe sur post-it, ingénierie sociale/phishing, clés USB trouvées, documents non broyés).
- L'éthique de la surveillance de masse : En filigrane, le jeu questionne la légitimité d'un programme de surveillance comme LERNA. Il démontre que la centralisation extrême de données, même pensée pour la sécurité nationale, devient une arme de destruction massive lorsqu'elle est compromise. La morale est que collecter de la donnée impose un devoir de sécurisation absolu.
- Sensibilisation concrète : Le jeu se termine par une vidéo de prévention qui ne fait pas que faire peur, mais qui donne aux joueurs des réflexes applicables au quotidien pour se protéger (activer l'A2F, repérer un e-mail frauduleux, isoler du réseau une machine infectée).

---

## 4. SPÉCIFICATIONS TECHNIQUES (L'Ingénierie)

### 4.1 Stack Technologique
*   **Software :** Langages, Frameworks (Frontend/Backend), Base de données.
HTML5 / CSS3 / JavaScript / PHP / JSON / C / C++
*   **Hardware / IoT :** Liste des composants physiques (Microcontrôleurs, capteurs, actionneurs)
Carte ESP32 / LEDs (rouge et verte) / Buzzer piézoélectrique sonore / écran LCD / Bouton-poussoir / Clé USB / Poste de travail / Téléphone
*  **Connexion :**
Connexion entre le poste de travail, le téléphone et le faux serveur via le protocole MQTT

### 4.2 Architecture du Système
L'architecture repose sur un réseau local (LAN) pour garantir la stabilité le jour des JPO (aucune dépendance à Internet). Le système est conçu pour être simple et robuste :
- Le Frontend (Poste de travail - HTML/CSS/JS) :
C'est l'interface visuelle du terminal. Le JavaScript capte ce que le joueur tape au clavier. Pour vérifier si une commande est bonne ou pour actualiser la jauge, le JavaScript utilise la fonction native fetch() pour interroger le PHP de manière transparente (sans recharger la page).
- Le Backend (Serveur Web - PHP / JSON) :
C'est le cerveau du jeu. Des scripts PHP très simples reçoivent les commandes du joueur. L'état de la partie (temps restant, erreurs, avancement) est tout simplement stocké et lu dans des petits fichiers JSON. C'est léger, efficace et très facile à débugger.
- La Communication IoT (Broker MQTT) :
  - Un serveur MQTT local (ex: Mosquitto) permet au PC et à la carte ESP32 de se parler avec des messages très courts (des "topics").
  - Du Web vers l'IoT : Quand les joueurs activent le code à l'Épreuve 4, le système publie un message alarme/ON. La carte ESP32 (en C/C++) le lit et allume instantanément les LEDs rouges et le buzzer.
  - De l'IoT vers le Web : À l'Épreuve 5, quand le joueur appuie sur le gros bouton (Kill Switch), l'ESP32 publie le message alarme/OFF. Le terminal web capte ce message, stoppe la jauge, et affiche l'écran de victoire.

### 4.3 Interface & Expérience Utilisateur (UI/UX)
- **Interface :** Description des écrans principaux et de l'ergonomie.
  -  Le Terminal Joueur : Design épuré façon "console de hacker" (fond noir, texte vert, police d'écriture d'ordinateur rétro). L'écran n'affiche que l'historique des commandes et les retours du système.
  - La Jauge de Fuite : Une grosse barre de progression très visible sur le serveur physique (écran LCD). Elle attire l'œil en changeant de couleur selon l'urgence (Vert au début, puis Orange, puis Rouge clignotant à la fin).
  - Le Dashboard Game Master : Une simple page web cachée (pour l'équipe AIR) avec des boutons basiques pour ajouter/retirer du temps à la jauge ou envoyer un indice texte qui apparaîtra sur le terminal des joueurs.
  - Le Téléphone (Système A2F) : Le téléphone est connecté au Wi-Fi local. Il affiche une simple page web en plein écran. Quand le joueur tape le mot de passe sur le PC, la page web du téléphone s'actualise toute seule et affiche un faux SMS d'alerte avec le code A2F.
- **Interaction Physique :** Comment le joueur interagit-il avec les objets physiques ?
  - Le Clavier d'ordinateur : Outil d'investigation principal. Devoir taper manuellement les commandes (sans souris) donne le sentiment gratifiant d'être un "hacker".
  - L'Écran tactile du téléphone (A2F) : Le joueur doit prendre un vrai smartphone, taper le code PIN sur l'écran tactile et lire le faux SMS pour valider l'étape de double authentification.
  - Le Bouton du faux serveur : Lors de l'épreuve finale, la résolution ne se fait pas au clavier, mais par une action physique brute : frapper un bouton-poussoir d'arrêt d'urgence. Cela coupe l'alarme et procure une satisfaction immédiate.
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
  - Logique de la Jauge de fuite (timer + augmentation en cas d'erreur/aide).

- **Should-Have (Important) :** Fonctionnalités qui enrichissent l'expérience.
  - Vidéo introductive 
  - Epreuve 1 : Ajout de l'A2F
  - Epreuve 2 : Ajout de la raison de la connexion (trouver pourquoi Adrien s'est connecté avant d'exporter la BDD)
  - Epreuve 4 : Ajout des feuilles transparentes pour trouver le bout de code manquant
  - Epreuve 5 : Enrichit le scénario
 
  - Interface Maître du Jeu (Game Master) sur un autre écran pour déclencher des indices à distance et ajuster la jauge si les joueurs bloquent.
  - Serveur IoT avec retours lumineux (LEDs) et sonores (Buzzer).

- **Nice-to-Have (Bonus) :** Fonctionnalités pour l'exposition JPO si le temps le permet.
  - Version anglaise
  - Système renouvelable (codes qui changent, commandes qui changent, etc...)
  - Vidéo de sensibilisation à la fin

### 5.2 Jalons de Validation (Milestones)
| Jalon | Date | Livrable |
| -- | --| -- |
| Jalon 1 | 01 Octobre 2026 | GDD |
| Jalon 2 | mi-novembre 2026 | MVP |
| Jalon 3 | mi-janvier 2027 | BETA |
| Jalon 4 | mi-février 2027 | Produit final |

---

## 6. ANALYSE DES RISQUES
| Risque identifié | Impact (H/M/L) | Stratégie d'atténuation |
| :--- | :--- | :--- |
| Casse ou défaillance du matériel physique (IoT) | H | Mode "Software-only" de secours : Prévoir une commande secrète (ex: override port404) que le Game Master peut taper pour simuler l'action physique et débloquer les joueurs sans le matériel. |
| Blocage sur la syntaxe du terminal | H | Tolérance logicielle et UX : Coder un parseur de commandes permissif (accepter les synonymes et ignorer la casse/les majuscules). Fournir un "Manuel des commandes" très visuel sur le bureau. |
| Retard de développement ou d'intégration | H | Application stricte du MVP (Must-Have) : Développer et valider l'Escape Game 100% sur ordinateur d'abord (Jalon 2). L'IoT et le matériel physique ne seront intégrés qu'ensuite en bonus (Jalon 3). |
| Panne de réseau ou de Wi-Fi | H | Faire tourner tout le jeu en local (localhost) sans dépendre d'Internet. |
| Gestion du temps de jeu défaillante | M | Contrôle via le Game Master (GM) : Le dashboard du GM (Should-Have) permettra d'augmenter ou de baisser manuellement la jauge de fuite, et d'envoyer des indices sur le terminal pour lisser la durée à ~20 min. |
| Vol, perte ou usure des objets "fouille" | L | Kit de rechange : Prévoir les documents imprimés en triple exemplaire (calques, post-it, mails). Laisser le téléphone branché sur secteur en permanence sur le bureau. |

