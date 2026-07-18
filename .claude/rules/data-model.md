---
paths:
  - "database/**/*.php"
  - "app/Models/**/*.php"
  - "app/Enums/**/*.php"
  - "app/{Actions,Services}/**/*.php"
  - "tests/**/*Database*.php"
  - "tests/Feature/**/*Application*.php"
  - "tests/Feature/**/*Candidate*.php"
---

# Database invariants

Nguồn cột/FK/index: `docs/DATABASE-DICTIONARY.md`; quan hệ: `docs/ERD.md`; 6 luồng nghiệp vụ
mà các invariant này phục vụ: `docs/CORE-FLOWS.md`. Database: **MariaDB 11.4 LTS** (ADR-039).

- `users` Phase 1 chỉ `staff`/`admin` (không có `candidate`, không có `candidates.user_id`, không có `users.phone_normalized`, `email` NOT NULL — ADR-028). `candidates` = hồ sơ; `candidate_contacts` = contact. Guest không cần user, luôn ứng tuyển dạng guest.
- Duplicate Candidate Contract 4 trường hợp tường minh, không fuzzy/Levenshtein/AI (`docs/CORE-FLOWS.md` mục 6.2, ADR-040): chỉ trường hợp 1 (phone+tên khớp chính xác+ngày sinh khớp hoặc cả 2 đều thiếu) tái sử dụng; 3 trường hợp còn lại (thiếu ngày sinh 1 bên, tên khác, ngày sinh mâu thuẫn) đều tạo Candidate mới + `needs_duplicate_review=true`. Merge phải transaction, giữ dấu vết, admin tự chọn Application giữ lại khi cả hai có application cùng job — **`candidate_id` của cặp Application trùng job không bị đổi** (giữ nguyên gốc, kể cả bản bị đóng); lịch sử hợp nhất hiển thị qua truy vấn "merged family" đệ quy (`WITH RECURSIVE`) theo `merged_into_candidate_id`, không di chuyển dữ liệu hàng loạt (`docs/CORE-FLOWS.md` mục 6.3, ADR-034).
- Một job là một đợt tuyển; unique `(candidate_id, job_id)`. `applications.submission_token` **NOT NULL, UNIQUE** — chống double-submit cùng 1 lần gửi form, khác với unique candidate+job (chống ứng tuyển lại); session lưu được nhiều token cùng lúc (nhiều tab/nhiều Job), token đối chiếu đúng `job_id` khi submit — xem `docs/CORE-FLOWS.md` mục 3, ADR-041.
- `applications` lưu `submission_snapshot`, `job_snapshot` và consent tại thời điểm submit; snapshot không dùng để filter/report.
- `applications.owner_branch_id` copy từ `jobs.owner_branch_id` lúc tạo, không suy ra động qua Job; đổi cơ sở đi qua `application_branch_histories`, không tạo Application mới. **Không có cột `assigned_to`/`referral_code`** trong Phase 1 (ADR-021, ADR-029).
- Pipeline: `new`, `contacting`, `consulted`, `interview_scheduled`, `interviewed`, `waiting_start`, `started`, `closed`, và `closed → new` (mở lại có kiểm soát — 7 điều kiện đầy đủ ở `docs/CORE-FLOWS.md` mục 5.5, ADR-031). Transition matrix chính thức: mục 5.1. Contact Result dùng đúng 11 giá trị enum chính thức ở mục 5.2, đồng nhất với `docs/DATABASE-DICTIONARY.md`.
- **Workflow cycle** (`applications.workflow_cycle`, tăng mỗi lần mở lại): mọi điều kiện transition cần bằng chứng (Contact Log/Appointment) phải lọc thêm `workflow_cycle` bằng chu kỳ hiện tại — dữ liệu chu kỳ trước không được mở khóa trạng thái chu kỳ mới (`docs/CORE-FLOWS.md` mục 5.4, ADR-030).
- Job status: `draft`, `published`, `paused`, `closed`; chỉ 5 transition hợp lệ (`docs/CORE-FLOWS.md` mục 1), không tự thêm transition khác. Mọi transition qua `ChangeJobStatusAction`, ghi `job_status_histories` (khác `job_verifications` — ADR-033); `paused → published` phải re-check toàn bộ điều kiện publish, gồm cả điều kiện địa điểm đủ rõ và đã xác minh (mục 1.2, ADR-047).
- **Job Draft Contract** (`docs/CORE-FLOWS.md` mục 1.0, ADR-046): Job draft được phép thiếu company/location đầy đủ, lương, quyền lợi, xác minh — nhưng bắt buộc có `title`, `company_id`, `owner_branch_id`, `created_by` ngay từ lúc tạo.
- **Job Branch Contract** (`docs/CORE-FLOWS.md` mục 1.1, ADR-038, ADR-046, ADR-054): `jobs.owner_branch_id` **NOT NULL ngay từ lúc tạo** (không nullable ở draft) — chỉ set lúc tạo (staff: tự động = `users.branch_id`; admin: chọn tường minh, bắt buộc) và đổi qua `ChangeJobBranchAction` (chỉ admin, chỉ khi `jobs.status` ∈ {`draft`,`paused`} và chưa `deleted_at` — **không** khi `published` hoặc `closed`, cơ sở đích active/chưa xóa, có lý do) — ghi `job_branch_histories` (`changed_by` NOT NULL, khác với `application_branch_histories` cho phép null=hệ thống). `hr.jobs.update` không được sửa cột này. Application đã tạo giữ nguyên `owner_branch_id` cũ khi Job đổi cơ sở sau đó.
- **Job Publish Contract** (`docs/CORE-FLOWS.md` mục 1.2, ADR-047): ngoài 9 điều kiện gốc, publish còn yêu cầu location `is_primary` đủ rõ (`administrative_unit_id` hoặc `address_detail` khác null) và có `job_verifications.result=still_open` trong lịch sử (Admin override được, phải có `job_status_histories.reason`).
- **Job Verification** (`docs/CORE-FLOWS.md` mục 1.3, ADR-048): `jobs.last_checked_at` cập nhật ở mọi lần xác nhận; `jobs.last_verified_at` chỉ khi `result=still_open`. Scheduler cảnh báo và điều kiện publish đều dùng `last_verified_at`, không phải `last_checked_at`.
- **Company/Company Location Quick Create** (`docs/CORE-FLOWS.md` mục 0.2, 0.3, ADR-045): `companies` chỉ `name`/`status`/`created_by` bắt buộc; `company_locations.administrative_unit_id`/`address_detail` nullable. Dữ liệu chưa biết luôn `NULL`, không lưu chuỗi `"Chưa xác định"`. Khi `industrial_park_id` khác null, `administrative_unit_id` bắt buộc bằng đúng tỉnh của KCN đó, kiểm tra ở Service (ADR-052).
- **Enum Strategy** (ADR-055): `jobs.status`/`applications.stage` dùng DB `enum()` (state machine chặt). 5 cột khác (`company_contacts.status`, `jobs.employment_type`, `jobs.close_reason`, `pages.status`, `settings.type`) dùng `varchar` + PHP backed enum + Form Request/`Rule::in` validation — không dùng DB `enum()`, đổi giá trị sau này không cần migration.
- **PII schema tối thiểu `applications`** (`docs/CORE-FLOWS.md` mục 7.2.1, ADR-056): `submitted_full_name`/`submitted_phone`/`submitted_phone_normalized`/`submission_snapshot` giữ NOT NULL khi anonymize — **mask/thay thế**, không set NULL; `consent_ip`/`consent_user_agent` (đã nullable) → set NULL. Nội dung redact cụ thể bên trong `submission_snapshot` vẫn CẦN CHỐT (mục 7.2, go-live blocker, không ảnh hưởng cấu trúc cột).
- Lịch hẹn (`application_appointments`, type `callback`/`interview`) tách khỏi `stage`; `interview_scheduled → interviewed` yêu cầu appointment interview `status=completed` **thuộc chu kỳ hiện tại**. Đổi lịch = hủy bản ghi cũ (`cancelled`/`no_show`) + tạo bản ghi mới, không sửa đè `scheduled_at`.
- Đúng 1 `job_locations.is_primary = true` mỗi job, bảo vệ bằng unique trên cột generated (`docs/DATABASE-DICTIONARY.md` mục `job_locations`), không chỉ validate ở Service.
- Contact attempt, status history, branch history, job status history, note, verification và export log là dữ liệu riêng; các bảng `*_histories`/`*_attempts` chỉ INSERT. Phase 1 không có bảng phân công (`application_assignment_histories`) — trách nhiệm theo dõi qua người thực hiện trên từng bảng lịch sử (ADR-019, ADR-021).
- Địa điểm dùng `administrative_units`, `company_locations` (địa điểm/nhà máy công ty khách hàng, không gọi "chi nhánh"), `job_locations`; không lặp province/KCN trên jobs. `branches` (cơ sở nội bộ) là bảng khác (ADR-015).
- Tiền VND dùng unsigned bigint.
- Không tạo FK mơ hồ hoặc bảng Phase 2. Phase 1 không có `lead_requests`/`favorites`/Candidate Account dưới bất kỳ hình thức nào (ADR-021, ADR-028).
- Không hard-delete dữ liệu cốt lõi; RESTRICT cho company→job, job/candidate→application, application→history, branch→job/application, job→job_status_histories. Cascade chỉ cho pivot/phụ trợ; actor lịch sử có thể SET NULL.
- Mọi ràng buộc quan trọng phải có database constraint và test, không chỉ validation — bảng phân lớp "DB bảo vệ vs Service bảo vệ": `docs/DATABASE-DICTIONARY.md` mục "Ràng buộc".

## Transaction bắt buộc

1. Apply: kiểm tra `submission_token` trùng (idempotent, trả lại bản ghi cũ nếu trùng) → normalize/match candidate (duplicate contract) → application (`workflow_cycle=1`, copy `owner_branch_id` từ job) + snapshots → initial status history (`null → new`) → initial branch history (`null → owner_branch_id`).
2. Change stage: lock → validate transition theo matrix + **điều kiện thuộc chu kỳ hiện tại** (`docs/CORE-FLOWS.md` 5.1, 5.4) → nếu `closed → new`: kiểm tra đủ 7 điều kiện reopen (mục 5.5: actor, lý do, candidate còn hoạt động, không phải đóng do duplicate, job chưa xóa, job còn nhận hồ sơ hoặc admin ngoại lệ, không vi phạm unique), reset `close_reason`/`closed_at`/`expected_start_at`, tăng `workflow_cycle` → history (note bắt buộc khi mở lại) → update.
3. Transfer branch: lock → kiểm tra đủ điều kiện (cơ sở đích tồn tại, active, chưa xóa, khác cơ sở hiện tại, actor là admin, có lý do — mục 6.1) → branch history (reason bắt buộc) → update `owner_branch_id`, giữ nguyên các bảng con.
4. Merge candidate: lock cả 2 candidate + application liên quan → kiểm tra không tạo vòng lặp, không tự merge, không merge candidate anonymized → admin chọn application giữ lại, application còn lại đóng `duplicate` **không đổi `candidate_id`** (mục 6.3) → move application khác job sang candidate đích → mark merged (`merge_reason`).
5. Change job status: lock → validate transition (mục 1) → nếu `→ published`: re-check toàn bộ điều kiện publish (mục 1.2, gồm địa điểm đủ rõ + đã xác minh `still_open` hoặc admin override có lý do) → `job_status_histories` → update.
6. Verify job: tạo `job_verifications` → luôn cập nhật `jobs.last_checked_at`; nếu `result=still_open` cập nhật thêm `jobs.last_verified_at` → nếu `result` ∈ {`paused`,`closed`}: đổi `jobs.status` kèm `job_status_histories` trong cùng transaction (mục 1.3).
7. Change job branch: lock Job → kiểm tra `jobs.status` ∈ {`draft`,`paused`}, chưa `deleted_at`, cơ sở đích active/chưa xóa, có lý do (mục 1.1, ADR-054) → `job_branch_histories` → update `owner_branch_id`. Application đã tạo trước đó không đổi theo.

Không có transaction "Assign"/"Claim" trong Phase 1 (ADR-021). Controller không chứa các
transaction trên; dùng Action/Service.
