<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\DailyReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DailyReport>
 */
final class DailyReportFactory extends Factory
{
    protected $model = DailyReport::class;

    public function definition(): array
    {
        $works = [
            'Lập trình module phân quyền & vai trò sử dụng Spatie và Livewire.',
            'Tối ưu hóa các truy vấn SQL, sửa lỗi kết nối database.',
            'Họp bàn thiết kế tính năng báo cáo ngày với ban Giám đốc.',
            'Viết tài liệu API, hướng dẫn triển khai hệ thống cho khách hàng.',
            'Kiểm thử tự động các tính năng, bổ sung 15 test case cho User Module.',
            'Khắc phục sự cố tải chậm trang lịch công tác, cấu hình cache.',
            'Hỗ trợ phòng kinh doanh cấu hình phần mềm CRM và đồng bộ danh bạ.',
            'Kiểm tra định kỳ sao lưu dữ liệu, bảo mật server, dọn dẹp log file.',
        ];

        $plans = [
            'Tiếp tục tối ưu hóa hiệu năng, chỉnh sửa giao diện xem lịch.',
            'Triển khai bản vá lỗi cho khách hàng thử nghiệm trên môi trường staging.',
            'Viết bộ lọc tìm kiếm nâng cao cho báo cáo ngày và tích hợp FullCalendar.',
            'Kiểm tra lại toàn bộ quyền truy cập (Policy) của các nhóm người dùng.',
            'Đào tạo nội bộ cho phòng kế toán sử dụng phần mềm chấm công mới.',
        ];

        $issues = [
            'Kết nối mạng nội bộ thỉnh thoảng bị gián đoạn, cần liên hệ IT support.',
            'Thiếu tài liệu hướng dẫn kỹ thuật của nhà cung cấp dịch vụ bên thứ ba.',
            'Cần làm rõ thêm yêu cầu nghiệp vụ của phòng kinh doanh về lọc báo cáo.',
            'Không có vấn đề gì lớn.',
        ];

        return [
            'user_id' => User::factory(),
            'report_date' => now()->toDateString(),
            'work_done' => fake()->randomElement($works),
            'plan_tomorrow' => fake()->randomElement($plans),
            'issues' => fake()->optional(0.3)->randomElement($issues), // 30% chance of issues
        ];
    }
}
