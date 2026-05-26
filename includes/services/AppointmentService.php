<?php

declare(strict_types=1);

final class AppointmentService
{
    private array $validStatuses = ['waiting', 'notified', 'arrived', 'in_chair', 'completed', 'cancelled', 'no_show', 'scheduled'];

    public function __construct(
        private StorageInterface $storage,
        private WaitTimeService $waitTimeService,
        private array $config
    ) {
    }

    public function onlineCheckinsPaused(): bool
    {
        $settings = $this->storage->getSettings();
        return !empty($settings['pause_online_checkins']);
    }

    public function getDashboardData(): array
    {
        $queue = $this->storage->getQueue();
        $appointments = $this->storage->getAppointments();
        $today = (new DateTimeImmutable())->format('Y-m-d');

        $todayAppointments = array_values(array_filter($appointments, static function (array $row) use ($today): bool {
            return str_starts_with((string) ($row['appointment_time'] ?? ''), $today);
        }));

        return [
            'queue' => $queue,
            'appointments_today' => $todayAppointments,
            'completed' => array_values(array_filter($queue, fn (array $q): bool => ($q['status'] ?? '') === 'completed')),
            'cancelled_or_no_show' => array_values(array_filter($queue, fn (array $q): bool => in_array($q['status'] ?? '', ['cancelled', 'no_show'], true))),
        ];
    }

    public function createWalkIn(array $input, bool $byAdmin = false): array
    {
        $queue = $this->storage->getQueue();
        $appointments = $this->storage->getAppointments();
        $services = $this->loadServices();
        $settings = $this->storage->getSettings();

        $id = generate_id('q');
        $entry = [
            'id' => $id,
            'name' => sanitize_string($input['name'] ?? ''),
            'phone' => sanitize_phone($input['phone'] ?? ''),
            'email' => sanitize_email($input['email'] ?? ''),
            'service' => sanitize_string($input['service'] ?? ''),
            'preferred_barber' => sanitize_string($input['preferred_barber'] ?? ''),
            'party_size' => max(1, (int) ($input['party_size'] ?? 1)),
            'service_duration' => max(5, (int) ($input['service_duration'] ?? ($services[$input['service']]['duration'] ?? $this->config['default_appointment_length']))),
            'status' => 'waiting',
            'created_at' => now_iso(),
            'marketing_opt_in' => !empty($input['marketing_opt_in']),
            'sms_opt_in' => !empty($input['sms_opt_in']),
            'source' => $byAdmin ? 'admin' : 'online',
            'assigned_barber' => '',
        ];

        $queue[] = $entry;

        $activeBarbers = (int) ($settings['active_barbers_override'] ?? $this->config['active_barbers']);
        $entry['estimated_wait_minutes'] = $this->waitTimeService->estimateWaitMinutes($queue, $appointments, $services, $activeBarbers);

        // Keep the estimate on the persisted record too.
        $queue[array_key_last($queue)]['estimated_wait_minutes'] = $entry['estimated_wait_minutes'];

        $this->storage->saveQueue($queue);

        return $entry;
    }

    public function createAppointment(array $input, bool $byAdmin = false): array
    {
        $appointmentTime = sanitize_string($input['appointment_time'] ?? '');
        $barber = sanitize_string($input['preferred_barber'] ?? 'Any');
        $serviceKey = sanitize_string($input['service'] ?? '');
        if (!$this->isSlotAvailable($appointmentTime, $barber, $serviceKey)) {
            throw new RuntimeException('Selected time slot is not available.');
        }

        $services = $this->loadServices();
        $appointments = $this->storage->getAppointments();

        $entry = [
            'id' => generate_id('apt'),
            'name' => sanitize_string($input['name'] ?? ''),
            'phone' => sanitize_phone($input['phone'] ?? ''),
            'email' => sanitize_email($input['email'] ?? ''),
            'service' => sanitize_string($input['service'] ?? ''),
            'preferred_barber' => $barber,
            'service_duration' => max(5, (int) ($input['service_duration'] ?? ($services[$input['service']]['duration'] ?? $this->config['default_appointment_length']))),
            'appointment_time' => $appointmentTime,
            'status' => 'scheduled',
            'created_at' => now_iso(),
            'marketing_opt_in' => !empty($input['marketing_opt_in']),
            'sms_opt_in' => !empty($input['sms_opt_in']),
            'source' => $byAdmin ? 'admin' : 'online',
            'assigned_barber' => '',
        ];

        $appointments[] = $entry;
        $this->storage->saveAppointments($appointments);

        return $entry;
    }

    public function isSlotAvailable(string $appointmentTime, string $preferredBarber = 'Any', string $requestedService = 'haircut'): bool
    {
        if ($appointmentTime === '') {
            return false;
        }

        try {
            $start = new DateTimeImmutable($appointmentTime);
        } catch (Throwable $e) {
            return false;
        }

        $settings = $this->storage->getSettings();
        if ($this->isBlockedBySettings($start, $preferredBarber, $settings)) {
            return false;
        }

        $appointments = $this->storage->getAppointments();
        $services = $this->loadServices();
        $activeBarbers = (int) ($settings['active_barbers_override'] ?? $this->config['active_barbers']);

        $collisions = 0;
        foreach ($appointments as $appointment) {
            if (in_array($appointment['status'] ?? '', ['cancelled', 'no_show'], true)) {
                continue;
            }

            try {
                $existingStart = new DateTimeImmutable((string) ($appointment['appointment_time'] ?? ''));
            } catch (Throwable $e) {
                continue;
            }

            $existingDuration = max(5, (int) ($appointment['service_duration'] ?? $this->config['default_appointment_length']));
            $existingEnd = $existingStart->modify('+' . $existingDuration . ' minutes');
            $requestedDuration = max(5, (int) ($services[$requestedService]['duration'] ?? $this->config['default_appointment_length']));
            $requestedEnd = $start->modify('+' . $requestedDuration . ' minutes');

            $overlap = $start < $existingEnd && $existingStart < $requestedEnd;
            if (!$overlap) {
                continue;
            }

            $existingBarber = (string) ($appointment['preferred_barber'] ?? 'Any');
            if ($preferredBarber !== 'Any' && $existingBarber === $preferredBarber) {
                return false;
            }

            $collisions++;
        }

        return $collisions < max(1, $activeBarbers);
    }

    public function updateQueueEntry(string $id, array $input): void
    {
        $queue = $this->storage->getQueue();
        foreach ($queue as &$entry) {
            if (($entry['id'] ?? '') !== $id) {
                continue;
            }

            $entry['name'] = sanitize_string($input['name'] ?? $entry['name']);
            $entry['phone'] = sanitize_phone($input['phone'] ?? $entry['phone']);
            $entry['email'] = sanitize_email($input['email'] ?? $entry['email']);
            $entry['service'] = sanitize_string($input['service'] ?? $entry['service']);
            $entry['preferred_barber'] = sanitize_string($input['preferred_barber'] ?? $entry['preferred_barber']);
            $entry['assigned_barber'] = sanitize_string($input['assigned_barber'] ?? $entry['assigned_barber'] ?? '');
            $entry['party_size'] = max(1, (int) ($input['party_size'] ?? $entry['party_size']));
            $entry['service_duration'] = max(5, (int) ($input['service_duration'] ?? $entry['service_duration'] ?? 30));
            $status = sanitize_string($input['status'] ?? $entry['status']);
            if (in_array($status, $this->validStatuses, true)) {
                $entry['status'] = $status;
            }
            break;
        }

        $this->storage->saveQueue($queue);
    }

    public function updateAppointment(string $id, array $input): void
    {
        $appointments = $this->storage->getAppointments();
        foreach ($appointments as &$entry) {
            if (($entry['id'] ?? '') !== $id) {
                continue;
            }

            $entry['name'] = sanitize_string($input['name'] ?? $entry['name']);
            $entry['phone'] = sanitize_phone($input['phone'] ?? $entry['phone']);
            $entry['email'] = sanitize_email($input['email'] ?? $entry['email']);
            $entry['service'] = sanitize_string($input['service'] ?? $entry['service']);
            $entry['preferred_barber'] = sanitize_string($input['preferred_barber'] ?? $entry['preferred_barber']);
            $entry['assigned_barber'] = sanitize_string($input['assigned_barber'] ?? $entry['assigned_barber'] ?? '');
            $entry['service_duration'] = max(5, (int) ($input['service_duration'] ?? $entry['service_duration'] ?? 30));
            $status = sanitize_string($input['status'] ?? $entry['status']);
            if (in_array($status, $this->validStatuses, true)) {
                $entry['status'] = $status;
            }
            break;
        }

        $this->storage->saveAppointments($appointments);
    }

    public function saveSettings(array $input): void
    {
        $settings = $this->storage->getSettings();
        $settings['pause_online_checkins'] = !empty($input['pause_online_checkins']);
        $settings['active_barbers_override'] = max(1, (int) ($input['active_barbers_override'] ?? $this->config['active_barbers']));
        $this->storage->saveSettings($settings);
    }

    public function addBlockedSlot(array $input): void
    {
        $settings = $this->storage->getSettings();
        $settings['blocked_slots'] = $settings['blocked_slots'] ?? [];
        $settings['blocked_slots'][] = [
            'id' => generate_id('blk'),
            'start' => sanitize_string($input['start'] ?? ''),
            'end' => sanitize_string($input['end'] ?? ''),
            'barber' => sanitize_string($input['barber'] ?? 'Any'),
            'reason' => sanitize_string($input['reason'] ?? ''),
        ];

        $this->storage->saveSettings($settings);
    }

    public function removeBlockedSlot(string $id): void
    {
        $settings = $this->storage->getSettings();
        $blocked = $settings['blocked_slots'] ?? [];
        $settings['blocked_slots'] = array_values(array_filter($blocked, static fn (array $slot): bool => ($slot['id'] ?? '') !== $id));
        $this->storage->saveSettings($settings);
    }

    private function isBlockedBySettings(DateTimeImmutable $start, string $barber, array $settings): bool
    {
        $blockedSlots = $settings['blocked_slots'] ?? [];
        foreach ($blockedSlots as $slot) {
            try {
                $slotStart = new DateTimeImmutable((string) ($slot['start'] ?? ''));
                $slotEnd = new DateTimeImmutable((string) ($slot['end'] ?? ''));
            } catch (Throwable $e) {
                continue;
            }

            $barberMatch = ($slot['barber'] ?? 'Any') === 'Any' || ($slot['barber'] ?? '') === $barber;
            if ($barberMatch && $start >= $slotStart && $start < $slotEnd) {
                return true;
            }
        }

        return false;
    }

    private function loadServices(): array
    {
        $services = $this->storage->getServices();
        if (!empty($services)) {
            return $services;
        }

        return $this->config['services'];
    }
}
