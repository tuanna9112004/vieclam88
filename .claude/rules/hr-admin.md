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
- Application scope theo cơ sở: Policy phải kiểm tra `users.branch_id === application.owner_branch_id` cho staff (admin không giới hạn) trước khi cho xem/sửa — không chỉ chặn ở route, phải chặn ở Policy vì URL trực tiếp phải trả 403. Không có Policy/route "claim"/"assign" trong Phase 1 (ADR-021) — mọi staff cùng cơ sở có quyền như nhau trên Application của cơ sở đó.
- Chuyển cơ sở (`hr.applications.transfer-branch`) chỉ admin; luôn ghi `application_branch_histories` với `reason` bắt buộc.
- Đổi `applications.stage` chỉ qua `ChangeApplicationStageAction`, validate theo transition matrix (`docs/CORE-FLOWS.md` 5.1), không sửa cột từ controller. Mở lại hồ sơ đã `closed` dùng cùng action/route (`hr.applications.stage`), không có route riêng; bắt buộc lý do mở lại.
- Đổi lịch hẹn (`hr.applications.appointments.store`) tạo bản ghi mới thay vì sửa `scheduled_at` của lịch cũ; route `update` chỉ dùng để cập nhật `status`/`outcome`.
- Notes, snapshots, consent detail và contact nội bộ không xuất hiện ở public.
- CSV chỉ xuất allowlist cột, chống formula injection, ghi đúng một `export_logs`; không giữ file lâu dài.
- Admin khóa staff bằng `users.status = locked`, không hard-delete. Staff bắt buộc có `branch_id`.
- Dashboard Phase 1 chỉ dùng KPI đã được chốt; không tự mở rộng.
- Route, quyền và controller phải khớp `docs/ROUTE-MAP.md`.
