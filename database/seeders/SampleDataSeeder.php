<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Models\DailyReport;
use App\Models\DutySchedule;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class SampleDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create target users for each role with predictable emails
        $users = [];

        $roleUsers = [
            RoleEnum::Director->value => [
                'name' => 'Nguyễn Giám Đốc',
                'username' => 'giamdoc',
                'email' => 'giamdoc@example.com',
            ],
            RoleEnum::IT->value => [
                'name' => 'Trần Kỹ Thuật (IT)',
                'username' => 'it',
                'email' => 'it@example.com',
            ],
            RoleEnum::Sales->value => [
                'name' => 'Lê Kinh Doanh',
                'username' => 'sales',
                'email' => 'sales@example.com',
            ],
            RoleEnum::Accountant->value => [
                'name' => 'Phạm Kế Toán',
                'username' => 'ketoan',
                'email' => 'ketoan@example.com',
            ],
            RoleEnum::Consultant->value => [
                'name' => 'Hoàng Tư Vấn',
                'username' => 'tuvan',
                'email' => 'tuvan@example.com',
            ],
        ];

        foreach ($roleUsers as $roleName => $data) {
            $user = User::query()->firstOrCreate(
                ['username' => $data['username']],
                [
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make('password'),
                ]
            );

            // Sync role to user
            $user->syncRoles([$roleName]);
            $users[] = $user;
        }

        foreach ($this->operationStaff() as $staff) {
            $roleName = $staff['operation_role'] === 'Quản lý vận hành'
                ? RoleEnum::Director->value
                : RoleEnum::Consultant->value;
            $username = $this->usernameFor($staff['name']);

            $user = User::query()->updateOrCreate(
                ['username' => $username],
                [
                    'name' => $staff['name'],
                    'email' => $username.'@ttvh-thanhcong.local',
                    'password' => Hash::make('password'),
                    'operation_branch' => $staff['operation_branch'],
                    'operation_role' => $staff['operation_role'],
                    'employment_status' => $staff['employment_status'],
                ],
            );

            $user->syncRoles([$roleName]);
            $users[] = $user;
        }

        // Fetch super admin if exists
        $superAdmin = User::query()->where('username', 'superadmin')->first();
        if ($superAdmin) {
            $users[] = $superAdmin;
        }

        // 2. Generate daily reports for the past 7 days
        // Staff users can create reports. Directors do not need to.
        $staffUsers = array_filter($users, function (User $u) {
            return ! $u->hasRole(RoleEnum::Director->value);
        });

        foreach ($staffUsers as $user) {
            for ($i = 0; $i < 7; $i++) {
                $date = now()->subDays($i)->toDateString();

                DailyReport::query()->firstOrCreate(
                    [
                        'user_id' => $user->id,
                        'report_date' => $date,
                    ],
                    DailyReport::factory()->make([
                        'user_id' => $user->id,
                        'report_date' => $date,
                    ])->toArray(),
                );
            }
        }

        // 3. Generate duty schedules for the past 5 days and next 5 days
        foreach ($users as $user) {
            if (DutySchedule::query()->where('created_by', $user->id)->exists()) {
                continue;
            }

            // Create 3-4 schedules for each user at random dates
            for ($i = 0; $i < 4; $i++) {
                $start = now()->addDays(rand(-5, 5))->setHour(rand(8, 16))->setMinute(0)->setSecond(0);
                $end = (clone $start)->addHour(rand(1, 3));

                DutySchedule::factory()->create([
                    'created_by' => $user->id,
                    'start_at' => $start,
                    'end_at' => $end,
                ]);
            }
        }
    }

    /**
     * @return array<int, array{name: string, operation_branch: string, operation_role: string, employment_status: string}>
     */
    private function operationStaff(): array
    {
        return [
            ['name' => 'Đàm Ngọc Anh', 'operation_branch' => 'Bắc Ninh', 'operation_role' => 'Quản lý vận hành', 'employment_status' => 'Chính thức'],
            ['name' => 'Hoàng Tuấn Bảo', 'operation_branch' => 'Bắc Ninh', 'operation_role' => 'Chuyên viên vận hành', 'employment_status' => 'Chính thức'],
            ['name' => 'Hoàng Đình Anh', 'operation_branch' => 'Bắc Ninh', 'operation_role' => 'Chuyên viên vận hành', 'employment_status' => 'Chính thức'],
            ['name' => 'Lý Văn Quyết', 'operation_branch' => 'Bắc Ninh', 'operation_role' => 'Chuyên viên vận hành', 'employment_status' => 'Thử việc'],
            ['name' => 'Phùng Minh Hiếu', 'operation_branch' => 'Bắc Ninh', 'operation_role' => 'Chuyên viên vận hành', 'employment_status' => 'Chính thức'],
            ['name' => 'Trần Thu Thủy', 'operation_branch' => 'Bắc Ninh', 'operation_role' => 'Chuyên viên vận hành', 'employment_status' => 'Chính thức'],
            ['name' => 'Quách Hồng Ánh', 'operation_branch' => 'Bắc Giang', 'operation_role' => 'Quản lý vận hành', 'employment_status' => 'Chính thức'],
            ['name' => 'Nguyễn Thạc Hùng', 'operation_branch' => 'Bắc Giang', 'operation_role' => 'Chuyên viên vận hành', 'employment_status' => 'Chính thức'],
            ['name' => 'Nguyễn Văn Anh', 'operation_branch' => 'Bắc Giang', 'operation_role' => 'Chuyên viên vận hành', 'employment_status' => 'Chính thức'],
            ['name' => 'Phan Văn Khánh', 'operation_branch' => 'Bắc Giang', 'operation_role' => 'Chuyên viên vận hành', 'employment_status' => 'Chính thức'],
            ['name' => 'Lê Thị Thùy Vân', 'operation_branch' => 'Hà Nam - Nam Định', 'operation_role' => 'Quản lý vận hành', 'employment_status' => 'Chính thức'],
            ['name' => 'Nguyễn Huyền My', 'operation_branch' => 'Nam Định', 'operation_role' => 'Chuyên viên vận hành', 'employment_status' => 'Chính thức'],
            ['name' => 'Ngô Trọng Tài', 'operation_branch' => 'Đà Nẵng', 'operation_role' => 'Quản lý vận hành', 'employment_status' => 'Chính thức'],
            ['name' => 'Võ Tấn Minh', 'operation_branch' => 'Đà Nẵng', 'operation_role' => 'Chuyên viên vận hành', 'employment_status' => 'Chính thức'],
            ['name' => 'Nguyễn Thị Thơm', 'operation_branch' => 'Hà Nam', 'operation_role' => 'Chuyên viên vận hành', 'employment_status' => 'Chính thức'],
            ['name' => 'Vũ Thị Diệu Thúy', 'operation_branch' => 'Hà Nam', 'operation_role' => 'Chuyên viên vận hành', 'employment_status' => 'Chính thức'],
            ['name' => 'Nguyễn Thị Thúy Vân', 'operation_branch' => 'Nghệ An', 'operation_role' => 'Chuyên viên vận hành', 'employment_status' => 'Thử việc'],
        ];
    }

    private function usernameFor(string $name): string
    {
        return Str::of($name)
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString();
    }
}
