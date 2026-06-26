<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class MailMessageDTO
{
    /**
     * @param  array<int, string>  $to
     * @param  array<int, string>  $cc
     * @param  array<int, string>  $bcc
     */
    public function __construct(
        public array $to,
        public array $cc,
        public array $bcc,
        public string $subject,
        public string $body,
    ) {}

    /**
     * @param  array{to?: string, cc?: string|null, bcc?: string|null, subject?: string, body?: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            to: self::parseRecipients((string) ($data['to'] ?? '')),
            cc: self::parseRecipients((string) ($data['cc'] ?? '')),
            bcc: self::parseRecipients((string) ($data['bcc'] ?? '')),
            subject: trim((string) ($data['subject'] ?? '')),
            body: trim((string) ($data['body'] ?? '')),
        );
    }

    /**
     * @return array<int, string>
     */
    private static function parseRecipients(string $value): array
    {
        $recipients = preg_split('/[,;\s]+/', trim($value)) ?: [];

        return array_values(array_filter(array_map(
            static fn (string $email): string => strtolower(trim($email)),
            $recipients,
        )));
    }
}
