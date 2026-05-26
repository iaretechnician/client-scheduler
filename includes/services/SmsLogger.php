<?php

declare(strict_types=1);

final class SmsLogger
{
    public function __construct(private StorageInterface $storage)
    {
    }

    public function log(string $to, string $message, string $type, array $meta = []): void
    {
        $this->storage->logNotification([
            'timestamp' => now_iso(),
            'provider' => 'logger',
            'to' => $to,
            'type' => $type,
            'message' => $message,
            'meta' => $meta,
        ]);
    }
}
