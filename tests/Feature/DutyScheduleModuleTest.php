<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\DTOs\DutyScheduleDTO;
use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\DutySchedule;
use App\Models\User;
use App\Services\DutyScheduleService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DutyScheduleModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedules_table_has_expected_columns(): void
    {
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create();

        $schedule = DutySchedule::create([
            'title' => 'Meeting with GREECO CEO',
            'description' => 'Discuss partnership opportunities',
            'location' => 'Room 101',
            'start_at' => now()->toDateTimeString(),
            'end_at' => now()->addHour()->toDateTimeString(),
            'label_color' => 'success',
            'created_by' => $user->id,
        ]);

        $this->assertDatabaseHas('duty_schedules', [
            'id' => $schedule->id,
            'title' => 'Meeting with GREECO CEO',
            'label_color' => 'success',
            'created_by' => $user->id,
        ]);
    }

    public function test_authorized_user_can_access_schedules_index(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff = User::factory()->create();
        $staff->givePermissionTo(PermissionEnum::DashboardView->value, PermissionEnum::ScheduleView->value);

        $this->actingAs($staff)
            ->get(route('duty-schedules.index'))
            ->assertOk()
            ->assertSee('Lịch công tác')
            ->assertDontSee('timeGridWeek', false)
            ->assertDontSee('timeGridDay', false);
    }

    public function test_unauthorized_user_cannot_access_schedules_index(): void
    {
        $this->seed(PermissionSeeder::class);

        $guestUser = User::factory()->create();

        $this->actingAs($guestUser)
            ->get(route('duty-schedules.index'))
            ->assertStatus(403);
    }

    public function test_schedule_service_creates_updates_retrieves_and_deletes_schedule(): void
    {
        $this->seed(PermissionSeeder::class);

        $user = User::factory()->create();
        $service = app(DutyScheduleService::class);

        // 1. Create
        $dto = DutyScheduleDTO::fromArray([
            'title' => 'Weekly Sync',
            'description' => 'Regular team sync',
            'location' => 'Online',
            'start_at' => '2026-06-02 10:00:00',
            'end_at' => '2026-06-02 11:00:00',
            'label_color' => 'info',
            'created_by' => $user->id,
        ]);

        $schedule = $service->create($dto);

        $this->assertDatabaseHas('duty_schedules', [
            'id' => $schedule->id,
            'title' => 'Weekly Sync',
            'created_by' => $user->id,
        ]);

        // 2. Retrieve Range
        $events = $service->getEventsInRange('2026-06-01 00:00:00', '2026-06-03 00:00:00');
        $this->assertCount(1, $events);
        $this->assertSame($schedule->id, $events->first()->id);

        // 3. Update
        $updateDto = DutyScheduleDTO::fromArray([
            'title' => 'Weekly Sync Updated',
            'description' => 'Regular team sync desc update',
            'location' => 'Meeting Room A',
            'start_at' => '2026-06-02 10:30:00',
            'end_at' => '2026-06-02 11:30:00',
            'label_color' => 'purple',
        ]);

        $updated = $service->update($schedule, $updateDto);
        $this->assertSame('Weekly Sync Updated', $updated->title);
        $this->assertSame('Meeting Room A', $updated->location);
        $this->assertSame('purple', $updated->label_color);

        // 4. Delete
        $service->delete($updated);

        $this->assertSoftDeleted('duty_schedules', [
            'id' => $updated->id,
        ]);
    }
    public function test_policies_allow_creators_and_admins_to_update_delete_schedules(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff1 = User::factory()->create();
        $staff1->assignRole(RoleEnum::IT->value);

        $staff2 = User::factory()->create();
        $staff2->assignRole(RoleEnum::IT->value);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value);

        $schedule = DutySchedule::create([
            'title' => 'Staff 1 Meeting',
            'start_at' => '2026-06-02 08:00:00',
            'created_by' => $staff1->id,
        ]);

        // Staff 1 can view, update and delete own schedule
        $this->assertTrue($staff1->can('view', $schedule));
        $this->assertTrue($staff1->can('update', $schedule));
        $this->assertTrue($staff1->can('delete', $schedule));

        // Staff 2 can view, but CANNOT update or delete Staff 1's schedule
        $this->assertTrue($staff2->can('view', $schedule));
        $this->assertFalse($staff2->can('update', $schedule));
        $this->assertFalse($staff2->can('delete', $schedule));

        // Admin can view, update and delete Staff 1's schedule
        $this->assertTrue($admin->can('view', $schedule));
        $this->assertTrue($admin->can('update', $schedule));
        $this->assertTrue($admin->can('delete', $schedule));
    }

    public function test_private_schedules_mask_details_for_unauthorized_users(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff1 = User::factory()->create();
        $staff1->assignRole(RoleEnum::IT->value);

        $staff2 = User::factory()->create();
        $staff2->assignRole(RoleEnum::IT->value);

        $director = User::factory()->create();
        $director->assignRole(RoleEnum::Director->value);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::SuperAdmin->value);

        // Create private schedule
        $schedule = DutySchedule::create([
            'title' => 'Secret Strategy Session',
            'description' => 'Top secret details',
            'location' => 'War Room',
            'start_at' => '2026-06-02 09:00:00',
            'end_at' => '2026-06-02 10:00:00',
            'is_private' => true,
            'created_by' => $staff1->id,
        ]);

        // 1. Staff 1 (creator) gets full details
        $this->actingAs($staff1);
        $component1 = \Livewire\Livewire::test(\App\Livewire\DutySchedules\DutyScheduleIndex::class);
        $events1 = $component1->instance()->getEvents('2026-06-01 00:00:00', '2026-06-03 00:00:00');
        $this->assertCount(1, $events1);
        $this->assertStringContainsString('Secret Strategy Session', $events1[0]['title']);
        $this->assertSame('🔒 ' . $staff1->name . ': Secret Strategy Session', $events1[0]['title']);
        $this->assertSame('🔒 Secret Strategy Session', $events1[0]['raw_title']);
        $this->assertSame('Top secret details', $events1[0]['description']);
        $this->assertSame('War Room', $events1[0]['location']);

        // 2. Admin (authorized role) gets full details
        $this->actingAs($admin);
        $componentAdmin = \Livewire\Livewire::test(\App\Livewire\DutySchedules\DutyScheduleIndex::class);
        $eventsAdmin = $componentAdmin->instance()->getEvents('2026-06-01 00:00:00', '2026-06-03 00:00:00');
        $this->assertCount(1, $eventsAdmin);
        $this->assertStringContainsString('Secret Strategy Session', $eventsAdmin[0]['title']);
        $this->assertSame('🔒 ' . $staff1->name . ': Secret Strategy Session', $eventsAdmin[0]['title']);
        $this->assertSame('🔒 Secret Strategy Session', $eventsAdmin[0]['raw_title']);

        // 3. Staff 2 (unauthorized) gets masked details
        $this->actingAs($staff2);
        $component2 = \Livewire\Livewire::test(\App\Livewire\DutySchedules\DutyScheduleIndex::class);
        $events2 = $component2->instance()->getEvents('2026-06-01 00:00:00', '2026-06-03 00:00:00');
        $this->assertCount(1, $events2);
        $this->assertStringNotContainsString('Secret Strategy Session', $events2[0]['title']);
        $this->assertStringContainsString('Lịch riêng tư', $events2[0]['title']);
        $this->assertSame('🔒 Lịch riêng tư', $events2[0]['title']);
        $this->assertSame('🔒 Lịch riêng tư', $events2[0]['raw_title']);
        $this->assertNull($events2[0]['description']);
        $this->assertNull($events2[0]['location']);
        $this->assertContains('bg-secondary-subtle', $events2[0]['classNames']); // mapped to private class

        // 4. Director (authorized role via role check) gets full details
        $this->actingAs($director);
        $componentDirector = \Livewire\Livewire::test(\App\Livewire\DutySchedules\DutyScheduleIndex::class);
        $eventsDirector = $componentDirector->instance()->getEvents('2026-06-01 00:00:00', '2026-06-03 00:00:00');
        $this->assertCount(1, $eventsDirector);
        $this->assertStringContainsString('Secret Strategy Session', $eventsDirector[0]['title']);
        $this->assertSame('🔒 ' . $staff1->name . ': Secret Strategy Session', $eventsDirector[0]['title']);
        $this->assertSame('🔒 Secret Strategy Session', $eventsDirector[0]['raw_title']);
        $this->assertSame('Top secret details', $eventsDirector[0]['description']);
        $this->assertSame('War Room', $eventsDirector[0]['location']);
    }

    public function test_director_can_view_all_day_schedules_via_click(): void
    {
        $this->seed(PermissionSeeder::class);

        $director = User::factory()->create();
        $director->assignRole(RoleEnum::Director->value);

        $staff = User::factory()->create();
        $staff->assignRole(RoleEnum::IT->value);

        // Create a schedule for staff on 2026-06-02
        $schedule = DutySchedule::create([
            'title' => 'Staff Meeting',
            'description' => 'Staff meeting description',
            'location' => 'Meeting Room 1',
            'start_at' => '2026-06-02 10:00:00',
            'end_at' => '2026-06-02 11:00:00',
            'created_by' => $staff->id,
        ]);

        $this->actingAs($director);
        
        $test = \Livewire\Livewire::test(\App\Livewire\DutySchedules\DutyScheduleIndex::class)
            ->call('showDaySchedules', '2026-06-02')
            ->assertSet('selectedDateStr', '02/06/2026')
            ->assertDispatched('schedule:open-day-schedules');

        $daySchedules = $test->instance()->daySchedules;
        $this->assertCount(1, $daySchedules);
        $this->assertSame('Staff Meeting', $daySchedules[0]['title']);
        $this->assertNotSame('N/A', $daySchedules[0]['creator_name']);
    }

    public function test_user_can_create_duty_schedule_with_participants(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(RoleEnum::IT->value);

        $participant1 = User::factory()->create();
        $participant2 = User::factory()->create();

        $this->actingAs($staff);

        $futureDate = now()->addDays(2)->format('Y-m-d\TH:i');

        \Livewire\Livewire::test(\App\Livewire\DutySchedules\DutyScheduleIndex::class)
            ->set('title', 'Project Sync')
            ->set('start_at', $futureDate)
            ->set('user_ids', [$participant1->id, $participant2->id])
            ->call('save')
            ->assertHasNoErrors();

        $schedule = DutySchedule::where('title', 'Project Sync')->first();
        $this->assertNotNull($schedule);
        $this->assertCount(2, $schedule->users);
        $this->assertTrue($schedule->users->contains($participant1->id));
        $this->assertTrue($schedule->users->contains($participant2->id));
    }

    public function test_director_can_create_duty_schedule(): void
    {
        $this->seed(PermissionSeeder::class);

        $director = User::factory()->create();
        $director->assignRole(RoleEnum::Director->value);

        $this->actingAs($director);

        $this->assertTrue($director->can('create', DutySchedule::class));

        $futureDate = now()->addDays(2)->format('Y-m-d\TH:i');

        \Livewire\Livewire::test(\App\Livewire\DutySchedules\DutyScheduleIndex::class)
            ->set('title', 'Director Strategy Sync')
            ->set('start_at', $futureDate)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('duty_schedules', [
            'title' => 'Director Strategy Sync',
            'created_by' => $director->id,
        ]);
    }

    public function test_cannot_create_duty_schedule_in_the_past(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff = User::factory()->create();
        $staff->assignRole(RoleEnum::IT->value);

        $this->actingAs($staff);

        $pastDate = now()->subDays(2)->format('Y-m-d\TH:i');

        \Livewire\Livewire::test(\App\Livewire\DutySchedules\DutyScheduleIndex::class)
            ->set('title', 'Past Sync')
            ->set('start_at', $pastDate)
            ->call('save')
            ->assertHasErrors(['start_at']);

        // Check openCreate with past date triggers swal:alert and doesn't dispatch open-create
        $pastDateStr = now()->subDays(2)->format('Y-m-d');
        \Livewire\Livewire::test(\App\Livewire\DutySchedules\DutyScheduleIndex::class)
            ->call('openCreate', $pastDateStr)
            ->assertNotDispatched('schedule:open-create')
            ->assertDispatched('swal:alert');
    }

    public function test_can_filter_duty_schedules_by_participant_or_creator(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff1 = User::factory()->create();
        $staff1->assignRole(RoleEnum::IT->value);

        $staff2 = User::factory()->create();
        $staff2->assignRole(RoleEnum::IT->value);

        $service = app(DutyScheduleService::class);

        // Schedule 1: Created by staff1, no participants
        $s1 = DutySchedule::create([
            'title' => 'Schedule 1',
            'start_at' => '2026-06-02 08:00:00',
            'created_by' => $staff1->id,
        ]);

        // Schedule 2: Created by staff2, with participant staff1
        $s2 = DutySchedule::create([
            'title' => 'Schedule 2',
            'start_at' => '2026-06-02 09:00:00',
            'created_by' => $staff2->id,
        ]);
        $s2->users()->sync([$staff1->id]);

        // Schedule 3: Created by staff2, no participants
        $s3 = DutySchedule::create([
            'title' => 'Schedule 3',
            'start_at' => '2026-06-02 10:00:00',
            'created_by' => $staff2->id,
        ]);

        // Filter by staff1
        $this->actingAs($staff1);
        $component = \Livewire\Livewire::test(\App\Livewire\DutySchedules\DutyScheduleIndex::class)
            ->set('filterUserId', $staff1->id);

        $events = $component->instance()->getEvents('2026-06-02 00:00:00', '2026-06-02 23:59:59');

        // Should return Schedule 1 and Schedule 2, but not Schedule 3
        $eventIds = collect($events)->pluck('id')->toArray();
        $this->assertContains($s1->id, $eventIds);
        $this->assertContains($s2->id, $eventIds);
        $this->assertNotContains($s3->id, $eventIds);
    }
}
