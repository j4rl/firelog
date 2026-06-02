<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_login();

$page_title = 'Vapen';
$stmt = $pdo->prepare('SELECT id, manufacturer, model, caliber, weapon_class FROM weapons WHERE user_id = ? ORDER BY manufacturer, model');
$stmt->execute([current_user_id()]);
$weapons = $stmt->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<div class="stack">
    <div class="actions"><a class="button" href="weapon_add.php">Lägg till vapen</a></div>
    <?php if (!$weapons): ?>
        <section class="card"><p class="muted">Inga vapen är registrerade ännu.</p></section>
    <?php endif; ?>
    <div class="list">
        <?php foreach ($weapons as $weapon): ?>
            <article class="list-item">
                <strong><?= e($weapon['manufacturer']) ?> <?= e($weapon['model']) ?></strong>
                <span class="meta"><?= e($weapon['caliber']) ?> · Klass <?= e($weapon['weapon_class']) ?></span>
                <a class="button secondary" href="weapon_edit.php?id=<?= (int) $weapon['id'] ?>">Redigera</a>
            </article>
        <?php endforeach; ?>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
