<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        login_user($user);
        header('Location: index.php');
        exit;
    }
    $error = 'Email ou mot de passe incorrect.';
}

$page_title = 'Connexion';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
<h1>Connexion</h1>
<?php if ($error): ?><p class="alert alert-error"><?= e($error) ?></p><?php endif; ?>
<div class="card auth-card">
    <form method="post">
        <label>Email <input type="email" name="email" required></label>
        <label>Mot de passe <input type="password" name="password" required></label>
        <button type="submit">Se connecter</button>
    </form>
    <p>Pas encore de compte ? <a href="register.php">Créez un compte déclarant</a>.</p>
</div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
