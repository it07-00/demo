<div>
    <div
        wire:ignore.self
        class="modal fade"
        id="userResetPasswordModal"
        tabindex="-1"
        aria-labelledby="userResetPasswordModalLabel"
        aria-hidden="true"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form wire:submit="save">
                    <div class="modal-header">
                        <h5 class="modal-title" id="userResetPasswordModalLabel">Đặt lại mật khẩu</h5>
                        <button type="button" class="btn-close" wire:click="close" aria-label="Đóng"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <div class="fw-semibold">{{ $name }}</div>
                            <div class="text-muted small">{{ $email }}</div>
                        </div>

                        <div class="alert alert-warning mb-0">
                            <label class="form-label fw-semibold" for="default-reset-password">Mật khẩu mặc định</label>
                            <div class="input-group">
                                <input
                                    id="default-reset-password"
                                    type="password"
                                    class="form-control"
                                    value="{{ $defaultPassword }}"
                                    readonly
                                >
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    data-password-toggle="#default-reset-password"
                                    title="Hiện mật khẩu"
                                    aria-label="Hiện mật khẩu"
                                >
                                    <i class="fi fi-rr-eye" data-password-toggle-icon></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" wire:click="close">Hủy</button>
                        <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">
                            Đặt lại mật khẩu
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
