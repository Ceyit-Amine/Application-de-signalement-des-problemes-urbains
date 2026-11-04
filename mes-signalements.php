<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_login();

$stmt = db()->prepare(
    'SELECT r.*, c.nom AS categorie, s.nom AS service
     FROM reports r
     JOIN categories c ON c.id = r.category_id
     LEFT JOIN services s ON s.id = r.service_id
     WHERE r.user_id = ? ORDER BY r.created_at DESC'
);
$stmt->execute([current_user()['id']]);
$reports = $stmt->fetchAll();

$page_title = 'Mes signalements';
require __DIR__ . '/includes/header.php';
?>
<h1>Mes signalements</h1>
<?php if (!$reports): ?>
    <p>Vous n’avez encore rien signalé. <a href="signaler.php">Signaler un problème</a></p>
<?php endif; ?>
<div class="grid">
    <?php foreach ($reports as $r): ?>
        <article class="card">
            <h3><a href="signalement.php?id=<?= (int) $r['id'] ?>"><?= e($r['titre']) ?></a></h3>
            <p><span class="status" style="background:<?= e(status_color($r['status'])) ?>"><?= e(status_label($r['status'])) ?></span>
                <?= e($r['categorie']) ?> — <?= e($r['quartier']) ?></p>
            <p class="muted"><?= e(date('d/m/Y', strtotime($r['created_at']))) ?>
                <?php if ($r['service']): ?> — <?= e($r['service']) ?><?php endif; ?></p>
            <?php if ($r['status'] === 'rejete' && $r['rejection_reason']): ?>
                <p class="alert alert-error">Motif du rejet : <?= e($r['rejection_reason']) ?></p>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
