<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DocumentRegulationModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_user_can_access_document_regulations_index(): void
    {
        $this->seed(PermissionSeeder::class);

        $director = User::factory()->create();
        $director->assignRole(RoleEnum::Director->value);

        $this->actingAs($director)
            ->get(route('document-regulations.index'))
            ->assertOk()
            ->assertSee('Quy định Tài liệu');
    }

    public function test_unauthorized_user_cannot_access_document_regulations_index(): void
    {
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('document-regulations.index'))
            ->assertStatus(403);
    }
}
