<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

$page_title = 'Användare';
$message = '';
$error = '';
$usersTable = db_table('users');
$weaponsTable = db_table('weapons');
$sessionsTable = db_table('shooting_sessions');
$seriesTable = db_table('series');

if (is_post()) {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete') {
        if ($id === current_user_id()) {
            $error = 'Du kan inte ta bort det konto du är inloggad med.';
        } else {
            $stmt = $pdo->prepare("DELETE FROM {$usersTable} WHERE id = ?");
            $stmt->execute([$id]);
            $message = 'Användaren togs bort.';
        }
    }
}

$stmt = $pdo->query(
    'SELECT u.id, u.username, u.is_admin, u.created_at,
            COUNT(DISTINCT w.id) AS weapon_count,
            COUNT(DISTINCT ss.id) AS session_count,
            COUNT(DISTINCT s.id) AS series_count
     FROM ' . $usersTable . ' u
     LEFT JOIN ' . $weaponsTable . ' w ON w.user_id = u.id
     LEFT JOIN ' . $sessionsTable . ' ss ON ss.user_id = u.id
     LEFT JOIN ' . $seriesTable . ' s ON s.session_id = ss.id
     GROUP BY u.id, u.username, u.is_admin, u.created_at
     ORDER BY u.is_admin DESC, u.username'
);
$users = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="stack">
    <?php if ($message): ?><div class="message ok"><?= e($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?= e($error) ?></div><?php endif; ?>

    <div class="mobile-list list">
        <?php foreach ($users as $user): ?>
            <article class="list-item">
                <strong class="item-title"><?= e($user['username']) ?><?= (int) $user['is_admin'] === 1 ? '<span class="badge">Admin</span>' : '' ?></strong>
                <span class="meta"><?= (int) $user['weapon_count'] ?> vapen · <?= (int) $user['session_count'] ?> pass · <?= (int) $user['series_count'] ?> serier</span>
                <div class="actions">
                    <a class="button secondary" href="admin_user_edit.php?id=<?= (int) $user['id'] ?>">Redigera</a>
                    <?php if ((int) $user['id'] !== current_user_id()): ?>
                        <form class="inline-form" method="post" onsubmit="return confirm('Ta bort användaren och all kopplad data?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                            <button class="danger" type="submit">Ta bort</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Användare</th><th>Roll</th><th>Data</th><th>Skapad</th><th>Åtgärder</th></tr></thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= e($user['username']) ?></td>
                    <td><?= (int) $user['is_admin'] === 1 ? 'Admin' : 'Skytt' ?></td>
                    <td><?= (int) $user['weapon_count'] ?> vapen · <?= (int) $user['session_count'] ?> pass · <?= (int) $user['series_count'] ?> serier</td>
                    <td><?= e($user['created_at']) ?></td>
                    <td>
                        <div class="table-actions">
                            <a class="button secondary" href="admin_user_edit.php?id=<?= (int) $user['id'] ?>">Redigera</a>
                            <?php if ((int) $user['id'] !== current_user_id()): ?>
                                <form class="inline-form" method="post" onsubmit="return confirm('Ta bort användaren och all kopplad data?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                    <button class="danger" type="submit">Ta bort</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
