# Compte Rendu d'Activité (CRA) - Tanim VEER

**Rôle principal :** Responsable Matériel (IoT) et organisation

## Semaine 1 (du 05/10 au 11/10/2026)

**Focus de la semaine :** Préparation du matériel et organisation
**Temps total travaillé :** 1h30

### Synthèse
Cette semaine, j'ai regardé la liste du matériel que le professeur nous prête pour voir ce qu'il nous manque, puis j'ai envoyé un mail pour demander le matériel dont on a besoin. J'ai aussi cherché où récupérer ou acheter les objets du décor et le petit matériel

### Détail des activités

| Catégorie | Description de la tâche | Temps (h) | Statut |
| :--- | :--- | :--- | :--- |
| Matériel | Lecture de la liste du matériel prêté et repérage de ce qui manque | 20min | Terminé |
| Matériel | Rédaction et envoi du mail de demande de matériel au professeur | 10min | Terminé |
| Organisation | Mis en place d'une liste des objets à récupérer et à acheter (décor, cadenas, boutons, interrupteurs) | 1h | Terminé |

### Difficultés et solutions
**Problème :** la liste prêtée ne contient pas de buzzer (petit haut-parleur qui sonne), d'interrupteurs ni de gros bouton, et on ne sait pas encore s'il y a assez de prises sur la carte d'extension pour tout brancher
**Solution :** j'ai demandé ce qu'on avait besoin et prévenu du matériel manquant par mail, et on achètera le matériel si besoin

### Objectifs S+1
- Récupérer le matériel prêté
- Installer PlatformIO pour programmer et tester la carte
- Faire un premier essai : un appui sur un bouton envoie un message au serveur
- Se répartir les tâches avec l'équipe pour la première version du jeu


## Semaine 2 (du 12/10 au 18/10/2026)

**Focus de la semaine :** Récupération et prise en main du matériel
**Temps total travaillé :** 1h15

### Synthèse
Jeudi 15/10, j'ai récupéré le matériel qu'on nous prête. J'ai ensuite commencé à le prendre en main : j'ai regardé les cartes, les capteurs et les écrans et j'ai commencé à voir comment les brancher entre eux et je n'ai pas encore programmé la carte

### Détail des activités

| Catégorie | Description de la tâche | Temps (h) | Statut |
| :--- | :--- | :--- | :--- |
| Matériel | Récupération du matériel prêté par le professeur (jeudi 15/10) | 15min | Terminé |
| Matériel | Prise en main du matériel : identification des composants et premiers branchements | 1h | En cours |

### Difficultés et solutions
**Problème :** le matériel n'a été récupéré que jeudi, donc il restait peu de temps pour avancer cette semaine
**Solution :** j'ai fait seulement la prise en main et je commence la programmation la semaine prochaine

### Objectifs S+1
- Installer PlatformIO (le logiciel conseillé par le professeur pour programmer la carte) et tester la carte
- Faire un premier essai : un appui sur un bouton envoie un message au serveur
- Brancher les LED et l'écran du faux serveur
- Se répartir les tâches avec l'équipe pour la première version du jeu

## Semaine 3 (du 19/10 au 25/10/2026)

**Focus de la semaine :** Tests du matériel prêté et prêt à l'équipe pour avancer sur le code
**Temps total travaillé :** 2h15

### Synthèse
Cette semaine, j'ai pris le matériel prêté par le professeur chez moi pour le tester : j'ai vérifié que les cartes, les boutons, les LED et l'écran fonctionnent. Ensuite, je l'ai passé à Ethan pour qu'il puisse avancer sur le code et faire ses propres tests de son côté. Avec l'équipe, on s'est réparti les tâches et on a calé les séances de travail en présentiel de la semaine du 26 au 31/10

### Détail des activités

| Catégorie | Description de la tâche | Temps (h) | Statut |
| :--- | :--- | :--- | :--- |
| Matériel | Tests des composants prêtés (cartes, boutons, LED, écran) pour vérifier qu'ils fonctionnent | 1h30 | Terminé |
| Organisation | Passage du matériel à Ethan pour qu'il puisse coder et tester de son côté | 15min | Terminé |
| Organisation | Répartition des tâches et mise en place des séances en présentiel du 26 au 31/10 | 30min | Terminé |

### Difficultés et solutions
**Problème :** on n'a qu'un seul exemplaire du matériel, donc on doit se le passer pour que chacun puisse tester
**Solution :** je l'ai passé à Ethan pendant qu'il codait, et on prévoit de tester ensemble en présentiel la semaine prochaine

### Objectifs S+1
- Se retrouver en présentiel avec l'équipe pour avancer ensemble sur les épreuves
- Tester ensemble la liaison entre le code et le matériel (LED, écran, boutons)
- Brancher le buzzer, les interrupteurs et le gros bouton
- Tester le code sur les cartes (Raspberry Pi et Arduino)
- Avancer avec l'équipe sur l'authentification par téléphone (A2F)


## Semaine 4 (du 26/10 au 01/11/2026)

**Focus de la semaine :** Branchements en présentiel, liaison carte/site et abandon du Raspberry Pi
**Temps total travaillé :** 5h15

### Synthèse
Jeudi 29/10, on s'est retrouvés en présentiel et on a fait les branchements : LED, buzzer, servo, barre de LED, écran et les boutons sur la carte d'extension. J'ai ensuite relié la carte au site par le broker MQTT et j'ai testé que les boutons et l'alarme réagissent bien. J'ai aussi essayé de préparer le Raspberry Pi, mais la carte SD ne marchait pas, donc on a décidé de tout faire tourner sur le PC. Pour finir, j'ai rangé le code et je l'ai mis sur GitHub en plusieurs commits signés

### Détail des activités

| Catégorie | Description de la tâche | Temps (h) | Statut |
| :--- | :--- | :--- | :--- |
| Matériel | Présentiel du jeudi 29/10 : branchements de tous les composants et vérification de chacun avec un programme de test | 2h | Terminé |
| IoT | Connexion de la carte au Wi-Fi et au broker MQTT, puis test des boutons et de l'alarme depuis le site | 1h | Terminé |
| Matériel | Préparation de la carte SD du Raspberry Pi (plusieurs essais) | 45min | Abandonné |
| Organisation | Rangement du projet en dossiers, tests et mise en ligne sur GitHub en plusieurs commits signés | 1h30 | Terminé |

### Difficultés et solutions
**Problème :** la carte SD du Raspberry Pi n'enregistrait rien, même avec une autre prise, et la carte ne voulait pas se connecter au Wi-Fi (deux lettres du mot de passe étaient inversées)
**Solution :** on a abandonné le Raspberry Pi et tout tourne sur le PC, et j'ai corrigé le mot de passe. J'ai aussi vu que la page du téléphone ne s'ouvrait pas sur mon portable parce que le site n'était ouvert qu'à ce PC, donc je l'ai ouvert au réseau

### Objectifs S+1
- Semaine de DST : travailler plus calmement sur le projet
- Tester avec les vrais boutons de la baie et étiqueter les boutons
- Régler le volume du buzzer dans la salle
- Préparer les vidéos de prévention et la vidéo d'introduction

## Semaine 5 (du 02/11 au 08/11/2026)

**Focus de la semaine :** Semaine de DST : relecture et tests du travail de l'équipe (coffre d'urgence)
**Temps total travaillé :** 1h15

### Synthèse
Cette semaine c'était les DST, donc on a peu travaillé sur le projet. Jeudi 05/11, Ethan (qui a le matériel) a ajouté l'application « Coffre d'urgence » : on entre le code du superviseur, le servo ouvre le coffre, puis le site détecte la clé USB branchée au PC et on glisse le fichier urgence.sh dedans pour continuer vers l'épreuve du code à trous. De mon côté j'ai relu ses changements et j'ai relancé tous les tests (règles du jeu, épreuves, partie complète) : tout passe
J'ai aussi vu que le port du broker MQTT est passé de 1883 à 1884 côté site, mais pas dans le programme de la carte ni dans les tests

### Détail des activités

| Catégorie | Description de la tâche | Temps (h) | Statut |
| :--- | :--- | :--- | :--- |
| Code | Relecture des changements d'Ethan (coffre d'urgence, détection de la clé USB, servo commandé par MQTT) | 45min | Terminé |
| Tests | Lancement des tests sur la nouvelle version : règles du jeu, épreuves et partie complète | 15min | Terminé |
| IoT | Repérage du décalage de port MQTT (1884 côté site, 1883 côté carte et tests) | 15min | En cours |

### Difficultés et solutions
**Problème :** le broker écoute maintenant sur le port 1884, mais la carte et les tests utilisent 1883 par défaut, donc la carte ne se connecte plus au site si on ne change rien
**Solution :** ajouter la ligne `#define MQTT_SERVER_PORT 1884` dans mon fichier secrets.h, et se mettre d'accord avec Ethan pour harmoniser le port partout

### Objectifs S+1
- Récupérer les changements d'Ethan (git pull) et tester le coffre avec le vrai servo et une vraie clé USB
- Se mettre d'accord avec Ethan sur le port MQTT (1883 ou 1884)
- Étiqueter les boutons de la baie et régler le volume du buzzer dans la salle
- Faire le point avec Jean-François sur le backend NestJS pour décider de la stack (le site actuel est en PHP)
- Préparer les vidéos de prévention et la vidéo d'introduction
