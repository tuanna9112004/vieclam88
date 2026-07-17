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

# HR

- Mọi route `/hr/*` dùng auth + role; thao tác model vẫn phải qua Policy.
- Candidate/guest không truy cập dữ liệu HR; staff không quản lý admin.
- Notes, snapshots, consent detail và contact nội bộ không xuất hiện ở public.
- CSV chỉ xuất allowlist cột, chống formula injection, ghi đúng một `export_logs`; không giữ file lâu dài.
- Admin khóa staff bằng `users.status = locked`, không hard-delete.
- Dashboard Phase 1 chỉ dùng KPI đã được chốt; không tự mở rộng.
- Route, quyền và controller phải khớp `docs/ROUTE-MAP.md`.
