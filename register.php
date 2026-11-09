<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($nom === '' || $email === '' || strlen($password) < 6) {
        $error = 'Remplissez tous les champs (mot de passe : 6 caractères minimum).';
    } else {
        try {
            $stmt = db()->prepare(
                "INSERT INTO users (nom, email, password_hash, role) VALUES (?, ?, ?, 'declarant')"
            );
            $stmt->execute([$nom, $email, password_hash($password, PASSWORD_DEFAULT)]);
            $stmt = db()->prepare('SELECT * FROM users WHERE id = ?');
            $stmt->execute([db()->lastInsertId()]);
            login_user($stmt->fetch());
            header('Location: index.php');
            exit;
        } catch (PDOException $e) {
            $error = 'Cet email est déjà utilisé.';
        }
    }
}

$page_title = 'Inscription';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
<h1>Inscription</h1>
<p class="muted">L'inscription crée un compte <strong>déclarant</strong>.
Les comptes agent et administrateur sont créés par l'administrateur.</p>
<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
<div class="card auth-card">
    <form method="post">
        <label>Nom complet <input type="text" name="nom" required></label>
        <label>Email <input type="email" name="email" required></label>
        <label>Mot de passe <input type="password" name="password" required></label>
        <button type="submit">Créer mon compte</button>
    </form>
</div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
