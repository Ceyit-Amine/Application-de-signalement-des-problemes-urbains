<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
$user = current_user();
$unread = $user ? unread_notifications_count($user['id']) : 0;
// $page_title et $extra_head peuvent être définis par chaque page avant l'inclusion.
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title ?? APP_NAME) ?> — <?= e(APP_NAME) ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <?php if (!empty($extra_head)) echo $extra_head; ?>
</head>
<body>
<header class="topbar">
    <a class="logo" href="index.php">📍 <?= e(APP_NAME) ?></a>
    <nav>
        <a href="index.php">Signalements</a>
        <a href="tableau-de-bord.php">Tableau de bord</a>
        <?php if ($user): ?>
            <a href="signaler.php">Signaler un problème</a>
            <a href="mes-signalements.php">Mes signalements</a>
            <?php if (is_agent()): ?>
                <a href="mes-affectations.php">Mes affectations</a>
            <?php endif; ?>
            <?php if (is_admin()): ?>
                <a href="admin.php">Administration</a>
            <?php endif; ?>
            <a href="notifications.php">Notifications<?php if ($unread > 0): ?> <span class="badge"><?= $unread ?></span><?php endif; ?></a>
            <span class="user-chip"><?= e($user['nom']) ?> (<?= e($user['role']) ?>)</span>
            <a href="logout.php">Déconnexion</a>
        <?php else: ?>
            <a href="login.php">Connexion</a>
            <a href="register.php">Inscription</a>
        <?php endif; ?>
    </nav>
</header>
<main class="container">
