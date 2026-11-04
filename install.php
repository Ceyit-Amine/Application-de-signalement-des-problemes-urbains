<?php
// install.php — à ouvrir UNE fois dans le navigateur, puis à supprimer.
// Crée les tables (même schéma que database/schema.sql), les catégories,
// les services et 3 comptes de démonstration.
require_once __DIR__ . '/config/config.php';

$pdo = new PDO(
    'mysql:host=' . DB_HOST . ';charset=utf8mb4',
    DB_USER,
    DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$pdo->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
$pdo->exec('USE `' . DB_NAME . '`');

$sql = file_get_contents(__DIR__ . '/database/schema.sql');
// On ne rejoue que la partie après "USE ..." (la création de la base est déjà faite),
// instruction par instruction (PDO n'exécute pas plusieurs requêtes d'un coup).
$pos = strpos($sql, 'CREATE TABLE');
foreach (explode(";\n", substr($sql, $pos)) as $statement) {
    $statement = trim($statement);
    if ($statement !== '') {
        $pdo->exec($statement);
    }
}

// Comptes de démonstration (mots de passe hachés à l'installation).
$demo = [
    ['Administrateur', 'admin@demo.local', 'Admin123!', 'admin', null],
    ['Agent Démo', 'agent@demo.local', 'Agent123!', 'agent', 1],
    ['Citoyen Démo', 'citoyen@demo.local', 'Citoyen123!', 'declarant', null],
];
$stmt = $pdo->prepare(
    'INSERT IGNORE INTO users (nom, email, password_hash, role, service_id) VALUES (?, ?, ?, ?, ?)'
);
foreach ($demo as $u) {
    $stmt->execute([$u[0], $u[1], password_hash($u[2], PASSWORD_DEFAULT), $u[3], $u[4]]);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Installation terminée</title></head>
<body style="font-family: sans-serif; max-width: 700px; margin: 40px auto;">
<h1>Installation terminée ✅</h1>
<p>Les tables, catégories, services et comptes de démonstration ont été créés.</p>
<ul>
    <li>Administrateur : admin@demo.local / Admin123!</li>
    <li>Agent : agent@demo.local / Agent123!</li>
    <li>Déclarant : citoyen@demo.local / Citoyen123!</li>
</ul>
<p><strong>Supprimez maintenant ce fichier <code>install.php</code> du serveur</strong>,
puis <a href="index.php">ouvrez l'application</a>.</p>
</body>
</html>
