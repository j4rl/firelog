<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_logged_in()) {
    redirect(current_user_is_admin() ? 'admin.php' : 'dashboard.php');
}

$page_title = 'Skapa konto';
$error = '';
$usersTable = db_table('users');

if (is_post()) {
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || strlen($password) < 8) {
        $error = 'Ange användarnamn och minst 8 tecken långt lösenord.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO {$usersTable} (username, password_hash) VALUES (?, ?)");
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
            $_SESSION['user_id'] = (int) $pdo->lastInsertId();
            $_SESSION['is_admin'] = false;
            session_regenerate_id(true);
            redirect('dashboard.php');
        } catch (PDOException $exception) {
            $error = 'Användarnamnet är upptaget eller kunde inte sparas.';
        }
    }
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
            <input type="password" name="password" autocomplete="new-password" minlength="8" required>
        </label>
        <button type="submit">Skapa konto</button>
    </form>
    <a class="muted" href="login.php">Har du redan konto? Logga in</a>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
