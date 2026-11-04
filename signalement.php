<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = db()->prepare(
    'SELECT r.*, c.nom AS categorie, u.nom AS auteur, u.id AS auteur_id,
            s.nom AS service, a.nom AS agent_nom
     FROM reports r
     JOIN categories c ON c.id = r.category_id
     JOIN users u ON u.id = r.user_id
     LEFT JOIN services s ON s.id = r.service_id
     LEFT JOIN users a ON a.id = r.agent_id
     WHERE r.id = ?'
);
$stmt->execute([$id]);
$report = $stmt->fetch();

$error = '';
if ($report && $_SERVER['REQUEST_METHOD'] === 'POST' && is_logged_in()) {
    $action = $_POST['action'] ?? '';
    $user = current_user();

    if ($action === 'status' && can_manage_report($report)) {
        $err = change_report_status(
            $report,
            $_POST['status'] ?? '',
            $user['id'],
            $_POST['rejection_reason'] ?? null
        );
        if ($err) {
            $error = $err;
        } else {
            header('Location: signalement.php?id=' . $id);
            exit;
        }
    } elseif ($action === 'assign' && is_admin()) {
        $service_id = (int) ($_POST['service_id'] ?? 0);
        $agent_id = (int) ($_POST['agent_id'] ?? 0);
        $stmt = db()->prepare('UPDATE reports SET service_id = ?, agent_id = ? WHERE id = ?');
        $stmt->execute([
            $service_id > 0 ? $service_id : null,
            $agent_id > 0 ? $agent_id : null,
            $id,
        ]);
        notify_user(
            (int) $report['user_id'],
            $id,
            'Votre signalement #' . $id . ' a été affecté à un service de traitement.'
        );
        header('Location: signalement.php?id=' . $id);
        exit;
    } elseif ($action === 'comment') {
        $can_comment = is_admin() || can_manage_report($report)
            || (int) $report['user_id'] === (int) $user['id'];
        $texte = trim($_POST['commentaire'] ?? '');
        if ($can_comment && $texte !== '') {
            $photo = upload_photo('photo', 'resolutions', $error);
            if ($photo !== false) {
                $is_resolution = (can_manage_report($report) && !empty($_POST['is_resolution'])) ? 1 : 0;
                $stmt = db()->prepare(
                    'INSERT INTO comments (report_id, user_id, commentaire, photo, is_resolution)
                     VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$id, $user['id'], $texte, $photo, $is_resolution]);
                header('Location: signalement.php?id=' . $id);
                exit;
            }
        } else {
            $error = 'Le commentaire est vide ou vous n’êtes pas autorisé à commenter.';
        }
    }
    // Relecture après une erreur (statut inchangé, mais on réaffiche la fiche).
    $stmt = db()->prepare('SELECT * FROM reports WHERE id = ?');
    $stmt->execute([$id]);
    // Les informations jointes sont rechargées plus bas si besoin ; ici on
    // garde $report tel quel pour l'affichage, le statut n'ayant pas changé.
}

if (!$report) {
    $page_title = 'Signalement introuvable';
    require __DIR__ . '/includes/header.php';
    echo '<p class="alert alert-error">Ce signalement n’existe pas.</p>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$comments = db()->prepare(
    'SELECT cm.*, u.nom AS auteur, u.role AS role_auteur
     FROM comments cm JOIN users u ON u.id = cm.user_id
     WHERE cm.report_id = ? ORDER BY cm.created_at ASC'
);
$comments->execute([$id]);
$comments = $comments->fetchAll();

$history = db()->prepare(
    'SELECT h.*, u.nom AS auteur FROM status_history h
     JOIN users u ON u.id = h.changed_by
     WHERE h.report_id = ? ORDER BY h.created_at ASC'
);
$history->execute([$id]);
$history = $history->fetchAll();

$services = db()->query('SELECT * FROM services ORDER BY nom')->fetchAll();
$agents = db()->query("SELECT * FROM users WHERE role = 'agent' ORDER BY nom")->fetchAll();
$can_manage = is_logged_in() && can_manage_report($report);
$is_author = is_logged_in() && (int) $report['user_id'] === (int) current_user()['id'];

$page_title = 'Signalement #' . $id;
require __DIR__ . '/includes/header.php';
?>

<h1><?= e($report['titre']) ?> <small>#<?= (int) $report['id'] ?></small></h1>
<p><span class="status" style="background:<?= e(status_color($report['status'])) ?>"><?= e(status_label($report['status'])) ?></span>
    <?= e($report['categorie']) ?> — <?= e($report['quartier']) ?></p>

<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>

<?php if ($report['status'] === 'rejete'): ?>
    <div class="alert alert-error">
        <strong>Signalement rejeté — motif visible par son auteur :</strong><br>
        <?= e($report['rejection_reason'] ?: 'Aucun motif précisé.') ?>
    </div>
<?php endif; ?>

<div class="card">
    <?php if ($report['photo']): ?>
        <img class="photo" src="<?= e($report['photo']) ?>" alt="Photographie du problème">
    <?php endif; ?>
    <p><?= nl2br(e($report['description'])) ?></p>
    <p class="muted">
        Déclaré par <?= e($report['auteur']) ?>, le <?= e(date('d/m/Y H:i', strtotime($report['created_at']))) ?><br>
        <?php if ($report['adresse']): ?>Adresse : <?= e($report['adresse']) ?><br><?php endif; ?>
        Service : <?= e($report['service'] ?? 'Non affecté') ?><br>
        Agent : <?= e($report['agent_nom'] ?? 'Non affecté') ?>
    </p>
    <?php if ($report['latitude'] && $report['longitude']): ?>
        <div id="single-map" class="map map-small"></div>
    <?php else: ?>
        <p class="muted">Aucune localisation sur la carte pour ce signalement.</p>
    <?php endif; ?>
</div>

<?php if ($can_manage): ?>
<div class="card">
    <h2>Traitement (agent / administrateur)</h2>
    <form method="post">
        <input type="hidden" name="action" value="status">
        <label>Nouveau statut
            <select name="status" required>
                <?php foreach (report_statuses() as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $report['status'] === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Motif de rejet (obligatoire si le statut choisi est « Rejeté »)
            <textarea name="rejection_reason" placeholder="Ex : doublon, hors périmètre, informations insuffisantes…"></textarea></label>
        <button type="submit">Mettre à jour le statut</button>
    </form>
</div>
<?php endif; ?>

<?php if (is_admin()): ?>
<div class="card">
    <h2>Affectation à un service de traitement</h2>
    <form method="post">
        <input type="hidden" name="action" value="assign">
        <label>Service
            <select name="service_id">
                <option value="0">— Aucun —</option>
                <?php foreach ($services as $s): ?>
                    <option value="<?= (int) $s['id'] ?>" <?= (int) $report['service_id'] === (int) $s['id'] ? 'selected' : '' ?>><?= e($s['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Agent
            <select name="agent_id">
                <option value="0">— Aucun —</option>
                <?php foreach ($agents as $a): ?>
                    <option value="<?= (int) $a['id'] ?>" <?= (int) $report['agent_id'] === (int) $a['id'] ? 'selected' : '' ?>><?= e($a['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Enregistrer l’affectation</button>
    </form>
</div>
<?php endif; ?>

<div class="card">
    <h2>Historique des statuts</h2>
    <?php if (!$history): ?>
        <p class="muted">Aucun changement de statut pour le moment (statut initial : Reçu).</p>
    <?php else: ?>
        <ul>
            <?php foreach ($history as $h): ?>
                <li><?= e(date('d/m/Y H:i', strtotime($h['created_at']))) ?> —
                    <?= e(status_label($h['old_status'])) ?> → <strong><?= e(status_label($h['new_status'])) ?></strong>
                    par <?= e($h['auteur']) ?>
                    <?php if ($h['reason']): ?> (motif : <?= e($h['reason']) ?>)<?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</div>

<div class="card">
    <h2>Commentaires et photographies de résolution</h2>
    <?php if (!$comments): ?>
        <p class="muted">Aucun commentaire pour le moment.</p>
    <?php endif; ?>
    <?php foreach ($comments as $c): ?>
        <div class="card">
            <p><strong><?= e($c['auteur']) ?></strong> (<?= e($c['role_auteur']) ?>)
                — <?= e(date('d/m/Y H:i', strtotime($c['created_at']))) ?>
                <?php if ($c['is_resolution']): ?> — <strong>📸 Photographie de résolution</strong><?php endif; ?></p>
            <p><?= nl2br(e($c['commentaire'])) ?></p>
            <?php if ($c['photo']): ?>
                <img class="photo" src="<?= e($c['photo']) ?>" alt="Photographie de résolution">
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <?php if (is_logged_in() && (is_admin() || $can_manage || $is_author)): ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="comment">
            <label>Ajouter un commentaire <textarea name="commentaire" required></textarea></label>
            <label>Photographie (facultative) <input type="file" name="photo" accept="image/jpeg,image/png,image/webp"></label>
            <?php if ($can_manage): ?>
                <label><input type="checkbox" name="is_resolution" value="1">
                    Il s’agit d’une photographie de résolution</label>
            <?php endif; ?>
            <button type="submit">Publier le commentaire</button>
        </form>
    <?php elseif (!is_logged_in()): ?>
        <p><a href="login.php">Connectez-vous</a> pour commenter.</p>
    <?php endif; ?>
</div>

<script>
window.SINGLE_REPORT = <?= json_encode([
    'title' => $report['titre'],
    'latitude' => $report['latitude'] ? (float) $report['latitude'] : null,
    'longitude' => $report['longitude'] ? (float) $report['longitude'] : null,
], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
