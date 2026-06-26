<div>
    <div class="app-page-head d-flex align-items-center justify-content-between">
        <div class="clearfix">
            <h1 class="app-page-title">Cài đặt hệ thống</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Cài đặt
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    @if ($successMessage)
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fi fi-rr-check me-2"></i> {{ $successMessage }}
            <button type="button" class="btn-close" wire:click="$set('successMessage', null)" aria-label="Close"></button>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header border-bottom py-3">
                    <ul class="nav nav-pills gap-2" id="settingsTab" role="tablist" wire:ignore>
                        <li class="nav-item">
                            <a class="nav-link active fw-semibold px-3" data-bs-toggle="tab" href="#general" role="tab">Tổng quan</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold px-3" data-bs-toggle="tab" href="#backup" role="tab">Sao lưu & Bộ nhớ đệm</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link fw-semibold px-3" data-bs-toggle="tab" href="#logs" role="tab">Nhật ký hoạt động</a>
                        </li>
                    </ul>
                </div>
                
                <form wire:submit.prevent="save">
                    <div class="card-body p-4">
                        <div class="tab-content">
                            <!-- Tab: General -->
                            <div class="tab-pane fade show active" id="general" role="tabpanel" wire:ignore.self>
                                <h5 class="fw-bold mb-4 text-dark"><i class="fi fi-rr-settings-sliders me-2 text-primary"></i>Cấu hình tổng quan</h5>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Tên website <span class="text-danger">*</span></label>
                                        <input type="text" wire:model="website_name" class="form-control @error('website_name') is-invalid @enderror" placeholder="Nhập tên website..." />
                                        @error('website_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Email liên hệ <span class="text-danger">*</span></label>
                                        <input type="email" wire:model="contact_email" class="form-control @error('contact_email') is-invalid @enderror" placeholder="Nhập email liên hệ..." />
                                        @error('contact_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Múi giờ <span class="text-danger">*</span></label>
                                        <select wire:model="timezone" class="form-select @error('timezone') is-invalid @enderror">
                                            <option value="Asia/Ho_Chi_Minh">Asia/Ho_Chi_Minh (Việt Nam)</option>
                                            <option value="UTC">UTC (Giờ phối hợp quốc tế)</option>
                                            <option value="America/New_York">America/New_York (Mỹ)</option>
                                            <option value="Europe/London">Europe/London (Anh)</option>
                                        </select>
                                        @error('timezone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Ngôn ngữ mặc định <span class="text-danger">*</span></label>
                                        <select wire:model="language" class="form-select @error('language') is-invalid @enderror">
                                            <option value="vi">Tiếng Việt</option>
                                            <option value="en">English</option>
                                            <option value="fr">French</option>
                                        </select>
                                        @error('language') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>
                            </div>
 
                            <!-- Tab: Backup -->
                            <div class="tab-pane fade" id="backup" role="tabpanel" wire:ignore.self>
                                <h5 class="fw-bold mb-4 text-dark"><i class="fi fi-rr-database me-2 text-primary"></i>Sao lưu & Dữ liệu</h5>
                                <p class="text-muted small">Sao lưu dữ liệu định kỳ giúp bảo vệ hệ thống khỏi mất mát dữ liệu.</p>
                                <div class="d-flex flex-wrap gap-2 mt-3">
                                    <button type="button" wire:click="backupNow" class="btn btn-primary btn-shadow waves-effect">
                                        <i class="fi fi-rr-cloud-upload me-1"></i> Sao lưu ngay lập tức
                                    </button>
                                    <button type="button" wire:click="exportData" class="btn btn-outline-secondary waves-effect">
                                        <i class="fi fi-rr-download me-1"></i> Xuất cấu hình (JSON)
                                    </button>
                                    <button type="button" wire:click="downloadSourceCode" class="btn btn-outline-success waves-effect">
                                        <i class="fi fi-rr-file-zip me-1"></i> Tải về Source Code (ZIP)
                                    </button>
                                    <button type="button" wire:click="clearCache" class="btn btn-outline-danger waves-effect">
                                        <i class="fi fi-rr-trash me-1"></i> Xóa toàn bộ Cache ứng dụng
                                    </button>
                                </div>
                            </div>

                            <!-- Tab: Logs -->
                            <div class="tab-pane fade" id="logs" role="tabpanel" wire:ignore.self>
                                <h5 class="fw-bold mb-4 text-dark"><i class="fi fi-rr-list-check me-2 text-primary"></i>Nhật ký hoạt động hệ thống</h5>
                                <p class="text-muted small mb-3">Hiển thị tối đa 50 hành động gần đây nhất của hệ thống (Đăng nhập, Thiết lập, Sao lưu).</p>
                                
                                <div class="table-responsive border rounded-3" style="max-height: 480px; overflow-y: auto;">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="table-light sticky-top">
                                            <tr>
                                                <th class="ps-3 text-xs text-uppercase fw-bold text-muted" style="font-size: 0.72rem;">Thời gian</th>
                                                <th class="text-xs text-uppercase fw-bold text-muted" style="font-size: 0.72rem;">Tài khoản</th>
                                                <th class="text-xs text-uppercase fw-bold text-muted" style="font-size: 0.72rem;">Hành động</th>
                                                <th class="text-xs text-uppercase fw-bold text-muted" style="font-size: 0.72rem;">Chi tiết</th>
                                                <th class="text-xs text-uppercase fw-bold text-muted" style="font-size: 0.72rem;">Địa chỉ IP</th>
                                                <th class="pe-3 text-xs text-uppercase fw-bold text-muted" style="font-size: 0.72rem;">Thiết bị</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($logs as $log)
                                                <tr wire:key="log-{{ $log->id }}">
                                                    <td class="ps-3 text-nowrap">
                                                        <span class="text-dark fw-bold text-xs" style="font-size: 0.76rem;">{{ $log->created_at->format('H:i:s d/m/Y') }}</span>
                                                        <div class="text-muted mt-0.5" style="font-size: 0.65rem;">
                                                            {{ $log->created_at->diffForHumans() }}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        @if ($log->user_id)
                                                            <div class="d-flex align-items-center gap-2">
                                                                <div class="avatar bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:24px;height:24px;font-size:0.65rem;">
                                                                    {{ strtoupper(substr($log->user_name ?? 'U', 0, 1)) }}
                                                                </div>
                                                                <span class="text-body fw-medium text-xs">{{ $log->user_name }}</span>
                                                            </div>
                                                        @else
                                                            <span class="text-muted text-xs">— (Khách)</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @php
                                                            $actionBadgeClass = match($log->action) {
                                                                'login' => 'bg-success-subtle text-success border border-success',
                                                                'logout' => 'bg-secondary-subtle text-secondary border border-secondary',
                                                                'failed_login' => 'bg-danger-subtle text-danger border border-danger',
                                                                'backup_db', 'backup_source' => 'bg-info-subtle text-info border border-info',
                                                                'update_settings' => 'bg-primary-subtle text-primary border border-primary',
                                                                'clear_cache' => 'bg-warning-subtle text-warning border border-warning',
                                                                default => 'bg-light text-muted border'
                                                            };
                                                            $actionIcon = match($log->action) {
                                                                'login' => 'fi-rr-key',
                                                                'logout' => 'fi-rr-exit',
                                                                'failed_login' => 'fi-rr-shield-exclamation',
                                                                'backup_db', 'backup_source' => 'fi-rr-database',
                                                                'update_settings' => 'fi-rr-settings-sliders',
                                                                'clear_cache' => 'fi-rr-trash',
                                                                default => 'fi-rr-info'
                                                            };
                                                            $actionName = match($log->action) {
                                                                'login' => 'Đăng nhập',
                                                                'logout' => 'Đăng xuất',
                                                                'failed_login' => 'Đăng nhập lỗi',
                                                                'backup_db' => 'Backup DB',
                                                                'backup_source' => 'Backup Source',
                                                                'export_settings' => 'Xuất JSON',
                                                                'update_settings' => 'Sửa cấu hình',
                                                                'clear_cache' => 'Xóa Cache',
                                                                default => $log->action
                                                            };
                                                        @endphp
                                                        <span class="badge {{ $actionBadgeClass }} px-2 py-1 fw-bold" style="font-size: 0.65rem; border-radius: 4px;">
                                                            <i class="fi {{ $actionIcon }} me-1"></i>{{ $actionName }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="text-body text-xs">{{ $log->description }}</span>
                                                    </td>
                                                    <td>
                                                        <code class="text-primary text-xs">{{ $log->ip_address ?? '—' }}</code>
                                                    </td>
                                                    <td class="pe-3" style="max-width: 160px;">
                                                        <span class="text-muted d-block text-truncate" style="font-size: 0.68rem;" title="{{ $log->user_agent }}">
                                                            {{ $log->user_agent ?? '—' }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center py-5 text-muted small">
                                                        <i class="fi fi-rr-inbox scale-3x d-block mb-3 text-muted"></i>
                                                        Chưa có nhật ký hoạt động nào được ghi nhận.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer bg-light p-4 text-end">
                        <button type="submit" class="btn btn-success btn-shadow px-4 waves-effect waves-light">
                            <i class="fi fi-rr-disk me-1"></i> Lưu toàn bộ cấu hình
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@script
<script>
    $wire.on('swal:alert', (data) => {
        const payload = Array.isArray(data) ? data[0] : data;
        Swal.fire({
            icon: payload.icon || 'success',
            title: payload.title || 'Thông báo',
            text: payload.text || '',
            timer: 3000,
            timerProgressBar: true,
            showConfirmButton: false,
            position: 'top-end',
            toast: true,
            background: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#1e293b' : '#ffffff',
            color: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#f8fafc' : '#0f172a',
            customClass: {
                popup: 'rounded-4 shadow-sm border-0'
            }
        });
    });
</script>
@endscript
