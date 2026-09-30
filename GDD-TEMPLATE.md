# 🧩 GDD-TEMPLATE : [Nom du Projet]
**Document de Conception de Jeu (Game Design Document)**

| Information | Détails |
| :--- | :--- |
| **Groupe / Équipe** | [Noms des membres] |
| **Version** | [ex: 1.0 - Draft initial] |
| **Date de mise à jour** | [JJ/MM/AAAA] |

---

## 1. VISION & CONCEPT (Le "Pitch")
*Cette section doit permettre à n'importe qui de comprendre votre projet en 30 secondes.*

*   **High Concept (Le Pitch) :** Résumez votre jeu en une phrase percutante.
*   **Genre :** (ex: Puzzle-game, Enquête narrative, Simulation de gestion...)
*   **Public Cible :** Qui sont vos joueurs ? (Âge, profil, niveau d'aisance technologique).
*   **La Proposition de Valeur :** Pourquoi ce jeu est-il unique ? Quelle est l'expérience émotionnelle recherchée ?

---

## 2. LE GAMEPLAY (Les Règles du Jeu)
*Décrivez ici comment le jeu fonctionne concrètement.*

### 2.1 La Boucle de Gameplay (Core Loop)
Décrivez le cycle d'action répétitif du joueur. 
*(Exemple : Explorer $\rightarrow$ Collecter un indice $\rightarrow$ Résoudre une énigme $\rightarrow$ Débloquer une zone)*.

### 2.2 Mécaniques de Jeu
Quelles sont les actions que le joueur peut effectuer ? 
*   **Actions directes :** (ex: Saisir un code, déplacer un objet sur l'écran).
*   **Systèmes de jeu :** (ex: Gestion du temps, système de score, inventaire).

### 2.3 Structure & Progression
*   **Le Flow :** Comment le joueur progresse-t-il ? (Linéaire, en arborescence, monde ouvert).
*   **Difficulté :** Comment la difficulté augmente-t-elle au fil du jeu ?
*   **Conditions de Victoire & Défaite :** Comment le joueur gagne-t-il ? Quelles sont les conditions d'échec (Time-out, erreurs fatales) ?

---

## 3. SCÉNARIO & THÉMATIQUE (L'Univers)
*Cette section lie le jeu à sa dimension narrative et éthique.*

### 3.1 L'Intrigue & l'Univers
Décrivez le contexte, l'ambiance (SF, historique, etc.) et l'histoire qui porte le jeu.

### 3.2 Intégration des Enjeux (Éthique / Durable)
**Obligatoire :** Expliquez comment votre scénario ou vos mécaniques de jeu intègrent une problématique éthique, durable ou écologique. Comment ce sujet est-il devenu un élément central de l'expérience ?

---

## 4. SPÉCIFICATIONS TECHNIQUES (L'Ingénierie)
*Cette section est cruciale pour la validation de votre faisabilité technique.*

### 4.1 Stack Technologique
*   **Software :** Langages, Frameworks (Frontend/Backend), Base de données.
*   **Hardware / IoT :** Liste des composants physiques (Microcontrôleurs, capteurs, actionneurs) et mode de connexion.

### 4.2 Architecture du Système
Décrivez comment les composants communiquent entre eux (ex: Communication via API REST, protocole MQTT pour l'IoT, etc.).

### 4.3 Interface & Expérience Utilisateur (UI/UX)
*   **Interface :** Description des écrans principaux et de l'ergonomie.
*   **Interaction Physique :** Si applicable, comment le joueur interagit-il avec les objets physiques ?

---

## 5. PLAN DE PRODUCTION (La Roadmap)
*Transformez votre vision en tâches concrètes.*

### 5.1 Liste des Fonctionnalités (Feature List)
Divisez votre projet en fonctionnalités claires.
*   **Must-Have (Critique) :** Fonctionnalités indispensables pour le MVP.
*   **Should-Have (Important) :** Fonctionnalités qui enrichissent l'expérience.
*   **Nice-to-Have (Bonus) :** Fonctionnalités pour l'exposition JPO si le temps le permet.

### 5.2 Jalons de Validation (Milestones)
Rappelez les dates clés du projet et ce qui doit être livré à chaque étape.

---

## 6. ANALYSE DES RISQUES
*Anticipez les problèmes pour ne pas être pris de court.*

| Risque identifié | Impact (H/M/L) | Stratégie d'atténuation |
| :--- | :--- | :--- |
| *ex: Instabilité du hardware* | *H* | *Prévoir un mode "Software-only" de secours.* |
| *ex: Complexité du scénario* | *M* | *Simplifier les énigmes si le développement prend du retard.* |
