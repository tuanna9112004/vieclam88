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

- Auth staff/admin (Phase 1 chỉ 2 role này), dashboard cơ bản.
- Quản lý địa giới, KCN, công ty, location/contact, job (kèm `job_status_histories` mỗi lần
  đổi trạng thái), **cơ sở nội bộ (`branches`)**.
- Quản lý application; filter theo cơ sở, stage (theo transition matrix + chu kỳ xử lý, gồm mở
  lại `closed → new` có kiểm soát — đủ 7 điều kiện), contact attempt, appointment
  (callback/interview, đổi lịch = tạo mới), chuyển cơ sở ngoại lệ (đủ điều kiện, chỉ admin),
  note và histories. Bất kỳ staff thuộc đúng cơ sở đều xử lý được mọi hồ sơ của cơ sở đó —
  không có phân công/claim (ADR-021). Xem `docs/CORE-FLOWS.md`.
- Trang chi tiết Candidate (merged family) và merge candidate, chỉ admin.
- Xác nhận job còn tuyển, CSV + export log, soft-delete/restore, tài khoản staff.

## Ngoài phạm vi

Không tự thêm: Zalo Mini App, app riêng, cộng tác viên/hoa hồng/referral, thanh toán, hợp đồng, chấm công/lương, realtime chat, SMS/Zalo automation, AI matching, KPI/dashboard nâng cao, blog đầy đủ, RBAC nhiều tầng, full audit log (audit trail theo từng action vẫn có — ADR-019), hồ sơ/CCCD upload, Lead dưới mọi hình thức kể cả form "yêu cầu tư vấn" và `lead_requests` (Phase 2, ADR-021), phân công/claim/assign hồ sơ và tự động phân công/round-robin (Phase 2, ADR-021), **Candidate Account dưới mọi hình thức** (đăng ký/đăng nhập/`/tai-khoan`/dashboard/theo dõi trạng thái qua tài khoản — Phase 2, ADR-028), Favorites (Phase 2, ADR-021), import dữ liệu hàng loạt (ADR-029).

Ảnh ở `docs/ui-reference/out-of-scope/` không phải yêu cầu chức năng.
