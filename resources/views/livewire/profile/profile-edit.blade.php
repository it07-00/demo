<div>
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div class="clearfix">
            <h1 class="app-page-title">Thông tin cá nhân</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Hồ sơ cá nhân
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row">
        <!-- Header Banner Card -->
        <div class="col-lg-12 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex flex-wrap gap-4 align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="position-relative">
                                <div class="avatar avatar-xxl rounded-circle overflow-hidden border">
                                    @php
                                        $avatarUrl = $user->avatar_url;
                                        $avatarInitials = $user->avatar_initials;
                                        if ($avatarUpload) {
                                            try {
                                                $avatarUrl = $avatarUpload->temporaryUrl();
                                            } catch (\Exception $e) {
                                                // fallback
                                            }
                                        }
                                    @endphp
                                    @if ($avatarUrl)
                                        <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" class="object-fit-cover w-100 h-100">
                                    @else
                                        <span class="w-100 h-100 d-flex align-items-center justify-content-center bg-primary-subtle text-primary fw-bold fs-2">
                                            {{ $avatarInitials }}
                                        </span>
                                    @endif
                                </div>
                                <a href="javascript:void(0);" onclick="document.getElementById('avatarUploadInput').click()" class="avatar avatar-xxs bg-primary rounded-circle text-white position-absolute top-0 end-0 d-flex align-items-center justify-content-center" style="z-index: 10;" title="Thay đổi ảnh đại diện">
                                    <i class="fi fi-rr-camera"></i>
                                </a>
                                <input type="file" id="avatarUploadInput" wire:model="avatarUpload" class="d-none" accept="image/*" />
                                <div wire:loading.flex wire:target="avatarUpload" class="position-absolute start-0 top-0 w-100 h-100 rounded-circle bg-dark bg-opacity-50 align-items-center justify-content-center" style="z-index: 5;">
                                    <div class="spinner-border spinner-border-sm text-white" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                            </div>
                            <div class="ms-3">
                                <h4 class="fw-bold mb-1 text-dark">{{ $user->name }}</h4>
                                <small class="text-muted d-block mb-2">{{ $user->roles->pluck('name')->first() ?? 'Người dùng' }}</small>
                                <div class="d-flex flex-wrap gap-1 mt-2">
                                    <span class="badge badge-sm px-2 rounded-pill text-bg-primary">
                                        {{ $user->roles->pluck('name')->first() ?? 'Nhân viên' }}
                                    </span>
                                    <span class="badge badge-sm px-2 rounded-pill text-bg-success">Active</span>
                                </div>
                                @if ($avatarUpload)
                                    <div class="mt-2">
                                        <button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="avatarUpload,save" class="btn btn-sm btn-success btn-shadow py-1 px-3 d-inline-flex align-items-center gap-1">
                                            <span wire:loading.remove wire:target="avatarUpload,save">
                                                <i class="fi fi-rr-disk me-1"></i> Lưu ảnh mới
                                            </span>
                                            <span wire:loading wire:target="avatarUpload,save">
                                                <span class="spinner-border spinner-border-sm me-1" style="width: 0.75rem; height: 0.75rem;" role="status" aria-hidden="true"></span>
                                                Đang lưu...
                                            </span>
                                        </button>
                                    </div>
                                @endif
                                @error('avatarUpload')
                                    <div class="text-danger small mt-2">
                                        <i class="fi fi-rr-exclamation me-1"></i>{{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Left Column: Basic Info -->
        <div class="col-lg-4 col-sm-12 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title fw-bold mb-0 text-dark">
                        <i class="fi fi-rr-info me-2 text-primary"></i>Thông tin cơ bản
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4">
                        <span class="mb-1 d-block text-muted small fw-semibold">Họ và tên</span>
                        <p class="text-dark fw-semibold mb-0 fs-6">{{ $user->name }}</p>
                    </div>
                    <div class="mb-4">
                        <span class="mb-1 d-block text-muted small fw-semibold">Tên đăng nhập (Username)</span>
                        <p class="text-primary fw-bold mb-0 fs-6">{{ $user->username }}</p>
                    </div>
                    <div class="mb-4">
                        <span class="mb-1 d-block text-muted small fw-semibold">Email tài khoản</span>
                        <p class="text-dark fw-semibold mb-0 fs-6">{{ $user->email }}</p>
                    </div>
                    <div class="mb-4">
                        <span class="mb-1 d-block text-muted small fw-semibold">Ngày sinh</span>
                        <p class="text-dark fw-semibold mb-0 fs-6">{{ $user->dob ? $user->dob->format('d/m/Y') : 'Chưa cập nhật' }}</p>
                    </div>
                    <div class="mb-4">
                        <span class="mb-1 d-block text-muted small fw-semibold">Địa chỉ</span>
                        <p class="text-dark fw-semibold mb-0 fs-6">{{ $user->address ?: 'Chưa cập nhật' }}</p>
                    </div>
                    <div class="mb-2">
                        <span class="mb-1 d-block text-muted small fw-semibold">Ngày tham gia</span>
                        <p class="text-dark fw-semibold mb-0 fs-6">{{ $user->created_at?->format('d/m/Y H:i') ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Settings Form -->
        <div class="col-lg-8 col-sm-12 mb-4">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title fw-bold mb-0 text-dark">
                        <i class="fi fi-rr-user-gear me-2 text-primary"></i>Thiết lập tài khoản
                    </h5>
                </div>
                <div class="card-body p-4">
                    @if ($successMessage)
                        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                            <i class="fi fi-rr-check me-2"></i> {{ $successMessage }}
                            <button type="button" class="btn-close" wire:click="$set('successMessage', null)" aria-label="Close"></button>
                        </div>
                    @endif

                    <form wire:submit.prevent="save">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Họ và tên <span class="text-danger">*</span></label>
                                <input type="text" wire:model="name" class="form-control @error('name') is-invalid @enderror" placeholder="Nhập họ và tên..." />
                                @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Email liên hệ <span class="text-danger">*</span></label>
                                <input type="email" wire:model="email" class="form-control @error('email') is-invalid @enderror" placeholder="example@example.com" />
                                @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Ngày sinh</label>
                                <input type="date" wire:model="dob" class="form-control @error('dob') is-invalid @enderror" />
                                @error('dob') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Địa chỉ</label>
                                <input type="text" wire:model="address" class="form-control @error('address') is-invalid @enderror" placeholder="Nhập địa chỉ..." />
                                @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Vai trò hệ thống</label>
                                <input type="text" class="form-control bg-light" value="{{ $user->roles->pluck('name')->first() ?? 'Người dùng' }}" readonly />
                                <small class="text-muted d-block mt-1">Vai trò hệ thống do IT cấp và không thể thay đổi.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Mật khẩu hiện tại</label>
                                <div class="input-group">
                                    <input id="profile-current-password" type="password" wire:model="current_password" class="form-control @error('current_password') is-invalid @enderror" placeholder="••••••••" autocomplete="current-password" />
                                    <button type="button" class="btn btn-outline-secondary" data-password-toggle="#profile-current-password" title="Hiện mật khẩu" aria-label="Hiện mật khẩu">
                                        <i class="fi fi-rr-eye" data-password-toggle-icon></i>
                                    </button>
                                </div>
                                @error('current_password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                <small class="text-muted d-block mt-1">Bắt buộc khi đổi mật khẩu.</small>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Mật khẩu mới</label>
                                <div class="input-group">
                                    <input id="profile-password" type="password" wire:model="password" class="form-control @error('password') is-invalid @enderror" placeholder="••••••••" autocomplete="new-password" />
                                    <button type="button" class="btn btn-outline-secondary" data-password-toggle="#profile-password" title="Hiện mật khẩu" aria-label="Hiện mật khẩu">
                                        <i class="fi fi-rr-eye" data-password-toggle-icon></i>
                                    </button>
                                </div>
                                @error('password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                <small class="text-muted d-block mt-1">Để trống nếu không có nhu cầu đổi mật khẩu mới.</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-semibold">Xác nhận mật khẩu mới</label>
                                <div class="input-group">
                                    <input id="profile-password-confirmation" type="password" wire:model="password_confirmation" class="form-control" placeholder="••••••••" autocomplete="new-password" />
                                    <button type="button" class="btn btn-outline-secondary" data-password-toggle="#profile-password-confirmation" title="Hiện mật khẩu" aria-label="Hiện mật khẩu">
                                        <i class="fi fi-rr-eye" data-password-toggle-icon></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="text-end mt-3">
                            <button type="submit" wire:loading.attr="disabled" wire:target="avatarUpload,save" class="btn btn-success btn-shadow px-4 waves-effect waves-light">
                                <span wire:loading.remove wire:target="avatarUpload,save">
                                    <i class="fi fi-rr-disk me-1"></i> Lưu thay đổi
                                </span>
                                <span wire:loading wire:target="avatarUpload,save">
                                    <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                                    Đang lưu...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@script
<script>
    $wire.on('profile-avatar:updated', (data) => {
        const payload = Array.isArray(data) ? data[0] : data;
        const avatarUrl = payload?.url;
        const initials = payload?.initials || 'U';

        document.querySelectorAll('[data-current-user-avatar-img]').forEach((image) => {
            if (!avatarUrl) {
                image.classList.add('d-none');
                return;
            }

            image.src = avatarUrl;
            image.classList.remove('d-none');
        });

        document.querySelectorAll('[data-current-user-avatar-initials]').forEach((element) => {
            element.textContent = initials;
            element.classList.toggle('d-none', Boolean(avatarUrl));
        });
    });

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
