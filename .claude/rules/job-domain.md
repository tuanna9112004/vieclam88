---
paths:
  - "app/**/*Job*.php"
  - "app/**/*Company*.php"
  - "app/**/*IndustrialPark*.php"
  - "database/**/*job*.php"
  - "database/**/*compan*.php"
  - "database/**/*industrial_park*.php"
  - "resources/views/hr/jobs/**/*.blade.php"
  - "resources/views/jobs/**/*.blade.php"
  - "tests/**/*Job*.php"
  - "tests/**/*Company*.php"
---

# Company và Job domain

Nguồn: `docs/CORE-FLOWS.md` mục 0–2, các bảng Company/Job trong Dictionary và ADR được tham chiếu tại đó.

- Company/Location Quick Create chỉ bắt buộc tên; dữ liệu chưa biết lưu `NULL`. KCN phải khớp đơn vị hành chính.
- Job draft bắt buộc title/company/owner branch/creator; description, requirements, benefits và dữ liệu publish được phép thiếu.
- Staff tạo Job được server gán cơ sở mình; chỉ Admin đổi cơ sở, chỉ khi `draft`/`paused`, có reason/history/lock.
- Status chỉ đổi qua domain Action và `job_status_histories`; mọi lần publish/re-publish phải chạy toàn bộ predicate `PUB-*`.
- Verification publish dùng bản ghi mới nhất; `last_checked_at` cập nhật mọi lần, `last_verified_at` chỉ khi `still_open`; áp dụng đúng ma trận Status×Result.
- Salary dùng hai mode loại trừ nhau; Shift cần ít nhất một `job_work_shifts`; đúng một primary job location.
- `company_contact_id` nếu có phải cùng Company, active/chưa xóa; public chỉ khi `is_public=true`; CTA chính vẫn là Branch phone/Zalo.
- Job paused/closed/expired không nhận Application; quy tắc URL/public nằm ở `.claude/rules/public-site.md`.
