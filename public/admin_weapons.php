<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$page_title = 'Vapen';
$message = '';
$error = '';
$weaponsTable = db_table('weapons');
$sessionsTable = db_table('shooting_sessions');
$seriesTable = db_table('series');
$usersTable = db_table('users');

if (is_post()) {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete') {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("DELETE FROM {$sessionsTable} WHERE weapon_id = ?");
            $stmt->execute([$id]);
            $stmt = $pdo->prepare("DELETE FROM {$weaponsTable} WHERE id = ?");
            $stmt->execute([$id]);
            $pdo->commit();
            $message = 'Vapnet och kopplade pass/serier togs bort.';
        } catch (Throwable) {
            $pdo->rollBack();
            $error = 'Vapnet kunde inte tas bort.';
        }
    }
}

$stmt = $pdo->query(
    'SELECT w.*, u.username, COUNT(DISTINCT ss.id) AS session_count, COUNT(DISTINCT s.id) AS series_count
     FROM ' . $weaponsTable . ' w
     JOIN ' . $usersTable . ' u ON u.id = w.user_id
     LEFT JOIN ' . $sessionsTable . ' ss ON ss.weapon_id = w.id
     LEFT JOIN ' . $seriesTable . ' s ON s.session_id = ss.id
     GROUP BY w.id, u.username
     ORDER BY u.username, w.manufacturer, w.model'
);
$weapons = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="stack">
    <?php if ($message): ?><div class="message ok"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?= e($error) ?></div><?php endif; ?>

    <div class="mobile-list list">
        <?php foreach ($weapons as $weapon): ?>
            <article class="list-item">
                <strong><?= e($weapon['manufacturer']) ?> <?= e($weapon['model']) ?></strong>
                <span class="meta"><?= e($weapon['username']) ?> · <?= e($weapon['caliber']) ?> · Klass <?= e($weapon['weapon_class']) ?></span>
                <span class="meta"><?= (int) $weapon['session_count'] ?> pass · <?= (int) $weapon['series_count'] ?> serier</span>
                <div class="actions">
                    <a class="button secondary" href="admin_weapon_edit.php?id=<?= (int) $weapon['id'] ?>">Redigera</a>
                    <form class="inline-form" method="post" onsubmit="return confirm('Ta bort vapnet och alla kopplade pass/serier?');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $weapon['id'] ?>">
                        <button class="danger" type="submit">Ta bort</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Ägare</th><th>Vapen</th><th>Kaliber</th><th>Klass</th><th>Data</th><th>Åtgärder</th></tr></thead>
            <tbody>
            <?php foreach ($weapons as $weapon): ?>
                <tr>
                    <td><?= e($weapon['username']) ?></td>
                    <td><?= e($weapon['manufacturer']) ?> <?= e($weapon['model']) ?></td>
                    <td><?= e($weapon['caliber']) ?></td>
                    <td><?= e($weapon['weapon_class']) ?></td>
                    <td><?= (int) $weapon['session_count'] ?> pass · <?= (int) $weapon['series_count'] ?> serier</td>
                    <td>
                        <div class="table-actions">
                            <a class="button secondary" href="admin_weapon_edit.php?id=<?= (int) $weapon['id'] ?>">Redigera</a>
                            <form class="inline-form" method="post" onsubmit="return confirm('Ta bort vapnet och alla kopplade pass/serier?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int) $weapon['id'] ?>">
                                <button class="danger" type="submit">Ta bort</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
