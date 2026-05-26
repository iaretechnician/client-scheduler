<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$data = $app['appointmentService']->getDashboardData();
$waiting = array_values(array_filter($data['queue'], static fn (array $q): bool => in_array($q['status'] ?? '', ['waiting', 'notified', 'arrived', 'in_chair'], true)));
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<main class="container">
    <h1>Admin Dashboard</h1>
    <p>
        <a href="/admin/queue.php">Queue</a> |
        <a href="/admin/appointments.php">Appointments</a> |
        <a href="/admin/customers.php">Customers</a> |
        <a href="/admin/settings.php">Settings</a> |
        <a href="/admin/logout.php">Log out</a>
    </p>

    <div class="card-grid">
        <section class="card"><h2>Currently Waiting</h2><p><?= count($waiting) ?></p></section>
        <section class="card"><h2>Today's Appointments</h2><p><?= count($data['appointments_today']) ?></p></section>
        <section class="card"><h2>Completed</h2><p><?= count($data['completed']) ?></p></section>
        <section class="card"><h2>No-shows/Cancellations</h2><p><?= count($data['cancelled_or_no_show']) ?></p></section>
    </div>
</main>
</body>
</html>
