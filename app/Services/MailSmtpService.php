<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\MailMessageDTO;
use App\DTOs\MailSettingsDTO;
use App\Mail\ComposedMail;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

final class MailSmtpService
{
    public function send(MailSettingsDTO $settings, MailMessageDTO $message): void
    {
        $this->ensureCanSend($settings);

        if ($message->to === []) {
            throw new InvalidArgumentException('Chưa nhập người nhận email.');
        }

        if ($message->subject === '') {
            throw new InvalidArgumentException('Chưa nhập tiêu đề email.');
        }

        if ($message->body === '') {
            throw new InvalidArgumentException('Chưa nhập nội dung email.');
        }

        $this->applyRuntimeConfig($settings);

        $pendingMail = Mail::to($message->to);

        if ($message->cc !== []) {
            $pendingMail->cc($message->cc);
        }

        if ($message->bcc !== []) {
            $pendingMail->bcc($message->bcc);
        }

        $pendingMail->send(new ComposedMail(
            subjectLine: $message->subject,
            body: $message->body,
            fromAddress: $settings->fromAddress,
            fromName: $settings->fromName,
        ));
    }

    public function sendTest(MailSettingsDTO $settings, string $recipient): void
    {
        $this->ensureCanSend($settings);

        $this->applyRuntimeConfig($settings);

        Mail::raw(
            "Đây là email kiểm tra cấu hình SMTP từ hệ thống TTVH-TC.\n\nNếu bạn nhận được email này, cấu hình gửi mail đang hoạt động.",
            function ($message) use ($recipient, $settings): void {
                $message
                    ->to($recipient)
                    ->from($settings->fromAddress, $settings->fromName)
                    ->subject('TTVH-TC - Kiểm tra cấu hình email');
            },
        );
    }

    private function ensureCanSend(MailSettingsDTO $settings): void
    {
        if ($settings->smtpHost === '' || $settings->smtpUsername === '' || $settings->smtpPassword === null) {
            throw new InvalidArgumentException('Chưa cấu hình đủ SMTP host, username hoặc password.');
        }

        if ($settings->fromAddress === '') {
            throw new InvalidArgumentException('Chưa cấu hình email người gửi.');
        }
    }

    private function applyRuntimeConfig(MailSettingsDTO $settings): void
    {
        $encryption = match ($settings->smtpEncryption) {
            'ssl' => 'ssl',
            'starttls' => 'tls',
            default => null,
        };

        config([
            'mail.default' => 'smtp',
            'mail.from.address' => $settings->fromAddress,
            'mail.from.name' => $settings->fromName,
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.host' => $settings->smtpHost,
            'mail.mailers.smtp.port' => $settings->smtpPort,
            'mail.mailers.smtp.encryption' => $encryption,
            'mail.mailers.smtp.username' => $settings->smtpUsername,
            'mail.mailers.smtp.password' => $settings->smtpPassword,
            'mail.mailers.smtp.timeout' => $settings->timeout,
        ]);

        app('mail.manager')->forgetMailers();
    }
}
