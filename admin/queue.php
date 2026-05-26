<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$services = $app['storage']->getServices();
$barbers = $app['config']['barbers'];

if (is_post()) {
    $action = sanitize_string($_POST['action'] ?? '');
    if ($action === 'update') {
        $id = sanitize_string($_POST['id'] ?? '');
        $app['appointmentService']->updateQueueEntry($id, $_POST);

        $status = sanitize_string($_POST['status'] ?? '');
        $name = sanitize_string($_POST['name'] ?? '');
        $phone = sanitize_phone($_POST['phone'] ?? '');
        if ($status === 'notified') {
            $app['notificationService']->sendWaitAlmostReady($phone, $name, 10);
        }
        if ($status === 'arrived' || $status === 'in_chair') {
            $app['notificationService']->sendBarberReady($phone, $name);
        }
    }

    if ($action === 'add_walkin') {
        $entry = $app['appointmentService']->createWalkIn($_POST, true);
        $app['customerService']->saveVisit($_POST, 'walkin', $entry['id']);
    }

    redirect_to('/admin/queue.php');
}

$queue = $app['storage']->getQueue();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Queue</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<main class="container">
    <h1>Queue Management</h1>
    <p><a href="/admin/index.php">← Dashboard</a></p>

    <section class="card">
        <h2>Manually Add Walk-In</h2>
        <form method="post" class="form-grid">
            <input type="hidden" name="action" value="add_walkin">
            <label>Name <input type="text" name="name" required></label>
            <label>Phone <input type="tel" name="phone" required></label>
            <label>Email <input type="email" name="email" required></label>
            <label>Service
                <select name="service" required>
                    <?php foreach ($services as $key => $service): ?><option value="<?= e($key) ?>"><?= e($service['label']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Preferred Barber
                <select name="preferred_barber">
                    <?php foreach ($barbers as $barber): ?><option value="<?= e($barber) ?>"><?= e($barber) ?></option><?php endforeach; ?>
                </select>
            </label>
            <label>Party Size <input type="number" name="party_size" min="1" value="1"></label>
            <button type="submit">Add Walk-In</button>
        </form>
    </section>

    <section class="card mt-16">
        <h2>Current Queue</h2>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Name</th><th>Phone</th><th>Service</th><th>Status</th><th>Barber</th><th>Duration</th><th>Action</th>
                </tr>
                </thead>
                <tbody>
                <?php if ($queue === []): ?><tr><td colspan="7">Queue is empty.</td></tr><?php endif; ?>
                <?php foreach ($queue as $row): ?>
                    <tr>
                        <form method="post">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="id" value="<?= e((string) ($row['id'] ?? '')) ?>">
                            <td><input type="text" name="name" value="<?= e((string) ($row['name'] ?? '')) ?>"></td>
                            <td><input type="tel" name="phone" value="<?= e((string) ($row['phone'] ?? '')) ?>"></td>
                            <td>
                                <select name="service">
                                    <?php foreach ($services as $key => $service): ?>
                                        <option value="<?= e($key) ?>" <?= (($row['service'] ?? '') === $key) ? 'selected' : '' ?>><?= e($service['label']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="status">
                                    <?php foreach (['waiting','notified','arrived','in_chair','completed','cancelled','no_show'] as $status): ?>
                                        <option value="<?= e($status) ?>" <?= (($row['status'] ?? '') === $status) ? 'selected' : '' ?>><?= e($status) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select name="assigned_barber">
                                    <option value="">Unassigned</option>
                                    <?php foreach ($barbers as $barber): ?>
                                        <option value="<?= e($barber) ?>" <?= (($row['assigned_barber'] ?? '') === $barber) ? 'selected' : '' ?>><?= e($barber) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="preferred_barber" value="<?= e((string) ($row['preferred_barber'] ?? 'Any')) ?>">
                            </td>
                            <td><input type="number" name="service_duration" min="5" value="<?= (int) ($row['service_duration'] ?? 30) ?>"></td>
                            <td><button type="submit">Save</button></td>
                        </form>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
</body>
</html>
