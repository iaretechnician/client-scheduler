<?php

declare(strict_types=1);
require_once __DIR__ . '/includes/bootstrap.php';

$errors = [];
$created = null;
$services = $app['storage']->getServices();
$barbers = $app['config']['barbers'];

if ($app['appointmentService']->onlineCheckinsPaused()) {
    $errors[] = 'Online check-ins are currently paused. Please call the shop.';
}

if (is_post() && $errors === []) {
    $payload = [
        'name' => sanitize_string($_POST['name'] ?? ''),
        'phone' => sanitize_phone($_POST['phone'] ?? ''),
        'email' => sanitize_email($_POST['email'] ?? ''),
        'service' => sanitize_string($_POST['service'] ?? ''),
        'preferred_barber' => sanitize_string($_POST['preferred_barber'] ?? 'Any'),
        'party_size' => max(1, (int) ($_POST['party_size'] ?? 1)),
        'marketing_opt_in' => !empty($_POST['marketing_opt_in']),
        'sms_opt_in' => !empty($_POST['sms_opt_in']),
    ];

    if ($payload['name'] === '' || $payload['phone'] === '' || $payload['email'] === '' || $payload['service'] === '') {
        $errors[] = 'Please fill out all required fields.';
    }

    if ($errors === []) {
        $created = $app['appointmentService']->createWalkIn($payload);
        $app['customerService']->saveVisit($payload, 'walkin', $created['id']);

        if ($payload['sms_opt_in']) {
            $app['notificationService']->sendBookingConfirmation($payload['phone'], $payload['name'], 'walk-in check-in');
            $app['notificationService']->sendDirections($payload['phone'], $app['config']['directions_url']);
        }

        redirect_to('/confirmation.php?type=checkin&id=' . urlencode($created['id']));
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Walk-In Check-In</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<main class="container">
    <h1>Walk-In Queue Check-In</h1>
    <p><a href="/">← Back to home</a></p>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-error"><?= e($error) ?></div>
    <?php endforeach; ?>

    <form method="post" class="card form-grid">
        <label>Name* <input type="text" name="name" required></label>
        <label>Phone* <input type="tel" name="phone" required></label>
        <label>Email* <input type="email" name="email" required></label>
        <label>Preferred Service*
            <select name="service" required>
                <option value="">Select a service</option>
                <?php foreach ($services as $key => $service): ?>
                    <option value="<?= e($key) ?>"><?= e($service['label']) ?> (<?= (int) $service['duration'] ?> min)</option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Preferred Barber
            <select name="preferred_barber">
                <?php foreach ($barbers as $barber): ?>
                    <option value="<?= e($barber) ?>"><?= e($barber) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>People in Party
            <input type="number" name="party_size" min="1" value="1">
        </label>

        <label class="checkbox"><input type="checkbox" name="sms_opt_in" value="1"> Send me text updates</label>
        <label class="checkbox"><input type="checkbox" name="marketing_opt_in" value="1"> Marketing opt-in</label>

        <button type="submit">Join Queue</button>
    </form>
</main>
</body>
</html>
