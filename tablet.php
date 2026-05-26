<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$queue = array_values(array_filter($app['storage']->getQueue(), static function (array $entry): bool {
    return in_array($entry['status'] ?? 'waiting', ['waiting', 'notified', 'arrived', 'in_chair'], true);
}));

$appointments = $app['storage']->getAppointments();
$services = $app['storage']->getServices();
$settings = $app['storage']->getSettings();
$activeBarbers = (int) ($settings['active_barbers_override'] ?? $app['config']['active_barbers']);
$current = null;
$next = null;

foreach ($queue as $entry) {
    if (($entry['status'] ?? '') === 'in_chair') {
        $current = $entry;
        break;
    }
}

$waitingOnly = array_values(array_filter($queue, fn (array $q): bool => in_array($q['status'] ?? '', ['waiting', 'notified', 'arrived'], true)));
$next = $waitingOnly[0] ?? null;
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Queue Display</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="tablet-display">
<main class="container">
    <h1><?= e($app['config']['shop_name']) ?> Queue</h1>

    <div class="card-grid">
        <section class="card">
            <h2>Current Customer</h2>
            <p><?= $current ? e((string) $current['name']) : '—' ?></p>
        </section>
        <section class="card">
            <h2>Next Customer</h2>
            <p><?= $next ? e((string) $next['name']) : '—' ?></p>
        </section>
    </div>

    <section class="card mt-16">
        <h2>Waiting List</h2>
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Service</th>
                <th>Status</th>
                <th>Approx Wait</th>
            </tr>
            </thead>
            <tbody>
            <?php if ($waitingOnly === []): ?>
                <tr><td colspan="4">No one is waiting.</td></tr>
            <?php endif; ?>
            <?php foreach ($waitingOnly as $index => $entry): ?>
                <tr>
                    <td><?= e((string) ($entry['name'] ?? 'Guest')) ?></td>
                    <td><?= e((string) ($entry['service'] ?? '')) ?></td>
                    <td><?= e((string) ($entry['status'] ?? 'waiting')) ?></td>
                    <td><?= $app['waitTimeService']->estimatePositionWaitMinutes($waitingOnly, $index, $appointments, $services, $activeBarbers) ?> min</td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p class="small">Auto-refreshes every 30 seconds.</p>
    </section>
</main>
<script src="/assets/js/app.js"></script>
</body>
</html>
