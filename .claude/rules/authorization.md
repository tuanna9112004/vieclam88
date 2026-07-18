---
paths:
  - "app/Policies/**/*.php"
  - "app/Http/Middleware/**/*.php"
  - "app/Providers/**/*.php"
  - "app/Http/Controllers/Auth/**/*.php"
  - "routes/**/*.php"
  - "tests/**/*Authorization*.php"
  - "tests/**/*Policy*.php"
  - "tests/Feature/Auth/**/*.php"
---

# Authentication và authorization

- Mọi `/hr/*` cần auth; `EnsureUserIsActive` chạy trước `EnsurePasswordChanged`. User bị khóa mất quyền ở request kế tiếp.
- Staff bắt buộc có Branch, chỉ truy cập Job/Application đúng scope; Admin không giới hạn cơ sở. URL trực tiếp trái quyền phải 403, không chỉ ẩn nút.
- Candidate không có `branch_id`; Staff chỉ mở Candidate khi merged family có Application thuộc cơ sở mình và chỉ thấy phần được phép.
- Admin-only: quản lý Branch/Staff, transfer branch, merge/anonymize Candidate, duplicate review, delete/restore shared data, export toàn hệ thống.
- Không có claim/assign/round-robin trong Phase 1; mọi Staff cùng Branch có quyền xử lý Application của Branch.
- Password-first-change và lock/reset/unlock phải khớp `docs/ROUTE-MAP.md` và acceptance criteria.
- Authorization luôn ở Policy/middleware/backend; Controller không tự suy quyền bằng dữ liệu từ client.
