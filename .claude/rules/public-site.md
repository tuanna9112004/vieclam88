---
paths:
  - "app/Http/Controllers/Public/**/*.php"
  - "app/Http/Requests/Public/**/*.php"
  - "resources/views/public/**/*.blade.php"
  - "resources/views/jobs/**/*.blade.php"
  - "resources/views/companies/**/*.blade.php"
  - "routes/web.php"
  - "tests/Feature/Public/**/*.php"
---

# Public site

- Guest không có tài khoản; không tạo `/tai-khoan`, Favorites, Lead/yêu cầu tư vấn hoặc route Phase 2.
- Danh sách/search/sitemap chỉ lấy Job public hợp lệ. Draft không public; paused/closed/expired không nhận Application.
- Job paused/closed/expired giữ URL chi tiết `200`, hiển thị không còn nhận hồ sơ, ẩn form apply, giữ CTA Branch và Job liên quan theo contract.
- CTA gọi/Zalo dùng `branches.phone`/`branches.zalo`; Company Contact không thay CTA và chỉ hiển thị thêm khi được phép public.
- Form apply phải lấy token server, không nhận branch/stage từ client, không thu dữ liệu nhạy cảm ngoài contract.
- Không lộ note, snapshot, consent detail, contact nội bộ hoặc pipeline.
- Không copy thương hiệu/nội dung đối thủ; ảnh tham khảo chỉ định hướng layout.
