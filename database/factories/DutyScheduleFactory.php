<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DutySchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DutySchedule>
 */
final class DutyScheduleFactory extends Factory
{
    protected $model = DutySchedule::class;

    public function definition(): array
    {
        $titles = [
            'Họp giao ban tuần đầu tháng',
            'Gặp gỡ khách hàng ký hợp đồng',
            'Họp kỹ thuật triển khai dự án',
            'Kiểm tra định kỳ trang thiết bị',
            'Phỏng vấn ứng viên tuyển dụng mới',
            'Đào tạo nội bộ hệ thống CRM',
            'Thăm và làm việc tại chi nhánh',
            'Báo cáo tài chính tháng',
        ];

        $locations = [
            'Phòng họp tầng 2',
            'Online (Google Meet)',
            'Văn phòng đối tác',
            'Chi nhánh Quận 1',
            'Phòng Lab Kỹ thuật',
            'Phòng Giám đốc',
        ];

        $colors = ['primary', 'success', 'warning', 'danger', 'info', 'purple'];

        $start = fake()->dateTimeBetween('-5 days', '+5 days');
        $end = (clone $start)->modify('+1 hour');

        return [
            'title' => fake()->randomElement($titles),
            'description' => fake()->sentence(),
            'location' => fake()->randomElement($locations),
            'start_at' => $start,
            'end_at' => $end,
            'label_color' => fake()->randomElement($colors),
            'is_private' => fake()->boolean(20), // 20% chance of being private
            'created_by' => User::factory(),
        ];
    }
}
