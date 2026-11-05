<?php
/* Le contenu du scénario : réseau, mails, documents, base de données, script à trous, indices.
   Tout ce qui est "texte du jeu" est ici ; la logique est dans game.php, l'affichage dans app.js. */

/* ---- Les étapes de la partie : game_step() vaut le nombre d'objectifs déjà réussis ---- */
const S_LOGIN    = 1;   // mot de passe trouvé
const S_2FA      = 2;   // code du téléphone saisi : le terminal s'ouvre
const S_IP       = 3;   // IP du pirate bloquée
const S_PIVOT    = 4;   // relais du pirate coupé dans le réseau
const S_SERVEURS = 5;   // les 4 serveurs redémarrés dans le bon ordre (boutons physiques)
const S_SUSPECT  = 6;   // employé qui a ouvert le piège trouvé (SQL)
const S_AUTH     = 7;   // ID du superviseur trouvé (SQL)
const S_ALARME   = 8;   // run urgence.sh : l'alarme se déclenche
const S_SCRIPT   = 9;   // script d'arrêt réparé : le kill switch physique est armé

const PIN_TELEPHONE   = '0712';   // date de naissance de Kovac, dans le mail d'anniversaire
const PENALITE_ERREUR = 3;        // une mauvaise réponse coûte 3 % de fuite
const COUT_INDICE     = 5;        // un indice coûte 5 % de fuite

/* ---- Réseau ---- */
const SERVEUR_PIVOT_IP   = '192.168.1.17';
const SERVEUR_PIVOT_PORT = 4444;

function hotes(): array
{
    return [
        'routeur'    => ['nom' => 'ROUTEUR-DGSI',  'ip' => '192.168.1.1',  'x' => 400, 'y' => 70,  'type' => 'routeur',    'ports' => [22 => 'ssh', 80 => 'http']],
        'switch'     => ['nom' => 'SW-CORE',       'ip' => '192.168.1.2',  'x' => 400, 'y' => 210, 'type' => 'switch',     'ports' => [22 => 'ssh']],
        'bd'         => ['nom' => 'SRV-BD',        'ip' => '192.168.1.30', 'x' => 130, 'y' => 210, 'type' => 'serveur',    'ports' => [3306 => 'mysql']],
        'archives'   => ['nom' => 'SRV-ARCHIVES',  'ip' => '192.168.1.17', 'x' => 240, 'y' => 385, 'type' => 'serveur',    'ports' => [445 => 'partage fichiers', 4444 => 'inconnu']],
        'web'        => ['nom' => 'SRV-WEB',       'ip' => '192.168.1.10', 'x' => 670, 'y' => 210, 'type' => 'serveur',    'ports' => [80 => 'http', 443 => 'https']],
        'pc-rh'      => ['nom' => 'PC-RH',         'ip' => '192.168.1.23', 'x' => 400, 'y' => 405, 'type' => 'poste',      'ports' => [139 => 'netbios', 445 => 'partage fichiers']],
        'imprimante' => ['nom' => 'IMPRIMANTE-3F', 'ip' => '192.168.1.55', 'x' => 560, 'y' => 390, 'type' => 'imprimante', 'ports' => [9100 => 'impression']],
        'pc-kovac'   => ['nom' => 'PC-KOVAC',      'ip' => '192.168.1.77', 'x' => 735, 'y' => 350, 'type' => 'poste',      'ports' => []],
        'pirate'     => ['nom' => 'INCONNU',       'ip' => '192.168.1.42', 'x' => 700, 'y' => 70,  'type' => 'pirate',     'ports' => []],
    ];
}

/** Les câbles : tout passe par le switch, le routeur est relié à l'extérieur (le pirate). */
function liens(): array
{
    return [['routeur', 'switch'], ['switch', 'bd'], ['switch', 'archives'], ['switch', 'web'], ['switch', 'pc-rh'],
            ['switch', 'imprimante'], ['switch', 'pc-kovac'], ['routeur', 'pirate']];
}

/** Les flux réseau visibles en mode simulation et dans netstat : [de, vers, port, texte]. */
function flux(): array
{
    return [
        ['web', 'bd', 3306, 'requêtes SQL'],
        ['archives', 'pc-rh', 445, 'partage de fichiers'],
        ['web', 'routeur', 443, 'trafic des visiteurs'],
        ['archives', 'pirate', 4444, 'connexion sortante'],
        ['imprimante', 'pc-rh', 9100, 'impression'],
    ];
}

/** Bouton physique -> serveur. Les boutons sont étiquetés sur la baie. */
const BOUTON_SERVEUR = ['serveur1' => 'web', 'serveur2' => 'archives', 'serveur3' => 'bd', 'serveur4' => 'routeur'];

function nom_serveur(string $id): string { return hotes()[$id]['nom'] ?? $id; }

function bouton_du_serveur(string $id): string
{
    $b = array_search($id, BOUTON_SERVEUR, true);
    return $b === false ? '?' : substr($b, -1);   // "serveur3" -> "3"
}

/* ---- Messagerie : 'etape' = étape à partir de laquelle le mail arrive ---- */
function mails(): array
{
    return [
        ['id' => 1, 'etape' => S_LOGIN, 'de' => 'Léa Martinez (RH)', 'sujet' => 'Joyeux anniversaire Adrien !',
         'heure' => '08:41', 'texte' => "Salut Adrien,\n\nToute l'équipe te souhaite un très bon anniversaire ! Rendez-vous le 07/12 à la cafétéria à 15 h pour le gâteau.\n\nOn a même réussi à cacher la surprise à ton père :)\n\nLéa"],
        ['id' => 2, 'etape' => S_LOGIN, 'de' => 'rh-dgsi-interne@gmail.com', 'sujet' => 'URGENT : nouvelle politique RH à lire avant 9 h 15',
         'heure' => '09:11', 'pj' => 'Politique_RH_2026.pdf.exe',
         'texte' => "Bonjour,\n\nMerci d'ouvrir la pièce jointe et de confirmer sa lecture avant 9 h 15. Tout retard sera signalé à votre hiérarchie.\n\nService des Ressources Humaines"],
        ['id' => 3, 'etape' => S_LOGIN, 'de' => 'Cafétéria DGSI', 'sujet' => 'Menu de la semaine',
         'heure' => '07:30', 'texte' => "Lundi : lasagnes. Mardi : poisson pané. Mercredi : curry de légumes. Jeudi : jour du burger. Vendredi : la surprise du chef."],
        ['id' => 4, 'etape' => S_LOGIN, 'de' => 'Formation', 'sujet' => 'Rappel : module RGPD à terminer',
         'heure' => '07:55', 'texte' => "Il vous reste 3 modules de sensibilisation RGPD à terminer avant la fin du mois."],
        ['id' => 9, 'etape' => S_LOGIN, 'de' => 'IMPRIMANTE-3F', 'sujet' => 'Bourrage papier au 3e étage',
         'heure' => '08:12', 'texte' => "Merci de retirer le papier coincé dans le bac 2."],
        ['id' => 10, 'etape' => S_2FA, 'de' => 'DSI Support', 'sujet' => 'Bienvenue sur votre poste : où trouver de l\'aide',
         'heure' => '09:19', 'texte' => "Adrien,\n\nEn cas d'incident, toute la documentation est dans l'application Documents, dossier Documentation. Vous y trouverez la procédure d'incident, le guide du terminal et, au fil de l'enquête, les guides réseau, SQL et Python.\n\nLa fenêtre « Mission » en haut à droite rappelle votre objectif en cours. Un bouton Indice est disponible dans la barre des tâches (il coûte 5 % de fuite).\n\nLa DSI"],
        ['id' => 11, 'etape' => S_IP, 'de' => 'E. Blanc (AUDIT_INTERNE)', 'sujet' => 'Ports autorisés : le rapport T3',
         'heure' => '09:23', 'texte' => "Si tu soupçonnes un relais, compare les ports ouverts avec l'inventaire validé du rapport d'audit T3 (Documents, dossier Rapports). La règle n°4 est claire : un port qui n'y figure pas doit être fermé.\n\nUne machine de la carte réseau se laisse interroger : clique dessus, onglet CLI, tape help.\n\nBlanc"],
        ['id' => 5, 'etape' => S_PIVOT, 'de' => 'M. Lefebvre (IT_SUPPORT)', 'sujet' => 'Rappel : redémarrer après un incident',
         'heure' => '09:24', 'texte' => "Adrien,\n\nSi le réseau a été touché, on redémarre les serveurs avec les boutons de la baie. Règle d'or : un serveur ne démarre qu'après ceux dont il dépend. Sur la carte réseau, une flèche « X vers Y » veut dire que X dépend de Y : Y doit être démarré avant.\n\nLes boutons de la baie sont numérotés de 1 à 4 et ne sont pas rangés dans l'ordre : regarde la photo de la baie (application Photos). L'application Baie serveurs te dira ce qui manque si tu te trompes (la séquence repart alors de zéro).\n\nM. Lefebvre"],
        ['id' => 6, 'etape' => S_SERVEURS, 'de' => 'M. Lefebvre (IT_SUPPORT)', 'sujet' => 'Mémo : le SQL en 30 secondes',
         'heure' => '09:31', 'texte' => "Pour interroger la base du personnel avec la commande sql :\n\n  sql SELECT nom FROM personnel WHERE departement = 'ARCHIVES'\n  sql SELECT p.nom, o.fichier FROM personnel p JOIN ouvertures o ON o.personnel_id = p.id\n\nSELECT choisit les colonnes, FROM la table, WHERE filtre les lignes, JOIN relie deux tables avec une colonne commune.\nTapez 'bd' pour voir les tables et leurs colonnes. Le guide complet est dans Documents > Documentation (Guide_SQL).\n\nM. Lefebvre"],
        ['id' => 7, 'etape' => S_SERVEURS, 'de' => 'R. Gomez (SECURITE_PHY)', 'sujet' => 'Export pour l\'enquête',
         'heure' => '09:33', 'texte' => "J'ai exporté dans la base la liste des pièces jointes ouvertes ce matin (table ouvertures) et les habilitations d'urgence (table habilitations). L'extension du fichier piégé est étrange, je te laisse regarder.\n\nPour le superviseur : le droit s'appelle URGENCE_PHYSIQUE, mais deux personnes l'ont. Regarde leur service.\n\nGomez"],
        ['id' => 8, 'etape' => S_AUTH, 'de' => 'K. Diallo (ZERO-JOUR)', 'sujet' => 'Procédure d\'arrêt d\'urgence',
         'heure' => '09:40', 'texte' => "J'ai écrit la procédure d'arrêt d'urgence en Python. Elle est dans urgence.sh. Si elle est corrompue, il faudra la réparer. Quelques rappels :\n- l'ID du superviseur, c'est celui que tu viens de trouver dans la base\n- le port à surveiller est celui du relais que tu as fermé\n- on compare avec l'opérateur d'égalité de Python, et on refuse avec un booléen\n- un serveur qui n'est pas « en_ligne » fait échouer la vérification\n- on tue un processus avec sa méthode kill(), seulement s'il s'appelle backdoor ET écoute le port du relais\n- l'alarme se coupe en publiant OFF sur le sujet MQTT lerna/alarme, quand au moins un processus a été tué\n- le guide Python est dans Documents > Documentation\n\nDiallo"],
    ];
}

/* ---- Documents (explorateur, navigateur intranet) : 'etape' = étape à partir de laquelle ils apparaissent ---- */
function documents(): array
{
    return [
        ['id' => 'guide-terminal', 'dossier' => 'Documentation', 'titre' => 'Guide_du_terminal.txt', 'etape' => S_2FA, 'texte' =>
"GUIDE DU TERMINAL

  aide                      les commandes disponibles
  statut                    vos objectifs et où chercher
  indice                    un indice (+5 % de fuite)
  show logs                 le journal du système
  cibler [ip]               bloque une adresse IP
  scan, netstat             machines, ports et connexions
  bloquer [ip] [port]       ferme un port
  bd, sql [requête]         la base du personnel
  suspect [id], auth [id]   vos réponses de l'enquête
  run urgence.sh            lance le script d'urgence

Une erreur coûte 3 % de fuite. La jauge monte aussi toute seule : à 100 %, la partie est perdue."],

        ['id' => 'procedure', 'dossier' => 'Documentation', 'titre' => 'Procedure_incident.pdf', 'etape' => S_2FA, 'texte' =>
"PROCÉDURE D'INCIDENT

1. Lire les logs : d'où vient l'intrusion ?
2. Bloquer l'adresse IP du pirate.
3. Chercher son relais caché dans le réseau : un port qui n'est pas dans l'inventaire (Rapport_T3.pdf).
4. Redémarrer les 4 serveurs avec les boutons de la baie, en respectant leurs dépendances.
5. Trouver dans la base du personnel qui a ouvert le fichier piégé.
6. Trouver le superviseur d'urgence de la cellule Zéro-Jour.
7. Lancer urgence.sh, le réparer si besoin, puis appuyer sur ARRÊT."],

        ['id' => 'rapport-t3', 'dossier' => 'Rapports', 'titre' => 'Rapport_T3.pdf', 'etape' => S_2FA, 'texte' =>
"RAPPORT D'AUDIT T3 : INVENTAIRE DU RÉSEAU
Cellule Audit interne

Extrait de l'inventaire validé (machines et ports autorisés) :

  ROUTEUR-DGSI     192.168.1.1     22 (ssh), 80 (http)
  SW-CORE          192.168.1.2     22 (ssh)
  SRV-WEB          192.168.1.10    80 (http), 443 (https)
  SRV-ARCHIVES     192.168.1.17    445 (partage de fichiers)
  PC-RH            192.168.1.23    139 (netbios), 445 (partage de fichiers)
  SRV-BD           192.168.1.30    3306 (mysql)
  IMPRIMANTE-3F    192.168.1.55    9100 (impression)
  PC-KOVAC         192.168.1.77    aucun port ouvert

RÈGLE N°4
Tout port ouvert qui n'apparaît pas dans cet inventaire est considéré comme une intrusion et doit être fermé immédiatement.

Conclusion de l'audit : réseau conforme au 31/03."],

        ['id' => 'reunion', 'dossier' => 'Mes documents', 'titre' => 'Compte_rendu_reunion.txt', 'etape' => S_2FA, 'texte' =>
"COMPTE RENDU : réunion de la cellule Zéro-Jour

Présents : A. Kovac, K. Diallo, R. Gomez, M. Lefebvre

- Rappel : en cas de crise, la procédure d'arrêt d'urgence est lancée par le superviseur de la cellule, celui qui détient l'habilitation URGENCE_PHYSIQUE. Attention : la Direction (C. Moreau) a aussi cette habilitation, mais ce n'est pas la cellule Zéro-Jour.
- Gomez exporte les ouvertures de pièces jointes de la semaine dans la base.
- Diallo a écrit urgence.sh en Python.
- Lefebvre rappelle que les serveurs ont un ordre de démarrage : toujours regarder les dépendances."],

        ['id' => 'notes', 'dossier' => 'Mes documents', 'titre' => 'notes.txt', 'etape' => S_2FA, 'texte' =>
"- changer mon mot de passe (encore trop facile à deviner)
- dentiste jeudi
- ne pas oublier le gâteau de Léa"],

        ['id' => 'guide-reseau', 'dossier' => 'Documentation', 'titre' => 'Guide_reseau.txt', 'etape' => S_IP, 'texte' =>
"GUIDE RÉSEAU

Une adresse IP identifie une machine. Un port identifie un service sur cette machine (445 = partage de fichiers, 443 = site web).

TROUVER L'INTRUS
1. Dans la carte réseau, cliquez sur une machine : l'onglet Config liste ses ports.
2. Comparez avec l'inventaire du Rapport_T3.pdf : un port absent de la liste est un intrus.
3. Fermez-le : onglet CLI de la machine (close [port]) ou terminal (bloquer [ip] [port]).

LES DÉPENDANCES
Une flèche jaune « X vers Y » veut dire que X dépend de Y : on démarre Y avant X."],

        ['id' => 'guide-sql', 'dossier' => 'Documentation', 'titre' => 'Guide_SQL.txt', 'etape' => S_SERVEURS, 'texte' =>
"GUIDE SQL

Une base est faite de tables, qui ont des colonnes. La commande bd les liste. Pour interroger : sql SELECT ...

  SELECT nom, departement FROM personnel
  SELECT nom FROM personnel WHERE departement = 'ZERO-JOUR'
  SELECT * FROM ouvertures WHERE fichier LIKE '%.exe'

Pour relier deux tables, JOIN sur la colonne qu'elles ont en commun :
  SELECT p.nom, o.fichier FROM personnel p JOIN ouvertures o ON o.personnel_id = p.id

On combine des conditions avec AND. Le texte va entre apostrophes. Tapez la requête sur une seule ligne. Une erreur de syntaxe ne coûte rien.
Pour répondre : suspect [id], puis auth [id]."],

        ['id' => 'guide-python', 'dossier' => 'Documentation', 'titre' => 'Guide_Python.txt', 'etape' => S_ALARME, 'texte' =>
"GUIDE PYTHON

  a == b     égal              a != b     différent          a >= b     plus grand ou égal
  a and b    les deux conditions            a or b     l'une des deux
  True, False    vrai, faux (avec une majuscule)
  n = 0 puis n = n + 1       un compteur
  for p in liste:            répète pour chaque élément
  p.kill()                   appelle la méthode kill de p (tue le processus)
  mqtt.publish(\"sujet\", \"message\")     envoie un message
  Sur le sujet lerna/alarme : ON allume l'alarme, OFF l'éteint.

Chaque trou a un commentaire qui dit ce qu'il faut mettre."],

        ['id' => 'corb-virus', 'dossier' => 'Corbeille', 'titre' => 'Politique_RH_2026.pdf.exe', 'etape' => S_2FA, 'texte' =>
"[Fichier mis en quarantaine par l'antivirus]

Nom       : Politique_RH_2026.pdf.exe
Détection : Trojan.Lerna.Dropper
Remarque  : ce fichier se faisait passer pour un PDF (double extension .pdf.exe). Il a été ouvert ce matin à 09:12 par un employé de l'entreprise. Qui ? La base du personnel garde la trace des ouvertures de pièces jointes (table ouvertures)."],
        ['id' => 'corb-mdp', 'dossier' => 'Corbeille', 'titre' => 'ancien_mot_de_passe.txt', 'etape' => S_2FA, 'texte' => "admin2019\n\n(ne marche plus depuis la migration, ne pas réutiliser)"],
        ['id' => 'corb-note', 'dossier' => 'Corbeille', 'titre' => 'brouillon_audit.txt', 'etape' => S_2FA, 'texte' =>
"Penser à lire les ports autorisés dans le rapport d'audit avant chaque contrôle.
Un port comme 4444 n'est utilisé par aucun service légitime chez nous."],
        ['id' => 'corb-badges', 'dossier' => 'Corbeille', 'titre' => 'export_badges_2025.csv', 'etape' => S_2FA, 'texte' => "id;porte;heure\n4117;SALLE_ZEROJOUR;08:55\n6710;SALLE_SERVEUR;08:58\n(fichier tronqué)"],
    ];
}

/* ---- Photos : dessinées par app.js (les scènes sont des SVG), ici les titres et la légende ---- */
function photos(): array
{
    return [
        ['id' => 'tableau', 'etape' => S_2FA, 'titre' => 'Tableau blanc : réunion de crise', 'date' => 'hier, 17:42'],
        ['id' => 'baie', 'etape' => S_PIVOT, 'titre' => 'La baie serveurs (étiquettes des boutons)', 'date' => 'lundi, 10:15'],
        ['id' => 'postit', 'etape' => S_SERVEURS, 'titre' => 'Post-it collé sur l\'écran de Gomez', 'date' => 'ce matin, 09:30'],
        ['id' => 'gateau', 'etape' => S_LOGIN, 'titre' => 'Le gâteau de Léa', 'date' => 'samedi, 15:03'],
        ['id' => 'vacances', 'etape' => S_LOGIN, 'titre' => 'Vacances en Bretagne', 'date' => 'août, 14:20'],
    ];
}

/* ---- Base de données du personnel (créée en mémoire à chaque requête, lecture seule) ---- */
function sql_schema(): array
{
    return [
        'personnel'    => ['id', 'nom', 'departement', 'niveau_acces'],
        'ouvertures'   => ['id', 'personnel_id', 'fichier', 'heure'],
        'habilitations' => ['personnel_id', 'droit'],
    ];
}

function sql_seed(): string
{
    return "
    /* COLLATE NOCASE : 'zero-jour' et 'ZERO-JOUR' sont égaux, pour ne pas punir une requête tapée en minuscules */
    CREATE TABLE personnel (id INTEGER PRIMARY KEY, nom TEXT COLLATE NOCASE, departement TEXT COLLATE NOCASE, niveau_acces INTEGER);
    INSERT INTO personnel VALUES
      (1042, 'J. DUPONT',   'RESSOURCES_HUM', 2), (2199, 'M. LEFEBVRE', 'IT_SUPPORT',   3),
      (3014, 'S. BENSAID',  'LOGISTIQUE',     1), (3588, 'C. MOREAU',   'DIRECTION',    5),
      (4117, 'A. KOVAC',    'ZERO-JOUR',      5), (5002, 'L. MARTIN',   'ARCHIVES',     2),
      (6710, 'R. GOMEZ',    'SECURITE_PHY',   4), (8891, 'K. DIALLO',   'ZERO-JOUR',    5),
      (9022, 'P. ROUSSEL',  'COMMUNICATION',  2), (9954, 'E. BLANC',    'AUDIT_INTERNE',4);
    CREATE TABLE ouvertures (id INTEGER PRIMARY KEY, personnel_id INTEGER, fichier TEXT COLLATE NOCASE, heure TEXT);
    INSERT INTO ouvertures VALUES
      (1, 1042, 'Planning_Juin.xlsx',          '08:55'), (2, 2199, 'Rapport_T3.pdf',            '09:03'),
      (3, 5002, 'Politique_RH_2026.pdf.exe',   '09:12'), (4, 9022, 'Newsletter_Interne.pdf',    '09:14'),
      (5, 9954, 'Audit_S2.docx',               '09:20'), (6, 3014, 'Inventaire_Stock.csv',      '09:21');
    CREATE TABLE habilitations (personnel_id INTEGER, droit TEXT COLLATE NOCASE);
    INSERT INTO habilitations VALUES
      (4117, 'URGENCE_PHYSIQUE'), (3588, 'URGENCE_PHYSIQUE'), (4117, 'LECTURE_LOGS'),
      (8891, 'LECTURE_LOGS'), (6710, 'ACCES_SALLE_SERVEUR'), (2199, 'ACCES_SALLE_SERVEUR');
    ";
}

const ID_SUSPECT     = 5002;   // L. MARTIN a ouvert le .pdf.exe
const ID_SUPERVISEUR = 4117;   // ZERO-JOUR + URGENCE_PHYSIQUE

/* ---- Code à trous : {n} = un trou. 12 trous, de l'enquête (1, 2), de la logique (3 à 11) et de MQTT (12) ---- */
function script_lignes(): array
{
    return [
        '# urgence.sh : procédure d\'arrêt d\'urgence de L.E.R.N.A (version corrompue)',
        'import mqtt',
        '',
        'SUPERVISEUR = {1}                        # l\'ID du superviseur, trouvé dans la base',
        'PORT_RELAIS = {2}                        # le port du relais que vous avez fermé',
        'SERVEURS = ["routeur", "bd", "archives", "web"]',
        '',
        'def autoriser(id_saisi):',
        '    if id_saisi {3} SUPERVISEUR:           # est-ce exactement le même ID ?',
        '        return True',
        '    return {4}                            # sinon on refuse (un booléen)',
        '',
        'def serveurs_ok(etats):',
        '    for nom in SERVEURS:',
        '        if etats[nom] {5} "en_ligne":      # un serveur qui n\'est PAS en ligne...',
        '            return False                  # ... fait échouer la vérification',
        '    return True',
        '',
        'def arreter(processus):',
        '    tues = {6}                            # compteur de processus tués : il part de...',
        '    {7} p in processus:                   # on parcourt chaque processus',
        '        if p.nom == "backdoor" {8} p.port == PORT_RELAIS:     # les DEUX conditions à la fois',
        '            p.{9}()                       # on tue le processus',
        '            tues = tues {10} 1             # on ajoute 1 au compteur',
        '    return tues',
        '',
        'def couper_alarme(tues):',
        '    if tues {11} 1:                       # au moins un processus a été tué',
        '        mqtt.publish("lerna/alarme", "{12}")     # on éteint l\'alarme',
    ];
}

/** Réponses acceptées par trou (comparées en minuscules, sans guillemets ni espaces). */
function script_reponses(): array
{
    return [1 => ['4117'], 2 => ['4444'], 3 => ['=='], 4 => ['false'], 5 => ['!='], 6 => ['0'],
            7 => ['for'], 8 => ['and'], 9 => ['kill'], 10 => ['+'], 11 => ['>='], 12 => ['off']];
}

function script_banque(): array
{
    return ['==', '=', '!=', '>=', '<=', '>', '+', '-', 'and', 'or', 'not', 'True', 'False', '0', '1',
            'for', 'while', 'if', 'kill', 'stop', 'exit', 'OFF', 'ON', '4117', '3588', '8891', '4444', '443', '445'];
}

/** Titre de chaque objectif, pour la page Game Master et l'écran de fin. */
function objectifs(): array
{
    return [
        0 => 'Trouver le mot de passe', 1 => 'Code de double authentification', 2 => 'Repérer l\'IP du pirate',
        3 => 'Couper le relais dans le réseau', 4 => 'Redémarrer les serveurs (boutons)', 5 => 'Trouver l\'employé piégé (SQL)',
        6 => 'Trouver l\'ID du superviseur (SQL)', 7 => 'Lancer le script d\'urgence', 8 => 'Réparer le script (code à trous)',
        9 => 'Appuyer sur le kill switch',
    ];
}

/** La fenêtre « Mission » : le contexte et une piste (où chercher), sans donner la réponse. */
function briefing(): array
{
    return [
        0 => ['contexte' => 'Le poste de travail d\'Adrien Kovac est verrouillé.', 'piste' => 'Cherchez autour de l\'ordinateur : le cadre photo sur le bureau.'],
        1 => ['contexte' => 'Le compte est protégé par une double authentification : un code à 6 chiffres envoyé sur le téléphone de Kovac.', 'piste' => 'Le téléphone est sur la table, verrouillé par un PIN à 4 chiffres. Une date, dans un mail ? (Le téléphone s\'ouvre sur la page téléphone.)'],
        2 => ['contexte' => 'Vous êtes dans le système. Une intrusion est en cours : des données sortent.', 'piste' => 'Terminal : tapez show logs et cherchez une connexion à distance suspecte. La documentation est dans l\'application Documents.'],
        3 => ['contexte' => 'L\'IP du pirate est bloquée, mais il garde un accès par un relais caché dans le réseau interne.', 'piste' => 'Ouvrez la Carte réseau, et comparez les ports ouverts avec le Rapport_T3.pdf (Documents > Rapports). Guide_reseau.txt explique la méthode.'],
        4 => ['contexte' => 'Le relais est coupé mais les 4 serveurs sont corrompus : il faut les redémarrer avec les boutons de la baie.', 'piste' => 'Ouvrez Baie serveurs, la carte réseau (flèches « dépend de ») et la photo de la baie. Un serveur démarre après ceux dont il dépend.'],
        5 => ['contexte' => 'Un employé a ouvert le fichier piégé à 09:12. Il faut le retrouver dans la base du personnel.', 'piste' => 'Terminal : bd puis sql SELECT ... Guide_SQL.txt (Documents) explique les JOIN. Cherchez une double extension.'],
        6 => ['contexte' => 'Seul le superviseur d\'urgence de la cellule ZERO-JOUR peut lancer la procédure d\'arrêt.', 'piste' => 'Croisez les tables personnel et habilitations. Deux personnes ont le droit URGENCE_PHYSIQUE : regardez leur service.'],
        7 => ['contexte' => 'Vous avez l\'identité du superviseur : ouvrez l\'application Coffre d\'urgence.', 'piste' => 'Entrez le code 4117 dans le Coffre d\'urgence, branchez la clé USB au PC, puis glissez-déposez urgence.sh depuis Documents vers le Coffre.'],
        8 => ['contexte' => 'ALARME : le pirate a corrompu la procédure d\'arrêt. Le bouton ARRÊT ne marchera pas tant qu\'elle n\'est pas réparée.', 'piste' => 'Ouvrez urgence.sh (éditeur). 12 trous à remplir. Guide_Python.txt et le mail de Diallo expliquent presque tout.'],
        9 => ['contexte' => 'La procédure est réparée : le kill switch est armé.', 'piste' => 'Appuyez sur le gros bouton ARRÊT de la baie, avant que la jauge n\'atteigne 100 %.'],
    ];
}

/** Indices : 3 par objectif, du plus général au plus précis. Chacun coûte 5 % de fuite. */
function indices(): array
{
    $o = ordre_redemarrage();
    $b = fn(string $id) => 'bouton ' . bouton_du_serveur($id) . ' (' . nom_serveur($id) . ')';
    $sequence = implode(', puis ', array_map($b, $o));
    return [
        0 => ['Le mot de passe est sur le bureau de Kovac : regardez le cadre photo.',
              'Ce n\'est pas un mot de la langue : regardez ce qui est écrit sous la photo.',
              'Un prénom suivi d\'une année de naissance, tout en minuscules : max2018.'],
        1 => ['Le code à 6 chiffres est sur le téléphone de Kovac : ouvrez la page téléphone. Elle est verrouillée par un PIN.',
              'Le PIN a 4 chiffres : c\'est une date (jour puis mois) quelque part dans les mails de Kovac. Cherchez un mail personnel, pas un mail de travail.',
              'Le mail de Léa parle du 07/12. Le PIN est 0712. Recopiez ensuite le code à 6 chiffres sur l\'écran de connexion (il change toutes les 30 secondes).'],
        2 => ['Dans le terminal, tapez show logs et lisez les lignes marquées WARNING et CRITICAL.',
              'Cherchez une ligne [AUTH] avec une authentification à distance (User: SYSTEM_OVERRIDE) : elle contient une adresse IP.',
              'Tapez : cibler 192.168.1.42'],
        3 => ['Comparez les ports ouverts des machines (carte réseau, ou commande scan) avec la liste autorisée du Rapport_T3.pdf (Documents > Rapports).',
              'SRV-ARCHIVES a un port qui n\'est pas dans l\'inventaire. La commande netstat montre qu\'il est relié à l\'IP du pirate.',
              'Tapez : bloquer 192.168.1.17 4444   (ou cliquez sur SRV-ARCHIVES dans la carte, onglet CLI : close 4444).'],
        4 => ['Ouvrez Baie serveurs et la carte réseau : les flèches jaunes « X vers Y » disent que X dépend de Y, donc Y démarre avant X. Commencez par celui qui ne dépend de rien.',
              'Le premier à démarrer est ' . nom_serveur($o[0]) . ', le deuxième ' . nom_serveur($o[1]) . '. La photo de la baie (Photos) montre quel bouton correspond à quel serveur.',
              'Appuyez dans cet ordre : ' . $sequence . '.'],
        5 => ['Tapez bd pour voir les tables, puis lisez Guide_SQL.txt (Documents). La table ouvertures dit qui a ouvert quel fichier.',
              'Cherchez un fichier avec une double extension (.pdf.exe) : sql SELECT * FROM ouvertures WHERE fichier LIKE \'%.exe\'. Il faut ensuite relier à la table personnel avec un JOIN.',
              'Tapez : suspect 5002'],
        6 => ['Il faut croiser les tables personnel et habilitations (JOIN sur personnel_id). Le droit à chercher s\'appelle URGENCE_PHYSIQUE.',
              'Deux personnes ont ce droit. Ajoutez une condition sur le département : ZERO-JOUR (voir aussi le compte rendu de réunion).',
              'Tapez : auth 4117'],
        7 => ['Ouvrez l\'application « Coffre d\'urgence » sur le bureau et entrez le code 4117.',
              'Le coffre physique s\'ouvre : insérez la clé USB dans le PC. Un dossier Clé USB apparaît dans Documents.',
              'Glissez-déposez urgence.sh depuis Documents vers le Coffre d\'urgence (ou tapez "run urgence.sh" dans le terminal).'],
        8 => ['Ouvrez l\'éditeur urgence.sh (bureau). Chaque trou a un commentaire. Les trous 1 et 2 viennent de votre enquête, les autres de Guide_Python.txt et du mail de Diallo.',
              'Trous 1 et 2 : l\'ID du superviseur (4117) et le port du relais fermé (4444). Trous 3, 5, 8, 11 : des comparaisons et un « et ». Trou 6 : où commence un compteur ? Trou 10 : comment ajoute-t-on 1 ?',
              'Dans l\'ordre : 4117, 4444, ==, False, !=, 0, for, and, kill, +, >=, OFF'],
        9 => ['Le kill switch est armé : appuyez sur le bouton ARRÊT de la baie.', 'Le gros bouton rouge, sur la baie.', 'Le gros bouton rouge, sur la baie.'],
    ];
}
