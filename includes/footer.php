<?php
declare(strict_types=1);

$active_page = basename($_SERVER['SCRIPT_NAME'] ?? '');
$nav_items = [
    'dashboard.php' => 'Hem',
    'weapons.php' => 'Vapen',
    'session_new.php' => 'Skjut',
    'history.php' => 'Historik',
    'stats.php' => 'Stats',
];
?>
    </main>

    <?php if (is_logged_in()): ?>
        <nav class="bottom-nav" aria-label="Mobilnavigation">
            <?php foreach ($nav_items as $href => $label): ?>
                <a class="<?= $active_page === $href ? 'active' : '' ?>" href="<?= e($href) ?>"><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>
    <?php endif; ?>
</div>
<script src="../assets/app.js"></script>
</body>
</html>
