---
paths:
  - "app/Http/**/*.php"
  - "app/Policies/**/*.php"
  - "app/Providers/**/*.php"
  - "config/**/*.php"
  - "routes/**/*.php"
  - "resources/views/**/*.blade.php"
  - "tests/**/*Security*.php"
---

# Security

- CSRF, Form Request, allowlist mass assignment, escaped Blade output; raw HTML chỉ khi đã sanitize.
- Rate limit login/application; honeypot mặc định, CAPTCHA chỉ khi có abuse đo được.
- Không log password, token, raw phone lock key, consent payload hoặc PII không cần thiết; không đọc `.env`.
- Upload (nếu có trong scope) phải kiểm MIME/size, tên ngẫu nhiên và chặn executable.
- File riêng tư (CV, tài liệu ứng viên...) lưu disk private, không public URL trực tiếp; chỉ
  tải/xem qua controller có Policy đúng branch, response an toàn với tên file (chống header injection).
- Authorization kiểm tra backend/Policy; input client không được quyết định role, branch, owner hoặc stage/status.
- Export chống CSV formula injection và giới hạn cột theo quyền. Production phải `APP_DEBUG=false`.
