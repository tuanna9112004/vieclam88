# Decision Index

Nguồn sự thật cho ADR của dự án. Nội dung ADR được chia theo chủ đề để Claude chỉ đọc đúng nhóm cần thiết.

## Nhóm quyết định

- [Kiến trúc và nền tảng](architecture-and-platform.md) — ADR-001..ADR-080 theo chủ đề, không liên tục.
- [Phạm vi và sản phẩm](scope-and-product.md) — ADR-012..ADR-072 theo chủ đề, không liên tục.
- [Company và Job domain](company-and-job-domain.md) — ADR-008..ADR-074 theo chủ đề, không liên tục.
- [Candidate và Application domain](candidate-and-application-domain.md) — ADR-005..ADR-076 theo chủ đề, không liên tục.
- [Bảo mật, quyền riêng tư và vận hành](security-privacy-and-operations.md) — ADR-013..ADR-077 theo chủ đề, không liên tục.

## Danh mục ADR

| ADR | Tiêu đề | File |
|---|---|---|
| [ADR-001](architecture-and-platform.md#adr-001) | Laravel monolith, một codebase một database | `architecture-and-platform.md` |
| [ADR-002](architecture-and-platform.md#adr-002) | HR dùng path `/hr`, không dùng subdomain | `architecture-and-platform.md` |
| [ADR-003](architecture-and-platform.md#adr-003) | MariaDB, không dùng SQLite làm database chính | `architecture-and-platform.md` |
| [ADR-004](architecture-and-platform.md#adr-004) | PHP 8.4.x, Laravel 13.x, khóa major version | `architecture-and-platform.md` |
| [ADR-005](candidate-and-application-domain.md#adr-005) | Không dùng phone number làm định danh duy nhất của candidate | `candidate-and-application-domain.md` |
| [ADR-006](candidate-and-application-domain.md#adr-006) | `users` là tài khoản đăng nhập, `candidates` là hồ sơ nghiệp vụ | `candidate-and-application-domain.md` |
| [ADR-007](candidate-and-application-domain.md#adr-007) | Application lưu snapshot tại thời điểm ứng tuyển | `candidate-and-application-domain.md` |
| [ADR-008](company-and-job-domain.md#adr-008) | Một `job` là một đợt tuyển dụng, không tái sử dụng job cũ | `company-and-job-domain.md` |
| [ADR-009](candidate-and-application-domain.md#adr-009) | Tách pipeline xử lý khỏi lịch sử liên hệ (contact attempts) | `candidate-and-application-domain.md` |
| [ADR-010](architecture-and-platform.md#adr-010) | Dùng `administrative_units` phân cấp, không lưu chuỗi tự do *(target thay bằng ADR-080, chưa migrate)* | `architecture-and-platform.md` |
| [ADR-011](company-and-job-domain.md#adr-011) | Không lặp địa điểm giữa `jobs` và `company_locations` *(target thay bằng ADR-080, chưa migrate)* | `company-and-job-domain.md` |
| [ADR-012](scope-and-product.md#adr-012) | Không tạo `referrer_id` mơ hồ, chưa xây module cộng tác viên | `scope-and-product.md` |
| [ADR-013](security-privacy-and-operations.md#adr-013) | CSV cho xuất dữ liệu, ghi log mỗi lần xuất | `security-privacy-and-operations.md` |
| [ADR-014](architecture-and-platform.md#adr-014) | `users.role` dạng enum đơn giản, chưa xây RBAC *(target thay bằng ADR-080, chưa migrate)* | `architecture-and-platform.md` |
| [ADR-015](company-and-job-domain.md#adr-015) | Thêm `branches` (cơ sở nội bộ), tách khỏi `company_locations` *(target thay bằng ADR-080, chưa migrate)* | `company-and-job-domain.md` |
| [ADR-016](candidate-and-application-domain.md#adr-016) | Application copy `owner_branch_id` từ Job lúc tạo, không suy ra động | `candidate-and-application-domain.md` |
| [ADR-017](candidate-and-application-domain.md#adr-017) | Duplicate handling contract: 3 trường hợp tách biệt, không gộp chung logic | `candidate-and-application-domain.md` |
| [ADR-018](scope-and-product.md#adr-018) | Chuyển đổi Lead (`lead_requests`) thành Application dời sang Phase 2 | `scope-and-product.md` |
| [ADR-019](security-privacy-and-operations.md#adr-019) | "Audit trail theo từng action", không xây "audit log" tổng quát *(mở rộng bởi ADR-080 — thêm `activity_logs` bổ sung cho hành động chưa có audit trail riêng, không thay thế)* | `security-privacy-and-operations.md` |
| [ADR-020](security-privacy-and-operations.md#adr-020) | Staff chỉ xem Application thuộc cơ sở phụ trách (thay vì toàn bộ) | `security-privacy-and-operations.md` |
| [ADR-021](scope-and-product.md#adr-021) | Bỏ Lead, Assignment và Favorites khỏi phạm vi database Phase 1 (siết phạm vi lần 2) | `scope-and-product.md` |
| [ADR-022](candidate-and-application-domain.md#adr-022) | Idempotency contract: `applications.submission_token` | `candidate-and-application-domain.md` |
| [ADR-023](company-and-job-domain.md#adr-023) | CTA Gọi/Zalo luôn ưu tiên contact cơ sở, không dùng `company_contacts` làm CTA thay thế | `company-and-job-domain.md` |
| [ADR-024](candidate-and-application-domain.md#adr-024) | Chốt Application transition matrix mở rộng và Contact Result enum chính thức | `candidate-and-application-domain.md` |
| [ADR-025](company-and-job-domain.md#adr-025) | Chốt Job transition matrix tường minh và quy tắc đổi lịch hẹn (appointment) | `company-and-job-domain.md` |
| [ADR-026](candidate-and-application-domain.md#adr-026) | Tinh chỉnh duplicate handling contract: khớp tên chính xác, merge do admin chọn thủ công | `candidate-and-application-domain.md` |
| [ADR-027](security-privacy-and-operations.md#adr-027) | Khung chính sách dữ liệu cá nhân tối thiểu (thời hạn lưu vẫn [CẦN CHỐT]) | `security-privacy-and-operations.md` |
| [ADR-028](scope-and-product.md#adr-028) | Bỏ Candidate Account khỏi schema Phase 1 (`users.role=candidate`, `candidates.user_id`) | `scope-and-product.md` |
| [ADR-029](scope-and-product.md#adr-029) | Bỏ `applications.referral_code` và `actor_type=import` khỏi schema Phase 1 | `scope-and-product.md` |
| [ADR-030](candidate-and-application-domain.md#adr-030) | Workflow cycle contract: chống dữ liệu chu kỳ xử lý cũ mở khóa trạng thái mới | `candidate-and-application-domain.md` |
| [ADR-031](candidate-and-application-domain.md#adr-031) | Hoàn thiện Reopen Application contract (`closed → new`) | `candidate-and-application-domain.md` |
| [ADR-032](candidate-and-application-domain.md#adr-032) | Hoàn thiện validation khi chuyển cơ sở (Transfer Branch) | `candidate-and-application-domain.md` |
| [ADR-033](company-and-job-domain.md#adr-033) | Thêm `job_status_histories`, tách khỏi `job_verifications` | `company-and-job-domain.md` |
| [ADR-034](candidate-and-application-domain.md#adr-034) | "Merged family": Candidate nguồn không di chuyển dữ liệu, hiển thị hợp nhất qua truy vấn | `candidate-and-application-domain.md` |
| [ADR-035](candidate-and-application-domain.md#adr-035) | Chốt `submission_token` NOT NULL, lưu trực tiếp trên `applications` | `candidate-and-application-domain.md` |
| [ADR-036](security-privacy-and-operations.md#adr-036) | Chốt chính sách dữ liệu cá nhân và duyệt 5 enum đề xuất | `security-privacy-and-operations.md` |
| [ADR-037](security-privacy-and-operations.md#adr-037) | Sửa lại: chuyển chính sách dữ liệu cá nhân và enum về [CẦN CHỐT]/[đề xuất] (thay thế ADR-036) | `security-privacy-and-operations.md` |
| [ADR-038](company-and-job-domain.md#adr-038) | Job Branch Contract: quản lý `jobs.owner_branch_id`, thêm `job_branch_histories` | `company-and-job-domain.md` |
| [ADR-039](architecture-and-platform.md#adr-039) | Khóa phiên bản MariaDB 11.4 LTS | `architecture-and-platform.md` |
| [ADR-040](candidate-and-application-domain.md#adr-040) | Duplicate Candidate Contract: 4 trường hợp tường minh (thay thế phần liên quan của ADR-017/026) | `candidate-and-application-domain.md` |
| [ADR-041](candidate-and-application-domain.md#adr-041) | Submission Token Lifecycle chính thức: session đa-token, diễn đạt lại quy tắc dùng token | `candidate-and-application-domain.md` |
| [ADR-042](scope-and-product.md#adr-042) | Job Verification Scheduler Contract: chỉ cảnh báo ở Phase 1, không tự động pause | `scope-and-product.md` |
| [ADR-043](scope-and-product.md#adr-043) | Quy tắc hiển thị Job `closed`/`paused`: giữ URL, giữ CTA, không xây "liên hệ tư vấn chung" | `scope-and-product.md` |
| [ADR-044](company-and-job-domain.md#adr-044) | Sửa lỗi hướng quan hệ ERD: `administrative_units` ↔ `branches`, `candidates.current_administrative_unit_id` | `company-and-job-domain.md` |
| [ADR-045](company-and-job-domain.md#adr-045) | Company & Company Location Quick Create Contract; `company_locations.administrative_unit_id`/`address_detail` đổi thành nullable *(target thay bằng ADR-080, chưa migrate)* | `company-and-job-domain.md` |
| [ADR-046](company-and-job-domain.md#adr-046) | Job Draft Contract chính thức; chốt `jobs.owner_branch_id` NOT NULL từ lúc tạo (không còn nullable ở draft) | `company-and-job-domain.md` |
| [ADR-047](company-and-job-domain.md#adr-047) | Job Publish Contract: thêm điều kiện xác minh còn tuyển và điều kiện địa điểm đủ rõ, kèm admin override có kiểm soát | `company-and-job-domain.md` |
| [ADR-048](company-and-job-domain.md#adr-048) | Job Verification: tách `last_checked_at` (mọi lần xác nhận) khỏi `last_verified_at` (chỉ khi `still_open`) | `company-and-job-domain.md` |
| [ADR-049](scope-and-product.md#adr-049) | Phân loại 3 nhóm blocker (Migration / Go-live / Phase 2 decision); tách khỏi điều kiện chuyển Giai đoạn 1 | `scope-and-product.md` |
| [ADR-050](architecture-and-platform.md#adr-050) | Initial Admin Bootstrap Contract (`php artisan app:create-admin`) | `architecture-and-platform.md` |
| [ADR-051](architecture-and-platform.md#adr-051) | Seeder Classification: production-safe / demo-test / dữ liệu vận hành thật | `architecture-and-platform.md` |
| [ADR-052](company-and-job-domain.md#adr-052) | Validation tỉnh/thành khớp với khu công nghiệp (`company_locations` ↔ `industrial_parks`) *(target thay bằng ADR-080, chưa migrate)* | `company-and-job-domain.md` |
| [ADR-053](company-and-job-domain.md#adr-053) | Company Location/Contact: tách quyền xóa/khôi phục về riêng Admin (sửa lỗ hổng Route Map) | `company-and-job-domain.md` |
| [ADR-054](company-and-job-domain.md#adr-054) | Job Branch Transfer: chỉ cho phép khi `draft`/`paused`, cấm tuyệt đối khi `closed` hoặc đã xóa | `company-and-job-domain.md` |
| [ADR-055](architecture-and-platform.md#adr-055) | Enum Strategy: VARCHAR + PHP backed enum cho enum phụ chưa chốt, gỡ bỏ migration blocker *(phần `employment_type` target thay bằng ADR-080, chưa migrate)* | `architecture-and-platform.md` |
| [ADR-056](security-privacy-and-operations.md#adr-056) | PII schema tối thiểu cho `applications`: nullability và cơ chế anonymize (tách khỏi retention) | `security-privacy-and-operations.md` |
| [ADR-057](scope-and-product.md#adr-057) | Phase 1 Plan Baseline v1.0 (freeze chính thức) | `scope-and-product.md` |
| [ADR-058](company-and-job-domain.md#adr-058) | Job Verification: publish chỉ dựa vào bản ghi mới nhất, thêm `job_verification_valid_days` | `company-and-job-domain.md` |
| [ADR-059](company-and-job-domain.md#adr-059) | Ma trận chính thức Job Status × Job Verification Result | `company-and-job-domain.md` |
| [ADR-060](company-and-job-domain.md#adr-060) | Job Publish Predicate chính thức (22 điều kiện) + `jobs.job_description` đổi NULLABLE | `company-and-job-domain.md` |
| [ADR-061](candidate-and-application-domain.md#adr-061) | Submission concurrency khác `submission_token`: khóa named lock theo `phone_normalized` | `candidate-and-application-domain.md` |
| [ADR-062](candidate-and-application-domain.md#adr-062) | Duplicate Review data model: thêm bảng `candidate_duplicate_reviews` | `candidate-and-application-domain.md` |
| [ADR-063](candidate-and-application-domain.md#adr-063) | Duplicate matching hardening: resolve candidate merged về root, thêm `candidates.full_name_normalized` | `candidate-and-application-domain.md` |
| [ADR-064](company-and-job-domain.md#adr-064) | Primary field semantics: bỏ `company_locations.is_primary`, khóa DB `candidate_contacts.is_primary`, chốt `company_contacts.is_primary` | `company-and-job-domain.md` |
| [ADR-065](architecture-and-platform.md#adr-065) | Administrative unit root uniqueness: thêm `root_slug_key` generated + unique | `architecture-and-platform.md` |
| [ADR-066](architecture-and-platform.md#adr-066) | Quy ước timestamp và hạ tầng Laravel Phase 1 tối giản | `architecture-and-platform.md` |
| [ADR-067](security-privacy-and-operations.md#adr-067) | Password-first-change flow đầy đủ và bổ sung route HR admin còn thiếu | `security-privacy-and-operations.md` |
| [ADR-068](company-and-job-domain.md#adr-068) | Soft delete/restore: rà soát toàn bộ bảng có `deleted_at`, thêm `hr.branches.restore` | `company-and-job-domain.md` |
| [ADR-069](architecture-and-platform.md#adr-069) | Migration order chính thức và Roadmap Giai đoạn 1 chia 7 nhóm triển khai | `architecture-and-platform.md` |
| [ADR-070](architecture-and-platform.md#adr-070) | Administrative dataset provenance contract | `architecture-and-platform.md` |
| [ADR-071](security-privacy-and-operations.md#adr-071) | Chính sách PII trong nội dung free-text (rà soát, go-live blocker) | `security-privacy-and-operations.md` |
| [ADR-072](scope-and-product.md#adr-072) | Job expired behavior chính thức (`effective_status = expired`) | `scope-and-product.md` |
| [ADR-073](architecture-and-platform.md#adr-073) | ERD cardinality: tách các edge gộp nhiều FK khác nullability | `architecture-and-platform.md` |
| [ADR-074](company-and-job-domain.md#adr-074) | Final publish consistency: Salary modes, Admin verification override và Company Contact ownership | `company-and-job-domain.md` |
| [ADR-075](candidate-and-application-domain.md#adr-075) | Candidate matching phải xét toàn bộ phone roots; Duplicate Review là nguồn sự thật | `candidate-and-application-domain.md` |
| [ADR-076](candidate-and-application-domain.md#adr-076) | Cùng Job phải được kiểm tra trên toàn merged family | `candidate-and-application-domain.md` |
| [ADR-077](security-privacy-and-operations.md#adr-077) | Tài khoản bị khóa mất quyền ở request kế tiếp | `security-privacy-and-operations.md` |
| [ADR-078](architecture-and-platform.md#adr-078) | Final Consistency Patch và semantic checker mở rộng | `architecture-and-platform.md` |
| [ADR-079](architecture-and-platform.md#adr-079) | Administrative dataset source: import từ `provinces.open-api.vn` (resolve ADR-070) | `architecture-and-platform.md` |
| [ADR-080](architecture-and-platform.md#adr-080) | Chốt baseline kiến trúc Phase 2: áp dụng đề xuất "cấu trúc lại" (PDF v1.1) — chưa migrate code | `architecture-and-platform.md` |

## Quy tắc cập nhật

- ADR mới chỉ dùng khi có quyết định kiến trúc/nghiệp vụ ảnh hưởng lâu dài; không dùng ADR như nhật ký phiên.
- Không sửa ý nghĩa ADR cũ âm thầm. Quyết định thay thế phải nêu ADR bị thay thế.
- Sau khi thêm hoặc di chuyển ADR, chạy `python scripts/check-claude-config.py`.
