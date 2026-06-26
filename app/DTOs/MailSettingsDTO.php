<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class MailSettingsDTO
{
    public function __construct(
        public bool $enabled,
        public string $fromName,
        public string $fromAddress,
        public string $imapHost,
        public int $imapPort,
        public string $imapEncryption,
        public string $imapUsername,
        public ?string $imapPassword,
        public string $smtpHost,
        public int $smtpPort,
        public string $smtpEncryption,
        public string $smtpUsername,
        public ?string $smtpPassword,
        public int $timeout,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            enabled: filter_var($data['enabled'] ?? false, FILTER_VALIDATE_BOOL),
            fromName: trim((string) ($data['from_name'] ?? 'TTVH-TC')),
            fromAddress: strtolower(trim((string) ($data['from_address'] ?? ''))),
            imapHost: trim((string) ($data['imap_host'] ?? 'mail.example.com')),
            imapPort: max(1, (int) ($data['imap_port'] ?? 993)),
            imapEncryption: self::normalizeEncryption((string) ($data['imap_encryption'] ?? 'ssl')),
            imapUsername: trim((string) ($data['imap_username'] ?? '')),
            imapPassword: self::blankToNull($data['imap_password'] ?? null),
            smtpHost: trim((string) ($data['smtp_host'] ?? 'mail.example.com')),
            smtpPort: max(1, (int) ($data['smtp_port'] ?? 465)),
            smtpEncryption: self::normalizeEncryption((string) ($data['smtp_encryption'] ?? 'ssl')),
            smtpUsername: trim((string) ($data['smtp_username'] ?? '')),
            smtpPassword: self::blankToNull($data['smtp_password'] ?? null),
            timeout: max(5, min(60, (int) ($data['timeout'] ?? 15))),
        );
    }

    public function withPasswords(?string $imapPassword, ?string $smtpPassword): self
    {
        return new self(
            enabled: $this->enabled,
            fromName: $this->fromName,
            fromAddress: $this->fromAddress,
            imapHost: $this->imapHost,
            imapPort: $this->imapPort,
            imapEncryption: $this->imapEncryption,
            imapUsername: $this->imapUsername,
            imapPassword: $imapPassword,
            smtpHost: $this->smtpHost,
            smtpPort: $this->smtpPort,
            smtpEncryption: $this->smtpEncryption,
            smtpUsername: $this->smtpUsername,
            smtpPassword: $smtpPassword,
            timeout: $this->timeout,
        );
    }

    private static function normalizeEncryption(string $value): string
    {
        $value = strtolower(trim($value));

        return in_array($value, ['ssl', 'starttls', 'none'], true) ? $value : 'ssl';
    }

    private static function blankToNull(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
