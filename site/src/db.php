<?php
/* Base SQLite du jeu : un seul fichier, aucun serveur à installer.
   Deux tables : etat (clé/valeur, l'état de la partie en cours) et evenements (journal de la partie).
   Le chemin se change avec la variable d'environnement LERNA_DB (les tests utilisent un fichier temporaire). */

function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;
    $path = getenv('LERNA_DB') ?: __DIR__ . '/../data/lerna.sqlite';
    if (!is_dir(dirname($path))) @mkdir(dirname($path), 0775, true);
    $pdo = new PDO('sqlite:' . $path, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('PRAGMA busy_timeout = 3000');
    $pdo->exec('PRAGMA journal_mode = WAL');
    $pdo->exec('CREATE TABLE IF NOT EXISTS etat (cle TEXT PRIMARY KEY, valeur TEXT NOT NULL)');
    $pdo->exec("CREATE TABLE IF NOT EXISTS evenements (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ts INTEGER NOT NULL,
        type TEXT NOT NULL,
        detail TEXT NOT NULL DEFAULT ''
    )");
    /* L'historique : une ligne par partie terminée (victoire, défaite ou abandon). Il survit aux remises à zéro. */
    $pdo->exec("CREATE TABLE IF NOT EXISTS parties (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        debut INTEGER NOT NULL, fin INTEGER NOT NULL, issue TEXT NOT NULL,
        duree INTEGER NOT NULL, jauge INTEGER NOT NULL, indices INTEGER NOT NULL, erreurs INTEGER NOT NULL,
        penalites INTEGER NOT NULL, rab INTEGER NOT NULL, objectif INTEGER NOT NULL,
        etapes TEXT NOT NULL DEFAULT '{}', indices_detail TEXT NOT NULL DEFAULT '{}'
    )");
    return $pdo;
}

function state_get(string $key, ?string $default = null): ?string
{
    $st = db()->prepare('SELECT valeur FROM etat WHERE cle = ?');
    $st->execute([$key]);
    $v = $st->fetchColumn();
    return $v === false ? $default : (string) $v;
}

function state_set(string $key, string|int $value): void
{
    db()->prepare('INSERT INTO etat (cle, valeur) VALUES (?, ?)
                   ON CONFLICT(cle) DO UPDATE SET valeur = excluded.valeur')->execute([$key, (string) $value]);
}

function state_del(string ...$keys): void
{
    $st = db()->prepare('DELETE FROM etat WHERE cle = ?');
    foreach ($keys as $k) $st->execute([$k]);
}

function event_add(string $type, string $detail = ''): void
{
    $pdo = db();
    $pdo->prepare('INSERT INTO evenements (ts, type, detail) VALUES (?, ?, ?)')->execute([time(), $type, $detail]);
    $pdo->exec('DELETE FROM evenements WHERE id <= (SELECT MAX(id) FROM evenements) - 500'); // on garde les 500 derniers
}

/** Les $n derniers événements, du plus récent au plus ancien. */
function events_last(int $n = 30): array
{
    $st = db()->prepare('SELECT id, ts, type, detail FROM evenements ORDER BY id DESC LIMIT ?');
    $st->execute([$n]);
    return $st->fetchAll();
}
