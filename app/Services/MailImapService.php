<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\MailSettingsDTO;
use RuntimeException;

final class MailImapService
{
    /** @var resource|null */
    private $stream = null;

    private int $tagCounter = 1;

    public function testConnection(MailSettingsDTO $settings): void
    {
        $this->connect($settings);
        $this->command('NOOP');
        $this->disconnect();
    }

    /**
     * @return array{messages: array<int, array<string, mixed>>, total: int, page: int, per_page: int, last_page: int}
     */
    public function listInbox(MailSettingsDTO $settings, int $page = 1, int $perPage = 10, string $folder = 'INBOX'): array
    {
        $page = max(1, $page);
        $perPage = max(5, min(50, $perPage));

        $this->connect($settings);
        $this->selectFolder($folder);

        $search = $this->command('UID SEARCH ALL');
        $uids = $this->parseSearchUids($search['text']);
        rsort($uids);

        $total = count($uids);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);
        $pageUids = array_slice($uids, ($page - 1) * $perPage, $perPage);

        $messages = [];
        foreach ($pageUids as $uid) {
            $messages[] = $this->fetchSummary($uid);
        }

        $this->disconnect();

        return [
            'messages' => $messages,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => $lastPage,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getMessage(MailSettingsDTO $settings, int $uid, string $folder = 'INBOX'): array
    {
        $this->connect($settings);
        $this->selectFolder($folder);

        $result = $this->command('UID FETCH '.$uid.' (FLAGS RFC822.SIZE BODY.PEEK[])');
        $rawMessage = $result['literals'][0] ?? '';

        if ($rawMessage === '') {
            $this->disconnect();
            throw new RuntimeException('Không đọc được nội dung email.');
        }

        $message = $this->parseRawMessage($rawMessage);
        $message['uid'] = $uid;
        $message['seen'] = str_contains($result['text'], '\\Seen');

        $this->disconnect();

        return $message;
    }

    private function connect(MailSettingsDTO $settings): void
    {
        if ($settings->imapHost === '' || $settings->imapUsername === '' || $settings->imapPassword === null) {
            throw new RuntimeException('Chưa cấu hình đủ IMAP host, username hoặc password.');
        }

        $scheme = $settings->imapEncryption === 'ssl' ? 'ssl://' : '';
        $remote = $scheme.$settings->imapHost.':'.$settings->imapPort;
        $errno = 0;
        $errstr = '';

        $stream = @stream_socket_client($remote, $errno, $errstr, $settings->timeout);

        if ($stream === false) {
            throw new RuntimeException('Không kết nối được IMAP: '.($errstr !== '' ? $errstr : 'unknown error'));
        }

        stream_set_timeout($stream, $settings->timeout);
        $this->stream = $stream;
        $this->readLine();

        if ($settings->imapEncryption === 'starttls') {
            $this->command('STARTTLS');

            if (! stream_socket_enable_crypto($this->stream, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('Không bật được STARTTLS cho kết nối IMAP.');
            }
        }

        $this->command('LOGIN '.$this->quote($settings->imapUsername).' '.$this->quote($settings->imapPassword));
    }

    private function disconnect(): void
    {
        if (is_resource($this->stream)) {
            try {
                $this->command('LOGOUT', false);
            } catch (RuntimeException) {
                // Ignore disconnect errors.
            }

            fclose($this->stream);
        }

        $this->stream = null;
    }

    private function selectFolder(string $folder): void
    {
        $this->command('SELECT '.$this->quote($folder));
    }

    /**
     * @return array{text: string, literals: array<int, string>}
     */
    private function command(string $command, bool $mustSucceed = true): array
    {
        if (! is_resource($this->stream)) {
            throw new RuntimeException('Kết nối IMAP chưa sẵn sàng.');
        }

        $tag = 'A'.str_pad((string) $this->tagCounter++, 4, '0', STR_PAD_LEFT);
        fwrite($this->stream, $tag.' '.$command."\r\n");

        $text = '';
        $literals = [];
        $statusLine = '';

        while (($line = fgets($this->stream)) !== false) {
            $text .= $line;

            if (preg_match('/\{(\d+)\}\r?\n$/', $line, $matches) === 1) {
                $length = (int) $matches[1];
                $literal = $length > 0 ? stream_get_contents($this->stream, $length) : '';
                $literals[] = $literal;
                $text .= $literal;

                continue;
            }

            if (str_starts_with($line, $tag.' ')) {
                $statusLine = trim($line);
                break;
            }
        }

        if ($mustSucceed && ! str_contains($statusLine, ' OK')) {
            throw new RuntimeException('IMAP trả về lỗi: '.($statusLine !== '' ? $statusLine : 'Không có phản hồi hợp lệ.'));
        }

        return [
            'text' => $text,
            'literals' => $literals,
        ];
    }

    private function readLine(): string
    {
        if (! is_resource($this->stream)) {
            throw new RuntimeException('Kết nối IMAP chưa sẵn sàng.');
        }

        $line = fgets($this->stream);

        if ($line === false) {
            throw new RuntimeException('Không nhận được phản hồi từ IMAP server.');
        }

        return $line;
    }

    private function quote(string $value): string
    {
        return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], $value).'"';
    }

    /**
     * @return array<int, int>
     */
    private function parseSearchUids(string $response): array
    {
        if (preg_match('/^\* SEARCH\s*(.*)$/mi', $response, $matches) !== 1) {
            return [];
        }

        preg_match_all('/\d+/', $matches[1], $uids);

        return array_map('intval', $uids[0] ?? []);
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchSummary(int $uid): array
    {
        $result = $this->command('UID FETCH '.$uid.' (FLAGS RFC822.SIZE BODY.PEEK[HEADER.FIELDS (DATE FROM TO SUBJECT)])');
        $headers = $this->parseHeaders($result['literals'][0] ?? $result['text']);

        return [
            'uid' => $uid,
            'from' => $headers['from'] ?? '(Không rõ người gửi)',
            'to' => $headers['to'] ?? '',
            'subject' => $headers['subject'] ?? '(Không có tiêu đề)',
            'date' => $headers['date'] ?? '',
            'date_human' => $this->formatDate($headers['date'] ?? ''),
            'seen' => str_contains($result['text'], '\\Seen'),
            'size' => $this->parseSize($result['text']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function parseRawMessage(string $rawMessage): array
    {
        [$rawHeaders, $rawBody] = $this->splitHeadersAndBody($rawMessage);
        $headers = $this->parseHeaders($rawHeaders);
        $body = $this->parseBody($rawBody, $headers);

        return [
            'from' => $headers['from'] ?? '(Không rõ người gửi)',
            'to' => $headers['to'] ?? '',
            'subject' => $headers['subject'] ?? '(Không có tiêu đề)',
            'date' => $headers['date'] ?? '',
            'date_human' => $this->formatDate($headers['date'] ?? ''),
            'html' => $body['html'],
            'text' => $body['text'],
            'is_html' => $body['is_html'],
            'attachments' => $body['attachments'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function parseHeaders(string $rawHeaders): array
    {
        $headers = [];
        $current = null;
        $lines = preg_split('/\r\n|\n|\r/', $rawHeaders) ?: [];

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            if (preg_match('/^\s+/', $line) === 1 && $current !== null) {
                $headers[$current] .= ' '.trim($line);

                continue;
            }

            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $current = strtolower(trim($name));
                $headers[$current] = trim($value);
            }
        }

        foreach ($headers as $name => $value) {
            $headers[$name] = $this->decodeMimeHeader($value);
        }

        return $headers;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitHeadersAndBody(string $rawMessage): array
    {
        $parts = preg_split("/\r\n\r\n|\n\n|\r\r/", $rawMessage, 2);

        return [
            $parts[0] ?? '',
            $parts[1] ?? '',
        ];
    }

    /**
     * @param  array<string, string>  $headers
     * @return array{html: string, text: string, is_html: bool, attachments: array<int, string>}
     */
    private function parseBody(string $rawBody, array $headers): array
    {
        $contentTypeRaw = $headers['content-type'] ?? 'text/plain';
        $contentType = strtolower($contentTypeRaw);

        if (str_contains($contentType, 'multipart/')) {
            return $this->parseMultipartBody($rawBody, $contentTypeRaw);
        }

        $decoded = $this->decodeBodyPart($rawBody, $headers);

        if (str_contains($contentType, 'text/html')) {
            return [
                'html' => $this->sanitizeHtml($decoded),
                'text' => trim(strip_tags($decoded)),
                'is_html' => true,
                'attachments' => [],
            ];
        }

        $text = trim($decoded);

        return [
            'html' => nl2br(e($text)),
            'text' => $text,
            'is_html' => false,
            'attachments' => [],
        ];
    }

    /**
     * @return array{html: string, text: string, is_html: bool, attachments: array<int, string>}
     */
    private function parseMultipartBody(string $rawBody, string $contentTypeRaw): array
    {
        if (preg_match('/boundary="?([^";]+)"?/i', $contentTypeRaw, $matches) !== 1) {
            $text = trim($rawBody);

            return [
                'html' => nl2br(e($text)),
                'text' => $text,
                'is_html' => false,
                'attachments' => [],
            ];
        }

        $boundary = $matches[1];
        $sections = preg_split('/^--'.preg_quote($boundary, '/').'(--)?\s*$/m', $rawBody) ?: [];
        $plain = '';
        $html = '';
        $attachments = [];

        foreach ($sections as $section) {
            $section = trim($section);

            if ($section === '' || $section === '--') {
                continue;
            }

            [$partHeadersRaw, $partBodyRaw] = $this->splitHeadersAndBody($section);
            $partHeaders = $this->parseHeaders($partHeadersRaw);
            
            $partContentTypeRaw = $partHeaders['content-type'] ?? 'text/plain';
            $partContentType = strtolower($partContentTypeRaw);
            $partDisposition = strtolower($partHeaders['content-disposition'] ?? '');

            if (str_contains($partDisposition, 'attachment')) {
                $attachments[] = $this->extractFilename($partHeaders['content-disposition'] ?? '') ?? 'attachment';

                continue;
            }

            if (str_contains($partContentType, 'multipart/')) {
                $nested = $this->parseMultipartBody($partBodyRaw, $partContentTypeRaw);
                $plain = $plain !== '' ? $plain : $nested['text'];
                $html = $html !== '' ? $html : $nested['html'];
                $attachments = array_merge($attachments, $nested['attachments']);

                continue;
            }

            $decoded = $this->decodeBodyPart($partBodyRaw, $partHeaders);

            if (str_contains($partContentType, 'text/html') && $html === '') {
                $html = $decoded;
            }

            if (str_contains($partContentType, 'text/plain') && $plain === '') {
                $plain = trim($decoded);
            }
        }

        if ($html !== '') {
            return [
                'html' => $this->sanitizeHtml($html),
                'text' => $plain !== '' ? $plain : trim(strip_tags($html)),
                'is_html' => true,
                'attachments' => $attachments,
            ];
        }

        return [
            'html' => nl2br(e($plain)),
            'text' => $plain,
            'is_html' => false,
            'attachments' => $attachments,
        ];
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function decodeBodyPart(string $body, array $headers): string
    {
        $encoding = strtolower($headers['content-transfer-encoding'] ?? '');
        $decoded = match ($encoding) {
            'base64' => base64_decode(preg_replace('/\s+/', '', $body) ?? '', true) ?: '',
            'quoted-printable' => quoted_printable_decode($body),
            default => $body,
        };

        $contentType = $headers['content-type'] ?? '';
        if (preg_match('/charset="?([^";]+)"?/i', $contentType, $matches) === 1) {
            $charset = strtoupper(trim($matches[1]));
            if ($charset !== '' && $charset !== 'UTF-8') {
                $converted = @mb_convert_encoding($decoded, 'UTF-8', $charset);
                if (is_string($converted)) {
                    return $converted;
                }
            }
        }

        return $decoded;
    }

    private function decodeMimeHeader(string $value): string
    {
        $decoded = @iconv_mime_decode($value, ICONV_MIME_DECODE_CONTINUE_ON_ERROR, 'UTF-8');

        return is_string($decoded) && $decoded !== '' ? $decoded : $value;
    }

    private function sanitizeHtml(string $html): string
    {
        $html = preg_replace('#<(script|style|iframe|object|embed|link|meta)[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = preg_replace('#\s+on[a-z]+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)#is', '', $html) ?? '';
        $html = preg_replace('#(href|src)\s*=\s*([\'"])\s*javascript:.*?\2#is', '$1="#"', $html) ?? '';

        return strip_tags($html, '<p><br><div><span><strong><b><em><i><u><ul><ol><li><blockquote><table><thead><tbody><tr><th><td><a><img><hr><pre><code>');
    }

    private function formatDate(string $date): string
    {
        $timestamp = strtotime($date);

        return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : '';
    }

    private function parseSize(string $response): int
    {
        if (preg_match('/RFC822\.SIZE\s+(\d+)/i', $response, $matches) !== 1) {
            return 0;
        }

        return (int) $matches[1];
    }

    private function extractFilename(string $contentDisposition): ?string
    {
        if (preg_match('/filename="?([^";]+)"?/i', $contentDisposition, $matches) !== 1) {
            return null;
        }

        return $this->decodeMimeHeader($matches[1]);
    }
}
