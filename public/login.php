<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    redirect(current_user_is_admin() ? 'admin.php' : 'dashboard.php');
}

$page_title = 'Logga in';
$error = '';
$usersTable = db_table('users');

if (is_post()) {
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    $stmt = $pdo->prepare("SELECT id, password_hash, is_admin FROM {$usersTable} WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['is_admin'] = (bool) $user['is_admin'];
        session_regenerate_id(true);
        redirect($_SESSION['is_admin'] ? 'admin.php' : 'dashboard.php');
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
