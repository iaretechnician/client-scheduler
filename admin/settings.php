<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

if (is_post()) {
    $app['appointmentService']->saveSettings($_POST);
    redirect_to('/admin/settings.php?saved=1');
}

$settings = $app['storage']->getSettings();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Settings</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<main class="container">
    <h1>Settings</h1>
    <p><a href="/admin/index.php">← Dashboard</a></p>

    <?php if (!empty($_GET['saved'])): ?><div class="alert" style="background:#052e16;color:#bbf7d0">Settings saved.</div><?php endif; ?>

    <form method="post" class="card form-grid">
        <label class="checkbox">
            <input type="checkbox" name="pause_online_checkins" value="1" <?= !empty($settings['pause_online_checkins']) ? 'checked' : '' ?>>
            Pause online check-ins
        </label>
        <label>Active Barbers
            <input type="number" name="active_barbers_override" min="1" value="<?= (int) ($settings['active_barbers_override'] ?? $app['config']['active_barbers']) ?>">
        </label>
        <button type="submit">Save Settings</button>
    </form>
</main>
</body>
</html>
