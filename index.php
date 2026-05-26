<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$settings = $app['storage']->getSettings();
$services = $app['storage']->getServices();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($app['config']['shop_name']) ?> - Home</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<main class="container">
    <h1><?= e($app['config']['shop_name']) ?></h1>
    <p>Welcome! Check in as a walk-in or book a scheduled appointment.</p>

    <?php if (!empty($settings['pause_online_checkins'])): ?>
        <div class="alert alert-warning">Online check-ins are currently paused by staff.</div>
    <?php endif; ?>

    <div class="card-grid">
        <a class="card" href="/checkin.php">
            <h2>Walk-In Check-In</h2>
            <p>Join the live waiting queue from your phone.</p>
        </a>
        <a class="card" href="/appointment.php">
            <h2>Book Appointment</h2>
            <p>Reserve an available time slot.</p>
        </a>
        <a class="card" href="/tablet.php">
            <h2>Waiting Room Display</h2>
            <p>Public queue board view.</p>
        </a>
    </div>

    <section class="card mt-16">
        <h2>Shop Information</h2>
        <p><strong>Address:</strong> <?= e($app['config']['address']) ?></p>
        <p><strong>Phone:</strong> <?= e($app['config']['phone']) ?></p>
        <p><strong>Directions:</strong> <a href="<?= e($app['config']['directions_url']) ?>" target="_blank" rel="noopener">Open Google Maps</a></p>
        <h3>Hours</h3>
        <ul>
            <?php foreach ($app['config']['business_hours'] as $day => $hours): ?>
                <li><strong><?= e($day) ?>:</strong> <?= e($hours) ?></li>
            <?php endforeach; ?>
        </ul>
        <h3>Services</h3>
        <ul>
            <?php foreach ($services as $key => $service): ?>
                <li><?= e($service['label']) ?> (<?= (int) $service['duration'] ?> min)</li>
            <?php endforeach; ?>
        </ul>
    </section>
</main>
<script src="/assets/js/app.js"></script>
</body>
</html>
