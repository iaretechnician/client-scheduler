<?php

declare(strict_types=1);

final class CustomerService
{
    public function __construct(private StorageInterface $storage)
    {
    }

    public function saveVisit(array $input, string $visitType, string $visitId): void
    {
        $customers = $this->storage->getCustomers();
        $phone = sanitize_phone($input['phone'] ?? '');
        $email = sanitize_email($input['email'] ?? '');

        $matchIndex = null;
        foreach ($customers as $index => $customer) {
            $customerPhone = sanitize_phone($customer['phone'] ?? '');
            $customerEmail = sanitize_email($customer['email'] ?? '');
            if (($phone !== '' && $phone === $customerPhone) || ($email !== '' && $email === $customerEmail)) {
                $matchIndex = $index;
                break;
            }
        }

        $visit = [
            'id' => $visitId,
            'type' => $visitType,
            'service' => (string) ($input['service'] ?? ''),
            'barber' => (string) ($input['preferred_barber'] ?? ''),
            'created_at' => now_iso(),
        ];

        if ($matchIndex === null) {
            $customers[] = [
                'id' => generate_id('cus'),
                'name' => (string) ($input['name'] ?? ''),
                'phone' => $phone,
                'email' => $email,
                'preferred_barber' => (string) ($input['preferred_barber'] ?? ''),
                'preferred_service' => (string) ($input['service'] ?? ''),
                'notes' => (string) ($input['notes'] ?? ''),
                'marketing_opt_in' => !empty($input['marketing_opt_in']),
                'sms_opt_in' => !empty($input['sms_opt_in']),
                'last_visit_at' => now_iso(),
                'visit_history' => [$visit],
            ];
        } else {
            $customers[$matchIndex]['name'] = (string) ($input['name'] ?? $customers[$matchIndex]['name'] ?? '');
            $customers[$matchIndex]['preferred_barber'] = (string) ($input['preferred_barber'] ?? '');
            $customers[$matchIndex]['preferred_service'] = (string) ($input['service'] ?? '');
            $customers[$matchIndex]['marketing_opt_in'] = !empty($input['marketing_opt_in']);
            $customers[$matchIndex]['sms_opt_in'] = !empty($input['sms_opt_in']);
            $customers[$matchIndex]['last_visit_at'] = now_iso();
            $customers[$matchIndex]['visit_history'] = $customers[$matchIndex]['visit_history'] ?? [];
            $customers[$matchIndex]['visit_history'][] = $visit;
        }

        $this->storage->saveCustomers($customers);
    }

    public function filteredList(array $filters): array
    {
        $customers = $this->storage->getCustomers();

        return array_values(array_filter($customers, function (array $customer) use ($filters): bool {
            if (($filters['marketing_opt_in'] ?? '') !== '') {
                $wanted = $filters['marketing_opt_in'] === '1';
                if ((bool) ($customer['marketing_opt_in'] ?? false) !== $wanted) {
                    return false;
                }
            }

            if (($filters['service'] ?? '') !== '' && ($customer['preferred_service'] ?? '') !== $filters['service']) {
                return false;
            }

            if (($filters['preferred_barber'] ?? '') !== '' && ($customer['preferred_barber'] ?? '') !== $filters['preferred_barber']) {
                return false;
            }

            if (($filters['last_visit_after'] ?? '') !== '') {
                try {
                    $cutoff = new DateTimeImmutable($filters['last_visit_after']);
                    $lastVisit = new DateTimeImmutable((string) ($customer['last_visit_at'] ?? '1970-01-01'));
                    if ($lastVisit < $cutoff) {
                        return false;
                    }
                } catch (Throwable $e) {
                    return false;
                }
            }

            return true;
        }));
    }
}
