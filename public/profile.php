<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_shooter();

$userId = current_user_id();
$page_title = 'Profil';
$message = '';
$error = '';
$usersTable = db_table('users');

$stmt = $pdo->prepare("SELECT username, birth_date FROM {$usersTable} WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    redirect('logout.php');
}

if (is_post()) {
    $birthDateInput = trim((string) ($_POST['birth_date'] ?? ''));
    $birthDate = normalize_birth_date($birthDateInput);

    if ($birthDateInput !== '' && $birthDate === null) {
        $error = 'Ange ett giltigt födelsedatum eller lämna fältet tomt.';
    } else {
        $stmt = $pdo->prepare("UPDATE {$usersTable} SET birth_date = ? WHERE id = ?");
        $stmt->execute([$birthDate, $userId]);
        $user['birth_date'] = $birthDate;
        $message = 'Profilen sparades.';
    }
}

$currentAge = age_on_date($user['birth_date'] ?? null, date('Y-m-d'));

require __DIR__ . '/../includes/header.php';
?>
<section class="card stack">
    <?php if ($message): ?><div class="message ok"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?= e($error) ?></div><?php endif; ?>

    <form class="form-grid" method="post">
        <label>Användarnamn
            <input value="<?= e($user['username']) ?>" disabled>
        </label>
        <label>Födelsedatum
            <input type="date" name="birth_date" value="<?= e($user['birth_date'] ?? '') ?>">
        </label>
        <p class="muted">
            <?= $currentAge !== null
                ? 'Ålder räknas automatiskt från profilen när du skapar nya skjutpass. Nuvarande ålder: ' . (int) $currentAge . ' år.'
                : 'Om födelsedatum saknas används ingen ålder för märkesserier.' ?>
        </p>
        <button type="submit">Spara profil</button>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
