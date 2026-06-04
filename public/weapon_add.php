<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_shooter();

$page_title = 'Lägg till vapen';
$error = '';
$weaponsTable = db_table('weapons');

if (is_post()) {
    $class = $_POST['weapon_class'] ?? '';
    if (!valid_weapon_class($class)) {
        $error = 'Välj klass A, B, C eller R.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO {$weaponsTable} (user_id, manufacturer, model, caliber, serial_number, weapon_class, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            current_user_id(),
            trim($_POST['manufacturer'] ?? ''),
            trim($_POST['model'] ?? ''),
            trim($_POST['caliber'] ?? ''),
            trim($_POST['serial_number'] ?? ''),
            $class,
            trim($_POST['notes'] ?? ''),
        ]);
        redirect('weapons.php');
    }
}

require __DIR__ . '/../includes/header.php';
?>
<section class="card stack">
    <?php if ($error): ?><div class="message error"><?= e($error) ?></div><?php endif; ?>
    <form class="form-grid two" method="post">
        <label>Fabrikat <input name="manufacturer" required></label>
        <label>Modell <input name="model" required></label>
        <label>Kaliber <input name="caliber" required></label>
        <label>Serienummer <input name="serial_number" required></label>
        <label>Klass
            <select name="weapon_class" required>
                <option value="A">A</option><option value="B">B</option><option value="C">C</option><option value="R">R</option>
            </select>
        </label>
        <label>Anteckningar <textarea name="notes"></textarea></label>
        <button type="submit">Spara vapen</button>
    </form>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
