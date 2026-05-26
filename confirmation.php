<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$type = sanitize_string($_GET['type'] ?? '');
$id = sanitize_string($_GET['id'] ?? '');
$record = null;

if ($type === 'checkin') {
    foreach ($app['storage']->getQueue() as $row) {
        if (($row['id'] ?? '') === $id) {
            $record = $row;
            break;
        }
    }
}

if ($type === 'appointment') {
    foreach ($app['storage']->getAppointments() as $row) {
        if (($row['id'] ?? '') === $id) {
            $record = $row;
            break;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirmation</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<main class="container">
    <h1>Confirmation</h1>
    <p><a href="/">← Return home</a></p>

    <?php if (!$record): ?>
        <div class="alert alert-error">Record not found.</div>
    <?php else: ?>
        <section class="card">
            <h2>Thanks, <?= e($record['name'] ?? 'Guest') ?>!</h2>
            <p><strong>Reference:</strong> <?= e((string) ($record['id'] ?? '')) ?></p>
            <p><strong>Service:</strong> <?= e((string) ($record['service'] ?? '')) ?></p>

            <?php if ($type === 'checkin'): ?>
                <p><strong>Status:</strong> <?= e((string) ($record['status'] ?? 'waiting')) ?></p>
                <p><strong>Estimated Wait:</strong> <?= (int) ($record['estimated_wait_minutes'] ?? 0) ?> minutes</p>
            <?php else: ?>
                <p><strong>Appointment Time:</strong> <?= e(format_datetime((string) ($record['appointment_time'] ?? ''))) ?></p>
            <?php endif; ?>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
