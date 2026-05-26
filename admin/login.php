<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_admin_logged_in()) {
    redirect_to('/admin/index.php');
}

$error = '';
if (is_post()) {
    $username = sanitize_string($_POST['username'] ?? '');
    $password = (string) ($_POST['password'] ?? '');
    if (admin_login($username, $password, $app['config'])) {
        redirect_to('/admin/index.php');
    }
    $error = 'Invalid login.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<main class="container">
    <h1>Admin Login</h1>
    <?php if ($error !== ''): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <form method="post" class="card form-grid">
        <label>Username <input type="text" name="username" required></label>
        <label>Password <input type="password" name="password" required></label>
        <button type="submit">Sign In</button>
    </form>
    <p class="small">Default development password is <code>change-me-now</code>. Change <code>admin.password_hash</code> in <code>/config/shop.php</code> immediately.</p>
</main>
</body>
</html>
