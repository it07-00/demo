<header class="app-header">
    <div class="app-header-inner">
        <button class="app-toggler" type="button" aria-label="app toggler">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <div class="app-header-start d-none d-md-flex">
            <form class="d-flex align-items-center h-100 w-lg-250px w-xxl-300px position-relative" action="javascript:void(0)">
                <button type="button" class="btn btn-sm border-0 position-absolute start-0 ms-3 p-0">
                    <i class="fi fi-rr-search"></i>
                </button>
                <input type="text" class="form-control rounded-5 ps-5" placeholder="Tìm kiếm mọi thứ..." data-bs-toggle="modal" data-bs-target="#searchResultsModal">
            </form>
        </div>

        <div class="app-header-end">
            <div class="px-lg-3 px-2 ps-0 d-flex align-items-center">
                <div class="dropdown">
                    <button class="btn btn-icon btn-action-gray rounded-circle waves-effect waves-light position-relative" id="ld-theme" type="button" data-bs-auto-close="outside" aria-expanded="false" data-bs-toggle="dropdown">
                        <i class="fi fi-rr-brightness scale-1x theme-icon-active"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li>
                            <button type="button" class="dropdown-item d-flex gap-2 align-items-center" data-bs-theme-value="light" aria-pressed="false">
                                <i class="fi fi-rr-brightness scale-1x" data-theme="light"></i> Sáng
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex gap-2 align-items-center" data-bs-theme-value="dark" aria-pressed="false">
                                <i class="fi fi-rr-moon scale-1x" data-theme="dark"></i> Tối
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex gap-2 align-items-center" data-bs-theme-value="auto" aria-pressed="true">
                                <i class="fi fi-br-circle-half-stroke scale-1x" data-theme="auto"></i> Tự động
                            </button>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="vr my-3"></div>
            <div class="d-flex align-items-center gap-sm-2 gap-0 px-lg-4 px-sm-2 px-1">
                @livewire('notifications.notification-bell')

                @php
                    $currentUser = auth()->user();
                    $avatarUrl = $currentUser?->avatar_url;
                    $avatarInitials = $currentUser?->avatar_initials ?? 'U';
                    $transparentPixel = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';
                @endphp

                <div class="dropdown text-end ms-sm-3 ms-2 ms-lg-4">
                    <a href="#" class="d-flex align-items-center py-2" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
                        <div class="text-end me-2 d-none d-lg-inline-block">
                            <div class="fw-bold text-dark">{{ $currentUser?->name }}</div>
                            <small class="text-body d-block lh-sm">
                                <i class="fi fi-rr-angle-down text-3xs me-1"></i> {{ $currentUser?->roles->pluck('name')->first() ?? 'User' }}
                            </small>
                        </div>
                        <div class="avatar avatar-sm rounded-circle avatar-status-success bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-semibold">
                            <img src="{{ $avatarUrl ?? $transparentPixel }}" alt="{{ $currentUser?->name }}" class="{{ $avatarUrl ? '' : 'd-none' }}" data-current-user-avatar-img>
                            <span class="{{ $avatarUrl ? 'd-none' : '' }}" data-current-user-avatar-initials>{{ $avatarInitials }}</span>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end w-225px mt-1">
                        <li class="d-flex align-items-center p-2">
                            <div class="avatar avatar-sm rounded-circle bg-primary-subtle text-primary d-flex align-items-center justify-content-center fw-semibold">
                                <img src="{{ $avatarUrl ?? $transparentPixel }}" alt="{{ $currentUser?->name }}" class="{{ $avatarUrl ? '' : 'd-none' }}" data-current-user-avatar-img>
                                <span class="{{ $avatarUrl ? 'd-none' : '' }}" data-current-user-avatar-initials>{{ $avatarInitials }}</span>
                            </div>
                            <div class="ms-2">
                                <div class="fw-bold text-dark">{{ $currentUser?->name }}</div>
                                <small class="text-body d-block lh-sm">{{ $currentUser?->email }}</small>
                            </div>
                        </li>
                        <li>
                            <div class="dropdown-divider my-1"></div>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('profile.edit') }}">
                                <i class="fi fi-rr-user scale-1x"></i> Chỉnh sửa tài khoản
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
                                <i class="fi fi-rr-apps scale-1x"></i> Dashboard
                            </a>
                        </li>
                        @if (auth()->user()?->can('setting.view'))
                            <li>
                                <a class="dropdown-item d-flex align-items-center gap-2" href="{{ route('settings.index') }}">
                                    <i class="fi fi-rr-settings scale-1x"></i> Cài đặt
                                </a>
                            </li>
                            <li>
                                <div class="dropdown-divider my-1"></div>
                            </li>
                        @endif
                        <li>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="dropdown-item d-flex align-items-center gap-2 text-danger">
                                    <i class="fi fi-sr-exit scale-1x"></i> Đăng xuất
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</header>
