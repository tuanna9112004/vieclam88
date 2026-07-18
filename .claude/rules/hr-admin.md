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

- Mọi route `/hr/*` dùng auth + role (`staff`/`admin` — Phase 1 không có role khác); thao tác model vẫn phải qua Policy.
- Guest/ứng viên không có tài khoản, không truy cập dữ liệu HR (ADR-028); staff không quản lý admin.
- Application scope theo cơ sở: Policy phải kiểm tra `users.branch_id === application.owner_branch_id` cho staff (admin không giới hạn) trước khi cho xem/sửa — không chỉ chặn ở route, phải chặn ở Policy vì URL trực tiếp phải trả 403. Không có Policy/route "claim"/"assign" trong Phase 1 (ADR-021) — mọi staff thuộc đúng cơ sở có quyền như nhau trên Application của cơ sở đó.
- Chuyển cơ sở (`hr.applications.transfer-branch`) chỉ admin; kiểm tra đủ điều kiện ở `docs/CORE-FLOWS.md` mục 6.1 (cơ sở đích tồn tại, active, chưa xóa, khác cơ sở hiện tại) trước khi ghi `application_branch_histories` với `reason` bắt buộc; `lockForUpdate` chống 2 request đồng thời.
- Đổi `applications.stage` chỉ qua `ChangeApplicationStageAction`, validate theo transition matrix + chu kỳ hiện tại (`docs/CORE-FLOWS.md` mục 5.1, 5.4), không sửa cột từ controller. Mở lại hồ sơ đã `closed` dùng cùng action/route (`hr.applications.stage`), không có route riêng; kiểm tra đủ 7 điều kiện ở mục 5.5 (bắt buộc lý do, không mở lại được nếu `close_reason=duplicate`, candidate/job phải còn hợp lệ).
- Đổi lịch hẹn (`hr.applications.appointments.store`) tạo bản ghi mới thay vì sửa `scheduled_at` của lịch cũ; route `update` chỉ dùng để cập nhật `status`/`outcome`.
- `CompanyController@store`/`CompanyLocationController@store` chỉ bắt buộc `name` (Quick Create — `docs/CORE-FLOWS.md` mục 0.2, 0.3, ADR-045); không validate bắt buộc mã số thuế/trụ sở/website/logo/`administrative_unit_id`/`address_detail` lúc tạo. Khi `industrial_park_id` khác null, `administrative_unit_id` bắt buộc khớp tỉnh của KCN đó (ADR-052).
- `CompanyLocationController@destroy`/`restore` và `CompanyContactController@destroy`/`restore` chỉ **admin** — Staff chỉ có `store`/`update` (ADR-053).
- `JobController@store` bắt buộc `owner_branch_id` ngay từ lúc tạo (Job Draft Contract — mục 1.0, 1.1, ADR-046) nhưng cho phép thiếu company/location đầy đủ, lương, quyền lợi, xác minh.
- Đổi `jobs.status` chỉ qua `ChangeJobStatusAction`, ghi `job_status_histories` mỗi lần (khác `job_verifications`); mọi lần `→ published` (kể cả `paused → published`) phải re-check toàn bộ điều kiện publish (mục 1.2), gồm địa điểm đủ rõ và đã xác minh `still_open` (Admin override có lý do — ADR-047).
- `JobVerificationController@store` cập nhật `jobs.last_checked_at` mọi lần, `jobs.last_verified_at` chỉ khi `result=still_open` (mục 1.3, ADR-048).
- Đổi `jobs.owner_branch_id` chỉ qua `ChangeJobBranchAction` (`hr.jobs.transfer-branch`), chỉ admin, chỉ khi Job `draft`/`paused` và chưa `deleted_at` (**không** `published` hoặc `closed` — ADR-054), cơ sở đích active/chưa xóa, có lý do bắt buộc, ghi `job_branch_histories`. Staff không có quyền/route đổi cơ sở Job; `JobController@update` không được sửa cột này (`docs/CORE-FLOWS.md` mục 1.1).
- Merge candidate (`hr.candidates.merge`) chỉ admin; trang chi tiết candidate (`hr.candidates.show`) hiển thị "merged family" nhưng Staff vẫn chỉ thấy Application thuộc cơ sở mình trong family đó (`docs/CORE-FLOWS.md` mục 6.3).
- Notes, snapshots, consent detail và contact nội bộ không xuất hiện ở public.
- CSV chỉ xuất allowlist cột, chống formula injection, ghi đúng một `export_logs`; không giữ file lâu dài.
- Admin khóa staff bằng `users.status = locked`, không hard-delete. Staff bắt buộc có `branch_id`.
- Dashboard Phase 1 chỉ dùng KPI đã được chốt; không tự mở rộng.
- Route, quyền và controller phải khớp `docs/ROUTE-MAP.md`.
