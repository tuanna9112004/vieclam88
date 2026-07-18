# Phase 1 Scope — Baseline v1.0 (frozen)

**Trạng thái nội dung: ĐÓNG BĂNG (ADR-057); Git baseline chỉ hoàn tất sau commit/tag.** Đây là bản khai phạm vi chính thức của Phase 1, không phải
nguồn chi tiết — nguồn chi tiết vẫn là các file gốc được liên kết bên dưới, file này chỉ tổng
hợp + tuyên bố đóng băng để tránh việc tiếp tục bổ sung yêu cầu không giới hạn trước khi tạo
migration.

Sau mốc này, **không mở rộng thêm chức năng vào Phase 1** ngoài danh sách dưới đây, trừ khi phát
hiện lỗi nghiệp vụ hoặc lỗ hổng bảo mật nghiêm trọng cần vá trước migration. Chức năng không
được liệt kê ở đây mặc định thuộc `docs/PHASE-2-BACKLOG.md`.

## Nguồn chi tiết (không chép lại ở đây)

| Chủ đề | Nguồn duy nhất |
|---|---|
| 6 luồng nghiệp vụ cốt lõi | `docs/CORE-FLOWS.md` |
| Bootstrap/deployment sequence | `ROADMAP.md` mục "Bootstrap Sequence" |
| Schema đầy đủ (28 bảng business) | `docs/DATABASE-DICTIONARY.md` |
| Quan hệ | `docs/ERD.md` |
| Route | `docs/ROUTE-MAP.md` |
| Tiêu chí nghiệm thu | `docs/ACCEPTANCE-CRITERIA.md` |
| Quyết định kiến trúc (ADR-001..078) | `docs/decisions/INDEX.md` |
| Phân loại blocker (Migration/Go-live/Phase 2) | `ROADMAP.md` mục "Phân loại blocker" |
| Tiến độ hiện tại | `docs/PROJECT-STATUS.md` |

## Phạm vi Phase 1 — chức năng cấp cao (IN SCOPE)

**Public (guest, không tài khoản):**
- Trang chủ, danh sách/chi tiết việc làm, tìm kiếm và lọc (từ khóa, tỉnh/thành, KCN, công ty,
  lương, ca làm, quyền lợi).
- Danh sách/chi tiết công ty, trang khu công nghiệp.
- Gọi điện/Zalo (mở kênh liên lạc, không tạo bản ghi) — luôn dùng contact của `branches` phụ
  trách Job.
- Ứng tuyển guest qua form (idempotent bằng `submission_token`).
- Trang giới thiệu, liên hệ (tĩnh), FAQ, sitemap, SEO, responsive/mobile-first.

**HR (`/hr`, auth staff/admin):**
- Quản lý đơn vị hành chính, khu công nghiệp (admin).
- Quản lý cơ sở nội bộ `branches` (admin), gồm cấu hình `phone`/`zalo`.
- Company/Company Location/Company Contact — Quick Create (chỉ cần tên), Staff
  tạo/sửa, Admin thêm quyền soft delete/restore.
- Job: Draft Contract (thiếu dữ liệu được phép), Publish Contract (22 điều kiện có mã `PUB-*`, gồm `PUB-VERIFY`, Salary/Shift Predicate), transition matrix 5 bước, Job Branch Contract (đổi cơ sở chỉ khi draft/paused),
  Job Verification Scheduler (cảnh báo, không tự pause).
- Application: pipeline 8 trạng thái + reopen có kiểm soát, Contact Log, Appointment
  (callback/interview), Workflow Cycle, chuyển cơ sở ngoại lệ (admin), filter đầy đủ.
- Candidate: tìm kiếm, Candidate Access Policy (403 theo cơ sở qua merged family), Duplicate
  Candidate Contract (4 trường hợp), Duplicate Review (`candidate_duplicate_reviews`, admin),
  Merge (admin).
- Dashboard Staff/Admin (KPI cố định — `docs/CORE-FLOWS.md` mục 9.1), Export CSV + log.
- Tài khoản Staff/Admin: bootstrap Admin đầu tiên qua `php artisan app:create-admin`, tài khoản
  Staff tạo qua HR (admin), khóa bằng `status=locked`.

**Yêu cầu kỹ thuật nền tảng (không được lược bỏ):** authentication, Policy theo cơ sở,
validation, transaction, row locking, constraint/FK/index, soft delete có kiểm soát, submission
idempotency, CSRF, rate limit, audit trail theo từng action, chống CSV formula injection,
không lộ PII giữa các cơ sở.

## Ngoài phạm vi Phase 1 (xem chi tiết ở `docs/PHASE-2-BACKLOG.md`)

Lead (mọi kênh), Assignment/claim hồ sơ, Candidate Account (đăng ký/đăng nhập/tài khoản/theo
dõi trạng thái), Favorites, Zalo API/tự động nhắn tin, cộng tác viên/hoa hồng/referral, import
dữ liệu hàng loạt, AI matching, dashboard/BI nâng cao, auto-pause Job (mặc định tắt).

## Điều kiện đã đóng (không còn mở ở mức migration)

Quick Create Company/Location (ADR-045), Job Draft/Publish/Verification Contract mở rộng
(ADR-046, ADR-047, ADR-048, ADR-058, ADR-059, ADR-060), Enum Strategy loại bỏ migration blocker
(ADR-055), PII schema tối thiểu cho `applications` (ADR-056), quyền xóa/khôi phục Company
Location/Contact (ADR-053), Job Branch Transfer chỉ draft/paused (ADR-054), validation tỉnh/KCN
(ADR-052), bootstrap/seeder (ADR-050, ADR-051), Submission Concurrency Contract (ADR-061),
Duplicate Review data model (ADR-062), merged-root resolution + full name normalization
(ADR-063), primary field semantics (ADR-064), administrative unit root uniqueness (ADR-065),
timestamp/hạ tầng Laravel (ADR-066), password-first-change + Route Map đầy đủ (ADR-067), soft
delete/restore contract rà soát toàn bộ (ADR-068), migration order + roadmap 7 nhóm (ADR-069),
Job hết hạn (ADR-072), ERD cardinality (ADR-073), publish/contact consistency (ADR-074), matching nhiều-root + review summary (ADR-075), merged-family same-job (ADR-076), active-user middleware (ADR-077), semantic checker/final patch (ADR-078).

## Migration blocker

**Không còn.** 5 enum phụ (`docs/CORE-FLOWS.md` mục 8.2) dùng `varchar` + PHP backed enum
(ADR-055) — không còn cần công ty duyệt trước khi viết migration; đổi giá trị sau này chỉ cần sửa
code, không cần `ALTER TABLE`.

## Điều kiện còn mở (không chặn migration — go-live/Phase 2, xem `ROADMAP.md` "Phân loại blocker")

Thời hạn lưu dữ liệu và mức mask `submission_snapshot` (go-live blocker, `docs/CORE-FLOWS.md` mục
7.2, 7.4); `job_verification_valid_days` (go-live blocker, mục 1.3, ADR-058); nguồn dữ liệu
`administrative_units` chính thức (go-live blocker, ADR-070); cơ chế redact free-text nâng cao
(go-live blocker tùy chọn, mục 7.3.1, ADR-071); `job_auto_pause_enabled` (Phase 2 decision, mục
1.3).
