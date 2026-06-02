<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$page_title = 'Nytt skjutpass';
$stmt = $pdo->prepare('SELECT id, manufacturer, model, caliber FROM weapons WHERE user_id = ? ORDER BY manufacturer, model');
$stmt->execute([current_user_id()]);
$weapons = $stmt->fetchAll();
$error = '';

if (is_post() && $weapons) {
    $weaponId = (int) ($_POST['weapon_id'] ?? 0);
    $validWeapon = false;
    foreach ($weapons as $weapon) {
        if ((int) $weapon['id'] === $weaponId) {
            $validWeapon = true;
            break;
        }
    }

    if (!$validWeapon) {
        $error = 'Välj ett av dina registrerade vapen.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO shooting_sessions (user_id, weapon_id, session_date, location, discipline, distance_meters, notes) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            current_user_id(),
            $weaponId,
            $_POST['session_date'] ?: date('Y-m-d'),
            trim($_POST['location'] ?? ''),
            trim($_POST['discipline'] ?? ''),
            max(0, (int) ($_POST['distance_meters'] ?? 0)),
            trim($_POST['notes'] ?? ''),
        ]);
        redirect('shoot.php?session_id=' . (int) $pdo->lastInsertId());
    }
}

require __DIR__ . '/../includes/header.php';
?>
<div class="stack">
    <?php if (!$weapons): ?>
        <section class="card stack">
            <p class="muted">Lägg till ett vapen innan du skapar ditt första skjutpass.</p>
            <a class="button" href="weapon_add.php">Lägg till vapen</a>
        </section>
    <?php else: ?>
        <section class="card stack">
            <?php if ($error): ?><div class="message error"><?= e($error) ?></div><?php endif; ?>
            <form class="form-grid two" method="post">
                <label>Datum <input type="date" name="session_date" value="<?= e(date('Y-m-d')) ?>" required></label>
                <label>Plats <input name="location" required></label>
                <label>Disciplin <input name="discipline" required></label>
                <label>Avstånd, meter <input type="number" name="distance_meters" min="0" inputmode="numeric" required></label>
                <label>Vapen
                    <select name="weapon_id" required>
                        <?php foreach ($weapons as $weapon): ?>
                            <option value="<?= (int) $weapon['id'] ?>"><?= e($weapon['manufacturer']) ?> <?= e($weapon['model']) ?>, <?= e($weapon['caliber']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Anteckningar <textarea name="notes"></textarea></label>
                <button type="submit">Starta pass</button>
            </form>
        </section>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
