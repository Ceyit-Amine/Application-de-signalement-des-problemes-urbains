<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_role('admin');

$message = '';
$error = '';
$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    try {
        if ($action === 'create_user') {
            $nom = trim($_POST['nom'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'declarant';
            $service_id = (int) ($_POST['service_id'] ?? 0);
            if ($nom === '' || $email === '' || strlen($password) < 6
                || !in_array($role, ['declarant', 'agent', 'admin'], true)) {
                $error = 'Vérifiez les champs du nouvel utilisateur.';
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO users (nom, email, password_hash, role, service_id) VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $nom, $email, password_hash($password, PASSWORD_DEFAULT), $role,
                    $service_id > 0 ? $service_id : null,
                ]);
                $message = 'Utilisateur créé.';
            }
        } elseif ($action === 'create_category' && trim($_POST['nom'] ?? '') !== '') {
            $pdo->prepare('INSERT INTO categories (nom) VALUES (?)')
                ->execute([trim($_POST['nom'])]);
            $message = 'Catégorie créée.';
        } elseif ($action === 'create_service' && trim($_POST['nom'] ?? '') !== '') {
            $pdo->prepare('INSERT INTO services (nom) VALUES (?)')
                ->execute([trim($_POST['nom'])]);
            $message = 'Service créé.';
        }
    } catch (PDOException $e) {
        $error = 'Cette valeur existe déjà (email, catégorie ou service).';
    }
}

$users = $pdo->query(
    'SELECT u.*, s.nom AS service FROM users u
     LEFT JOIN services s ON s.id = u.service_id ORDER BY u.created_at DESC'
)->fetchAll();
$categories = $pdo->query('SELECT * FROM categories ORDER BY nom')->fetchAll();
$services = $pdo->query('SELECT * FROM services ORDER BY nom')->fetchAll();

$page_title = 'Administration';
require __DIR__ . '/includes/header.php';
?>
<h1>Administration</h1>
<?php if ($message): ?><p class="alert alert-success"><?= e($message) ?></p><?php endif; ?>
<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>

<div class="card">
    <h2>Créer un utilisateur (agent ou administrateur)</h2>
    <form method="post">
        <input type="hidden" name="action" value="create_user">
        <label>Nom <input type="text" name="nom" required></label>
        <label>Email <input type="email" name="email" required></label>
        <label>Mot de passe <input type="password" name="password" required></label>
        <label>Rôle
            <select name="role">
                <option value="declarant">Déclarant</option>
                <option value="agent">Agent de traitement</option>
                <option value="admin">Administrateur</option>
            </select>
        </label>
        <label>Service (pour un agent)
            <select name="service_id">
                <option value="0">— Aucun —</option>
                <?php foreach ($services as $s): ?>
                    <option value="<?= (int) $s['id'] ?>"><?= e($s['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Créer l’utilisateur</button>
    </form>
</div>

<div class="card">
    <h2>Utilisateurs</h2>
    <table>
        <tr><th>Nom</th><th>Email</th><th>Rôle</th><th>Service</th></tr>
        <?php foreach ($users as $u): ?>
            <tr>
                <td><?= e($u['nom']) ?></td><td><?= e($u['email']) ?></td>
                <td><?= e($u['role']) ?></td><td><?= e($u['service'] ?? '—') ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<div class="card">
    <h2>Catégories</h2>
    <form method="post">
        <input type="hidden" name="action" value="create_category">
        <label>Nouvelle catégorie <input type="text" name="nom" required></label>
        <button type="submit">Ajouter</button>
    </form>
    <ul><?php foreach ($categories as $c): ?><li><?= e($c['nom']) ?></li><?php endforeach; ?></ul>
</div>

<div class="card">
    <h2>Services de traitement</h2>
    <form method="post">
        <input type="hidden" name="action" value="create_service">
        <label>Nouveau service <input type="text" name="nom" required></label>
        <button type="submit">Ajouter</button>
    </form>
    <ul><?php foreach ($services as $s): ?><li><?= e($s['nom']) ?></li><?php endforeach; ?></ul>
    <p class="muted">L’affectation d’un signalement à un service et à un agent
        se fait depuis la fiche du signalement.</p>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
