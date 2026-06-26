<div>
    <div
        wire:ignore.self
        class="modal fade"
        id="userDeleteModal"
        tabindex="-1"
        aria-labelledby="userDeleteModalLabel"
        aria-hidden="true"
        data-bs-backdrop="static"
        data-bs-keyboard="false"
    >
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userDeleteModalLabel">Xóa người dùng</h5>
                    <button type="button" class="btn-close" wire:click="close" aria-label="Đóng"></button>
                </div>

                <div class="modal-body">
                    <p class="mb-0">Bạn có chắc chắn muốn xóa người dùng <strong>{{ $name }}</strong>? Tài khoản này sẽ bị xóa tạm thời.</p>
                    @error('user')
                        <div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>
                    @enderror
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light" wire:click="close">Hủy</button>
                    <button type="button" class="btn btn-danger" wire:click="delete" wire:loading.attr="disabled">
                        Xóa
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
