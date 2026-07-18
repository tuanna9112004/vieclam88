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

6 luồng nghiệp vụ mà khu vực HR phải triển khai đúng: `docs/CORE-FLOWS.md`.

- Mọi route `/hr/*` dùng auth + role; thao tác model vẫn phải qua Policy.
- Candidate/guest không truy cập dữ liệu HR; staff không quản lý admin.
- Application scope theo cơ sở: Policy phải kiểm tra `users.branch_id === application.owner_branch_id` cho staff (admin không giới hạn) trước khi cho xem/sửa — không chỉ chặn ở route, phải chặn ở Policy vì URL trực tiếp phải trả 403.
- Chuyển cơ sở (`hr.applications.transfer-branch`) chỉ admin; luôn ghi `application_branch_histories` với `reason` bắt buộc.
- Đổi `applications.stage` chỉ qua `ChangeApplicationStageAction`, validate theo transition matrix (`docs/CORE-FLOWS.md` 5.1), không sửa cột từ controller.
- Notes, snapshots, consent detail và contact nội bộ không xuất hiện ở public.
- CSV chỉ xuất allowlist cột, chống formula injection, ghi đúng một `export_logs`; không giữ file lâu dài.
- Admin khóa staff bằng `users.status = locked`, không hard-delete. Staff bắt buộc có `branch_id`.
- Dashboard Phase 1 chỉ dùng KPI đã được chốt; không tự mở rộng.
- Route, quyền và controller phải khớp `docs/ROUTE-MAP.md`.
