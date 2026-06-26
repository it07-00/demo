<!-- begin::Sidebar right -->
<div class="app-sidebar-end">
    <ul class="sidebar-list">
        <li>
            <a href="javascript:void(0)">
                <div class="avatar avatar-sm bg-warning shadow-sharp-warning rounded-circle text-white mx-auto mb-2">
                    <i class="fi fi-rr-to-do"></i>
                </div>
                <span class="text-dark">Công việc</span>
            </a>
        </li>
        <li>
            <a href="javascript:void(0)">
                <div class="avatar avatar-sm bg-secondary shadow-sharp-secondary rounded-circle text-white mx-auto mb-2">
                    <i class="fi fi-rr-interrogation"></i>
                </div>
                <span class="text-dark">Hỗ trợ</span>
            </a>
        </li>
        <li>
            <a href="{{ auth()->user()?->can('schedule.view') ? route('duty-schedules.index') : 'javascript:void(0)' }}">
                <div class="avatar avatar-sm bg-info shadow-sharp-info rounded-circle text-white mx-auto mb-2">
                    <i class="fi fi-rr-calendar"></i>
                </div>
                <span class="text-dark">Sự kiện</span>
            </a>
        </li>
        <li>
            <a href="{{ auth()->user()?->can('setting.view') ? route('settings.index') : 'javascript:void(0)' }}">
                <div class="avatar avatar-sm bg-gray shadow-sharp-gray rounded-circle text-white mx-auto mb-2">
                    <i class="fi fi-rr-settings"></i>
                </div>
                <span class="text-dark">Cài đặt</span>
            </a>
        </li>
    </ul>
</div>
<!-- end::Sidebar right -->
