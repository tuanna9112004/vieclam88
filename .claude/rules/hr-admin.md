---
paths:
  - "app/Http/Controllers/Hr/**/*.php"
  - "app/Http/Requests/Hr/**/*.php"
  - "resources/views/hr/**/*.blade.php"
  - "resources/js/hr/**/*"
  - "resources/css/hr/**/*"
  - "routes/hr.php"
  - "tests/Feature/Hr/**/*.php"
---

# HR admin

- Route/controller/request phải khớp `docs/ROUTE-MAP.md`; quyền chi tiết theo `.claude/rules/authorization.md`.
- Controller chỉ điều phối Request → Policy → Action/Service → response; không cập nhật stage/status/owner branch trực tiếp.
- Quick Create Company/Location không ép trường ngoài contract; delete/restore dữ liệu dùng chung chỉ Admin.
- Application list phải scope theo Branch và hỗ trợ đúng filter Phase 1; timeline không làm mất ranh giới các loại history.
- Notes, snapshots, consent và contact nội bộ không được đưa ra public.
- CSV dùng allowlist, chống formula injection và ghi `export_logs`; không lưu file lâu dài.
- Dashboard chỉ dùng KPI đã chốt; không tự thêm assignment, KPI cá nhân hoặc BI.
- Khi sửa Job/Application, đọc thêm rule domain tương ứng thay vì chép lại nghiệp vụ tại đây.
