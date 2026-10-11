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
