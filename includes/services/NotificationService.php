<?php

declare(strict_types=1);

final class NotificationService
{
    public function __construct(private array $smsConfig, private SmsLogger $smsLogger)
    {
    }

    public function sendBookingConfirmation(string $phone, string $name, string $type): void
    {
        $this->send($phone, "Hi {$name}, your {$type} is confirmed. Reply STOP to opt out.", 'booking_confirmation');
    }

    public function sendWaitAlmostReady(string $phone, string $name, int $minutes): void
    {
        $this->send($phone, "Hi {$name}, you're almost up. Estimated {$minutes} minutes.", 'wait_almost_ready');
    }

    public function sendBarberReady(string $phone, string $name): void
    {
        $this->send($phone, "Hi {$name}, your barber is ready for you now.", 'barber_ready');
    }

    public function sendDirections(string $phone, string $directionsUrl): void
    {
        $this->send($phone, "Directions to shop: {$directionsUrl}", 'directions');
    }

    private function send(string $phone, string $message, string $type, array $meta = []): void
    {
        if ($phone === '') {
            return;
        }

        // Placeholder provider branching for future SMS integration.
        $provider = strtolower((string) ($this->smsConfig['provider'] ?? 'logger'));
        if ($provider === 'logger') {
            $this->smsLogger->log($phone, $message, $type, $meta);
            return;
        }

        $this->smsLogger->log($phone, $message, $type, ['fallback' => true] + $meta);
    }
}
