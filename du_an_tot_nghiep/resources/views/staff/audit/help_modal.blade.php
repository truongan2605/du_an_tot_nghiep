{{-- Help Modal for Audit Log --}}
<div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="helpModalLabel">
                    <i class="fas fa-question-circle me-2"></i>Hướng Dẫn Sử Dụng Nhật Ký Thao Tác
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- What is it --}}
                <h6 class="text-primary mb-2"><i class="fas fa-book me-2"></i>Nhật Ký Thao Tác là gì?</h6>
                <p class="small mb-3">
                    Giống như <strong>"sổ nhật ký"</strong> trong văn phòng, Nhật Ký Thao Tác ghi lại mọi thay đổi quan trọng trong hệ thống quản lý khách sạn. 
                    Mỗi khi có ai đó thêm, sửa, xóa thông tin (phòng, booking, giá...), hệ thống tự động lưu lại:
                </p>
                <ul class="small mb-3">
                    <li><strong>Ai</strong> đã thực hiện (tên người dùng)</li>
                    <li><strong>Làm gì</strong> (tạo mới, sửa, xóa)</li>
                    <li><strong>Khi nào</strong> (ngày giờ chính xác)</li>
                    <li><strong>Thay đổi gì</strong> (giá trị cũ → giá trị mới)</li>
                </ul>

                <hr>

                {{-- Why need --}}
                <h6 class="text-primary mb-2"><i class="fas fa-lightbulb me-2"></i>Tại sao cần Nhật Ký Thao Tác?</h6>
                <div class="row g-2 mb-3 small">
                    <div class="col-md-6">
                        <div class="card border-success h-100">
                            <div class="card-body p-2">
                                <h6 class="small mb-1"><i class="fas fa-shield-alt text-success me-1"></i>Tăng trách nhiệm</h6>
                                <p class="mb-0 small text-muted">Nhân viên thận trọng hơn khi biết mọi thao tác đều được ghi lại</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-primary h-100">
                            <div class="card-body p-2">
                                <h6 class="small mb-1"><i class="fas fa-search text-primary me-1"></i>Phát hiện lỗi</h6>
                                <p class="mb-0 small text-muted">Dễ dàng tìm nguyên nhân khi có sự cố (VD: giá phòng sai)</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-warning h-100">
                            <div class="card-body p-2">
                                <h6 class="small mb-1"><i class="fas fa-history text-warning me-1"></i>Truy vết thay đổi</h6>
                                <p class="mb-0 small text-muted">Xem lịch sử thay đổi của bất kỳ dữ liệu nào</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card border-info h-100">
                            <div class="card-body p-2">
                                <h6 class="small mb-1"><i class="fas fa-gavel text-info me-1"></i>Tuân thủ quy định</h6>
                                <p class="mb-0 small text-muted">Đáp ứng yêu cầu pháp lý về lưu trữ dữ liệu</p>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                {{-- Examples --}}
                <h6 class="text-primary mb-2"><i class="fas fa-lightbulb me-2"></i>Ví Dụ Thực Tế</h6>
                <div class="card bg-light border-0 mb-3">
                    <div class="card-body p-2 small">
                        <p class="mb-2"><strong>Tình huống:</strong> Giá phòng 101 bị sai, từ 500,000₫ thành 600,000₫</p>
                        <p class="mb-2"><strong>Giải pháp:</strong></p>
                        <ol class="mb-0 ps-3">
                            <li>Vào Nhật Ký Thao Tác</li>
                            <li>Lọc Model: "Phong", ID: 101</li>
                            <li>Xem ai đã sửa, khi nào sửa</li>
                            <li>Thấy: "User: admin_nguyen, Ngày: 15/11, Thay đổi: gia_mac_dinh từ 500000 → 600000"</li>
                        </ol>
                    </div>
                </div>

                {{-- Event Types --}}
                <h6 class="text-primary mb-2"><i class="fas fa-tags me-2"></i>Các Loại Sự Kiện</h6>
                <div class="row g-2 small">
                    <div class="col-md-3">
                        <span class="badge bg-success w-100"><i class="fas fa-plus-circle me-1"></i>Tạo mới</span>
                        <p class="mb-0 mt-1 text-muted small">Thêm dữ liệu mới</p>
                    </div>
                    <div class="col-md-3">
                        <span class="badge bg-primary w-100"><i class="fas fa-edit me-1"></i>Cập nhật</span>
                        <p class="mb-0 mt-1 text-muted small">Sửa dữ liệu</p>
                    </div>
                    <div class="col-md-3">
                        <span class="badge bg-danger w-100"><i class="fas fa-trash me-1"></i>Xóa</span>
                        <p class="mb-0 mt-1 text-muted small">Xóa dữ liệu</p>
                    </div>
                    <div class="col-md-3">
                        <span class="badge bg-secondary w-100"><i class="fas fa-eye me-1"></i>Xem</span>
                        <p class="mb-0 mt-1 text-muted small">Truy cập xem</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
