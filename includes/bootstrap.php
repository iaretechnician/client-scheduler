<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$config = require __DIR__ . '/../config/shop.php';

$appEnv = strtolower((string) ($_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'development'));
if ($appEnv === 'production' && (($config['admin']['password'] ?? '') === 'change-me-now')) {
    throw new RuntimeException('Default admin password is not allowed in production. Update config/shop.php.');
}

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/storage/StorageInterface.php';
require_once __DIR__ . '/storage/JsonStorage.php';
require_once __DIR__ . '/services/WaitTimeService.php';
require_once __DIR__ . '/services/SmsLogger.php';
require_once __DIR__ . '/services/NotificationService.php';
require_once __DIR__ . '/services/CustomerService.php';
require_once __DIR__ . '/services/AppointmentService.php';

$storage = new JsonStorage(__DIR__ . '/../data');

if ($storage->getServices() === []) {
    $storage->saveServices($config['services']);
}

$waitTimeService = new WaitTimeService();
$notificationService = new NotificationService($config['sms'], new SmsLogger($storage));
$customerService = new CustomerService($storage);
$appointmentService = new AppointmentService($storage, $waitTimeService, $config);

$app = [
    'config' => $config,
    'storage' => $storage,
    'waitTimeService' => $waitTimeService,
    'notificationService' => $notificationService,
    'customerService' => $customerService,
    'appointmentService' => $appointmentService,
];
