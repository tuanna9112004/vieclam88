---
paths:
  - "app/**/*.php"
  - "database/**/*.php"
  - "routes/**/*.php"
  - "resources/**/*"
  - "tests/**/*.php"
---

# Phạm vi Phase 1

## Public

- Trang chủ; danh sách/chi tiết việc; tìm kiếm và lọc.
- Danh sách/chi tiết công ty; trang KCN.
- Gọi, Zalo, ứng tuyển guest, form yêu cầu tư vấn.
- Giới thiệu, liên hệ, FAQ, SEO, sitemap, responsive.

## HR

- Auth staff/admin, dashboard cơ bản.
- Quản lý địa giới, KCN, công ty, location/contact, job, **cơ sở nội bộ (`branches`)**.
- Quản lý application; filter theo cơ sở, assignment, stage (theo transition matrix), contact
  attempt, appointment (callback/interview), chuyển cơ sở ngoại lệ, note và histories. Xem
  `docs/CORE-FLOWS.md`.
- Lead (`lead_requests`): chỉ xem/ghi nhận yêu cầu tư vấn, xử lý thủ công ngoài hệ thống —
  **không** chuyển đổi thành application (Phase 2, ADR-018).
- Xác nhận job còn tuyển, CSV + export log, soft-delete/restore, tài khoản staff.

## Candidate account

Làm sau khi guest + HR ổn định: register/login, profile, favorites, applied jobs và liên kết candidate cũ.

## Ngoài phạm vi

Không tự thêm: Zalo Mini App, app riêng, cộng tác viên/hoa hồng/referral, thanh toán, hợp đồng, chấm công/lương, realtime chat, SMS/Zalo automation, AI matching, KPI/dashboard nâng cao, blog đầy đủ, RBAC nhiều tầng, full audit log (audit trail theo từng action vẫn có — ADR-019), hồ sơ/CCCD upload, chuyển đổi Lead thành Application (Phase 2, ADR-018), tự động phân công/round-robin hồ sơ.

Ảnh ở `docs/ui-reference/out-of-scope/` không phải yêu cầu chức năng.
