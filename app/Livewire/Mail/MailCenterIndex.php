<?php

declare(strict_types=1);

namespace App\Livewire\Mail;

use App\DTOs\MailMessageDTO;
use App\DTOs\MailSettingsDTO;
use App\Enums\PermissionEnum;
use App\Services\MailImapService;
use App\Services\MailSettingsService;
use App\Services\MailSmtpService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
#[Title('Hộp thư nội bộ')]
final class MailCenterIndex extends Component
{
    public string $activeTab = 'inbox';

    public bool $enabled = false;

    public string $from_name = 'TTVH-TC';

    public string $from_address = '';

    public string $imap_host = 'mail.example.com';

    public int $imap_port = 993;

    public string $imap_encryption = 'ssl';

    public string $imap_username = '';

    public string $imap_password = '';

    public string $smtp_host = 'mail.example.com';

    public int $smtp_port = 465;

    public string $smtp_encryption = 'ssl';

    public string $smtp_username = '';

    public string $smtp_password = '';

    public int $timeout = 15;

    public string $test_recipient = '';

    public string $compose_to = '';

    public string $compose_cc = '';

    public string $compose_bcc = '';

    public string $compose_subject = '';

    public string $compose_body = '';

    public string $folder = 'INBOX';

    public int $page = 1;

    public int $perPage = 10;

    public int $total = 0;

    public int $lastPage = 1;

    /** @var array<int, array<string, mixed>> */
    public array $messages = [];

    /** @var array<string, mixed>|null */
    public ?array $selectedMessage = null;

    public ?string $successMessage = null;

    public ?string $errorMessage = null;

    public bool $hasImapCredentials = false;

    public function mount(MailSettingsService $settingsService, MailImapService $imapService): void
    {
        Gate::authorize(PermissionEnum::MailView->value);

        $this->fillFromSettings($settingsService->load());
        $this->hasImapCredentials = $settingsService->hasImapCredentials();
        $this->test_recipient = auth()->user()?->email ?? '';

        if ($this->enabled && $this->hasImapCredentials) {
            $this->loadInbox($settingsService, $imapService);
        }
    }

    public function showTab(string $tab, MailSettingsService $settingsService, MailImapService $imapService): void
    {
        if (! in_array($tab, ['inbox', 'compose', 'settings'], true)) {
            return;
        }

        if ($tab === 'compose') {
            Gate::authorize(PermissionEnum::MailSend->value);
        }

        if ($tab === 'settings') {
            Gate::authorize(PermissionEnum::MailView->value);
        }

        $this->activeTab = $tab;
        $this->errorMessage = null;
        $this->successMessage = null;

        if ($tab === 'inbox' && $this->enabled && $this->hasImapCredentials && $this->messages === []) {
            $this->loadInbox($settingsService, $imapService);
        }
    }

    public function saveSettings(MailSettingsService $settingsService): void
    {
        Gate::authorize(PermissionEnum::MailView->value);

        $this->validate($this->rules());

        $settingsService->save($this->currentSettings());

        $this->imap_password = '';
        $this->smtp_password = '';
        $this->hasImapCredentials = $settingsService->hasImapCredentials();
        $this->success('Đã lưu cấu hình email.');
    }

    public function testImap(MailSettingsService $settingsService, MailImapService $imapService): void
    {
        Gate::authorize(PermissionEnum::MailView->value);

        $this->validate($this->rules());

        try {
            $imapService->testConnection($settingsService->withStoredSecrets($this->currentSettings()));
            $this->success('Kết nối IMAP thành công.');
        } catch (Throwable $exception) {
            $this->fail('Kết nối IMAP thất bại: '.$exception->getMessage());
        }
    }

    public function sendTestMail(MailSettingsService $settingsService, MailSmtpService $smtpService): void
    {
        Gate::authorize(PermissionEnum::MailView->value);

        $this->validate(array_merge($this->rules(), [
            'test_recipient' => ['required', 'email', 'max:255'],
        ]));

        try {
            $smtpService->sendTest($settingsService->withStoredSecrets($this->currentSettings()), $this->test_recipient);
            $this->success('Đã gửi email kiểm tra đến '.$this->test_recipient.'.');
        } catch (Throwable $exception) {
            $this->fail('Gửi mail thử thất bại: '.$exception->getMessage());
        }
    }

    public function sendMail(MailSettingsService $settingsService, MailSmtpService $smtpService): void
    {
        Gate::authorize(PermissionEnum::MailSend->value);

        $this->validate([
            'compose_to' => ['required', 'string', 'max:1000'],
            'compose_cc' => ['nullable', 'string', 'max:1000'],
            'compose_bcc' => ['nullable', 'string', 'max:1000'],
            'compose_subject' => ['required', 'string', 'max:255'],
            'compose_body' => ['required', 'string', 'max:20000'],
        ]);

        try {
            $message = MailMessageDTO::fromArray([
                'to' => $this->compose_to,
                'cc' => $this->compose_cc,
                'bcc' => $this->compose_bcc,
                'subject' => $this->compose_subject,
                'body' => $this->compose_body,
            ]);

            $this->validateRecipients($message);

            $smtpService->send($settingsService->withStoredSecrets($this->currentSettings()), $message);
            $this->resetCompose();
            $this->success('Đã gửi email thành công.');
        } catch (Throwable $exception) {
            $this->fail('Gửi email thất bại: '.$exception->getMessage());
        }
    }

    public function refreshInbox(MailSettingsService $settingsService, MailImapService $imapService): void
    {
        Gate::authorize(PermissionEnum::MailView->value);

        $this->selectedMessage = null;
        $this->loadInbox($settingsService, $imapService);
    }

    private function loadInbox(MailSettingsService $settingsService, MailImapService $imapService): void
    {
        $this->selectedMessage = null;

        try {
            $result = $imapService->listInbox(
                settings: $settingsService->withStoredSecrets($this->currentSettings()),
                page: $this->page,
                perPage: $this->perPage,
                folder: $this->folder,
            );

            $this->messages = $result['messages'];
            $this->total = $result['total'];
            $this->page = $result['page'];
            $this->perPage = $result['per_page'];
            $this->lastPage = $result['last_page'];
            $this->successMessage = null;
            $this->errorMessage = null;
        } catch (Throwable $exception) {
            $this->messages = [];
            $this->fail('Không tải được hộp thư: '.$exception->getMessage());
        }
    }

    public function openMessage(int $uid, MailSettingsService $settingsService, MailImapService $imapService): void
    {
        Gate::authorize(PermissionEnum::MailView->value);

        try {
            $this->selectedMessage = $imapService->getMessage(
                settings: $settingsService->withStoredSecrets($this->currentSettings()),
                uid: $uid,
                folder: $this->folder,
            );
            $this->errorMessage = null;
        } catch (Throwable $exception) {
            $this->fail('Không đọc được email: '.$exception->getMessage());
        }
    }

    public function closeMessage(): void
    {
        $this->selectedMessage = null;
    }

    public function nextPage(MailSettingsService $settingsService, MailImapService $imapService): void
    {
        if ($this->page >= $this->lastPage) {
            return;
        }

        $this->page++;
        $this->refreshInbox($settingsService, $imapService);
    }

    public function previousPage(MailSettingsService $settingsService, MailImapService $imapService): void
    {
        if ($this->page <= 1) {
            return;
        }

        $this->page--;
        $this->refreshInbox($settingsService, $imapService);
    }

    public function render(): View
    {
        return view('livewire.mail.mail-center-index');
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function rules(): array
    {
        return [
            'enabled' => ['boolean'],
            'from_name' => ['required', 'string', 'max:255'],
            'from_address' => ['required', 'email', 'max:255'],
            'imap_host' => ['required', 'string', 'max:255'],
            'imap_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'imap_encryption' => ['required', 'in:ssl,starttls,none'],
            'imap_username' => ['required', 'string', 'max:255'],
            'imap_password' => ['nullable', 'string', 'max:255'],
            'smtp_host' => ['required', 'string', 'max:255'],
            'smtp_port' => ['required', 'integer', 'min:1', 'max:65535'],
            'smtp_encryption' => ['required', 'in:ssl,starttls,none'],
            'smtp_username' => ['required', 'string', 'max:255'],
            'smtp_password' => ['nullable', 'string', 'max:255'],
            'timeout' => ['required', 'integer', 'min:5', 'max:60'],
        ];
    }

    private function currentSettings(): MailSettingsDTO
    {
        return MailSettingsDTO::fromArray([
            'enabled' => $this->enabled,
            'from_name' => $this->from_name,
            'from_address' => $this->from_address,
            'imap_host' => $this->imap_host,
            'imap_port' => $this->imap_port,
            'imap_encryption' => $this->imap_encryption,
            'imap_username' => $this->imap_username,
            'imap_password' => $this->imap_password,
            'smtp_host' => $this->smtp_host,
            'smtp_port' => $this->smtp_port,
            'smtp_encryption' => $this->smtp_encryption,
            'smtp_username' => $this->smtp_username,
            'smtp_password' => $this->smtp_password,
            'timeout' => $this->timeout,
        ]);
    }

    private function validateRecipients(MailMessageDTO $message): void
    {
        foreach (array_merge($message->to, $message->cc, $message->bcc) as $recipient) {
            if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
                throw new \InvalidArgumentException('Email người nhận không hợp lệ: '.$recipient);
            }
        }
    }

    private function resetCompose(): void
    {
        $this->compose_to = '';
        $this->compose_cc = '';
        $this->compose_bcc = '';
        $this->compose_subject = '';
        $this->compose_body = '';
    }

    private function fillFromSettings(MailSettingsDTO $settings): void
    {
        $this->enabled = $settings->enabled;
        $this->from_name = $settings->fromName;
        $this->from_address = $settings->fromAddress;
        $this->imap_host = $settings->imapHost;
        $this->imap_port = $settings->imapPort;
        $this->imap_encryption = $settings->imapEncryption;
        $this->imap_username = $settings->imapUsername;
        $this->smtp_host = $settings->smtpHost;
        $this->smtp_port = $settings->smtpPort;
        $this->smtp_encryption = $settings->smtpEncryption;
        $this->smtp_username = $settings->smtpUsername;
        $this->timeout = $settings->timeout;
    }

    private function success(string $message): void
    {
        $this->successMessage = $message;
        $this->errorMessage = null;
        $this->dispatch('swal:alert', [
            'icon' => 'success',
            'title' => 'Thành công!',
            'text' => $message,
            'toast' => true,
            'position' => 'top-end',
        ]);
    }

    private function fail(string $message): void
    {
        $this->errorMessage = $message;
        $this->successMessage = null;
        $this->dispatch('swal:alert', [
            'icon' => 'error',
            'title' => 'Thất bại!',
            'text' => $message,
        ]);
    }
}
