<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? config('app.name', 'TTVH-TC Admin') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('images/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,200..800;1,200..800&display=swap" rel="stylesheet">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/js/app.js'])
    @endif

    <link rel="stylesheet" href="{{ asset('css/all.css') }}">
    <link rel="stylesheet" href="{{ asset('css/lucide.css') }}">
    <link rel="stylesheet" href="{{ asset('css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/simplebar.css') }}">
    <link rel="stylesheet" href="{{ asset('css/waves.css') }}">
    <link rel="stylesheet" href="{{ asset('css/bootstrap-select.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/datatables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/sweetalert2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}?v=1.0.2">
    <link rel="stylesheet" href="{{ asset('css/app-brand.css') }}?v=1.0.0">

    @livewireStyles
    @stack('styles')
</head>
<body>
    <div class="page-layout">
        @include('partials.header')
        @include('partials.sidebar')
        @include('partials.sidebar-right')

        <main class="app-wrapper">
            <div class="container-fluid px-4">
                @if (session('status'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('status') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                {{ $slot ?? '' }}
                @yield('content')
            </div>
        </main>

        @include('partials.footer')

        <!-- begin::Search Modal -->
        <div class="modal fade" id="searchResultsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header py-1 px-3">
                        <form class="d-flex align-items-center position-relative w-100" action="javascript:void(0)">
                            <button type="button" class="btn btn-sm border-0 position-absolute start-0 p-0 text-sm">
                                <i class="fi fi-rr-search"></i>
                            </button>
                            <input type="text" class="form-control form-control-lg ps-4 border-0 shadow-none" id="searchInput" placeholder="Search anything's">
                        </form>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body pb-2" style="height: 300px;" data-simplebar>
                        <div id="recentlyResults">
                            <span class="text-uppercase text-2xs fw-semibold text-muted d-block mb-2">Recently Searched:</span>
                            <ul class="list-inline search-list">
                                <li>
                                    <a class="search-item" href="{{ route('dashboard') }}">
                                        <i class="fi fi-rr-apps"></i> Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="search-item" href="{{ route('users.index') }}">
                                        <i class="fi fi-rr-users"></i> Users
                                    </a>
                                </li>
                            </ul>
                        </div>
                        <div id="searchContainer"></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- end::Search Modal -->
    </div>

    <script src="{{ asset('js/global.min.js') }}"></script>
    <script src="{{ asset('js/Sortable.min.js') }}"></script>
    <script src="{{ asset('js/chart.js') }}"></script>
    <script src="{{ asset('js/flatpickr.min.js') }}"></script>
    <script src="{{ asset('js/apexcharts.min.js') }}"></script>
    <script src="{{ asset('js/datatables.min.js') }}"></script>
    <script src="{{ asset('js/appSettings.js') }}"></script>
    <script src="{{ asset('js/main.js') }}"></script>
    <script src="{{ asset('js/sweetalert2.all.min.js') }}"></script>

    @if (request()->routeIs('dashboard'))
        <script src="{{ asset('js/dashboard.js') }}"></script>
    @endif

    @livewireScripts
    @include('partials.password-toggle-script')
    <script>
        (() => {
            if (window.appBootstrapModalBridgeInitialized) {
                return;
            }

            window.appBootstrapModalBridgeInitialized = true;

            const cleanupModalState = (keepOneBackdrop = false) => {
                if (document.querySelector('.modal.show')) {
                    const backdrops = Array.from(document.querySelectorAll('.modal-backdrop'));

                    if (keepOneBackdrop && backdrops.length > 1) {
                        backdrops.slice(0, -1).forEach((backdrop) => backdrop.remove());
                    }

                    return;
                }

                document.body.classList.remove('modal-open');
                document.body.style.removeProperty('overflow');
                document.body.style.removeProperty('padding-right');
                document.querySelectorAll('.modal-backdrop').forEach((backdrop) => backdrop.remove());
            };

            const hideOtherModals = (currentModalEl) => {
                document.querySelectorAll('.modal.show').forEach((modalEl) => {
                    if (modalEl === currentModalEl) {
                        return;
                    }

                    bootstrap.Modal.getOrCreateInstance(modalEl).hide();
                });
            };

            const setTransitionState = (modalEl) => {
                if (modalEl.dataset.appModalBound === 'true') {
                    return;
                }

                modalEl.dataset.appModalBound = 'true';

                modalEl.addEventListener('shown.bs.modal', () => {
                    modalEl.dataset.appModalState = 'shown';
                    cleanupModalState(true);
                });

                modalEl.addEventListener('hidden.bs.modal', () => {
                    delete modalEl.dataset.appModalState;
                    cleanupModalState();
                });
            };

            const toggleModal = (modalId, action) => {
                const modalEl = document.getElementById(modalId);

                if (!modalEl || typeof bootstrap === 'undefined') {
                    return;
                }

                setTransitionState(modalEl);

                if (action === 'show') {
                    if (modalEl.dataset.appModalState === 'showing') {
                        return;
                    }

                    if (modalEl.classList.contains('show')) {
                        cleanupModalState(true);
                        return;
                    }

                    hideOtherModals(modalEl);
                    modalEl.dataset.appModalState = 'showing';
                }

                const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
                modal[action]();

                if (action === 'hide') {
                    modalEl.dataset.appModalState = 'hiding';
                    modalEl.addEventListener('hidden.bs.modal', cleanupModalState, { once: true });
                    window.setTimeout(cleanupModalState, 250);
                }
            };

            window.AppModal = {
                show: (modalId) => toggleModal(modalId, 'show'),
                hide: (modalId) => toggleModal(modalId, 'hide'),
                cleanup: () => cleanupModalState(),
            };

            const bindings = {
                'user-create:show': ['userCreateModal', 'show'],
                'user-create:hide': ['userCreateModal', 'hide'],
                'user-edit:show':   ['userEditModal',   'show'],
                'user-edit:hide':   ['userEditModal',   'hide'],
                'user-reset-password:show': ['userResetPasswordModal', 'show'],
                'user-reset-password:hide': ['userResetPasswordModal', 'hide'],
                'user-delete:show': ['userDeleteModal', 'show'],
                'user-delete:hide': ['userDeleteModal', 'hide'],
                'role-create:hide': ['addRoleModal',      'hide'],
                'role-edit:show':   ['editRoleModal',     'show'],
                'role-edit:hide':   ['editRoleModal',     'hide'],
                'report-create:show': ['reportCreateModal', 'show'],
                'report-create:hide': ['reportCreateModal', 'hide'],
                'report-detail:show': ['reportDetailModal', 'show'],
                'report-detail:hide': ['reportDetailModal', 'hide'],
            };

            Object.entries(bindings).forEach(([eventName, [modalId, action]]) => {
                window.addEventListener(eventName, () => toggleModal(modalId, action));
            });

            const registerLivewireListeners = () => {
                if (window.appLivewireModalListenersInitialized) {
                    return;
                }

                if (typeof Livewire === 'undefined') {
                    return;
                }

                window.appLivewireModalListenersInitialized = true;

                Object.entries(bindings).forEach(([eventName, [modalId, action]]) => {
                    Livewire.on(eventName, () => toggleModal(modalId, action));
                });
            };

            registerLivewireListeners();
            document.addEventListener('livewire:init', registerLivewireListeners);

            // â”€â”€ Global SweetAlert2 listener â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
            const fireSwal = (data) => {
                if (typeof Swal === 'undefined') return;
                const {
                    icon        = 'info',
                    title       = '',
                    text        = '',
                    toast       = false,
                    position    = toast ? 'top-end' : 'center',
                    timer       = toast ? 3000 : undefined,
                    showConfirmButton = !toast,
                    confirmButtonText = 'OK',
                } = data;
                Swal.fire({ icon, title, text, toast, position, timer, showConfirmButton, confirmButtonText, timerProgressBar: !!timer });
            };

            window.addEventListener('swal:alert', (e) => fireSwal(e.detail || {}));

            const registerSwalLivewire = () => {
                if (window.appSwalListenerInitialized) return;
                if (typeof Livewire === 'undefined') return;
                window.appSwalListenerInitialized = true;
                Livewire.on('swal:alert', (data) => fireSwal(Array.isArray(data) ? data[0] : data));
            };

            registerSwalLivewire();
            document.addEventListener('livewire:init', registerSwalLivewire);
        })();
    </script>
    @stack('scripts')
</body>
</html>

