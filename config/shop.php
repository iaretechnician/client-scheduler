<?php

declare(strict_types=1);

return [
    'shop_name' => 'Client Scheduler Barber Shop',
    'address' => '123 Main Street, Your City, ST 12345',
    'phone' => '(555) 123-4567',
    'directions_url' => 'https://maps.google.com/?q=123+Main+Street+Your+City+ST+12345',
    'business_hours' => [
        'Mon-Fri' => '9:00 AM - 6:00 PM',
        'Sat' => '9:00 AM - 4:00 PM',
        'Sun' => 'Closed',
    ],
    'default_appointment_length' => 30,
    'active_barbers' => 2,
    'admin' => [
        'username' => 'admin',
        // Development-only hash for password: change-me-now
        // Replace with your own hash from password_hash('your-password', PASSWORD_DEFAULT).
        'password_hash' => '$2y$10$aH9OsgokbPPVQVsZzbG0eeOxL35WsuYsIFXwCOtf3PAiTEDvDTHHa',
    ],
    'sms' => [
        'provider' => 'logger', // Placeholder: swap with twilio or another provider later.
        'api_key' => 'replace-with-provider-api-key',
        'from_number' => '+15555550123',
    ],
    'services' => [
        'haircut' => ['label' => 'Haircut', 'duration' => 30],
        'beard_trim' => ['label' => 'Beard Trim', 'duration' => 15],
        'haircut_beard' => ['label' => 'Haircut + Beard', 'duration' => 45],
        'kids_cut' => ['label' => 'Kids Cut', 'duration' => 25],
    ],
    'barbers' => ['Any', 'Alex', 'Jordan', 'Taylor'],
];
