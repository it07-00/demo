<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\DailyReportDTO;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Livewire\DailyReports\DailyReportIndex;
use App\Models\DailyReport;
use App\Models\User;
use App\Services\DailyReportService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

final class DailyReportModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_table_has_expected_columns(): void
    {
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create();

        $report = DailyReport::create([
            'user_id' => $user->id,
            'report_date' => '2026-06-02',
            'work_done' => 'Hoàn thành tích hợp tính năng báo cáo ngày.',
            'plan_tomorrow' => 'Xây dựng giao diện xem dạng calendar.',
            'issues' => 'Không có khó khăn lớn.',
        ]);

        $this->assertDatabaseHas('daily_reports', [
            'id' => $report->id,
            'user_id' => $user->id,
            'report_date' => '2026-06-02 00:00:00',
            'work_done' => 'Hoàn thành tích hợp tính năng báo cáo ngày.',
        ]);
    }

    public function test_authorized_user_can_access_reports_index(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff = User::factory()->create();
        $staff->givePermissionTo(PermissionEnum::DashboardView->value, PermissionEnum::ReportView->value);

        $this->actingAs($staff)
            ->get(route('daily-reports.index'))
            ->assertOk()
            ->assertSee('Báo cáo Ngày');
    }

    public function test_unauthorized_user_cannot_access_reports_index(): void
    {
        $this->seed(PermissionSeeder::class);

        $guestUser = User::factory()->create();

        $this->actingAs($guestUser)
            ->get(route('daily-reports.index'))
            ->assertStatus(403);
    }

    public function test_report_service_creates_updates_retrieves_and_deletes_report(): void
    {
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create();
        $service = app(DailyReportService::class);

        // 1. Create
        $dto = DailyReportDTO::fromArray([
            'user_id' => $user->id,
            'report_date' => '2026-06-02',
            'work_done' => 'Lập trình backend API cho báo cáo',
            'plan_tomorrow' => 'Viết unit test cho service',
            'issues' => 'Thiếu dữ liệu mẫu',
        ]);

        $report = $service->createReport($dto);

        $this->assertDatabaseHas('daily_reports', [
            'id' => $report->id,
            'work_done' => 'Lập trình backend API cho báo cáo',
            'user_id' => $user->id,
        ]);

        // 2. Exists
        $this->assertTrue($service->existsForDate($user->id, '2026-06-02'));
        $this->assertFalse($service->existsForDate($user->id, '2026-06-03'));

        // 3. Update
        $updateDto = DailyReportDTO::fromArray([
            'user_id' => $user->id,
            'report_date' => '2026-06-02',
            'work_done' => 'Lập trình backend API cho báo cáo (Đã cập nhật)',
            'plan_tomorrow' => 'Viết tích hợp test',
            'issues' => null,
        ]);

        $updated = $service->updateReport($report, $updateDto);
        $this->assertSame('Lập trình backend API cho báo cáo (Đã cập nhật)', $updated->work_done);
        $this->assertNull($updated->issues);

        // 4. Delete
        $service->deleteReport($updated);
        $this->assertSoftDeleted('daily_reports', [
            'id' => $updated->id,
        ]);
    }

    public function test_director_can_view_all_but_cannot_create(): void
    {
        $this->seed(PermissionSeeder::class);

        $director = User::factory()->create();
        $director->assignRole(RoleEnum::Director->value);

        $staff = User::factory()->create();
        $staff->assignRole(RoleEnum::IT->value);

        $report = DailyReport::create([
            'user_id' => $staff->id,
            'report_date' => '2026-06-02',
            'work_done' => 'Staff reported work',
        ]);

        // Director can view staff's report
        $this->assertTrue($director->can('view', $report));

        // Director CANNOT create reports
        $this->assertFalse($director->can('create', DailyReport::class));

        // Director component mount configures viewMode as 'all' and hides tab switcher
        $this->actingAs($director);
        Livewire::test(DailyReportIndex::class)
            ->assertSet('viewMode', 'all')
            ->assertSet('filterUserId', 0)
            ->assertDontSee('Của tôi');
    }

    public function test_report_employee_filter_lists_report_creators_only(): void
    {
        $this->seed(PermissionSeeder::class);

        $director = User::factory()->create();
        $director->assignRole(RoleEnum::Director->value);

        $staff = User::factory()->create();
        $staff->assignRole(RoleEnum::IT->value);

        $plainUser = User::factory()->create();

        $this->actingAs($director);

        Livewire::test(DailyReportIndex::class)
            ->assertViewHas('users', function ($users) use ($director, $staff, $plainUser) {
                $ids = $users->pluck('id');

                return $ids->contains($staff->id)
                    && ! $ids->contains($director->id)
                    && ! $ids->contains($plainUser->id);
            });
    }

    public function test_super_admin_can_see_view_mode_tabs(): void
    {
        $this->seed(PermissionSeeder::class);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value);

        $this->actingAs($admin);

        Livewire::test(DailyReportIndex::class)
            ->assertSee('Của tôi')
            ->assertSee('Tất cả');
    }

    public function test_get_events_for_calendar(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(RoleEnum::IT->value);

        $report = DailyReport::create([
            'user_id' => $staff->id,
            'report_date' => '2026-06-02',
            'work_done' => 'Staff task details for calendar testing',
            'issues' => 'Some blocker issues',
        ]);

        $this->actingAs($staff);

        // Fetch events through Livewire component
        $component = Livewire::test(DailyReportIndex::class);
        $events = $component->instance()->getEvents('2026-06-01', '2026-06-03', app(DailyReportService::class));

        $this->assertCount(1, $events);
        $this->assertSame($report->id, $events[0]['id']);
        $this->assertStringContainsString('Staff task details', $events[0]['title']);
        $this->assertSame('2026-06-02', $events[0]['start']);
        $this->assertTrue($events[0]['allDay']);
        $this->assertContains('bg-warning-subtle', $events[0]['classNames']); // color based on issues present
    }

    public function test_validation_rules_for_creating_report(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(RoleEnum::IT->value);

        $this->actingAs($staff);

        // 1. Check validation empty/short work_done
        Livewire::test(DailyReportIndex::class)
            ->set('formDate', '2026-06-02')
            ->set('formWorkDone', 'Short') // less than 10 characters
            ->call('save')
            ->assertHasErrors(['formWorkDone' => 'min']);

        // 2. Check validation missing date
        Livewire::test(DailyReportIndex::class)
            ->set('formDate', '')
            ->set('formWorkDone', 'Học tập nghiên cứu lập trình Laravel 12.')
            ->call('save')
            ->assertHasErrors(['formDate' => 'required']);
    }

    public function test_cannot_create_two_reports_for_same_date(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(RoleEnum::IT->value);

        $this->actingAs($staff);

        // Pre-create a report
        DailyReport::create([
            'user_id' => $staff->id,
            'report_date' => '2026-06-02',
            'work_done' => 'Đã báo cáo công việc ngày hôm nay.',
        ]);

        // Trying to create another report for the same day should fail validation
        Livewire::test(DailyReportIndex::class)
            ->set('formDate', '2026-06-02')
            ->set('formWorkDone', 'Báo cáo công việc trùng lặp ngày.')
            ->call('save')
            ->assertHasErrors(['formDate']);
    }

    public function test_staff_cannot_view_others_reports(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff1 = User::factory()->create();
        $staff1->assignRole(RoleEnum::IT->value);

        $staff2 = User::factory()->create();
        $staff2->assignRole(RoleEnum::IT->value);

        $reportOfStaff2 = DailyReport::create([
            'user_id' => $staff2->id,
            'report_date' => '2026-06-02',
            'work_done' => 'Công việc riêng của Staff 2',
        ]);

        // Staff 1 CANNOT view Staff 2's report detail (policy returns false)
        $this->actingAs($staff1);
        $this->assertFalse($staff1->can('view', $reportOfStaff2));
        $this->assertFalse($staff1->can('update', $reportOfStaff2));

        // Staff 2 CAN view and update their own report
        $this->actingAs($staff2);
        $this->assertTrue($staff2->can('view', $reportOfStaff2));
        $this->assertTrue($staff2->can('update', $reportOfStaff2));
    }

    public function test_filter_and_search_logic(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(RoleEnum::IT->value);

        $date1 = now()->subDay()->toDateString();
        $date2 = now()->toDateString();

        // Create reports
        DailyReport::create([
            'user_id' => $staff->id,
            'report_date' => $date1,
            'work_done' => 'Công việc thiết kế database',
        ]);

        DailyReport::create([
            'user_id' => $staff->id,
            'report_date' => $date2,
            'work_done' => 'Công việc lập trình frontend',
        ]);

        $this->actingAs($staff);

        // 1. Search filter
        Livewire::test(DailyReportIndex::class)
            ->set('search', 'lập trình')
            ->assertViewHas('reports', function ($reports) {
                return $reports->count() === 1 && str_contains($reports->first()->work_done, 'lập trình');
            });

        // 2. Date filter
        Livewire::test(DailyReportIndex::class)
            ->set('filterDate', $date1)
            ->assertViewHas('reports', function ($reports) {
                return $reports->count() === 1 && str_contains($reports->first()->work_done, 'database');
            });
    }

    public function test_director_can_view_all_day_reports_via_click(): void
    {
        $this->seed(PermissionSeeder::class);

        $director = User::factory()->create();
        $director->assignRole(RoleEnum::Director->value);

        $staff = User::factory()->create();
        $staff->assignRole(RoleEnum::IT->value);

        // Create a report on 2026-06-02
        $report = DailyReport::create([
            'user_id' => $staff->id,
            'report_date' => '2026-06-02',
            'work_done' => 'Staff reported work done on 2026-06-02',
        ]);

        $this->actingAs($director);

        $test = Livewire::test(DailyReportIndex::class)
            ->call('showDayReports', '2026-06-02')
            ->assertSet('selectedDateStr', '02/06/2026')
            ->assertDispatched('report:open-day-reports');

        $dayReports = $test->instance()->dayReports;
        $this->assertCount(1, $dayReports);
        $this->assertSame('Staff reported work done on 2026-06-02', $dayReports[0]['work_done']);
        $this->assertSame($staff->name, $dayReports[0]['user_name']);
    }

    public function test_report_index_defaults_to_filtering_current_date(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(RoleEnum::IT->value);

        $this->actingAs($staff);

        // Mount should default filterDate to today
        $component = Livewire::test(DailyReportIndex::class)
            ->assertSet('filterDate', now()->toDateString());

        // Switching viewMode should also reset filterDate to today
        $component->set('filterDate', '2026-06-01')
            ->set('viewMode', 'mine')
            ->assertSet('filterDate', now()->toDateString());
    }
}
