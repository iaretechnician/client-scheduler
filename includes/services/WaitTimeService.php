<?php

declare(strict_types=1);

final class WaitTimeService
{
    public function estimateWaitMinutes(
        array $queue,
        array $appointments,
        array $services,
        int $activeBarbers,
        ?DateTimeImmutable $now = null
    ): int {
        $now ??= new DateTimeImmutable();
        $barbers = max(1, $activeBarbers);

        $queueMinutes = 0;
        foreach ($queue as $entry) {
            $status = $entry['status'] ?? 'waiting';
            if (!in_array($status, ['waiting', 'notified', 'arrived', 'in_chair'], true)) {
                continue;
            }

            $duration = $this->serviceDuration($entry['service'] ?? '', $services, (int) ($entry['service_duration'] ?? 0));
            if ($status === 'in_chair') {
                // Simple assumption: if already in chair, about half of service time remains.
                $duration = max(5, (int) ceil($duration / 2));
            }
            $party = max(1, (int) ($entry['party_size'] ?? 1));
            $queueMinutes += $duration * $party;
        }

        $blockedByAppointments = 0;
        foreach ($appointments as $appointment) {
            $status = $appointment['status'] ?? 'scheduled';
            if (!in_array($status, ['scheduled', 'arrived', 'in_chair'], true)) {
                continue;
            }

            try {
                $at = new DateTimeImmutable((string) ($appointment['appointment_time'] ?? ''));
            } catch (Throwable $e) {
                continue;
            }

            if ($at < $now) {
                continue;
            }

            $blockedByAppointments += $this->serviceDuration(
                (string) ($appointment['service'] ?? ''),
                $services,
                (int) ($appointment['service_duration'] ?? 0)
            );
        }

        return (int) ceil(($queueMinutes + $blockedByAppointments) / $barbers);
    }

    public function estimatePositionWaitMinutes(
        array $queue,
        int $position,
        array $appointments,
        array $services,
        int $activeBarbers
    ): int {
        $ahead = array_slice($queue, 0, max(0, $position));
        return $this->estimateWaitMinutes($ahead, $appointments, $services, $activeBarbers);
    }

    private function serviceDuration(string $serviceKey, array $services, int $fallback = 30): int
    {
        if ($fallback > 0) {
            return $fallback;
        }

        return max(5, (int) ($services[$serviceKey]['duration'] ?? 30));
    }
}
