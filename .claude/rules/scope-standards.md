## 13. Ngoài phạm vi MVP

Chưa xây:

- App mobile hoặc Zalo Mini App.
- Chấm công, hợp đồng điện tử, quản lý nghỉ phép.
- Xu/điểm thưởng.
- Cổng doanh nghiệp đối tác.
- Cộng tác viên, hoa hồng, xác thực danh tính và tài khoản ngân hàng.
- Đánh giá công ty bằng sao.

Chỉ mở rộng các phần này sau khi website và quy trình HR hoạt động ổn định.

## 14. Quy chuẩn thực thi

- Code rõ ràng, nhất quán, tránh lặp nghiệp vụ giữa controller.
- Controller mỏng; validation ở Form Request; nghiệp vụ ở Service.
- Authorization bằng Policy/Middleware.
- Dùng migration, seeder và factory; không phụ thuộc SQL thủ công.
- Có dữ liệu mẫu để chạy thử ngay.
- Có README hướng dẫn cài trên XAMPP, cấu hình `.env`, migrate, seed, storage link và chạy test.
- Không tự thêm chức năng ngoài phạm vi khi chưa có yêu cầu.
