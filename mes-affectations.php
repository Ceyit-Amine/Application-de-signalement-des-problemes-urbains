<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_role(['agent', 'admin']);

$user = current_user();
$stmt = db()->prepare(
    'SELECT r.*, c.nom AS categorie, u.nom AS auteur, s.nom AS service
     FROM reports r
     JOIN categories c ON c.id = r.category_id
     JOIN users u ON u.id = r.user_id
     LEFT JOIN services s ON s.id = r.service_id
     WHERE r.agent_id = ? OR (r.service_id = ? AND ? IS NOT NULL)
     ORDER BY r.created_at DESC'
);
$stmt->execute([$user['id'], $user['service_id'], $user['service_id']]);
$reports = $stmt->fetchAll();

$page_title = 'Mes affectations';
require __DIR__ . '/includes/header.php';
?>
<h1>Signalements qui me sont affectés</h1>
<p class="muted">Ceux qui vous sont affectés directement, ou affectés à votre service.</p>
<?php if (!$reports): ?><p>Aucune affectation pour le moment.</p><?php endif; ?>
<div class="grid">
    <?php foreach ($reports as $r): ?>
        <article class="card">
            <h3><a href="signalement.php?id=<?= (int) $r['id'] ?>"><?= e($r['titre']) ?></a></h3>
            <p><span class="status" style="background:<?= e(status_color($r['status'])) ?>"><?= e(status_label($r['status'])) ?></span>
                <?= e($r['categorie']) ?> — <?= e($r['quartier']) ?></p>
            <p class="muted">Par <?= e($r['auteur']) ?>, le <?= e(date('d/m/Y', strtotime($r['created_at']))) ?>
                <?php if ($r['service']): ?> — <?= e($r['service']) ?><?php endif; ?></p>
        </article>
    <?php endforeach; ?>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
