## 6. Trang quản trị HR

Mục này mô tả thiết kế **đầy đủ cho Phase 2** (xem mục 1.1). Ở Phase 1 chỉ triển khai bản rút gọn đã liệt kê trong mục 1.1; các mục con dưới đây (6.1, phần lịch sử trạng thái ở 6.3, và phần phân quyền/gán việc ở 6.4) giữ lại làm tài liệu tham chiếu cho khi mở rộng, chưa cần code ngay.

### 6.1. Dashboard

- Ứng viên mới hôm nay/tháng.
- Ứng viên chưa liên hệ.
- Lịch phỏng vấn hôm nay.
- Ứng viên chờ đi làm và đã đi làm.
- Thống kê theo tỉnh, KCN, việc làm, nguồn và nhân viên.

### 6.2. Công ty và việc làm

- CRUD công ty; hỗ trợ ẩn, khôi phục và xóa mềm.
- CRUD việc làm thuộc công ty.
- Trạng thái việc làm: `draft`, `published`, `paused`, `closed`.
- Việc làm đóng, ẩn hoặc xóa mềm không xuất hiện công khai.
- Hỗ trợ nhân bản việc làm cũ.
- Tự ẩn hoặc cảnh báo việc làm hết hạn/chưa xác nhận còn tuyển.

### 6.3. Ứng viên và hồ sơ ứng tuyển

Mỗi ứng viên có thể ứng tuyển nhiều việc làm. Mỗi lần ứng tuyển tạo một hồ sơ riêng.

Thông tin cần quản lý:

- Ứng viên và số điện thoại chuẩn hóa.
- Việc làm/công ty/KCN ứng tuyển.
- Nguồn ứng viên.
- Người giới thiệu nếu có.
- Nhân viên phụ trách (`assigned_to`, nullable — nhân viên tự nhận xử lý; xem mục 1.1 về quyền xem ở Phase 1).
- Trạng thái hiện tại.
- Ngày đăng ký, cập nhật, phỏng vấn, dự kiến đi làm.
- Ghi chú nội bộ.

Trạng thái chuẩn:

- `new`: Mới đăng ký.
- `called`: Đã gọi.
- `no_answer`: Không nghe máy.
- `consulted`: Đã tư vấn.
- `interview_scheduled`: Đã hẹn phỏng vấn.
- `interviewed`: Đã đi phỏng vấn.
- `waiting_start`: Chờ đi làm.
- `started`: Đã đi làm.
- `unsuitable`: Không phù hợp.
- `cancelled`: Đã hủy.

Mỗi lần đổi trạng thái phải lưu trạng thái cũ, trạng thái mới, người thay đổi, thời gian và ghi chú.

### 6.4. Nhân viên và phân quyền

- Quản lý tài khoản staff, manager, admin.
- Gán ứng viên cho nhân viên.
- Giới hạn quyền xem dữ liệu nhạy cảm, xuất Excel, xóa và khôi phục.
- Lưu nhật ký thao tác quan trọng.

### 6.5. Nội dung và danh mục

- Quản lý tỉnh, KCN, lĩnh vực, ca làm, nguồn ứng viên.
- Quản lý FAQ, trang giới thiệu, thông tin liên hệ và banner.
