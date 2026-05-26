<?php

declare(strict_types=1);

require_once __DIR__ . '/StorageInterface.php';

final class JsonStorage implements StorageInterface
{
    private string $dataPath;

    public function __construct(string $dataPath)
    {
        $this->dataPath = rtrim($dataPath, '/');
        if (!is_dir($this->dataPath)) {
            mkdir($this->dataPath, 0775, true);
        }

        $this->ensureFile('queue.json', []);
        $this->ensureFile('appointments.json', []);
        $this->ensureFile('customers.json', []);
        $this->ensureFile('services.json', []);
        $this->ensureFile('settings.json', ['pause_online_checkins' => false, 'blocked_slots' => []]);
        $this->ensureFile('notifications.log', '', false);
    }

    public function getQueue(): array
    {
        return $this->readJson('queue.json');
    }

    public function saveQueue(array $queue): void
    {
        $this->writeJson('queue.json', $queue);
    }

    public function getAppointments(): array
    {
        return $this->readJson('appointments.json');
    }

    public function saveAppointments(array $appointments): void
    {
        $this->writeJson('appointments.json', $appointments);
    }

    public function getCustomers(): array
    {
        return $this->readJson('customers.json');
    }

    public function saveCustomers(array $customers): void
    {
        $this->writeJson('customers.json', $customers);
    }

    public function getSettings(): array
    {
        return $this->readJson('settings.json');
    }

    public function saveSettings(array $settings): void
    {
        $this->writeJson('settings.json', $settings);
    }

    public function getServices(): array
    {
        return $this->readJson('services.json');
    }

    public function saveServices(array $services): void
    {
        $this->writeJson('services.json', $services);
    }

    public function logNotification(array $entry): void
    {
        $line = json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL;
        file_put_contents($this->filePath('notifications.log'), $line, FILE_APPEND | LOCK_EX);
    }

    private function ensureFile(string $file, mixed $default, bool $json = true): void
    {
        $path = $this->filePath($file);
        if (!file_exists($path)) {
            $content = $json ? json_encode($default, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : (string) $default;
            file_put_contents($path, $content . ($json ? PHP_EOL : ''));
        }
    }

    private function filePath(string $file): string
    {
        return $this->dataPath . '/' . $file;
    }

    private function readJson(string $file): array
    {
        $content = file_get_contents($this->filePath($file));
        if ($content === false || trim($content) === '') {
            return [];
        }

        $decoded = json_decode($content, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function writeJson(string $file, array $data): void
    {
        file_put_contents(
            $this->filePath($file),
            json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            LOCK_EX
        );
    }
}
