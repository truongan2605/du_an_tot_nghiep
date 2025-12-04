@extends('layouts.admin')

@section('title', 'Audit Logs')

@section('content')
<div class="container-fluid p-3">
    {{-- Explanatory Header --}}
    <div class="alert alert-info border-0 shadow-sm mb-3">
        <div class="row align-items-center">
            <div class="col-md-9">
                <h5 class="alert-heading mb-2">
                    <i class="fas fa-info-circle me-2"></i>Nhật Ký Thao Tác (Audit Log)
                </h5>
                <p class="mb-2 small">
                    Trang này ghi lại <strong>tất cả các thay đổi</strong> trong hệ thống để đảm bảo <strong>minh bạch và bảo mật</strong>. 
                    Mỗi hành động được lưu trữ với thông tin: <strong>Ai</strong>, <strong>Làm gì</strong>, <strong>Khi nào</strong>, và <strong>Thay đổi ra sao</strong>.
                </p>
                <div class="d-flex flex-wrap gap-3 small">
                    <div><i class="fas fa-shield-alt text-success me-1"></i> Tăng trách nhiệm nhân viên</div>
                    <div><i class="fas fa-search text-primary me-1"></i> Phát hiện lỗi nhanh chóng</div>
                    <div><i class="fas fa-history text-warning me-1"></i> Truy vết thay đổi dữ liệu</div>
                </div>
            </div>
            <div class="col-md-3 text-md-end mt-2 mt-md-0">
                <button type="button" class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#helpModal">
                    <i class="fas fa-question-circle me-1"></i>Hướng dẫn
                </button>
            </div>
        </div>
    </div>

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <nav aria-label="breadcrumb" class="mb-1">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('staff.index') }}">Staff</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Nhật ký thao tác</li>
                </ol>
            </nav>
            <h4 class="mb-0">Danh sách thay đổi</h4>
            <div class="small text-muted">Theo dõi hành động của người dùng — An toàn và minh bạch</div>
        </div>

        <div class="d-flex gap-2 align-items-center">
            <a href="{{ route('staff.audit-logs.index') }}" class="btn btn-outline-secondary btn-sm" title="Reload">
                <i class="fas fa-sync-alt"></i> Làm mới
            </a>

            {{-- Export placeholder (implement in controller if needed) --}}
            <div class="dropdown">
                <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" id="exportMenu" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-file-export"></i> Xuất
                </button>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportMenu">
                    <li><a class="dropdown-item" href="{{ route('staff.audit-logs.index', array_merge(request()->query(), ['export' => 'csv'])) }}">CSV</a></li>
                    <li><a class="dropdown-item" href="{{ route('staff.audit-logs.index', array_merge(request()->query(), ['export' => 'xlsx'])) }}">Excel</a></li>
                    <li><a class="dropdown-item" href="{{ route('staff.audit-logs.index', array_merge(request()->query(), ['export' => 'pdf'])) }}" target="_blank">PDF</a></li>
                </ul>
            </div>
        </div>
    </div>

    {{-- Filters panel --}}
    <div class="card mb-3 shadow-sm border-0">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-end">
                <div class="col-auto">
                    <label class="form-label small mb-1">From</label>
                    <input type="date" name="from" value="{{ request('from') }}" class="form-control form-control-sm" />
                </div>
                <div class="col-auto">
                    <label class="form-label small mb-1">To</label>
                    <input type="date" name="to" value="{{ request('to') }}" class="form-control form-control-sm" />
                </div>

                <div class="col-auto">
                    <label class="form-label small mb-1">Người</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">Tất cả</option>
                        @foreach($users as $u)
                            <option value="{{ $u }}" @selected(request('user_id') == $u)>{{ $u }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-auto">
                    <label class="form-label small mb-1">Sự kiện</label>
                    <select name="event" class="form-select form-select-sm">
                        <option value="">Tất cả</option>
                        @foreach($events as $ev)
                            <option value="{{ $ev }}" @selected(request('event') == $ev)>{{ Str::ucfirst($ev) }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-auto">
                    <label class="form-label small mb-1">Model</label>
                    <select name="auditable_type" class="form-select form-select-sm">
                        <option value="">Tất cả</option>
                        @foreach($models as $model)
                            <option value="{{ $model['value'] }}" @selected(request('auditable_type') == $model['value'])>
                                {{ $model['label'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-auto flex-grow-1">
                    <label class="form-label small mb-1">Tìm nhanh</label>
                    <input id="quick-search" type="search" name="q" value="{{ request('q') }}" placeholder="user, ip, note" class="form-control form-control-sm" />
                </div>

                <div class="col-auto">
                    <button class="btn btn-primary btn-sm">Lọc</button>
                    <a href="{{ route('staff.audit-logs.index') }}" class="btn btn-outline-secondary btn-sm ms-1">Reset</a>
                </div>
            </form>
        </div>
    </div>

    {{-- Summary cards --}}
    <div class="row g-2 mb-3">
        <div class="col-sm-4 col-md-auto">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-2 text-center">
                    <div class="small text-muted">Tổng bản ghi</div>
                    <div class="h5 mb-0">{{ $totalCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4 col-md-auto">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-2 text-center">
                    <div class="small text-muted">Trong 24h</div>
                    <div class="h5 mb-0">{{ $recentCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-sm-4 col-md-auto">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-body p-2 text-center">
                    <div class="small text-muted">Loại sự kiện (trang)</div>
                    <div class="h5 mb-0">{{ count($events) }}</div>
                </div>
            </div>
        </div>
        <div class="col-auto ms-auto">
            <div class="small text-muted">Kết quả / trang: {{ $logs->perPage() }}</div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 64vh; overflow:auto;">
                <table class="table table-hover table-sm mb-0 align-middle">
                    <thead class="small text-muted sticky-top" style="background:#fff; z-index:1;">
                        <tr>
                            <th style="min-width:160px">Thời gian</th>
                            <th>Người</th>
                            <th style="min-width:120px">Sự kiện</th>
                            <th>Model</th>
                            <th>Thay đổi</th>
                            <th style="min-width:140px">Hành động</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse($logs as $log)
                            <tr @if($log->isRecent) class="table-warning" @endif>
                                <td class="text-nowrap" style="width:160px;">
                                    <div class="fw-medium">{{ $log->created_at->format('d/m/Y H:i:s') }}</div>
                                    <div class="small text-muted">{{ $log->created_at->diffForHumans() }}</div>
                                </td>

                                <td style="width:220px;">
                                    <div class="fw-medium">{{ $log->user_name }}</div>
                                    <div class="small text-muted">ID: {{ $log->user_id ?? '-' }}</div>
                                </td>

                                <td>
                                    @php
                                        $badgeClass = \App\Helpers\AuditFieldTranslator::getEventBadgeClass($log->event);
                                        $icon = \App\Helpers\AuditFieldTranslator::getEventIcon($log->event);
                                        $eventLabel = \App\Helpers\AuditFieldTranslator::getEventLabel($log->event);
                                    @endphp
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas {{ $icon }}"></i>
                                        <span class="badge {{ $badgeClass }}" data-bs-toggle="tooltip" title="Sự kiện">
                                            {{ $eventLabel }}
                                        </span>
                                    </div>
                                </td>

                                <td class="small" style="width:180px;">
                                    <div>{{ $log->auditable }}</div>
                                </td>

                                <td class="small text-muted" style="max-width:420px;">
                                    {{-- change summary already prepared by controller --}}
                                    {{ $log->changes_summary }}
                                </td>

                                <td style="width:160px;">
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-sm btn-outline-primary view-log-btn" data-log-id="{{ $log->id }}" aria-label="Xem chi tiết">
                                            <i class="fas fa-eye"></i>
                                        </button>

                                        <button class="btn btn-sm btn-outline-secondary copy-summary-btn" data-summary="{{ e($log->changes_summary) }}" aria-label="Sao chép tóm tắt">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>

                                    {{-- Hidden pre-rendered safe HTML for modal --}}
                                    <template id="details-{{ $log->id }}" class="d-none">{!! $log->detailsHtml !!}</template>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center small text-muted py-3">Không có bản ghi nào phù hợp</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Footer: pagination --}}
            <div class="p-3 d-flex justify-content-between align-items-center">
                <div class="small text-muted">Hiển thị {{ $logs->firstItem() ?? 0 }} — {{ $logs->lastItem() ?? 0 }} / {{ $logs->total() }} kết quả</div>
                <div>
                    {{ $logs->links('pagination::bootstrap-5') }}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Modal (only human-readable details; not raw JSON nor URL) --}}
<div class="modal fade" id="logModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chi tiết thay đổi</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Đóng"></button>
            </div>
            <div class="modal-body">
                <div id="modalDetailsContainer" class="small text-muted"></div>
            </div>
            <div class="modal-footer">
                <button id="modalCopySummary" type="button" class="btn btn-outline-secondary btn-sm"><i class="fas fa-copy"></i> Sao chép tóm tắt</button>
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .table-warning { background-color: rgba(255, 243, 205, 0.6) !important; }
    .badge { font-size: .75rem; padding: .35em .5em; }
    thead.sticky-top { position: sticky; top: 0; z-index: 2; }
    /* Improve readability on small screens */
    @media (max-width: 768px) {
        .table-responsive { font-size: .88rem; }
        .fw-medium { font-weight: 600; }
    }
    /* subtle hover */
    tbody tr:hover { background: rgba(0,0,0,0.02); }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Enable Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el); });

    const modalEl = document.getElementById('logModal');
    const detailsContainer = document.getElementById('modalDetailsContainer');
    const modal = new bootstrap.Modal(modalEl);
    let lastSummary = '';

    // View Modal: use pre-rendered safe HTML inside <template>
    document.querySelectorAll('.view-log-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-log-id');
            const tpl = document.getElementById('details-' + id);
            if (!tpl) return;
            detailsContainer.innerHTML = tpl.innerHTML || '<div class="small text-muted">Không có chi tiết</div>';
            // store summary for copy action
            const row = btn.closest('tr');
            lastSummary = row ? (row.querySelector('td:nth-child(5)')?.textContent || '') : '';
            modal.show();
        });
    });

    // Copy summary button in row
    document.querySelectorAll('.copy-summary-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const text = btn.getAttribute('data-summary') || '';
            try {
                await navigator.clipboard.writeText(text);
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-success');
                btn.innerHTML = '<i class="fas fa-check"></i>';
                setTimeout(() => {
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-secondary');
                    btn.innerHTML = '<i class="fas fa-copy"></i>';
                }, 1000);
            } catch (e) {
                console.error(e);
                alert('Không thể sao chép vào clipboard');
            }
        });
    });

    // Copy summary from modal
    const modalCopyBtn = document.getElementById('modalCopySummary');
    modalCopyBtn.addEventListener('click', async () => {
        if (!lastSummary) return;
        try {
            await navigator.clipboard.writeText(lastSummary);
            modalCopyBtn.classList.remove('btn-outline-secondary');
            modalCopyBtn.classList.add('btn-success');
            modalCopyBtn.innerHTML = '<i class="fas fa-check"></i> Đã sao chép';
            setTimeout(() => {
                modalCopyBtn.classList.remove('btn-success');
                modalCopyBtn.classList.add('btn-outline-secondary');
                modalCopyBtn.innerHTML = '<i class="fas fa-copy"></i> Sao chép tóm tắt';
            }, 1200);
        } catch (err) {
            alert('Không thể sao chép');
        }
    });

    // Quick-search: debounce input typing to auto-submit after user stops typing
    const quickSearch = document.getElementById('quick-search');
    if (quickSearch) {
        let timer = null;
        quickSearch.addEventListener('input', function () {
            clearTimeout(timer);
            timer = setTimeout(() => {
                // submit only if value changed from initial to avoid accidental submits
                const form = quickSearch.closest('form');
                if (form) form.submit();
            }, 700);
        });
    }

    // Keyboard: press "/" to focus quick search
    document.addEventListener('keydown', function (e) {
        if (e.key === '/' && !e.metaKey && !e.ctrlKey && !e.altKey) {
            const s = document.getElementById('quick-search');
            if (s) {
                e.preventDefault();
                s.focus();
            }
        }
    });
});
</script>

{{-- Include Help Modal --}}
@include('staff.audit.help_modal')

@endpush