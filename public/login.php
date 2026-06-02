<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$page_title = 'Logga in';
$error = '';

if (is_post()) {
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = (int) $user['id'];
        session_regenerate_id(true);
        redirect('dashboard.php');
    }

    $error = 'Fel användarnamn eller lösenord.';
}

require __DIR__ . '/../includes/header.php';
?>
<section class="card stack">
    <?php if ($error): ?><div class="message error"><?= e($error) ?></div><?php endif; ?>
    <form class="form-grid" method="post">
        <label>Användarnamn
            <input name="username" autocomplete="username" required>
        </label>
        <label>Lösenord
            <input type="password" name="password" autocomplete="current-password" required>
        </label>
        <button type="submit">Logga in</button>
    </form>
    <a class="muted" href="register.php">Skapa nytt konto</a>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
