<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\OperationCrmCustomer;
use App\Models\OperationProject;
use App\Models\OperationReceivable;
use App\Models\OperationRecruitmentReport;
use App\Models\OperationResponsibility;
use App\Services\OperationDataService;
use Illuminate\Database\Seeder;

final class OperationDataSeeder extends Seeder
{
    private const array CRM_STAGES = [
        'Đang trao đổi',
        'Ăn cafe',
        'Ký hợp đồng dài hạn',
        'Duy trì & mở rộng',
    ];

    public function run(OperationDataService $operations): void
    {
        $snapshot = $operations->generatedSnapshot();

        foreach ($snapshot['responsibilities'] as $responsibility) {
            OperationResponsibility::query()->updateOrCreate(
                ['no' => $responsibility['no']],
                [
                    'phase' => $responsibility['phase'],
                    'name' => $responsibility['name'],
                ],
            );
        }

        $projectByExternalId = [];

        foreach ($snapshot['projects'] as $project) {
            $model = OperationProject::query()->updateOrCreate(
                ['external_id' => $project['id']],
                [
                    'code' => $project['code'],
                    'name' => $project['name'],
                    'customer' => $project['customer'],
                    'customer_type' => $project['customer_type'],
                    'branch' => $project['branch'],
                    'product' => $project['product'],
                    'method' => $project['method'],
                    'policy' => $project['policy'],
                    'unit_price' => $project['unit_price'],
                    'recruit_status' => $project['recruit_status'],
                    'manager_external_id' => $project['manager_id'],
                    'manager_name' => $project['manager_name'],
                    'unassigned' => $project['unassigned'],
                    'team' => $project['team'],
                    'status' => $project['status'],
                    'demand' => $project['demand'],
                    'actual' => $project['actual'],
                    'shortage' => $project['shortage'],
                    'progress' => $project['progress'],
                    'contract_start' => $project['contract_start']->toDateString(),
                    'contract_end' => $project['contract_end']->toDateString(),
                    'paused_days' => $project['paused_days'],
                    'reported_today' => $project['reported_today'],
                    'docs' => $project['docs'],
                ],
            );

            $projectByExternalId[$project['id']] = $model;
        }

        foreach ($snapshot['report_history'] as $report) {
            $project = $projectByExternalId[$report['project_id']] ?? null;

            if (! $project instanceof OperationProject) {
                continue;
            }

            OperationRecruitmentReport::query()->updateOrCreate(
                [
                    'operation_project_id' => $project->id,
                    'report_date' => $report['date']->toDateString(),
                ],
                [
                    'branch' => $report['branch'],
                    'customer' => $report['customer'],
                    'manager' => $report['manager'],
                    'demand' => $report['demand'],
                    'method' => $report['method'],
                    'registered' => $report['registered'],
                    'interviewed' => $report['interviewed'],
                    'passed' => $report['passed'],
                    'started' => $report['started'],
                    'partner_trial' => $report['partner_trial'],
                    'rank' => $report['rank'],
                    'reporter' => $report['reporter'],
                    'reported_at' => $report['reported_at'],
                    'issues' => $report['issues'] ?? null,
                    'approved' => $report['approved'],
                ],
            );
        }

        foreach ($snapshot['receivables'] as $receivable) {
            OperationReceivable::query()->updateOrCreate(
                ['external_id' => $receivable['id']],
                [
                    'customer' => $receivable['customer'],
                    'amount' => $receivable['amount'],
                    'due_date' => $receivable['due_date']->toDateString(),
                    'state' => $receivable['state'],
                    'note' => $receivable['note'],
                    'paid' => $receivable['paid'],
                ],
            );
        }

        foreach ($snapshot['crm_customers'] as $customer) {
            $stageIndex = (int) $customer['stage_idx'];

            OperationCrmCustomer::query()->updateOrCreate(
                ['name' => $customer['name']],
                [
                    'type' => $customer['type'],
                    'stage' => self::CRM_STAGES[$stageIndex] ?? self::CRM_STAGES[0],
                    'stage_idx' => $stageIndex,
                    'relationship' => $this->normalizeRelationship((string) $customer['relationship']),
                    'contact_name' => $customer['contact_name'],
                    'contact_role' => $customer['contact_role'],
                    'contact_phone' => null,
                    'contact_email' => null,
                    'source' => $stageIndex < 2 ? 'Khai thác kinh doanh' : 'Khách hàng hiện hữu',
                    'priority' => $stageIndex >= 2 ? 'Cao' : 'Bình thường',
                    'owner_name' => null,
                    'revenue_monthly' => $customer['revenue_monthly'],
                    'last_meeting' => $customer['last_meeting']->toDateString(),
                    'next_meeting' => $customer['next_meeting']->toDateString(),
                    'next_action' => $stageIndex < 2 ? 'Chốt lịch gặp và cập nhật nhu cầu tuyển dụng' : 'Rà soát chất lượng dịch vụ và cơ hội mở rộng',
                    'active' => true,
                    'notes' => $customer['notes'],
                ],
            );
        }
    }

    private function normalizeRelationship(string $relationship): string
    {
        return match ($relationship) {
            'Ráº¥t tá»‘t' => 'Rất tốt',
            'Tá»‘t' => 'Tốt',
            'BÃ¬nh thÆ°á»ng' => 'Bình thường',
            default => in_array($relationship, ['Rất tốt', 'Tốt', 'Bình thường', 'Cần chăm sóc'], true)
                ? $relationship
                : 'Cần chăm sóc',
        };
    }
}
