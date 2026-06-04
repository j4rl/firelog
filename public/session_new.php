<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_shooter();

$page_title = 'Nytt skjutpass';
$weaponsTable = db_table('weapons');
$sessionsTable = db_table('shooting_sessions');
$usersTable = db_table('users');

$stmt = $pdo->prepare("SELECT id, manufacturer, model, caliber FROM {$weaponsTable} WHERE user_id = ? ORDER BY manufacturer, model");
$stmt->execute([current_user_id()]);
$weapons = $stmt->fetchAll();

$disciplineStmt = $pdo->prepare("SELECT DISTINCT discipline FROM {$sessionsTable} WHERE user_id = ? AND discipline <> '' ORDER BY discipline");
$disciplineStmt->execute([current_user_id()]);
$disciplineOptions = array_values(array_unique(array_merge(default_disciplines(), array_column($disciplineStmt->fetchAll(), 'discipline'))));

$latestSessionStmt = $pdo->prepare("SELECT location, discipline, distance_meters FROM {$sessionsTable} WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 1");
$latestSessionStmt->execute([current_user_id()]);
$lastSession = $latestSessionStmt->fetch();
$locationValue = is_post()
    ? trim((string) ($_POST['location'] ?? ''))
    : (string) ($lastSession['location'] ?? '');
$disciplineValue = is_post()
    ? trim((string) ($_POST['discipline'] ?? ''))
    : (string) ($lastSession['discipline'] ?? '');
$lastDistance = $lastSession['distance_meters'] ?? null;
$distanceValue = is_post()
    ? trim((string) ($_POST['distance_meters'] ?? ''))
    : ($lastDistance !== null ? (string) $lastDistance : '');

$profileStmt = $pdo->prepare("SELECT birth_date FROM {$usersTable} WHERE id = ?");
$profileStmt->execute([current_user_id()]);
$profile = $profileStmt->fetch();
$profileBirthDate = $profile['birth_date'] ?? null;
$profileAgeToday = age_on_date($profileBirthDate, date('Y-m-d'));

$error = '';

if (is_post() && $weapons) {
    $weaponId = (int) ($_POST['weapon_id'] ?? 0);
    $sessionDate = trim((string) ($_POST['session_date'] ?? ''));
    $sessionDate = $sessionDate !== '' ? $sessionDate : date('Y-m-d');
    $shooterAge = age_on_date($profileBirthDate, $sessionDate);
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
        $stmt = $pdo->prepare("INSERT INTO {$sessionsTable} (user_id, weapon_id, session_date, location, discipline, distance_meters, shooter_age, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            current_user_id(),
            $weaponId,
            $sessionDate,
            $locationValue,
            $disciplineValue,
            max(0, (int) $distanceValue),
            $shooterAge,
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
                <label>Plats <input name="location" value="<?= e($locationValue) ?>" required></label>
                <label>Disciplin
                    <input name="discipline" list="discipline-options" autocomplete="off" value="<?= e($disciplineValue) ?>" required>
                    <datalist id="discipline-options">
                        <?php foreach ($disciplineOptions as $discipline): ?>
                            <option value="<?= e($discipline) ?>"></option>
                        <?php endforeach; ?>
                    </datalist>
                </label>
                <label>Avstånd, meter <input type="number" name="distance_meters" min="0" inputmode="numeric" value="<?= e($distanceValue) ?>" required></label>
                <label>Vapen
                    <select name="weapon_id" required>
                        <?php foreach ($weapons as $weapon): ?>
                            <option value="<?= (int) $weapon['id'] ?>"><?= e($weapon['manufacturer']) ?> <?= e($weapon['model']) ?>, <?= e($weapon['caliber']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <p class="muted">
                    <?= $profileAgeToday !== null
                        ? 'Ålder för märkesserier hämtas automatiskt från profilen: ' . (int) $profileAgeToday . ' år i dag.'
                        : 'Ingen ålder används för märkesserier. Lägg till födelsedatum i profilen om ålder ska räknas automatiskt.' ?>
                </p>
                <label>Anteckningar <textarea name="notes"></textarea></label>
                <button type="submit">Starta pass</button>
            </form>
        </section>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
