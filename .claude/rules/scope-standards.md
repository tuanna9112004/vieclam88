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
- Gọi, Zalo, ứng tuyển guest.
- Giới thiệu, liên hệ (thông tin tĩnh, không có form gửi Lead), FAQ, SEO, sitemap, responsive.

## HR

- Auth staff/admin, dashboard cơ bản.
- Quản lý địa giới, KCN, công ty, location/contact, job, **cơ sở nội bộ (`branches`)**.
- Quản lý application; filter theo cơ sở, stage (theo transition matrix, gồm mở lại `closed →
  new` có kiểm soát), contact attempt, appointment (callback/interview, đổi lịch = tạo mới),
  chuyển cơ sở ngoại lệ, note và histories. Bất kỳ staff nào cùng cơ sở đều xử lý được mọi hồ
  sơ của cơ sở đó — không có phân công/claim (ADR-021). Xem `docs/CORE-FLOWS.md`.
- Xác nhận job còn tuyển, CSV + export log, soft-delete/restore, tài khoản staff.

## Candidate account

Làm sau khi guest + HR ổn định: register/login, profile, applied jobs và liên kết candidate cũ. Không có Favorites (Phase 2, ADR-021).

## Ngoài phạm vi

Không tự thêm: Zalo Mini App, app riêng, cộng tác viên/hoa hồng/referral, thanh toán, hợp đồng, chấm công/lương, realtime chat, SMS/Zalo automation, AI matching, KPI/dashboard nâng cao, blog đầy đủ, RBAC nhiều tầng, full audit log (audit trail theo từng action vẫn có — ADR-019), hồ sơ/CCCD upload, Lead dưới mọi hình thức kể cả form "yêu cầu tư vấn" và `lead_requests` (Phase 2, ADR-021), phân công/claim/assign hồ sơ và tự động phân công/round-robin (Phase 2, ADR-021), Favorites (Phase 2, ADR-021).

Ảnh ở `docs/ui-reference/out-of-scope/` không phải yêu cầu chức năng.
