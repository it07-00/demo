<div>
    <div class="app-page-head d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <div class="clearfix">
            <h1 class="app-page-title">Quy định Tài liệu</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        Quy định Tài liệu
                    </li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                            <i class="fi fi-rr-folder-open"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Nhóm tài liệu</div>
                            <div class="h4 mb-0 fw-bold">4</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                            <i class="fi fi-rr-check-circle"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Hiệu lực</div>
                            <div class="h4 mb-0 fw-bold">Đang áp dụng</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center" style="width:44px;height:44px;">
                            <i class="fi fi-rr-user-shield"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Phạm vi</div>
                            <div class="h4 mb-0 fw-bold">Nội bộ</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white border-bottom py-3">
            <h5 class="card-title mb-0 fw-bold text-dark">
                <i class="fi fi-rr-document-signed me-2 text-primary"></i>Danh sách quy định
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3 text-nowrap">Mã</th>
                            <th>Tên quy định</th>
                            <th>Phụ trách</th>
                            <th>Trạng thái</th>
                            <th class="pe-3">Nội dung chính</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($regulations as $regulation)
                            <tr>
                                <td class="ps-3 text-nowrap">
                                    <span class="badge bg-primary-subtle text-primary fw-semibold">{{ $regulation['code'] }}</span>
                                </td>
                                <td class="fw-semibold text-dark">{{ $regulation['title'] }}</td>
                                <td class="text-muted">{{ $regulation['owner'] }}</td>
                                <td>
                                    <span class="badge bg-success-subtle text-success border border-success">{{ $regulation['status'] }}</span>
                                </td>
                                <td class="pe-3" style="min-width: 360px;">
                                    <span class="text-body">{{ $regulation['summary'] }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
