<?php
declare(strict_types=1);

$active_page = basename($_SERVER['SCRIPT_NAME'] ?? '');
$nav_items = current_user_is_admin()
    ? [
        'admin.php' => 'Admin',
        'admin_users.php' => 'Användare',
        'admin_weapons.php' => 'Vapen',
        'admin_series.php' => 'Serier',
    ]
    : [
        'dashboard.php' => 'Hem',
        'weapons.php' => 'Vapen',
        'session_new.php' => 'Skjut',
        'history.php' => 'Historik',
        'stats.php' => 'Stats',
        'profile.php' => 'Profil',
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
<script src="assets/app.js"></script>
</body>
</html>
