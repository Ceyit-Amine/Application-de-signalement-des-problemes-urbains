<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$user = current_user();
// Marquer comme lues à l'ouverture de la page.
db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')
    ->execute([$user['id']]);

$stmt = db()->prepare(
    'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 100'
);
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();

$page_title = 'Notifications';
require __DIR__ . '/includes/header.php';
?>
<h1>Notifications</h1>
<p class="muted">Vous recevez une notification à chaque changement de statut
d’un de vos signalements, et à chaque affectation.</p>
<?php if (!$notifications): ?><p>Aucune notification pour le moment.</p><?php endif; ?>
<?php foreach ($notifications as $n): ?>
    <div class="card">
        <p><?= e($n['message']) ?></p>
        <p class="muted"><?= e(date('d/m/Y H:i', strtotime($n['created_at']))) ?>
            <?php if ($n['report_id']): ?>
                — <a href="signalement.php?id=<?= (int) $n['report_id'] ?>">Voir le signalement</a>
            <?php endif; ?></p>
    </div>
<?php endforeach; ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
