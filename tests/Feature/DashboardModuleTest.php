<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\DailyReport;
use App\Models\DutySchedule;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_renders_stats_and_recent_items(): void
    {
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create();
        $user->assignRole(RoleEnum::SuperAdmin->value);

        // Create some reports and schedules for today
        DailyReport::create([
            'user_id' => $user->id,
            'report_date' => now()->toDateString(),
            'work_done' => 'Developed dashboard modules test cases.',
        ]);

        DutySchedule::create([
            'title' => 'Project Launch Meeting',
            'start_at' => now()->format('Y-m-d H:i:s'),
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Dashboard')
            ->assertSee('/document-regulations')
            ->assertSee('Thành viên')
            ->assertSee('Vai trò')
            ->assertSee('Lịch hôm nay')
            ->assertSee('Báo cáo hôm nay')
            ->assertSee('Developed dashboard modules test cases.')
            ->assertSee('Project Launch Meeting');
    }
}
