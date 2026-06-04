<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

redirect(is_logged_in() ? (current_user_is_admin() ? 'admin.php' : 'dashboard.php') : 'login.php');
