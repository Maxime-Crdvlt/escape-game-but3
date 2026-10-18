# Compte Rendu d'Activité (CRA) - Jean-François DENA

**Rôle principal :** Développeur Fullstack

---

## Semaine 1 (du 05/10 au 11/10/2026)

**Focus de la semaine :** Prise en main des outils IoT (PlatformIO, MQTT) et préparation du backend
**Temps total travaillé :** 1h30

### Synthèse

Cette première semaine a été consacrée à la découverte des outils IoT du projet. J'ai assisté à la masterclass de mardi sur PlatformIO et MQTT, mais je n'ai pas pu la suivre avec toute l'attention nécessaire, car je travaillais sur un autre projet en parallèle. J'ai rattrapé ce retard en consultant un résumé détaillé de son contenu.

Je retiens que PlatformIO gère les bibliothèques de la carte comme un gestionnaire de dépendances, et que MQTT permet à l'ESP32 et au serveur de s'échanger de courts messages via un broker et des topics. Pour mon rôle côté backend, je devrai m'abonner aux topics publiés par l'ESP32, traiter ces messages et mettre à jour l'état de la partie. J'ai aussi noté que l'outil MQTTX permet de tester ces échanges sans avoir la carte, ce qui me permettra d'avancer sur le backend avant la réception du matériel.

### Détail des activités

| Catégorie | Description de la tâche / Lien ticket | Temps (h) | Statut |
| :--- | :--- | :--- | :--- |
| Formation | Masterclass « Développer avec des IoT comme un informaticien » (PlatformIO, MQTT) | 1 | Terminé |
| Documentation | Rattrapage via un résumé de la masterclass : bibliothèque PubSubClient, organisation des topics, tests avec MQTTX | 0.5 | Terminé |

### Difficultés et solutions

**Problème :** Je n'ai pas suivi la masterclass avec assez d'attention, car je travaillais sur un autre projet en même temps, et je n'ai donc pas avancé concrètement sur le projet cette semaine.
**Solution :** J'ai rattrapé le contenu avec un résumé. Dès la semaine prochaine, je réserve du temps dédié à la SAÉ en début de semaine pour commencer le développement.

### Objectifs S+1

- Définir avec l'équipe la stack finale (NestJS et PostgreSQL ou PHP et JSON) et la consigner dans le dépôt.
- Initialiser le projet backend dans le dépôt GitHub et lancer un premier serveur.
- Installer Mosquitto et MQTTX, puis réaliser un premier échange de messages entre un script de test et le backend.
- Définir avec Tanim les noms et le contenu des topics MQTT (badge, interrupteurs, bouton, alarme).

## Semaine 2 (du 12/10 au 18/10/2026)

**Focus de la semaine :** Réunions d'équipe, mise en place du broker MQTT et initialisation du backend
**Temps total travaillé :** 4h

### Synthèse

Cette semaine, nous avons récupéré le matériel IoT le jeudi 15 octobre et tenu deux réunions d'équipe en soirée, le mardi et le vendredi, pour avancer ensemble sur le projet. De mon côté, j'ai installé et configuré Mosquitto sur mon PC Windows et validé le circuit de messages avec MQTTX (abonnement à `lerna/#`, publication sur `lerna/test`, message reçu). J'ai relevé l'IP locale du PC et transmis à Tanim les informations de connexion au broker (IP, port 1883) pour que sa carte puisse s'y connecter. Côté code, j'ai initialisé le projet NestJS dans le dépôt (branche `backend-init`) et vérifié que le serveur démarre. J'ai aussi posé à l'équipe la question de la stack (NestJS + PostgreSQL + Mosquitto, ou PHP/JSON comme dans le GDD v1.4) ; en attendant la décision, j'ai avancé avec NestJS.

### Détail des activités

| Catégorie | Description de la tâche / Lien ticket | Temps (h) | Statut |
| :--- | :--- | :--- | :--- |
| Organisation | Réunions d'équipe en soirée (mardi et vendredi) pour structurer le travail | 2.0 | Terminé |
| Logistique / IoT | Récupération du matériel prêté (jeudi 15/10) | 0.25 | Terminé |
| Environnement | Installation de Mosquitto 2.1.2, configuration du listener (port 1883) et règle de pare-feu (réseau privé) | 0.5 | Terminé |
| Test IoT | Installation de MQTTX, connexion au broker local, test publication/abonnement sur `lerna/test` | 0.5 | Terminé |
| Backend | Installation de Node/NestJS, clonage du dépôt, création du projet, test (Hello World), commit et push de `backend-init` | 0.5 | Terminé |
| Cadrage / Équipe | Question sur le choix de la stack posée à l'équipe, et message à Tanim avec l'IP et le port du broker | 0.25 | En cours |

### Difficultés et solutions

**Problème :** Au début, MQTTX pointait vers un broker public (`broker.emqx.io`) au lieu de mon broker local, et le pare-feu Windows bloquait Mosquitto après un refus d'autorisation.
**Solution :** J'ai recréé la connexion avec `localhost`, supprimé les règles de blocage et ajouté une règle autorisant le port 1883 sur le réseau privé.

### Objectifs S+1 (Semaine 3)

- Faire valider la stack par l'équipe et la consigner dans le dépôt.
- Connecter NestJS à Mosquitto (abonnement à `lerna/#`, affichage des messages reçus).
- Définir avec Tanim les topics MQTT et leur format dans `docs/mqtt-topics.md`.
- Mardi 20/10 à l'IUT : brancher la carte et la faire communiquer avec le broker (IP à mettre à jour sur le réseau de l'IUT).
