<aside class="app-menubar" id="appMenubar">
    <div class="app-navbar-brand app-sidebar-brand">
        <a class="navbar-brand-mini" href="{{ route('dashboard') }}" aria-label="TTVH-TC">
            <span class="ttvh-brand-text">TTVH-TC</span>
        </a>
    </div>

    <nav class="app-navbar" data-simplebar>
        <ul class="menubar">
            <li class="menu-heading">
                <span class="menu-label">Menu điều hướng</span>
            </li>

            @if (auth()->user()?->can('dashboard.view'))
                <li class="menu-item">
                    <a class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <i class="fi fi-rr-apps"></i>
                        <span class="menu-label">Dashboard</span>
                    </a>
                </li>
            @endif

            @if (auth()->user()?->can('project.view'))
                <li class="menu-item">
                    <a class="menu-link {{ request()->routeIs('operations.projects') ? 'active' : '' }}" href="{{ route('operations.projects') }}">
                        <i class="fi fi-rr-briefcase"></i>
                        <span class="menu-label">Dự án & KH</span>
                    </a>
                </li>
            @endif

            @if (auth()->user()?->can('report.view'))
                <li class="menu-item">
                    <a class="menu-link {{ request()->routeIs('operations.daily') ? 'active' : '' }}" href="{{ route('operations.daily') }}">
                        <i class="fi fi-rr-chart-pie-alt"></i>
                        <span class="menu-label">Báo cáo vận hành</span>
                    </a>
                </li>
            @endif

            @if (auth()->user()?->can('staff.view'))
                <li class="menu-item">
                    <a class="menu-link {{ request()->routeIs('operations.staff') ? 'active' : '' }}" href="{{ route('operations.staff') }}">
                        <i class="fi fi-rr-users-alt"></i>
                        <span class="menu-label">Nhân sự & Phân công</span>
                    </a>
                </li>
            @endif

            @if (auth()->user()?->can('analytics.view'))
                <li class="menu-item">
                    <a class="menu-link {{ request()->routeIs('operations.analytics') ? 'active' : '' }}" href="{{ route('operations.analytics') }}">
                        <i class="fi fi-rr-chart-histogram"></i>
                        <span class="menu-label">KPI & Hiệu suất</span>
                    </a>
                </li>
            @endif

            @if (auth()->user()?->can('crm.view'))
                <li class="menu-item">
                    <a class="menu-link {{ request()->routeIs('operations.crm') ? 'active' : '' }}" href="{{ route('operations.crm') }}">
                        <i class="fi fi-rr-handshake"></i>
                        <span class="menu-label">CRM khách hàng</span>
                    </a>
                </li>
            @endif

            @if (auth()->user()?->can('alert.view'))
                <li class="menu-item">
                    <a class="menu-link {{ request()->routeIs('operations.alerts') ? 'active' : '' }}" href="{{ route('operations.alerts') }}">
                        <i class="fi fi-rr-bell-ring"></i>
                        <span class="menu-label">Cảnh báo</span>
                    </a>
                </li>
            @endif

            @if (auth()->user()?->can('user.view'))
                <li class="menu-item">
                    <a class="menu-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                        <i class="fi fi-rr-users"></i>
                        <span class="menu-label">Người dùng</span>
                    </a>
                </li>
            @endif

            @if (auth()->user()?->can('role.manage'))
                <li class="menu-item">
                    <a class="menu-link {{ request()->routeIs('roles-permissions.*') ? 'active' : '' }}" href="{{ route('roles-permissions.index') }}">
                        <i class="fi fi-rr-shield-check"></i>
                        <span class="menu-label">Vai trò & Quyền</span>
                    </a>
                </li>
            @endif

            @if (auth()->user()?->can('schedule.view'))
                <li class="menu-item">
                    <a class="menu-link {{ request()->routeIs('duty-schedules.*') ? 'active' : '' }}" href="{{ route('duty-schedules.index') }}">
                        <i class="fi fi-rr-calendar"></i>
                        <span class="menu-label">Lịch công tác</span>
                    </a>
                </li>
            @endif

            @if (auth()->user()?->can('report.view'))
                <li class="menu-item">
                    <a class="menu-link {{ request()->routeIs('daily-reports.*') ? 'active' : '' }}" href="{{ route('daily-reports.index') }}">
                        <i class="fi fi-rr-document"></i>
                        <span class="menu-label">Báo cáo ngày</span>
                    </a>
                </li>
            @endif

            @if (auth()->user()?->can('work_progress.view'))
                <li class="menu-item">
                    <a class="menu-link {{ request()->routeIs('work-progress.*') ? 'active' : '' }}" href="{{ route('work-progress.index') }}">
                        <i class="fi fi-rr-chart-line-up"></i>
                        <span class="menu-label">Tiến độ Công việc</span>
                    </a>
                </li>
            @endif

            @if (auth()->user()?->can('document.view'))
                <li class="menu-item">
                    <a class="menu-link {{ request()->routeIs('document-regulations.*') ? 'active' : '' }}" href="{{ route('document-regulations.index') }}">
                        <i class="fi fi-rr-document-signed"></i>
                        <span class="menu-label">Quy định tài liệu</span>
                    </a>
                </li>
            @endif

            @if (auth()->user()?->can('mail.view'))
                <li class="menu-item">
                    <a class="menu-link {{ request()->routeIs('mail.*') ? 'active' : '' }}" href="{{ route('mail.index') }}">
                        <i class="fi fi-rr-envelope"></i>
                        <span class="menu-label">Hộp thư</span>
                    </a>
                </li>
            @endif

            @if (auth()->user()?->can('setting.view'))
                <li class="menu-item">
                    <a class="menu-link {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ route('settings.index') }}">
                        <i class="fi fi-rr-settings"></i>
                        <span class="menu-label">Cài đặt</span>
                    </a>
                </li>
            @endif
        </ul>
    </nav>

    <div class="app-footer">
        @if (auth()->user()?->can('user.create'))
            <button type="button" class="btn btn-primary waves-effect btn-shadow btn-app-nav w-100" onclick="window.location='{{ route('users.index') }}'">
                <i class="fi fi-rr-plus me-1"></i> <span class="nav-text">Thêm người dùng</span>
            </button>
        @else
            <a href="{{ route('dashboard') }}" class="btn btn-outline-light waves-effect btn-shadow btn-app-nav w-100">
                <i class="fi fi-rr-home me-1"></i> <span class="nav-text">Dashboard</span>
            </a>
        @endif
    </div>
</aside>
