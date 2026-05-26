<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$filters = [
    'last_visit_after' => sanitize_string($_GET['last_visit_after'] ?? ''),
    'service' => sanitize_string($_GET['service'] ?? ''),
    'marketing_opt_in' => sanitize_string($_GET['marketing_opt_in'] ?? ''),
    'preferred_barber' => sanitize_string($_GET['preferred_barber'] ?? ''),
];

$customers = $app['customerService']->filteredList($filters);
$services = $app['storage']->getServices();
$barbers = $app['config']['barbers'];
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Customers</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<main class="container">
    <h1>Customer List</h1>
    <p><a href="/admin/index.php">← Dashboard</a></p>

    <form method="get" class="card form-grid">
        <h2>Filters</h2>
        <label>Last Visit On/After <input type="date" name="last_visit_after" value="<?= e($filters['last_visit_after']) ?>"></label>
        <label>Service
            <select name="service">
                <option value="">All</option>
                <?php foreach ($services as $key => $service): ?><option value="<?= e($key) ?>" <?= $filters['service'] === $key ? 'selected' : '' ?>><?= e($service['label']) ?></option><?php endforeach; ?>
            </select>
        </label>
        <label>Marketing Opt-In
            <select name="marketing_opt_in">
                <option value="">All</option>
                <option value="1" <?= $filters['marketing_opt_in'] === '1' ? 'selected' : '' ?>>Opted In</option>
                <option value="0" <?= $filters['marketing_opt_in'] === '0' ? 'selected' : '' ?>>Opted Out</option>
            </select>
        </label>
        <label>Preferred Barber
            <select name="preferred_barber">
                <option value="">All</option>
                <?php foreach ($barbers as $barber): ?><option value="<?= e($barber) ?>" <?= $filters['preferred_barber'] === $barber ? 'selected' : '' ?>><?= e($barber) ?></option><?php endforeach; ?>
            </select>
        </label>
        <button type="submit">Apply Filters</button>
    </form>

    <section class="card mt-16">
        <h2>Customers (<?= count($customers) ?>)</h2>
        <p>
            <button type="button" disabled>Send Coupon Email (placeholder)</button>
            <button type="button" disabled>Export Customer CSV (placeholder)</button>
        </p>

        <table>
            <thead><tr><th>Name</th><th>Phone</th><th>Email</th><th>Preferred Service</th><th>Preferred Barber</th><th>Marketing</th><th>SMS</th><th>Last Visit</th></tr></thead>
            <tbody>
            <?php if ($customers === []): ?><tr><td colspan="8">No customers found.</td></tr><?php endif; ?>
            <?php foreach ($customers as $customer): ?>
                <tr>
                    <td><?= e((string) ($customer['name'] ?? '')) ?></td>
                    <td><?= e(mask_phone((string) ($customer['phone'] ?? ''))) ?></td>
                    <td><?= e((string) ($customer['email'] ?? '')) ?></td>
                    <td><?= e((string) ($customer['preferred_service'] ?? '')) ?></td>
                    <td><?= e((string) ($customer['preferred_barber'] ?? '')) ?></td>
                    <td><?= !empty($customer['marketing_opt_in']) ? 'Yes' : 'No' ?></td>
                    <td><?= !empty($customer['sms_opt_in']) ? 'Yes' : 'No' ?></td>
                    <td><?= e(format_datetime((string) ($customer['last_visit_at'] ?? ''))) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
</body>
</html>
