<?php
require_once __DIR__ . '/includes/functions.php';

// Filtres de consultation (publics, même sans connexion).
$filtre_statut = $_GET['statut'] ?? '';
$filtre_categorie = (int) ($_GET['categorie'] ?? 0);
$filtre_quartier = trim($_GET['quartier'] ?? '');
$recherche = trim($_GET['q'] ?? '');

$where = [];
$params = [];
if ($filtre_statut !== '' && isset(report_statuses()[$filtre_statut])) {
    $where[] = 'r.status = ?';
    $params[] = $filtre_statut;
}
if ($filtre_categorie > 0) {
    $where[] = 'r.category_id = ?';
    $params[] = $filtre_categorie;
}
if ($filtre_quartier !== '') {
    $where[] = 'r.quartier = ?';
    $params[] = $filtre_quartier;
}
if ($recherche !== '') {
    $where[] = '(r.titre LIKE ? OR r.description LIKE ?)';
    $params[] = '%' . $recherche . '%';
    $params[] = '%' . $recherche . '%';
}
$sql = 'SELECT r.*, c.nom AS categorie, u.nom AS auteur, s.nom AS service
        FROM reports r
        JOIN categories c ON c.id = r.category_id
        JOIN users u ON u.id = r.user_id
        LEFT JOIN services s ON s.id = r.service_id';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY r.created_at DESC';
$stmt = db()->prepare($sql);
$stmt->execute($params);
$reports = $stmt->fetchAll();

$categories = db()->query('SELECT * FROM categories ORDER BY nom')->fetchAll();
$quartiers = existing_quartiers();

$page_title = 'Signalements';
require __DIR__ . '/includes/header.php';
?>

<h1>Signalements dans l’espace public</h1>
<p class="muted">Consultez les problèmes déjà signalés, leur statut et leur
localisation. Connectez-vous pour déclarer un nouveau problème.</p>

<div class="card">
    <form method="get" class="filters">
        <label>Recherche
            <input type="text" name="q" value="<?= e($recherche) ?>" placeholder="nid-de-poule, lampadaire…">
        </label>
        <label>Statut
            <select name="statut">
                <option value="">Tous</option>
                <?php foreach (report_statuses() as $key => $label): ?>
                    <option value="<?= e($key) ?>" <?= $filtre_statut === $key ? 'selected' : '' ?>><?= e($label) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Catégorie
            <select name="categorie">
                <option value="0">Toutes</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= (int) $cat['id'] ?>" <?= $filtre_categorie === (int) $cat['id'] ? 'selected' : '' ?>><?= e($cat['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Quartier
            <select name="quartier">
                <option value="">Tous</option>
                <?php foreach ($quartiers as $q): ?>
                    <option value="<?= e($q) ?>" <?= $filtre_quartier === $q ? 'selected' : '' ?>><?= e($q) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Filtrer</button>
    </form>
</div>

<div id="reports-map" class="map"></div>

<h2><?= count($reports) ?> signalement(s)</h2>
<div class="grid">
    <?php foreach ($reports as $r): ?>
        <article class="card report-card">
            <?php if ($r['photo']): ?>
                <img src="<?= e($r['photo']) ?>" alt="Photographie du problème">
            <?php endif; ?>
            <h3><a href="signalement.php?id=<?= (int) $r['id'] ?>"><?= e($r['titre']) ?></a></h3>
            <p><span class="status" style="background:<?= e(status_color($r['status'])) ?>"><?= e(status_label($r['status'])) ?></span>
                <?= e($r['categorie']) ?> — <?= e($r['quartier']) ?></p>
            <p><?= e(mb_substr($r['description'], 0, 140)) ?><?= mb_strlen($r['description']) > 140 ? '…' : '' ?></p>
            <p class="muted">Par <?= e($r['auteur']) ?>, le <?= e(date('d/m/Y', strtotime($r['created_at']))) ?>
                <?php if ($r['service']): ?> — <?= e($r['service']) ?><?php endif; ?></p>
            <?php if ($r['status'] === 'rejete' && $r['rejection_reason']): ?>
                <p class="alert alert-error">Rejeté — motif : <?= e($r['rejection_reason']) ?></p>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
    <?php if (!$reports): ?>
        <p>Aucun signalement ne correspond à ces filtres pour le moment.</p>
    <?php endif; ?>
</div>

<script>
// Données de la carte, générées depuis la base (pas de saisie utilisateur brute en JS).
window.REPORTS_MAP = <?= json_encode(array_map(function ($r) {
    return [
        'id' => (int) $r['id'],
        'title' => $r['titre'],
        'status_label' => status_label($r['status']),
        'color' => status_color($r['status']),
        'latitude' => $r['latitude'] ? (float) $r['latitude'] : null,
        'longitude' => $r['longitude'] ? (float) $r['longitude'] : null,
    ];
}, $reports), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
