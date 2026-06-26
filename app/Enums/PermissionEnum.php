<?php

declare(strict_types=1);

namespace App\Enums;

enum PermissionEnum: string
{
    case DashboardView = 'dashboard.view';
    case UserView = 'user.view';
    case UserCreate = 'user.create';
    case UserUpdate = 'user.update';
    case UserDelete = 'user.delete';
    case ScheduleView = 'schedule.view';
    case ScheduleCreate = 'schedule.create';
    case ScheduleUpdate = 'schedule.update';
    case ScheduleDelete = 'schedule.delete';
    case SettingView = 'setting.view';
    case SettingUpdate = 'setting.update';
    case MailView = 'mail.view';
    case MailSend = 'mail.send';
    case MailUpdate = 'mail.update';
    case DocumentView = 'document.view';
    case ProjectView = 'project.view';
    case ProjectCreate = 'project.create';
    case ProjectUpdate = 'project.update';
    case ProjectDelete = 'project.delete';
    case StaffView = 'staff.view';
    case AnalyticsView = 'analytics.view';
    case CrmView = 'crm.view';
    case CrmCreate = 'crm.create';
    case CrmUpdate = 'crm.update';
    case CrmDelete = 'crm.delete';
    case AlertView = 'alert.view';
    case RoleManage = 'role.manage';
    case ReportView = 'report.view';
    case ReportCreate = 'report.create';
    case ReportUpdate = 'report.update';
    case ReportDelete = 'report.delete';
    case WorkProgressView = 'work_progress.view';
    case WorkProgressCreate = 'work_progress.create';
    case WorkProgressUpdate = 'work_progress.update';
    case WorkProgressManage = 'work_progress.manage';

    public function label(): string
    {
        return match ($this) {
            self::DashboardView => 'Xem Dashboard',
            self::UserView => 'Xem người dùng',
            self::UserCreate => 'Tạo người dùng',
            self::UserUpdate => 'Cập nhật người dùng',
            self::UserDelete => 'Xóa người dùng',
            self::ScheduleView => 'Xem lịch công tác',
            self::ScheduleCreate => 'Tạo lịch công tác',
            self::ScheduleUpdate => 'Cập nhật lịch công tác',
            self::ScheduleDelete => 'Xóa lịch công tác',
            self::SettingView => 'Xem cài đặt hệ thống',
            self::SettingUpdate => 'Cập nhật cài đặt hệ thống',
            self::MailView => 'Xem hộp thư nội bộ',
            self::MailSend => 'Gửi email nội bộ',
            self::MailUpdate => 'Cập nhật cấu hình email',
            self::DocumentView => 'Xem quy định tài liệu',
            self::ProjectView => 'Xem dự án và khách hàng vận hành',
            self::ProjectCreate => 'Tạo dự án vận hành',
            self::ProjectUpdate => 'Cập nhật dự án vận hành',
            self::ProjectDelete => 'Xóa dự án vận hành',
            self::StaffView => 'Xem nhân sự và phân công vận hành',
            self::AnalyticsView => 'Xem KPI và hiệu suất vận hành',
            self::CrmView => 'Xem CRM khách hàng',
            self::CrmCreate => 'Thêm khách hàng CRM',
            self::CrmUpdate => 'Cập nhật khách hàng CRM',
            self::CrmDelete => 'Xóa khách hàng CRM',
            self::AlertView => 'Xem cảnh báo vận hành',
            self::RoleManage => 'Quản lý vai trò và phân quyền',
            self::ReportView => 'Xem báo cáo ngày',
            self::ReportCreate => 'Tạo báo cáo ngày',
            self::ReportUpdate => 'Sửa báo cáo ngày',
            self::ReportDelete => 'Xóa báo cáo ngày',
            self::WorkProgressView => 'Xem tiến độ công việc',
            self::WorkProgressCreate => 'Nhập tiến độ công việc',
            self::WorkProgressUpdate => 'Sửa tiến độ công việc',
            self::WorkProgressManage => 'Quản lý chỉ tiêu tuần',
        };
    }
}
