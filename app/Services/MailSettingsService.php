<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\MailSettingsDTO;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

final class MailSettingsService
{
    private const string PREFIX = 'mail.';

    public function load(?User $user = null): MailSettingsDTO
    {
        $user ??= auth()->user();
        $prefix = $this->prefixFor($user instanceof User ? $user : null);

        return MailSettingsDTO::fromArray([
            'enabled' => Setting::get($prefix.'enabled', false),
            'from_name' => Setting::get($prefix.'from_name', $user instanceof User ? $user->name : 'TTVH-TC'),
            'from_address' => Setting::get($prefix.'from_address', $user instanceof User ? $user->email : ''),
            'imap_host' => Setting::get($prefix.'imap_host', 'mail.example.com'),
            'imap_port' => Setting::get($prefix.'imap_port', 993),
            'imap_encryption' => Setting::get($prefix.'imap_encryption', 'ssl'),
            'imap_username' => Setting::get($prefix.'imap_username', ''),
            'imap_password' => $this->getSecret($prefix.'imap_password'),
            'smtp_host' => Setting::get($prefix.'smtp_host', 'mail.example.com'),
            'smtp_port' => Setting::get($prefix.'smtp_port', 465),
            'smtp_encryption' => Setting::get($prefix.'smtp_encryption', 'ssl'),
            'smtp_username' => Setting::get($prefix.'smtp_username', ''),
            'smtp_password' => $this->getSecret($prefix.'smtp_password'),
            'timeout' => Setting::get($prefix.'timeout', 15),
        ]);
    }

    public function save(MailSettingsDTO $dto, bool $keepEmptyPasswords = true, ?User $user = null): void
    {
        $user ??= auth()->user();
        $prefix = $this->prefixFor($user instanceof User ? $user : null);

        Setting::set($prefix.'enabled', $dto->enabled ? '1' : '0');
        Setting::set($prefix.'from_name', $dto->fromName);
        Setting::set($prefix.'from_address', $dto->fromAddress);
        Setting::set($prefix.'imap_host', $dto->imapHost);
        Setting::set($prefix.'imap_port', (string) $dto->imapPort);
        Setting::set($prefix.'imap_encryption', $dto->imapEncryption);
        Setting::set($prefix.'imap_username', $dto->imapUsername);
        Setting::set($prefix.'smtp_host', $dto->smtpHost);
        Setting::set($prefix.'smtp_port', (string) $dto->smtpPort);
        Setting::set($prefix.'smtp_encryption', $dto->smtpEncryption);
        Setting::set($prefix.'smtp_username', $dto->smtpUsername);
        Setting::set($prefix.'timeout', (string) $dto->timeout);

        if ($dto->imapPassword !== null || ! $keepEmptyPasswords) {
            Setting::set($prefix.'imap_password', $dto->imapPassword !== null ? Crypt::encryptString($dto->imapPassword) : null);
        }

        if ($dto->smtpPassword !== null || ! $keepEmptyPasswords) {
            Setting::set($prefix.'smtp_password', $dto->smtpPassword !== null ? Crypt::encryptString($dto->smtpPassword) : null);
        }
    }

    public function withStoredSecrets(MailSettingsDTO $dto, ?User $user = null): MailSettingsDTO
    {
        $stored = $this->load($user);

        return $dto->withPasswords(
            imapPassword: $dto->imapPassword ?? $stored->imapPassword,
            smtpPassword: $dto->smtpPassword ?? $stored->smtpPassword,
        );
    }

    public function hasImapCredentials(): bool
    {
        $settings = $this->load();

        return $settings->imapHost !== ''
            && $settings->imapUsername !== ''
            && $settings->imapPassword !== null;
    }

    private function prefixFor(?User $user): string
    {
        if (! $user instanceof User) {
            return self::PREFIX;
        }

        return self::PREFIX.'users.'.$user->id.'.';
    }

    private function getSecret(string $key): ?string
    {
        $value = Setting::get($key);

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }
}
