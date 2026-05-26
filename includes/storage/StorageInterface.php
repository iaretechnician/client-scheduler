<?php

declare(strict_types=1);

interface StorageInterface
{
    public function getQueue(): array;
    public function saveQueue(array $queue): void;

    public function getAppointments(): array;
    public function saveAppointments(array $appointments): void;

    public function getCustomers(): array;
    public function saveCustomers(array $customers): void;

    public function getSettings(): array;
    public function saveSettings(array $settings): void;

    public function getServices(): array;
    public function saveServices(array $services): void;

    public function logNotification(array $entry): void;
}
