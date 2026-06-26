<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Livewire\Operations\CrmIndex;
use App\Livewire\Operations\ProjectCrud;
use App\Models\OperationCrmCustomer;
use App\Models\User;
use App\Services\OperationDataService;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

final class OperationsModuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_access_operations_modules(): void
    {
        $this->seed();

        $user = User::query()->where('username', 'superadmin')->firstOrFail();
        $user->assignRole(RoleEnum::SuperAdmin->value);

        $routes = [
            route('operations.projects'),
            route('operations.daily'),
            route('operations.staff'),
            route('operations.analytics'),
            route('operations.crm'),
            route('operations.alerts'),
        ];

        foreach ($routes as $route) {
            $this->actingAs($user)
                ->get($route)
                ->assertOk();
        }
    }

    public function test_operation_data_service_is_database_only_when_operation_tables_are_empty(): void
    {
        $this->seed(PermissionSeeder::class);

        $data = app(OperationDataService::class)->all();

        $this->assertSame([], $data['projects']);
        $this->assertSame([], $data['report_history']);
        $this->assertSame([], $data['receivables']);
        $this->assertSame([], $data['crm_customers']);
    }

    public function test_operations_sidebar_links_are_available_to_director(): void
    {
        $this->seed();

        $director = User::query()->where('username', 'giamdoc')->firstOrFail();

        $this->actingAs($director)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('operations.projects'), false)
            ->assertSee(route('operations.daily'), false)
            ->assertSee(route('operations.staff'), false)
            ->assertSee(route('operations.analytics'), false)
            ->assertSee(route('operations.crm'), false)
            ->assertSee(route('operations.alerts'), false);
    }

    public function test_projects_page_targets_project_crud_livewire_events(): void
    {
        $this->seed();

        $director = User::query()->where('username', 'giamdoc')->firstOrFail();

        $this->actingAs($director)
            ->get(route('operations.projects'))
            ->assertOk()
            ->assertSee("\$dispatchTo('operations.project-crud', 'project:open-create')", false)
            ->assertSee("\$dispatchTo('operations.project-crud', 'project:open-edit'", false)
            ->assertDontSee("\$dispatch('project:open-create')", false);
    }

    public function test_director_can_manage_crm_customer_profiles(): void
    {
        $this->seed();

        $director = User::query()->where('username', 'giamdoc')->firstOrFail();

        Livewire::actingAs($director)
            ->test(CrmIndex::class)
            ->set('name', 'Công ty Kiểm thử CRM')
            ->set('type', 'Khách hàng tiềm năng')
            ->set('stage_idx', 1)
            ->set('relationshipField', 'Tốt')
            ->set('contact_name', 'Nguyễn Văn CRM')
            ->set('contact_role', 'Giám đốc nhân sự')
            ->set('contact_phone', '0900000000')
            ->set('contact_email', 'crm@example.com')
            ->set('source', 'Referral')
            ->set('priorityField', 'Cao')
            ->set('owner_name', 'Sales Admin')
            ->set('revenue_monthly', 125)
            ->set('last_meeting', '2026-06-20')
            ->set('next_meeting', '2026-06-28')
            ->set('next_action', 'Gửi báo giá')
            ->set('notesText', "Đã trao đổi nhu cầu\nQuan tâm SLA")
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('operation_crm_customers', [
            'name' => 'Công ty Kiểm thử CRM',
            'stage' => 'Ăn cafe',
            'stage_idx' => 1,
            'priority' => 'Cao',
            'contact_email' => 'crm@example.com',
        ]);
    }

    public function test_director_can_move_and_pause_crm_customer(): void
    {
        $this->seed();

        $director = User::query()->where('username', 'giamdoc')->firstOrFail();
        $customer = OperationCrmCustomer::query()->firstOrFail();

        Livewire::actingAs($director)
            ->test(CrmIndex::class)
            ->call('moveStage', $customer->id, 3)
            ->call('toggleActive', $customer->id)
            ->assertHasNoErrors();

        $customer->refresh();

        $this->assertSame('Duy trì & mở rộng', $customer->stage);
        $this->assertSame(3, (int) $customer->stage_idx);
        $this->assertFalse((bool) $customer->active);
    }

    public function test_director_can_upload_project_document(): void
    {
        Storage::fake('public');

        $this->seed();

        $director = User::query()->where('username', 'giamdoc')->firstOrFail();
        $file = UploadedFile::fake()->create('hop-dong.pdf', 128, 'application/pdf');

        $component = Livewire::actingAs($director)
            ->test(ProjectCrud::class)
            ->call('openCreate')
            ->set('formDocType', 'Hợp đồng')
            ->set('formDocFile', $file)
            ->call('addDocument')
            ->assertHasNoErrors();

        $docs = $component->get('formDocs');

        $this->assertCount(1, $docs);
        $this->assertSame('hop-dong.pdf', $docs[0]['name']);
        $this->assertSame('Hợp đồng', $docs[0]['type']);
        Storage::disk('public')->assertExists($docs[0]['path']);
    }
}
