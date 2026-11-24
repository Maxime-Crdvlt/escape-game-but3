# Compte Rendu d'Activité (CRA) - Ethan Mebaley Kahel

**Rôle principal :** Référent Tests / Data / Manipulation matérielle

---

## Semaine 1 (du 05/10 au 11/10/2026)
- temps total : 4h

Cette première semaine m'a surtout servi à préparer le terrain. J'ai installé tous les logiciels et outils dont on aura besoin pour coder et faire communiquer la partie électronique avec le site (PlatformIO, MQTT). En parallèle, j'ai regardé la liste des composants demandée par le prof pour comparer les prix et voir si on pouvait en récupérer gratuitement. Enfin, on s'est posé avec le groupe pour organiser le projet, bien répartir le rôle de chacun et planifier le début du code pour la semaine 2.

 Détail des activités
| Catégorie | Description de la tâche / Lien ticket | Temps (h) | Statut |
| :--- | :--- | :--- | :--- |
| Environnement | Installation et configuration des outils de développement et de liaison hardware/software | 1.5 | Terminé |
| Matériel / Sourcing | Analyse de la liste des composants du projet, estimation des prix et recherche d'options de récupération | 1.5 | Terminé |
| Cadrage / Équipe | Structuration de la première étape de développement et répartition des tâches avec l'équipe | 1 | Terminé |

 Difficultés et Solutions
- Problème : J'ai été rechercher des composants électroniques dans la déchetterie et la recyclerie de ma ville, mais pour la recyclerie il n'en avait aucun et pour la déchetterie il était en  impossible de les obtenir, c'était tout simplement interdit
- Solution : La comparaison des prix de certains composants neufs et d'occasions, on privilégie l'occasion pour le côté développement durable du projet
    
 Objectifs S+1
- Démarrer le développement collaboratif de la première étape (structure de base du code et du terminal).
- Mettre en œuvre la répartition des tâches validée au sein du groupe pour lancer les premiers scripts.
- Commencer à coder le terminal web : créer l'interface visuelle (HTML/CSS) et mettre en place l'invite de commande de l'Épreuve 1.


## Semaine 2 (du 12/10 au 18/10/2026)
- Temps total travaillé : 6h


Cette semaine, on a reçu le matériel le jeudi 15 octobre. On a tout donné à Tanim pour qu'il garde les composants et qu'on puisse commencer les tests. En plus de ça, on s'est fait deux réunions en équipe le soir, le mardi et le vendredi, pour bien travailler ensemble et avancer sur le projet. De mon côté, j'ai commencé à coder le terminal web pour l'Épreuve 1 (l'interface visuelle et la connexion). Pour la double authentification (A2F), je ne l'ai pas encore codée : j'ai commencé à me renseigner sur comment faire et j'ai cherché un ancien téléphone de récupération pour le projet. La semaine prochaine (mardi 20 octobre), on ramène tous les composants à l'IUT pour tout brancher ensemble et lier notre code aux cartes.

Detail des activites
| Categorie | Description de la tache / Lien ticket | Temps (h) | Statut |
| :--- | :--- | :--- | :--- |
| Organisation | Réunions d'équipe en soirée (mardi et vendredi) pour structurer le travail | 2.0 | Terminé |
| Logistique / IoT | Réception des composants, vérification et remise du matériel à Tanim | 1.0 | Terminé |
| Développement Web | Création des fichiers du terminal (HTML/CSS/JS) et du design pour l'Épreuve 1 | 2.0 | En cours |
| Recherche A2F | Documentation sur le fonctionnement de l'A2F et recherche d'un téléphone de récup | 1.0 | En cours |

Difficultes et Solutions
- Probleme : Trouver un smartphone de récupération fonctionnel pour servir de support à l'affichage du code A2F.
- Solution : Demander autour de nous dans le groupe et auprès de proches pour récupérer un vieux téléphone inutilisé.

Objectifs S+1 (Semaine 3)
- Apporter tout le matériel à l'IUT le mardi 20/10 pour faire les premiers branchements en groupe.

## Semaine 3 (du 19/10 au 25/10/2026)
- Temps total travaillé : 6h

Synthese
Cette semaine, j'ai bien avancé sur le code du terminal au format HTML. J'ai aussi pris en charge les composants chez moi cet semaine , ce qui m'a permis de faire un test concret : j'ai réussi à faire réagir une LED en fonction d'une commande passée dans le terminal (pour la dernière épreuve). Pour y arriver, j'ai installé le broker MQTT et PlatformIO sur VS Code, et j'ai réussi à faire la liaison entre tout ça. Enfin, on a calé avec mes camarades les sessions de travail en présentiel pour la semaine du 26 au 31 octobre afin d'enchaîner sur les différentes épreuves ensemble. j’ai repassé les composant à tanim pour qu’il continu ses test de son coter la semaine qui suit 

Detail des activites
| Categorie | Description de la tache / Lien ticket | Temps (h) | Statut |
| :--- | :--- | :--- | :--- |
| Developpement Web | Avancée importante sur le code du terminal HTML et la structure générale | 2.5 | En cours |
| Environnement & IoT | Installation de PlatformIO, du broker MQTT et mise en place de la liaison logicielle | 1.5 | Terminé |
| Tests Materiels | Récupération des composants chez moi et test d'une LED commandée depuis le terminal | 2.0 | Terminé |

Difficultes et Solutions
- Probleme : La liaison entre les différents outils et faire fonctionner la LED sur commande a été assez énervante à configurer.
- Solution : Documentation, tests pas à pas sur PlatformIO et vérification de la configuration du broker MQTT local pour stabiliser la communication.

Objectifs S+1 (Semaine 4 / du 26 au 31/10/2026)
- Se retrouver en présentiel avec l'équipe pour avancer ensemble sur les épreuves du projet.
- Continuer le développement des différentes étapes du jeu sur le terminal.
- Pousser les tests de liaison entre le code et les composants matériels.
- Tester le code sur les cartes électroniques (Raspberry Pi 3/Arduino).
- Avancer sur l'A2F et connecter le téléphone au reste du système.


## Semaine 4 (du 26/10 au 01/11/2026)
- Temps total travaillé : 4h

Synthese
Cette semaine, on s'est retrouvé jeudi pour travailler ensemble en présentiel. On a fait plusieurs branchements matériels et apporté d'autres améliorations. Le code du terminal a bien progressé, et on a implémenté l'A2F (double authentification) en version PC en attendant d'avoir un vrai téléphone portable. De plus, deux épreuves ont bien avancé : celle du réseau avec les boutons et celle du code à trou. La semaine prochaine sera plus calme niveau projet car c'est la semaine des DST, donc on va juste améliorer quelques petits détails.

Detail des activites
| Categorie | Description de la tache / Lien ticket | Temps (h) | Statut |
| :--- | :--- | :--- | :--- |
| Presentiel & IoT | Retrouvaille en équipe le jeudi, réalisation des branchements et améliorations matérielles | 2.0 | Terminé |
| Developpement Web | Amélioration du terminal et mise en place de l'A2F version PC en attendant le smartphone | 1.0 | Terminé |
| Avancement Epreuves | Progression sur l'épreuve du réseau (boutons) et l'épreuve du code à trou | 1.0 | En cours |

Difficultes et Solutions
- Probleme : Absence temporaire de smartphone dédié pour tester l'A2F en conditions réelles et gestion du planning serré avec les examens.
- Solution : Implémentation provisoire d'une version A2F sur PC et réorganisation du rythme de travail pour assurer les révisions des DST.

Objectifs S+1 (Semaine 5)
- Travailler un peu plus calmement sur le projet en raison de la semaine de DST.
- Améliorer quelques petits détails sur le code et les épreuves existantes.
- Préparer la suite des développements pour la période post-examens.


## Semaine 5 (du 02/11 au 08/11/2026)
- Temps total travaillé : 2h

Synthese
Cette semaine, avec les DST, je n'ai pas trop pu travailler sur le projet. J'ai quand même pris le temps de nettoyer quelques bugs dans le code et de mettre à jour la dernière épreuve pour qu'elle corresponde mieux à ce qu'on avait prévu au départ. 

Detail des activites
| Categorie | Description de la tache / Lien ticket | Temps (h) | Statut |
| :--- | :--- | :--- | :--- |
| Maintenance Code | Correction de bugs et nettoyage du code du terminal | 1.0 | Terminé |
| Ajustement Épreuve | Mise à jour de la dernière épreuve pour coller au design initial | 1.0 | Terminé |

Difficultes et Solutions
- Probleme : Manque de temps pour avancer en profondeur sur le projet à cause des révisions et des examens (DST).
- Solution : Focalisation sur des tâches rapides et ciblées (corrections de bugs et ajustements logiques) pour ne pas prendre de retard global.

Objectifs S+1 (Semaine 6)
- Travailler sur le décor et l'intégration physique de l'Escape Game : ranger les composants proprement dans une boîte pour que ce soit plus beau et beaucoup plus simple à transporter (très important).
- Continuer les tests et les finitions sur les épreuves avec l'équipe.

## Semaine 6 (du 09/11 au 15/11/2026)
Temps total travaillé : 4h

Synthese
Cette semaine, j'ai principalement fait du débogage pour corriger les derniers bugs, ainsi que des tests pour m'assurer que tout fonctionne bien. J'ai aussi pris le temps de peaufiner quelques détails et d'améliorer le style et la beauté de l'application pour qu'elle soit plus propre et agréable à utiliser.

Detail des activites
| Categorie | Description de la tache / Lien ticket | Temps (h) | Statut |
| :--- | :--- | :--- | :--- |
| Débogage & Tests | Correction des bugs restants et tests globaux du système | 2.0 | Terminé |
| UI / Design | Peaufinage de l'interface et amélioration du style visuel de l'application | 2.0 | Terminé |

Difficultes et Solutions
- Probleme : Petits dysfonctionnements d'affichage et bugs de liaison intermittents lors des tests.
- Solution : Reprise minutieuse du code CSS/JS et corrections ciblées pour stabiliser l'interface.

Objectifs S+1 (Semaine 7)
- Poursuivre l'intégration physique des composants dans la boîte de transport.
- Valider l'ensemble des épreuves avec les membres de l'équipe en présentiel Pour la MVP de la semaine suivante

## Semaine 7 (du 16/11 au 22/11/2026)
Temps total travaillé : 4h

Synthese
Cette semaine, on a présenté notre MVP avec Linda Frappat, la prof de droit. Elle a bien aimé notre projet et tout s'est bien passé pendant la présentation de notre début d'escape game. À part ça, on a surtout préparé et ajusté le code pour que tout soit prêt et fluide pour la présentation de la semaine.

Detail des activites
| Categorie | Description de la tache / Lien ticket | Temps (h) | Statut |
| :--- | :--- | :--- | :--- |
| Presentation / MVP | Présentation du début d'escape game (MVP) avec Linda Frappat et retours positifs | 2.0 | Terminé |
| Preparation Code | Préparation et vérification du code en vue de la présentation de la semaine | 2.0 | Terminé |

Difficultes et Solutions
- Probleme : S'assurer que le code et les fonctionnalités du MVP soient stables le jour de la présentation.
- Solution : Vérification en amont et préparation dédiée du code avant la démonstration.

Objectifs S+1 (Semaine 8)
- Poursuivre l'intégration physique des composants dans la boîte de transport.
- Continuer le développement des épreuves suivantes et les tests avec l'équipe.

