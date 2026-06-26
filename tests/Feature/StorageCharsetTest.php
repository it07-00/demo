<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class StorageCharsetTest extends TestCase
{
    public function test_custom_storage_route_serves_files_with_correct_charset(): void
    {
        // 1. Fake the default disk
        Storage::fake();

        // 2. Put a test file with Vietnamese text
        $content = "Dự án thử nghiệm UTF-8";
        Storage::put('public/projects/documents/test.txt', $content);

        // 3. Make GET request to the custom storage route
        $response = $this->get('/storage/projects/documents/test.txt');

        // 4. Assert response is OK
        $response->assertStatus(200);

        // 5. Assert the file content is exactly what was stored
        $this->assertSame($content, $response->getContent());

        // 6. Assert Content-Type header specifies charset=utf-8
        $contentType = $response->headers->get('Content-Type');
        $this->assertStringContainsString('text/plain', $contentType);
        $this->assertStringContainsString('charset=utf-8', strtolower($contentType));
    }
}
