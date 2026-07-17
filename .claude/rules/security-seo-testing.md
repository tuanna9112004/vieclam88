## 10. Bảo mật và dữ liệu

- Validate và sanitize mọi input.
- CSRF, rate limit, CAPTCHA cho form công khai khi cần.
- Hash mật khẩu; không ghi mật khẩu hoặc secret vào log.
- Kiểm soát upload theo loại, kích thước và tên file.
- Chống mass assignment, SQL injection, XSS và IDOR.
- Kiểm tra quyền ở backend, không chỉ ẩn nút trên giao diện.
- Dữ liệu nhạy cảm chỉ hiển thị cho vai trò được phép.
- Có checkbox đồng ý liên hệ và trang chính sách bảo mật.

## 11. SEO và hiệu năng

- URL thân thiện cho việc làm, công ty, tỉnh và KCN.
- Meta title, description, canonical, Open Graph và sitemap.
- Structured data phù hợp cho JobPosting.
- Ảnh WebP, lazy loading và phân trang server-side.
- Tránh N+1 query; tạo index cho trường tìm kiếm/lọc.

## 12. Kiểm thử bắt buộc

- Guest xem, lọc và ứng tuyển được.
- Form lưu đúng dữ liệu cơ bản; phần bổ sung có thể bỏ qua.
- Không tạo ứng viên trùng không cần thiết.
- Không cho ứng tuyển lặp cùng một việc làm.
- Hồ sơ xuất hiện đúng trong HR.
- Staff chỉ xem và sửa dữ liệu được phép.
- Đổi trạng thái tạo lịch sử.
- Việc làm ẩn/đóng/xóa không xuất hiện công khai.
- Soft delete và restore hoạt động.
- Website hoạt động tốt trên mobile.
