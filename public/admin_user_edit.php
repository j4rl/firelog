<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$usersTable = db_table('users');
$stmt = $pdo->prepare("SELECT id, username, is_admin, birth_date FROM {$usersTable} WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();
if (!$user) {
    redirect('admin_users.php');
}

$page_title = 'Redigera användare';
$error = '';

if (is_post()) {
    $username = trim($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    $isAdmin = isset($_POST['is_admin']) ? 1 : 0;
    $birthDateInput = trim((string) ($_POST['birth_date'] ?? ''));
    $birthDate = normalize_birth_date($birthDateInput);

    if ($username === '') {
        $error = 'Användarnamn krävs.';
    } elseif ($password !== '' && strlen($password) < 8) {
        $error = 'Nytt lösenord måste vara minst 8 tecken.';
    } elseif ($birthDateInput !== '' && $birthDate === null) {
        $error = 'Ange ett giltigt födelsedatum eller lämna fältet tomt.';
    } elseif ($id === current_user_id() && $isAdmin === 0) {
        $error = 'Du kan inte ta bort adminrollen från kontot du är inloggad med.';
    } else {
        try {
            if ($password !== '') {
                $stmt = $pdo->prepare("UPDATE {$usersTable} SET username = ?, password_hash = ?, is_admin = ?, birth_date = ? WHERE id = ?");
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $isAdmin, $birthDate, $id]);
            } else {
                $stmt = $pdo->prepare("UPDATE {$usersTable} SET username = ?, is_admin = ?, birth_date = ? WHERE id = ?");
                $stmt->execute([$username, $isAdmin, $birthDate, $id]);
            }
            if ($id === current_user_id()) {
                $_SESSION['is_admin'] = (bool) $isAdmin;
            }
            redirect('admin_users.php');
        } catch (PDOException) {
            $error = 'Användarnamnet är upptaget eller kunde inte sparas.';
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>
<section class="card stack">
    <?php if ($error): ?><div class="message error"><?= e($error) ?></div><?php endif; ?>
    <form class="form-grid" method="post">
        <label>Användarnamn <input name="username" value="<?= e($user['username']) ?>" required></label>
        <label>Nytt lösenord <input type="password" name="password" autocomplete="new-password" minlength="8" placeholder="Lämna tomt för oförändrat"></label>
        <label>Födelsedatum <input type="date" name="birth_date" value="<?= e($user['birth_date'] ?? '') ?>"></label>
        <label class="check-row"><input type="checkbox" name="is_admin" value="1" <?= (int) $user['is_admin'] === 1 ? 'checked' : '' ?>> Adminanvändare</label>
        <div class="actions">
            <button type="submit">Spara användare</button>
            <a class="button secondary" href="admin_users.php">Avbryt</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
