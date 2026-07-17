## 4. Vai trò

- `guest`: xem và ứng tuyển không cần tài khoản.
- `candidate`: lưu việc, xem việc đã ứng tuyển, sửa hồ sơ cơ bản.
- `staff`: xử lý ứng viên được phân công.
- `manager`: xem báo cáo và quản lý nghiệp vụ.
- `admin`: toàn quyền cấu hình, tài khoản và dữ liệu.

Chưa xây giao diện cộng tác viên trong MVP nhưng database phải hỗ trợ nguồn và người giới thiệu.

Lưu ý Phase 1 (xem mục 1.1): chưa cần `manager` riêng, staff chưa bị giới hạn xem theo phân
công (`assigned_to`) — mô tả "xử lý ứng viên được phân công" ở trên là mục tiêu Phase 2.

## 7. Quy tắc nghiệp vụ

- Chuẩn hóa số điện thoại trước khi tìm hoặc lưu.
- Không tạo ứng viên mới nếu số điện thoại đã tồn tại; cập nhật thông tin phù hợp.
- Ngăn ứng tuyển lặp cùng một việc làm; cho phép admin mở lại khi cần.
- Mọi hồ sơ mới phải xuất hiện trong HR.
- Mọi thay đổi trạng thái phải có lịch sử.
- Ghi chú nội bộ không xuất hiện ở website công khai.
- Công ty và việc làm dùng soft delete.
- Chỉ việc làm `published`, còn hạn và chưa xóa mới được hiển thị.
- Dữ liệu nguồn/người giới thiệu phải được lưu từ đầu để mở rộng cộng tác viên sau này.
