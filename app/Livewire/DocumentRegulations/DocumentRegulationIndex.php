<?php

declare(strict_types=1);

namespace App\Livewire\DocumentRegulations;

use App\Enums\PermissionEnum;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Quy định Tài liệu')]
final class DocumentRegulationIndex extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()?->can(PermissionEnum::DocumentView->value), 403);
    }

    public function render(): View
    {
        return view('livewire.document-regulations.document-regulation-index', [
            'regulations' => [
                [
                    'code' => 'QD-TL-01',
                    'title' => 'Phân loại tài liệu',
                    'owner' => 'Hành chính',
                    'status' => 'Đang áp dụng',
                    'summary' => 'Tài liệu nội bộ, biểu mẫu, hợp đồng, hồ sơ khách hàng và tài liệu kỹ thuật phải được lưu theo đúng nhóm nghiệp vụ.',
                ],
                [
                    'code' => 'QD-TL-02',
                    'title' => 'Đặt tên và phiên bản',
                    'owner' => 'Quản trị hệ thống',
                    'status' => 'Đang áp dụng',
                    'summary' => 'Tên file cần có mã tài liệu, ngày ban hành và phiên bản; không dùng tên chung chung hoặc trùng lặp gây khó tra cứu.',
                ],
                [
                    'code' => 'QD-TL-03',
                    'title' => 'Ban hành và cập nhật',
                    'owner' => 'Ban giám đốc',
                    'status' => 'Đang áp dụng',
                    'summary' => 'Tài liệu chính thức chỉ được sử dụng sau khi có người phụ trách duyệt và thông báo phiên bản hiện hành.',
                ],
                [
                    'code' => 'QD-TL-04',
                    'title' => 'Bảo mật và chia sẻ',
                    'owner' => 'IT',
                    'status' => 'Đang áp dụng',
                    'summary' => 'Không gửi tài liệu mật ra ngoài hệ thống khi chưa được phê duyệt; mọi bản chia sẻ phải đúng phạm vi người nhận.',
                ],
            ],
        ]);
    }
}
