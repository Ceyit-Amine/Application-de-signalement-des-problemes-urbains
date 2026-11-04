<?php
require_once __DIR__ . '/includes/functions.php';

$pdo = db();
$total = (int) $pdo->query('SELECT COUNT(*) FROM reports')->fetchColumn();
$resolus = (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'resolu'")->fetchColumn();
$en_cours = (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'en_cours'")->fetchColumn();
$rejetes = (int) $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'rejete'")->fetchColumn();

function group_counts($sql)
{
    return db()->query($sql)->fetchAll();
}
$par_quartier = group_counts(
    'SELECT quartier, COUNT(*) AS n FROM reports GROUP BY quartier ORDER BY n DESC'
);
$par_categorie = group_counts(
    'SELECT c.nom AS label, COUNT(*) AS n FROM reports r
     JOIN categories c ON c.id = r.category_id GROUP BY c.nom ORDER BY n DESC'
);
$par_statut = group_counts(
    'SELECT status, COUNT(*) AS n FROM reports GROUP BY status ORDER BY n DESC'
);
$par_service = group_counts(
    'SELECT COALESCE(s.nom, "Non affecté") AS label, COUNT(*) AS n
     FROM reports r LEFT JOIN services s ON s.id = r.service_id
     GROUP BY s.nom ORDER BY n DESC'
);

function max_n($rows)
{
    $max = 1;
    foreach ($rows as $row) {
        $max = max($max, (int) $row['n']);
    }
    return $max;
}

$page_title = 'Tableau de bord';
$extra_head = '<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>';
require __DIR__ . '/includes/header.php';
?>

<h1>Tableau de bord</h1>
<div class="stats">
    <div class="stat"><strong><?= $total ?></strong> signalements au total</div>
    <div class="stat"><strong><?= $resolus ?></strong> résolus</div>
    <div class="stat"><strong><?= $en_cours ?></strong> en cours</div>
    <div class="stat"><strong><?= $rejetes ?></strong> rejetés</div>
    <div class="stat"><strong><?= $total > 0 ? round($resolus / $total * 100) : 0 ?> %</strong> taux de résolution</div>
</div>

<div class="card">
    <h2>Par quartier</h2>
    <?php foreach ($par_quartier as $row): ?>
        <div class="bar-row"><span><?= e($row['quartier']) ?></span>
            <div class="bar" style="width:<?= (int) $row['n'] / max_n($par_quartier) * 100 ?>%"></div>
            <?= (int) $row['n'] ?></div>
    <?php endforeach; ?>
    <?php if (!$par_quartier): ?><p class="muted">Aucune donnée pour le moment.</p><?php endif; ?>
    <canvas id="chart-quartier" height="120"></canvas>
</div>

<div class="card">
    <h2>Par type de problème (catégorie)</h2>
    <?php foreach ($par_categorie as $row): ?>
        <div class="bar-row"><span><?= e($row['label']) ?></span>
            <div class="bar" style="width:<?= (int) $row['n'] / max_n($par_categorie) * 100 ?>%"></div>
            <?= (int) $row['n'] ?></div>
    <?php endforeach; ?>
    <?php if (!$par_categorie): ?><p class="muted">Aucune donnée pour le moment.</p><?php endif; ?>
    <canvas id="chart-categorie" height="120"></canvas>
</div>

<div class="card">
    <h2>Par statut</h2>
    <table>
        <?php foreach ($par_statut as $row): ?>
            <tr><td><?= e(status_label($row['status'])) ?></td><td><?= (int) $row['n'] ?></td></tr>
        <?php endforeach; ?>
    </table>
    <h2>Par service de traitement</h2>
    <table>
        <?php foreach ($par_service as $row): ?>
            <tr><td><?= e($row['label']) ?></td><td><?= (int) $row['n'] ?></td></tr>
        <?php endforeach; ?>
    </table>
</div>

<script>
function drawChart(id, labels, data) {
    var el = document.getElementById(id);
    if (!el || typeof Chart === 'undefined') return;
    new Chart(el, {
        type: 'bar',
        data: { labels: labels, datasets: [{ data: data, backgroundColor: '#0ea5a4' }] },
        options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });
}
drawChart('chart-quartier',
    <?= json_encode(array_column($par_quartier, 'quartier')) ?>,
    <?= json_encode(array_map('intval', array_column($par_quartier, 'n'))) ?>);
drawChart('chart-categorie',
    <?= json_encode(array_column($par_categorie, 'label')) ?>,
    <?= json_encode(array_map('intval', array_column($par_categorie, 'n'))) ?>);
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
