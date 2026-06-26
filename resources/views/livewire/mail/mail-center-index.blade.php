<div>
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div class="clearfix">
            <h1 class="app-page-title">Hộp thư nội bộ</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">Email</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <button type="button" wire:click="showTab('inbox')" class="btn {{ $activeTab === 'inbox' ? 'btn-primary' : 'btn-light' }} waves-effect">
                <i class="fi fi-rr-envelope me-1"></i> Hộp thư
            </button>
            @can('mail.send')
                <button type="button" wire:click="showTab('compose')" class="btn {{ $activeTab === 'compose' ? 'btn-primary' : 'btn-light' }} waves-effect">
                    <i class="fi fi-rr-paper-plane me-1"></i> Soạn mail
                </button>
            @endcan
            @can('mail.view')
                <button type="button" wire:click="showTab('settings')" class="btn {{ $activeTab === 'settings' ? 'btn-primary' : 'btn-light' }} waves-effect">
                    <i class="fi fi-rr-settings-sliders me-1"></i> Cấu hình
                </button>
            @endcan
        </div>
    </div>

    @if ($successMessage)
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fi fi-rr-check me-2"></i>{{ $successMessage }}
            <button type="button" class="btn-close" wire:click="$set('successMessage', null)" aria-label="Close"></button>
        </div>
    @endif

    @if ($errorMessage)
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fi fi-rr-exclamation me-2"></i>{{ $errorMessage }}
            <button type="button" class="btn-close" wire:click="$set('errorMessage', null)" aria-label="Close"></button>
        </div>
    @endif

    @if ($activeTab === 'inbox')
        <div class="row g-4">
            <div class="col-xl-4">
                <div class="card border-0 shadow-sm">
                    <div class="card-header border-0 pb-0 d-flex align-items-center justify-content-between">
                        <div>
                            <h5 class="card-title mb-1">Inbox</h5>
                            <small class="text-muted">{{ $total }} email trong {{ $folder }}</small>
                        </div>
                        <button type="button" wire:click="refreshInbox" wire:loading.attr="disabled" class="btn btn-icon btn-light rounded-circle">
                            <i class="fi fi-rr-refresh"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        @if (! $hasImapCredentials)
                            <div class="text-center text-muted py-5">
                                <i class="fi fi-rr-envelope-open display-6 d-block mb-3"></i>
                                <p class="mb-3">Chưa có cấu hình IMAP để đọc hộp thư.</p>
                                @can('mail.view')
                                    <button type="button" wire:click="showTab('settings')" class="btn btn-primary">
                                        Cấu hình email
                                    </button>
                                @endcan
                            </div>
                        @elseif (count($messages) === 0)
                            <div class="text-center text-muted py-5">
                                <i class="fi fi-rr-envelope display-6 d-block mb-3"></i>
                                <p class="mb-3">Chưa có email nào trong hộp thư hoặc chưa tải được dữ liệu.</p>
                                <button type="button" wire:click="refreshInbox" wire:loading.attr="disabled" class="btn btn-primary">
                                    <span wire:loading.remove wire:target="refreshInbox">
                                        <i class="fi fi-rr-refresh me-1"></i> Tải lại hộp thư
                                    </span>
                                    <span wire:loading wire:target="refreshInbox">
                                        <span class="spinner-border spinner-border-sm me-1"></span> Đang tải...
                                    </span>
                                </button>
                            </div>
                        @else
                            <div class="list-group list-group-flush">
                                @foreach ($messages as $message)
                                    <button type="button" wire:key="mail-{{ $message['uid'] }}" wire:click="openMessage({{ $message['uid'] }})" class="list-group-item list-group-item-action px-0 py-3 border-0 border-bottom">
                                        <div class="d-flex align-items-start justify-content-between gap-3">
                                            <div class="min-w-0">
                                                <div class="fw-semibold text-truncate {{ $message['seen'] ? 'text-body' : 'text-dark' }}">
                                                    {{ $message['subject'] }}
                                                </div>
                                                <small class="text-muted d-block text-truncate">{{ $message['from'] }}</small>
                                            </div>
                                            <small class="text-muted text-nowrap">{{ $message['date_human'] }}</small>
                                        </div>
                                    </button>
                                @endforeach
                            </div>

                            <div class="d-flex align-items-center justify-content-between mt-3">
                                <button type="button" wire:click="previousPage" class="btn btn-light btn-sm" @disabled($page <= 1)>
                                    <i class="fi fi-rr-angle-left"></i>
                                </button>
                                <small class="text-muted">Trang {{ $page }}/{{ $lastPage }}</small>
                                <button type="button" wire:click="nextPage" class="btn btn-light btn-sm" @disabled($page >= $lastPage)>
                                    <i class="fi fi-rr-angle-right"></i>
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        @if ($selectedMessage)
                            <div class="p-4 border-bottom">
                                <div class="d-flex align-items-start justify-content-between gap-3">
                                    <div class="min-w-0">
                                        <h4 class="mb-2 text-dark">{{ $selectedMessage['subject'] }}</h4>
                                        <div class="text-muted small">
                                            <div><strong>Từ:</strong> {{ $selectedMessage['from'] }}</div>
                                            <div><strong>Đến:</strong> {{ $selectedMessage['to'] }}</div>
                                            <div><strong>Ngày:</strong> {{ $selectedMessage['date_human'] }}</div>
                                        </div>
                                    </div>
                                    <button type="button" wire:click="closeMessage" class="btn btn-icon btn-light rounded-circle">
                                        <i class="fi fi-rr-cross-small"></i>
                                    </button>
                                </div>
                            </div>

                            @if (! empty($selectedMessage['attachments']))
                                <div class="px-4 py-3 border-bottom bg-light bg-opacity-50">
                                    <span class="fw-semibold me-2">Tệp đính kèm:</span>
                                    @foreach ($selectedMessage['attachments'] as $attachment)
                                        <span class="badge bg-secondary-subtle text-secondary me-1">{{ $attachment }}</span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="p-4 mail-message-body">
                                {!! $selectedMessage['html'] !!}
                            </div>
                        @else
                            <div class="text-center text-muted py-5">
                                <i class="fi fi-rr-envelope-open display-5 d-block mb-3"></i>
                                <h5 class="text-dark">Chọn một email để xem nội dung</h5>
                                <p class="mb-0">Nội dung email được đọc trực tiếp từ IMAP server.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    @if ($activeTab === 'compose')
        @can('mail.send')
            <form wire:submit.prevent="sendMail">
                <div class="row g-4">
                    <div class="col-xl-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header border-0 pb-0">
                                <h5 class="card-title mb-1">Soạn email</h5>
                                <small class="text-muted">Email sẽ được gửi bằng SMTP đã cấu hình cho {{ $from_address ?: 'mailbox hiện tại' }}.</small>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Người nhận <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="compose_to" class="form-control @error('compose_to') is-invalid @enderror" placeholder="khachhang@example.com, user@example.com">
                                    @error('compose_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    <small class="text-muted">Có thể nhập nhiều email, cách nhau bằng dấu phẩy.</small>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">CC</label>
                                        <input type="text" wire:model="compose_cc" class="form-control @error('compose_cc') is-invalid @enderror" placeholder="cc@example.com">
                                        @error('compose_cc') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">BCC</label>
                                        <input type="text" wire:model="compose_bcc" class="form-control @error('compose_bcc') is-invalid @enderror" placeholder="bcc@example.com">
                                        @error('compose_bcc') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Tiêu đề <span class="text-danger">*</span></label>
                                    <input type="text" wire:model="compose_subject" class="form-control @error('compose_subject') is-invalid @enderror" placeholder="Nhập tiêu đề email">
                                    @error('compose_subject') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>

                                <div class="mb-0">
                                    <label class="form-label fw-semibold">Nội dung <span class="text-danger">*</span></label>
                                    <textarea wire:model="compose_body" rows="12" class="form-control @error('compose_body') is-invalid @enderror" placeholder="Nhập nội dung email..."></textarea>
                                    @error('compose_body') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>
                            <div class="card-footer bg-light d-flex justify-content-end gap-2">
                                <button type="button" wire:click="$set('compose_body', '')" class="btn btn-light">
                                    Xóa nội dung
                                </button>
                                <button type="submit" wire:loading.attr="disabled" wire:target="sendMail" class="btn btn-primary btn-shadow">
                                    <span wire:loading.remove wire:target="sendMail">
                                        <i class="fi fi-rr-paper-plane me-1"></i> Gửi email
                                    </span>
                                    <span wire:loading wire:target="sendMail">
                                        <span class="spinner-border spinner-border-sm me-1"></span> Đang gửi...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header border-0 pb-0">
                                <h5 class="card-title mb-0">Thông tin gửi</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <span class="text-muted small d-block">Người gửi</span>
                                    <strong>{{ $from_name }}</strong>
                                    <div class="text-muted">{{ $from_address ?: 'Chưa cấu hình email người gửi' }}</div>
                                </div>
                                <div class="mb-3">
                                    <span class="text-muted small d-block">SMTP</span>
                                    <code>{{ $smtp_host }}:{{ $smtp_port }}</code>
                                    <span class="badge bg-primary-subtle text-primary ms-1">{{ strtoupper($smtp_encryption) }}</span>
                                </div>
                                <div class="alert alert-info mb-0">
                                    Nếu gửi thất bại với lỗi 535, hãy kiểm tra lại SMTP username/password hoặc đổi port 587 STARTTLS.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        @endcan
    @endif

    @if ($activeTab === 'settings')
        @can('mail.view')
            <form wire:submit.prevent="saveSettings">
                <div class="row g-4">
                    <div class="col-xl-8">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header border-0 pb-0">
                                <h5 class="card-title mb-1">Cấu hình hòm thư cá nhân</h5>
                                <small class="text-muted">Mỗi người dùng nhập username/password mailbox riêng của mình.</small>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Tên người gửi</label>
                                        <input type="text" wire:model="from_name" class="form-control @error('from_name') is-invalid @enderror">
                                        @error('from_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">Email người gửi</label>
                                        <input type="email" wire:model="from_address" class="form-control @error('from_address') is-invalid @enderror" placeholder="name@example.com">
                                        @error('from_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">IMAP host</label>
                                        <input type="text" wire:model="imap_host" class="form-control @error('imap_host') is-invalid @enderror">
                                        @error('imap_host') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-semibold">IMAP port</label>
                                        <input type="number" wire:model="imap_port" class="form-control @error('imap_port') is-invalid @enderror">
                                        @error('imap_port') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-semibold">IMAP bảo mật</label>
                                        <select wire:model="imap_encryption" class="form-select @error('imap_encryption') is-invalid @enderror">
                                            <option value="ssl">SSL</option>
                                            <option value="starttls">STARTTLS</option>
                                            <option value="none">Không mã hóa</option>
                                        </select>
                                        @error('imap_encryption') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">IMAP username</label>
                                        <input type="text" wire:model="imap_username" class="form-control @error('imap_username') is-invalid @enderror" placeholder="name@example.com">
                                        @error('imap_username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">IMAP password</label>
                                        <div class="input-group">
                                            <input id="imap-password" type="password" wire:model="imap_password" class="form-control @error('imap_password') is-invalid @enderror" placeholder="Để trống nếu không đổi">
                                            <button type="button" class="btn btn-outline-secondary" data-password-toggle="#imap-password" title="Hiện mật khẩu" aria-label="Hiện mật khẩu">
                                                <i class="fi fi-rr-eye" data-password-toggle-icon></i>
                                            </button>
                                        </div>
                                        @error('imap_password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                <hr class="my-4">

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">SMTP host</label>
                                        <input type="text" wire:model="smtp_host" class="form-control @error('smtp_host') is-invalid @enderror">
                                        @error('smtp_host') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-semibold">SMTP port</label>
                                        <input type="number" wire:model="smtp_port" class="form-control @error('smtp_port') is-invalid @enderror">
                                        @error('smtp_port') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label fw-semibold">SMTP bảo mật</label>
                                        <select wire:model="smtp_encryption" class="form-select @error('smtp_encryption') is-invalid @enderror">
                                            <option value="ssl">SSL</option>
                                            <option value="starttls">STARTTLS</option>
                                            <option value="none">Không mã hóa</option>
                                        </select>
                                        @error('smtp_encryption') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">SMTP username</label>
                                        <input type="text" wire:model="smtp_username" class="form-control @error('smtp_username') is-invalid @enderror" placeholder="name@example.com">
                                        @error('smtp_username') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-semibold">SMTP password</label>
                                        <div class="input-group">
                                            <input id="smtp-password" type="password" wire:model="smtp_password" class="form-control @error('smtp_password') is-invalid @enderror" placeholder="Để trống nếu không đổi">
                                            <button type="button" class="btn btn-outline-secondary" data-password-toggle="#smtp-password" title="Hiện mật khẩu" aria-label="Hiện mật khẩu">
                                                <i class="fi fi-rr-eye" data-password-toggle-icon></i>
                                            </button>
                                        </div>
                                        @error('smtp_password') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label fw-semibold">Timeout</label>
                                        <input type="number" wire:model="timeout" min="5" max="60" class="form-control @error('timeout') is-invalid @enderror">
                                        @error('timeout') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                    </div>
                                    <div class="col-md-8 mb-3 d-flex align-items-end">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" role="switch" id="mailEnabled" wire:model="enabled">
                                            <label class="form-check-label fw-semibold" for="mailEnabled">Bật hòm thư cá nhân</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-light d-flex flex-wrap justify-content-between gap-2">
                                <div class="d-flex flex-wrap gap-2">
                                    <button type="button" wire:click="testImap" wire:loading.attr="disabled" class="btn btn-outline-primary">
                                        <i class="fi fi-rr-plug me-1"></i> Test IMAP
                                    </button>
                                    <button type="button" wire:click="sendTestMail" wire:loading.attr="disabled" class="btn btn-outline-success">
                                        <i class="fi fi-rr-paper-plane me-1"></i> Gửi mail thử
                                    </button>
                                </div>
                                <button type="submit" wire:loading.attr="disabled" class="btn btn-primary btn-shadow">
                                    <span wire:loading.remove>
                                        <i class="fi fi-rr-disk me-1"></i> Lưu cấu hình
                                    </span>
                                    <span wire:loading>
                                        <span class="spinner-border spinner-border-sm me-1"></span> Đang xử lý...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-xl-4">
                        <div class="card border-0 shadow-sm mb-4">
                            <div class="card-header border-0 pb-0">
                                <h5 class="card-title mb-0">Thông tin DigitalOcean</h5>
                            </div>
                            <div class="card-body">
                                <dl class="mb-0">
                                    <dt class="fw-semibold text-dark">Máy chủ</dt>
                                    <dd class="border-start ps-3 mb-3"><code>mail.example.com</code></dd>
                                    <dt class="fw-semibold text-dark">IMAP</dt>
                                    <dd class="border-start ps-3 mb-3"><code>993 / SSL</code></dd>
                                    <dt class="fw-semibold text-dark">POP</dt>
                                    <dd class="border-start ps-3 mb-3"><code>995 / SSL</code></dd>
                                    <dt class="fw-semibold text-dark">SMTP</dt>
                                    <dd class="border-start ps-3 mb-0"><code>465 / SSL</code> hoặc <code>587 / STARTTLS</code></dd>
                                </dl>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm">
                            <div class="card-header border-0 pb-0">
                                <h5 class="card-title mb-0">Gửi email thử</h5>
                            </div>
                            <div class="card-body">
                                <label class="form-label fw-semibold">Người nhận test</label>
                                <input type="email" wire:model="test_recipient" class="form-control @error('test_recipient') is-invalid @enderror" placeholder="you@example.com">
                                @error('test_recipient') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                <small class="text-muted d-block mt-2">Nên gửi tới email cá nhân trước khi dùng cho hệ thống.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        @endcan
    @endif
</div>
