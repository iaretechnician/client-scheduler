<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$services = $app['storage']->getServices();
$barbers = $app['config']['barbers'];
$error = '';

if (is_post()) {
    $action = sanitize_string($_POST['action'] ?? '');

    if ($action === 'add_appointment') {
        try {
            $entry = $app['appointmentService']->createAppointment($_POST, true);
            $app['customerService']->saveVisit($_POST, 'appointment', $entry['id']);
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }

    if ($action === 'update') {
        $id = sanitize_string($_POST['id'] ?? '');
        $app['appointmentService']->updateAppointment($id, $_POST);
    }

    if ($action === 'block_slot') {
        $app['appointmentService']->addBlockedSlot($_POST);
    }

    if ($action === 'remove_block') {
        $app['appointmentService']->removeBlockedSlot(sanitize_string($_POST['id'] ?? ''));
    }

    if ($error === '') {
        redirect_to('/admin/appointments.php');
    }
}

$today = (new DateTimeImmutable())->format('Y-m-d');
$appointments = array_values(array_filter($app['storage']->getAppointments(), static fn (array $a): bool => str_starts_with((string) ($a['appointment_time'] ?? ''), $today)));
$settings = $app['storage']->getSettings();
$blockedSlots = $settings['blocked_slots'] ?? [];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Appointments</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<main class="container">
    <h1>Appointments</h1>
    <p><a href="/admin/index.php">← Dashboard</a></p>

    <?php if ($error !== ''): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

    <section class="card">
        <h2>Add Appointment</h2>
        <form method="post" class="form-grid">
            <input type="hidden" name="action" value="add_appointment">
            <label>Name <input type="text" name="name" required></label>
            <label>Phone <input type="tel" name="phone" required></label>
            <label>Email <input type="email" name="email" required></label>
            <label>Service
                <select name="service" required><?php foreach ($services as $key => $service): ?><option value="<?= e($key) ?>"><?= e($service['label']) ?></option><?php endforeach; ?></select>
            </label>
            <label>Preferred Barber
                <select name="preferred_barber"><?php foreach ($barbers as $barber): ?><option value="<?= e($barber) ?>"><?= e($barber) ?></option><?php endforeach; ?></select>
            </label>
            <label>Appointment Time <input type="datetime-local" name="appointment_time" required></label>
            <button type="submit">Create Appointment</button>
        </form>
    </section>

    <section class="card mt-16">
        <h2>Today's Appointments</h2>
        <table>
            <thead><tr><th>Name</th><th>Time</th><th>Service</th><th>Status</th><th>Assigned</th><th>Duration</th><th>Action</th></tr></thead>
            <tbody>
            <?php if ($appointments === []): ?><tr><td colspan="7">No appointments today.</td></tr><?php endif; ?>
            <?php foreach ($appointments as $row): ?>
                <tr>
                    <form method="post">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= e((string) ($row['id'] ?? '')) ?>">
                        <td><input type="text" name="name" value="<?= e((string) ($row['name'] ?? '')) ?>"></td>
                        <td><?= e(format_datetime((string) ($row['appointment_time'] ?? ''))) ?></td>
                        <td>
                            <select name="service"><?php foreach ($services as $key => $service): ?><option value="<?= e($key) ?>" <?= (($row['service'] ?? '') === $key) ? 'selected' : '' ?>><?= e($service['label']) ?></option><?php endforeach; ?></select>
                        </td>
                        <td>
                            <select name="status"><?php foreach (['scheduled','notified','arrived','in_chair','completed','cancelled','no_show'] as $status): ?><option value="<?= e($status) ?>" <?= (($row['status'] ?? '') === $status) ? 'selected' : '' ?>><?= e($status) ?></option><?php endforeach; ?></select>
                        </td>
                        <td>
                            <select name="assigned_barber">
                                <option value="">Unassigned</option>
                                <?php foreach ($barbers as $barber): ?><option value="<?= e($barber) ?>" <?= (($row['assigned_barber'] ?? '') === $barber) ? 'selected' : '' ?>><?= e($barber) ?></option><?php endforeach; ?>
                            </select>
                            <input type="hidden" name="preferred_barber" value="<?= e((string) ($row['preferred_barber'] ?? 'Any')) ?>">
                        </td>
                        <td><input type="number" min="5" name="service_duration" value="<?= (int) ($row['service_duration'] ?? 30) ?>"></td>
                        <td><button type="submit">Save</button></td>
                    </form>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <section class="card mt-16">
        <h2>Block Time Slots</h2>
        <form method="post" class="form-grid">
            <input type="hidden" name="action" value="block_slot">
            <label>Start <input type="datetime-local" name="start" required></label>
            <label>End <input type="datetime-local" name="end" required></label>
            <label>Barber
                <select name="barber"><?php foreach ($barbers as $barber): ?><option value="<?= e($barber) ?>"><?= e($barber) ?></option><?php endforeach; ?></select>
            </label>
            <label>Reason <input type="text" name="reason" placeholder="Lunch, break, unavailable"></label>
            <button type="submit">Add Block</button>
        </form>

        <h3 class="mt-16">Existing Blocks</h3>
        <table>
            <thead><tr><th>Start</th><th>End</th><th>Barber</th><th>Reason</th><th>Action</th></tr></thead>
            <tbody>
            <?php if ($blockedSlots === []): ?><tr><td colspan="5">No blocked slots.</td></tr><?php endif; ?>
            <?php foreach ($blockedSlots as $slot): ?>
                <tr>
                    <td><?= e(format_datetime((string) ($slot['start'] ?? ''))) ?></td>
                    <td><?= e(format_datetime((string) ($slot['end'] ?? ''))) ?></td>
                    <td><?= e((string) ($slot['barber'] ?? 'Any')) ?></td>
                    <td><?= e((string) ($slot['reason'] ?? '')) ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="action" value="remove_block">
                            <input type="hidden" name="id" value="<?= e((string) ($slot['id'] ?? '')) ?>">
                            <button type="submit">Remove</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
