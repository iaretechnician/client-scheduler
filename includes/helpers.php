<?php

declare(strict_types=1);

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function sanitize_string(?string $value): string
{
    return trim(filter_var((string) $value, FILTER_SANITIZE_FULL_SPECIAL_CHARS));
}

function sanitize_email(?string $value): string
{
    $email = trim((string) $value);
    return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : '';
}

function sanitize_phone(?string $value): string
{
    return preg_replace('/[^0-9+]/', '', (string) $value) ?? '';
}

function redirect_to(string $url): void
{
    header('Location: ' . $url);
    exit;
}

function request_method(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

function is_post(): bool
{
    return request_method() === 'POST';
}

function generate_id(string $prefix): string
{
    return $prefix . '_' . bin2hex(random_bytes(6));
}

function now_iso(): string
{
    return (new DateTimeImmutable())->format(DateTimeInterface::ATOM);
}

function mask_phone(string $phone): string
{
    $clean = preg_replace('/\D/', '', $phone) ?? '';
    if (strlen($clean) < 4) {
        return '***';
    }

    return '***-***-' . substr($clean, -4);
}

function format_datetime(string $datetime): string
{
    try {
        return (new DateTimeImmutable($datetime))->format('M j, Y g:i A');
    } catch (Throwable $e) {
        return $datetime;
    }
}
