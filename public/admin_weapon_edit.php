<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$id = (int) ($_GET['id'] ?? 0);
$weaponsTable = db_table('weapons');
$usersTable = db_table('users');
$stmt = $pdo->prepare(
    'SELECT w.*, u.username
     FROM ' . $weaponsTable . ' w
     JOIN ' . $usersTable . ' u ON u.id = w.user_id
     WHERE w.id = ?'
);
$stmt->execute([$id]);
$weapon = $stmt->fetch();
if (!$weapon) {
    redirect('admin_weapons.php');
}

$page_title = 'Redigera vapen';
$error = '';

if (is_post()) {
    $class = $_POST['weapon_class'] ?? '';
    if (!valid_weapon_class($class)) {
        $error = 'Välj klass A, B, C eller R.';
    } else {
        $stmt = $pdo->prepare("UPDATE {$weaponsTable} SET manufacturer = ?, model = ?, caliber = ?, serial_number = ?, weapon_class = ?, notes = ? WHERE id = ?");
        $stmt->execute([
            trim($_POST['manufacturer'] ?? ''),
            trim($_POST['model'] ?? ''),
            trim($_POST['caliber'] ?? ''),
            trim($_POST['serial_number'] ?? ''),
            $class,
            trim($_POST['notes'] ?? ''),
            $id,
        ]);
        redirect('admin_weapons.php');
    }
}

require __DIR__ . '/../includes/header.php';
?>
<section class="card stack">
    <p class="muted">Ägare: <?= e($weapon['username']) ?></p>
    <?php if ($error): ?><div class="message error"><?= e($error) ?></div><?php endif; ?>
    <form class="form-grid two" method="post">
        <label>Fabrikat <input name="manufacturer" value="<?= e($weapon['manufacturer']) ?>" required></label>
        <label>Modell <input name="model" value="<?= e($weapon['model']) ?>" required></label>
        <label>Kaliber <input name="caliber" value="<?= e($weapon['caliber']) ?>" required></label>
        <label>Serienummer <input name="serial_number" value="<?= e($weapon['serial_number']) ?>" required></label>
        <label>Klass
            <select name="weapon_class" required>
                <?php foreach (['A', 'B', 'C', 'R'] as $class): ?>
                    <option value="<?= $class ?>" <?= $weapon['weapon_class'] === $class ? 'selected' : '' ?>><?= $class ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Anteckningar <textarea name="notes"><?= e($weapon['notes'] ?? '') ?></textarea></label>
        <div class="actions">
            <button type="submit">Spara vapen</button>
            <a class="button secondary" href="admin_weapons.php">Avbryt</a>
        </div>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
