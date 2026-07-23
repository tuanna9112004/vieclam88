# Database Dictionary — vieclam88 (Phase 1)

Mô tả đầy đủ 28 bảng business Phase 1 (27 + `candidate_duplicate_reviews`, ADR-062 — xem mục
9.28). Hạ tầng Laravel (session/cache/queue) tách riêng, xem mục "Hạ tầng Laravel Phase 1" cuối
file (ADR-066) — không tính vào 28 bảng business. Xem quan hệ tổng quan ở `docs/ERD.md`, nguyên tắc thiết kế ở
`.claude/rules/database-schema.md`, quyết định kiến trúc ở `docs/decisions/INDEX.md`, và 6 luồng nghiệp vụ
mà các bảng này phải hỗ trợ ở `docs/CORE-FLOWS.md`. Database: **MariaDB 11.4 LTS** (ADR-039) —
mọi tính năng dùng trong file này (generated column, unique index trên generated column, CHECK
constraint, recursive CTE, JSON, row locking) đều được hỗ trợ đầy đủ từ bản này.

> **Mục 9.29–9.36 và các đoạn "Thay đổi mục tiêu Phase 2" bên dưới mô tả kiến trúc TARGET đã
> duyệt ở ADR-080 (`docs/decisions/architecture-and-platform.md`), CHƯA có trong database thật
> đang chạy.** Toàn bộ mục 9.1–9.28 còn lại là schema **thật, đúng migration hiện có** — vẫn là
> căn cứ duy nhất khi viết code hôm nay. Chỉ code theo phần "target"/mục 9.29+ khi task thuộc đúng
> batch trong `docs/PHASE-2-ARCHITECTURE-PROPOSAL.md`.

`lead_requests`, `favorites`, `application_assignment_histories` **không** nằm trong database
Phase 1 (ADR-021). `applications.assigned_to`, `applications.referral_code`,
`candidates.user_id` **không** tồn tại trong schema Phase 1 (ADR-028, ADR-029) — `users.role`
Phase 1 chỉ nhận `staff`/`admin` (không có `candidate`). Không tạo các cột/bảng này khi viết
migration Phase 1; thêm bằng migration riêng khi Phase 2 thực sự triển khai.

## Quy ước chung

- Mọi bảng dùng `id` dạng `bigint unsigned auto_increment` làm khóa chính, trừ khi ghi chú
  khác (vd `job_work_shifts` dùng composite key).
- `created_at`/`updated_at`: `timestamp nullable` (chuẩn Laravel), trừ bảng lịch sử
  (`*_histories`, `*_attempts`, `job_verifications`, `export_logs`) chỉ có `created_at`,
  không có `updated_at` — vì không được sửa sau khi tạo.
- **Quy ước timestamp (chính thức, ADR-066):** giá trị **"now"** ở cột "Default" trong các bảng
  cột dưới đây là **quy ước diễn đạt** ("cột này luôn có giá trị ngay sau khi tạo bản ghi") —
  **không** phải chỉ thị dùng `DEFAULT CURRENT_TIMESTAMP` ở tầng DB. `created_at`/`updated_at`
  luôn do **Eloquent ghi** qua `$table->timestamps()` (migration helper chuẩn Laravel); không
  khai `->useCurrent()`/`DEFAULT CURRENT_TIMESTAMP` thủ công ở bất kỳ migration nào trừ khi có
  ADR riêng. Timestamp nghiệp vụ (`published_at`, `verified_at`, `contacted_at`, `started_at`,
  `applications.created_at` đóng vai trò mốc "đã nộp"...) có ngữ nghĩa riêng biệt theo từng bảng,
  không dùng thay cho `created_at` chung.
- `deleted_at`: `timestamp nullable` (Laravel `SoftDeletes`), chỉ khai báo ở bảng có soft
  delete (xem "Chính sách xóa" cuối file).
- Tiền VND luôn `bigint unsigned`, không dùng `FLOAT`/`DOUBLE` (`.claude/rules/database-schema.md`).
- **Enum Strategy (ADR-055):** `jobs.status` và `applications.stage` (state machine trung tâm,
  transition matrix đã chốt chặt — `docs/CORE-FLOWS.md` mục 1.2, 5.1) dùng DB `enum()`. 5 cột
  còn "đề xuất" trước đây (`company_contacts.status`, `jobs.employment_type`,
  `jobs.close_reason`, `pages.status`, `settings.type`) dùng **`varchar` + PHP backed enum +
  validation tầng ứng dụng**, đánh dấu **[varchar+enum]** trong bảng cột bên dưới — **không còn
  là migration blocker**: đổi giá trị đề xuất sau này chỉ cần sửa code, không cần `ALTER TABLE`
  (`docs/CORE-FLOWS.md` mục 8.2).

## Ràng buộc: DB bảo vệ vs Service + test bảo vệ

| Bất biến | Tầng DB (constraint) | Tầng Service/Form Request | Ghi chú |
|---|---|---|---|
| Không trùng `(candidate_id, job_id)` | `UNIQUE(candidate_id, job_id)` | Kiểm tra trước để trả thông báo thân thiện | DB là chốt chặn cuối cho race condition |
| Không tạo 2 Application từ cùng 1 lần submit | `UNIQUE(submission_token)`, `NOT NULL` | Sinh token khi render form, gắn với session | DB là chốt chặn cuối; token luôn có giá trị vì Phase 1 chỉ tạo Application qua form |
| Đúng 1 `job_locations.is_primary = true` mỗi job | `UNIQUE` trên cột generated (mục `job_locations`) | Service kiểm tra khi tạo/sửa location | DB chặn race condition |
| Bằng chứng transition thuộc đúng chu kỳ xử lý hiện tại | — (không thể diễn đạt "chu kỳ mới nhất" bằng CHECK) | `ChangeApplicationStageAction` lọc `workflow_cycle = applications.workflow_cycle` khi truy vấn Contact Log/Appointment | `docs/CORE-FLOWS.md` mục 5.4 |
| `staff` phải có `branch_id` | — (cột nullable ở DB vì `admin` không cần) | Form Request tạo/sửa tài khoản staff bắt buộc chọn cơ sở | |
| `applications.stage = closed` phải có `close_reason` | — | `ChangeApplicationStageAction`/Form Request | |
| `applications.stage = started` phải có `started_at` | — | `ChangeApplicationStageAction` | |
| `closed → new` không vi phạm điều kiện reopen (candidate/job/duplicate) | — | `ChangeApplicationStageAction` — `docs/CORE-FLOWS.md` mục 5.5 | |
| `jobs.salary_min <= salary_max` | `CHECK` (MariaDB 11.4 hỗ trợ đầy đủ) | Form Request validate lại | Cả 2 tầng cùng bảo vệ |
| `jobs.min_age <= max_age` | `CHECK` (như trên) | Form Request validate lại | |
| Branch của Job phải `active` khi publish/reopen | — (branch có thể đổi trạng thái sau) | `ChangeJobStatusAction` kiểm tra lại toàn bộ điều kiện publish mỗi lần `paused→published` | Không thể là DB constraint cố định |
| Cơ sở đích khi chuyển cơ sở Application phải `active`, chưa xóa, khác cơ sở hiện tại | — | `ChangeApplicationBranchAction` — `docs/CORE-FLOWS.md` mục 6.1 | |
| Cơ sở đích khi chuyển cơ sở Job phải `active`, chưa xóa; Job phải `draft`/`paused`, chưa `deleted_at` | — | `ChangeJobBranchAction` — `docs/CORE-FLOWS.md` mục 1.1, ADR-054 | |
| Job/Application transition hợp lệ theo matrix | — | `ChangeJobStatusAction`/`ChangeApplicationStageAction` | Không có DB constraint kiểm tra state machine |
| Token thuộc đúng `job_id` khi submit form | — | Form Request đối chiếu token trong session với `job_id` đang submit | `docs/CORE-FLOWS.md` mục 3 |
| Truy vấn "merged family" (candidate đích + toàn bộ candidate nguồn nhiều tầng) | `WITH RECURSIVE` (MariaDB 11.4) | Service dùng CTE đệ quy theo `merged_into_candidate_id` | `docs/CORE-FLOWS.md` mục 6.3 |
| Location đủ rõ trước khi publish (`administrative_unit_id` hoặc `address_detail` khác null) | — (2 cột đều nullable ở DB — Quick Create, ADR-045) | `ChangeJobStatusAction` kiểm tra khi `to_status=published` | `docs/CORE-FLOWS.md` mục 0.3, 1.2 |
| Bản ghi `job_verifications` **mới nhất** của Job phải `result=still_open` (không phải "từng có" trong lịch sử), trừ Admin override có lý do | — | `ChangeJobStatusAction` kiểm tra khi `to_status=published`; Admin override ghi `job_status_histories.reason` | `docs/CORE-FLOWS.md` mục 1.2, 1.3, ADR-058, ADR-060 |
| Job Status × Verification Result Matrix (draft không nhận `paused`/`closed`; `closed` không nhận verification mới) | — | `JobVerificationController@store`/`ChangeJobStatusAction` | `docs/CORE-FLOWS.md` mục 1.3.1, ADR-059 |
| Tối đa 1 `candidate_contacts.is_primary=true`/type/candidate | `UNIQUE` trên cột generated `primary_flag_key` (mục `candidate_contacts`) | Action đổi primary có `lockForUpdate` | ADR-064 |
| Không có 2 đơn vị hành chính cấp root (`parent_id=null`) cùng `slug` | `UNIQUE` trên cột generated `root_slug_key` (mục `administrative_units`) | — | ADR-065 |
| Không tạo 2 `candidate_duplicate_reviews` cùng cặp candidate + lý do đang `pending` | `UNIQUE` trên cột generated `pending_pair_key` (mục `candidate_duplicate_reviews`) | — | ADR-062 |
| 2 request khác `submission_token` nhưng cùng `phone_normalized` không tạo 2 Candidate | — (named lock `GET_LOCK` ngoài phạm vi DB constraint) | `SubmitApplicationAction` khóa theo `phone_normalized` trước khi query/tạo Candidate | `docs/CORE-FLOWS.md` mục 3.1, ADR-061 |
| `jobs.job_description`/`requirements`/`benefits` phải có nội dung thực trước publish | — (cả 3 cột nullable ở DB — Job Draft Contract, ADR-060) | `ChangeJobStatusAction` kiểm tra khi `to_status=published` | `docs/CORE-FLOWS.md` mục 1.0, 1.2, ADR-060 |
| `PUB-SALARY`: `negotiable` loại trừ lương số; mode còn lại cần lương số dương hoặc mô tả thực | CHECK cục bộ cho min/max khi có thể; quy tắc chéo nhiều cột ở Service | Form Request chuẩn hóa cột sai mode về NULL; `ChangeJobStatusAction` kiểm tra | `docs/CORE-FLOWS.md` mục 1.2, ADR-074 |
| `jobs.company_contact_id` phải thuộc đúng Company, active, chưa xóa | FK chỉ bảo đảm contact tồn tại | Job Request/Service kiểm tra ownership/status; public thêm `is_public=true` | ADR-074 |
| Cùng Job trên merged family không tạo Application mới | Không biểu diễn được bằng unique đơn vì family là quan hệ đệ quy | `CreateApplicationAction` query family dưới named lock, trả Application canonical | ADR-076 |
| Job hết hạn (`published`+`expires_at<now()`) không nhận Application | — | `SubmitApplicationAction` kiểm tra "Job còn active" | `docs/CORE-FLOWS.md` mục 2.2, 3, ADR-072 |
| Staff mở Candidate chỉ khi merged family có Application thuộc cơ sở mình | — | Policy `hr.candidates.show` — 403 nếu không có | `docs/CORE-FLOWS.md` mục 6.4 |
| `company_locations.administrative_unit_id` phải khớp `industrial_parks.administrative_unit_id` khi có `industrial_park_id` | — | Form Request/Service `hr.company-locations.store`/`update` | `docs/CORE-FLOWS.md` mục 0.3, ADR-052 |
| Job Branch Transfer chỉ khi `jobs.status` ∈ {`draft`,`paused`}, chưa `deleted_at` | — | `ChangeJobBranchAction` | `docs/CORE-FLOWS.md` mục 1.1, ADR-054 |
| Staff không xóa/khôi phục `company_locations`/`company_contacts` | — | Policy — 403 nếu Staff | ADR-053 |

---

## 9.1. `users`

Phase 1 chỉ phục vụ **staff/admin** — không có Candidate Account (ADR-028). Mọi user Phase 1
đều cần đăng nhập `/hr/dang-nhap`, nên `email` bắt buộc.

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | Khóa chính |
| role | enum(staff,admin) | — | không | — | ✓ | — | — | — | Phase 1 không có `candidate` (ADR-028); `guest` không phải giá trị hợp lệ |
| branch_id | bigint | ✓ | có | null | ✓ | — | branches.id | SET NULL | Cơ sở nội bộ phụ trách; **bắt buộc khi `role=staff`** (chốt ở Form Request/Service, không ép NOT NULL ở DB vì `admin` không cần) |
| name | string(150) | — | không | — | — | — | — | — | Tên hiển thị |
| email | string(191) | — | **không** | — | — | ✓ | — | — | Bắt buộc — định danh đăng nhập duy nhất cho staff/admin trong Phase 1 |
| password | string(255) | — | không | — | — | — | — | — | Hash bcrypt/argon2 |
| status | enum(active,locked) | — | không | active | ✓ | — | — | — | Khóa tài khoản thay vì xóa |
| last_login_at | timestamp | — | có | null | — | — | — | — | |
| password_changed_at | timestamp | — | có | null | — | — | — | — | |
| remember_token | string(100) | — | có | null | — | — | — | — | Laravel remember-me |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

**Không có `phone_normalized`** trong Phase 1 — mục đích duy nhất trước đây là đăng nhập cho
Candidate Account, nay không còn áp dụng (ADR-028); thêm lại nếu Phase 2 cần.

**Chính sách xóa:** không hard delete. Ngừng sử dụng → `status = locked`.

**Thay đổi mục tiêu Phase 2 (ADR-080, chưa migrate):** `role` mở rộng thành
`enum(staff,branch_admin,super_admin)` — `admin` hiện tại tương đương `super_admin` (không giới
hạn cơ sở); `branch_admin` là vai trò mới, `branch_id` bắt buộc (giống `staff` nhưng thêm quyền
quản lý user/job/report trong phạm vi cơ sở mình, chưa có Policy nào xử lý hôm nay). Batch 3 ở
`docs/PHASE-2-ARCHITECTURE-PROPOSAL.md`.

---

## 9.2. `candidates`

**Không có cột `user_id`** trong Phase 1 — Candidate Account (đăng nhập cho ứng viên) là
Phase 2; liên kết candidate ↔ user chỉ có ý nghĩa khi tính năng đó tồn tại (ADR-028). Ứng viên
Phase 1 luôn là guest.

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| public_id | string(26) | — | không | — | — | ✓ | — | — | ULID public-facing, không lộ ID tuần tự |
| full_name | string(150) | — | không | — | — | — | — | — | |
| full_name_normalized | string(150) | — | không | — | ✓ | — | — | — | **Mới (ADR-063).** Sinh tự động ở tầng Model từ `full_name` (không nhận từ client) theo thuật toán chuẩn hóa `docs/CORE-FLOWS.md` mục 6.2 — giữ dấu tiếng Việt, không fuzzy. Dùng so khớp Duplicate Candidate Contract |
| date_of_birth | date | — | có | null | — | — | — | — | |
| gender | enum(male,female,other) | — | có | null | — | — | — | — | |
| current_administrative_unit_id | bigint | ✓ | có | null | ✓ | — | administrative_units.id | RESTRICT | Tỉnh/xã hiện tại |
| address_detail | string(255) | — | có | null | — | — | — | — | |
| education_level | string(100) | — | có | null | — | — | — | — | Free text Phase 1 |
| experience_summary | text | — | có | null | — | — | — | — | |
| preferred_shift | string(50) | — | có | null | — | — | — | — | Đối chiếu lỏng với `work_shifts.code`, chưa ép FK ở Phase 1 |
| available_from | date | — | có | null | — | — | — | — | |
| status | enum(active,merged,anonymized) | — | không | active | ✓ | — | — | — | |
| merged_into_candidate_id | bigint | ✓ | có | null | ✓ | — | candidates.id (self) | SET NULL | Trỏ sang candidate đích ngay tại thời điểm merge — không cập nhật lại khi đích đó bị merge tiếp (merge nhiều tầng, xem `docs/CORE-FLOWS.md` mục 6.3) |
| merged_at | timestamp | — | có | null | — | — | — | — | |
| merged_by | bigint | ✓ | có | null | — | — | users.id | SET NULL | |
| merge_reason | string(255) | — | có | null | — | — | — | — | Lý do merge do admin nhập |
| anonymized_at | timestamp | — | có | null | — | — | — | — | Xem chính sách dữ liệu cá nhân, `docs/CORE-FLOWS.md` mục 7 |
| anonymized_by | bigint | ✓ | có | null | — | — | users.id | SET NULL | Luôn `admin` (mục 7.3); mức mask cụ thể cho `applications.submission_snapshot` vẫn **[CẦN CHỐT VỚI CÔNG TY]** (mục 7.2) |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |
| deleted_at | timestamp | — | có | null | — | — | — | — | Soft delete |

**Chính sách xóa (ADR-068):** soft delete, merge, hoặc anonymize. **Không có route HTTP nào ở
Phase 1 tạo `deleted_at` cho candidates** — cột này chỉ dùng cho can thiệp Admin/DB thủ công
ngoài UI (ví dụ dữ liệu rác/spam nghiêm trọng), giữ lại vì Merge/Reopen contract đã kiểm tra điều
kiện "candidate chưa `deleted_at`" như lớp phòng vệ trước can thiệp đó. Candidate `status =
merged` không được dùng để tạo application mới, không được sửa, không được làm nguồn/đích của
merge khác. Candidate `status = anonymized` không được làm nguồn/đích của merge. Không hard
delete candidate đang có application.

**Thay đổi mục tiêu Phase 2 (ADR-080, chưa migrate):** thêm các cột thông tin bổ sung theo PDF mục
9.3 (đều nullable, không bắt buộc ở form đầu): `marital_status` enum, `foreign_language`
string(150), `ethnicity` string(100), `citizen_id_number` string(20) (dữ liệu nhạy cảm — mã hóa/che
khi hiển thị, cùng mức bảo vệ như PII hiện có ở `applications`), `citizen_id_issued_date` date,
`citizen_id_issued_place` string(150), `personal_introduction` text. `education_level`/
`experience_summary` đã có sẵn, không đổi. Batch 6 ở `docs/PHASE-2-ARCHITECTURE-PROPOSAL.md`
(cùng batch với `candidate_documents`, mục 9.35).

Truy vấn "merged family" và quy tắc đầy đủ:
`docs/CORE-FLOWS.md` mục 6.3.

---

## 9.3. `candidate_contacts`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| candidate_id | bigint | ✓ | không | — | ✓ | — | candidates.id | CASCADE | |
| type | enum(phone,email,zalo,other) | — | không | — | ✓ | — | — | — | |
| value | string(191) | — | không | — | — | — | — | — | Giá trị gốc người dùng nhập |
| normalized_value | string(191) | — | không | — | ✓ | — | — | — | Dùng để tìm kiếm/phát hiện trùng |
| is_primary | boolean | — | không | false | ✓ | — | — | — | Chỉ 1 primary/type/candidate — khóa bằng `primary_flag_key` bên dưới (ADR-064) |
| primary_flag_key | varchar(70), generated | — | có (null khi không primary) | null (generated) | ✓ | ✓ | — | — | **Mới (ADR-064).** `IF(is_primary, CONCAT(candidate_id,'-',type), NULL)` STORED. `UNIQUE` chặn 2 primary cùng `(candidate_id, type)` — cùng pattern `job_locations.primary_flag_job_id` |
| is_verified | boolean | — | không | false | — | — | — | — | |
| verified_at | timestamp | — | có | null | — | — | — | — | |
| is_active | boolean | — | không | true | ✓ | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

Index bắt buộc theo yêu cầu gốc: `(type, normalized_value)`, `(candidate_id, is_primary)`,
`(candidate_id, is_active)`, `UNIQUE(candidate_id, type, normalized_value)`. Đổi primary contact
đi qua Action có `lockForUpdate` trên các bản ghi cùng `(candidate_id, type)` trong 1 transaction
(ADR-064) — chống 2 request đồng thời đổi primary cho cùng candidate+type.

**Quy tắc:** không tự động gộp candidate chỉ vì trùng contact — service phát hiện trùng phải
kiểm tra thêm họ tên (khớp chính xác sau chuẩn hóa) và ngày sinh (`docs/CORE-FLOWS.md` mục
6.2).

---

## 9.4. `administrative_units`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| parent_id | bigint | ✓ | có | null | ✓ | — | administrative_units.id (self) | RESTRICT | Null = cấp tỉnh/thành phố |
| official_code | string(20) | — | có | null | — | ✓ (khi có giá trị) | — | — | Mã đơn vị hành chính nhà nước |
| name | string(150) | — | không | — | — | — | — | — | |
| slug | string(170) | — | không | — | ✓ | ✓ (composite: parent_id+slug) | — | — | |
| root_slug_key | varchar(170), generated | — | có (null khi không phải root) | null (generated) | ✓ | ✓ | — | — | **Mới (ADR-065).** `IF(parent_id IS NULL, slug, NULL)` STORED. `UNIQUE` chặn 2 đơn vị **cấp root** trùng `slug` — `UNIQUE(parent_id, slug)` không tự chặn được vì MariaDB coi mỗi `NULL` (mọi `parent_id` cấp root) là giá trị riêng biệt |
| type | enum(province,city,commune,ward,special_zone,legacy_district) | — | không | — | ✓ | — | — | — | |
| is_active | boolean | — | không | true | ✓ | — | — | — | |
| valid_from | date | — | có | null | — | — | — | — | |
| valid_to | date | — | có | null | — | — | — | — | Đơn vị cũ sau sáp nhập địa giới |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

**Chính sách xóa:** không hard delete. Đơn vị cũ → `is_active = false`, `valid_to` = ngày hết
hiệu lực, giữ lại phục vụ dữ liệu lịch sử.

**Provenance contract (ADR-070, nguồn dữ liệu chốt ở ADR-079):** import/upsert dữ liệu thật dựa
trên `official_code` khi có giá trị, `root_slug_key`/`(parent_id, slug)` khi chưa có mã chính
thức. Đơn vị `is_active=false` **không** được chọn cho dữ liệu mới (`company_locations`/
`branches`/`candidates.current_administrative_unit_id`) — kiểm tra ở Form Request. Nguồn dữ liệu
chính thức: API `provinces.open-api.vn` (v2), nhập qua `php artisan administrative-units:import`
(`app/Console/Commands/ImportAdministrativeUnitsCommand.php`) — khớp `official_code` với field
`code` (mã GSO) của API, không gọi API lúc runtime.

**Thay đổi mục tiêu Phase 2 (ADR-080, chưa migrate):** thay bằng 2 bảng `provinces`+`wards` (mục
9.29/9.30) — dữ liệu đã tương đương (tỉnh/thành → xã/phường, mã GSO) nhờ ADR-079, chỉ đổi cấu
trúc bảng (bỏ tự tham chiếu N cấp, chỉ còn đúng 2 cấp cứng). Batch 1 ở
`docs/PHASE-2-ARCHITECTURE-PROPOSAL.md`; **không xóa `administrative_units`** cho tới Contract
(batch 9) vì `branches`/`company_locations`/`candidates`/`industrial_parks` vẫn còn FK tới bảng
này ở batch 1.

---

## 9.5. `industrial_parks`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| administrative_unit_id | bigint | ✓ | không | — | ✓ | — | administrative_units.id | RESTRICT | |
| name | string(150) | — | không | — | — | — | — | — | |
| slug | string(170) | — | không | — | — | ✓ (composite: administrative_unit_id+slug) | — | — | |
| official_name | string(200) | — | có | null | — | — | — | — | |
| address_detail | string(255) | — | có | null | — | — | — | — | |
| is_active | boolean | — | không | true | ✓ | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

**Chính sách xóa:** không hard delete khi đã có location tham chiếu.

**Thay đổi mục tiêu Phase 2 (ADR-080, chưa migrate):** thêm `branch_id` (FK `branches.id`, chi
nhánh quản lý chính) và chuyển quan hệ địa chỉ từ 1-N (`administrative_unit_id`) sang N-N với
`wards` qua bảng mới `industrial_park_wards` (mục 9.31) — một KCN có thể trải nhiều xã/phường. Giữ
`administrative_unit_id` tới khi Contract (batch 9) để không phá dữ liệu cũ giữa chừng. Batch 2 ở
`docs/PHASE-2-ARCHITECTURE-PROPOSAL.md`.

---

## 9.6. `companies`

**Quick Create (ADR-045, `docs/CORE-FLOWS.md` mục 0.2):** chỉ `name`/`status`/`created_by` bắt
buộc. `short_name`, `description`, `logo_path`, `cover_path`, `industry`, `website` đã nullable —
không bắt buộc trước publish Job của công ty này. `slug`/`public_id` server tự sinh, không phải
input người dùng. Không lưu chuỗi `"Chưa xác định"` — dữ liệu chưa biết luôn là `NULL`.

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| public_id | string(26) | — | không | — | — | ✓ | — | — | |
| name | string(200) | — | không | — | — | — | — | — | |
| slug | string(220) | — | không | — | ✓ | ✓ | — | — | |
| short_name | string(100) | — | có | null | — | — | — | — | |
| description | text | — | có | null | — | — | — | — | |
| logo_path | string(255) | — | có | null | — | — | — | — | |
| cover_path | string(255) | — | có | null | — | — | — | — | |
| industry | string(100) | — | có | null | — | — | — | — | |
| website | string(255) | — | có | null | — | — | — | — | |
| is_verified | boolean | — | không | false | ✓ | — | — | — | |
| status | enum(active,hidden) | — | không | active | ✓ | — | — | — | |
| created_by | bigint | ✓ | không | — | — | — | users.id | RESTRICT | |
| updated_by | bigint | ✓ | có | null | — | — | users.id | SET NULL | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |
| deleted_at | timestamp | — | có | null | — | — | — | — | Soft delete |

**Chính sách xóa:** soft delete. Không cascade delete job khi xóa company.

---

## 9.7. `company_locations`

Địa điểm làm việc/nhà máy của **công ty khách hàng** — khác `branches` (cơ sở nội bộ
vieclam88, mục 9.23). Không dùng từ "chi nhánh" để mô tả bảng này (dễ nhầm với `branches`, xem
ADR-015).

**Quick Create (ADR-045, `docs/CORE-FLOWS.md` mục 0.3):** chỉ `name` bắt buộc lúc tạo —
`administrative_unit_id` và `address_detail` **nullable**, bổ sung sau. Bắt buộc có ít nhất một
trong hai (khác null) trước khi location đó được dùng làm primary location của một Job publish
(`docs/CORE-FLOWS.md` mục 1.2, `PUB-LOCATION-CLEAR`) — không phải điều kiện tạo location.

**Validation tỉnh/KCN (ADR-052, `docs/CORE-FLOWS.md` mục 0.3):** khi `industrial_park_id` khác
`null`, `administrative_unit_id` bắt buộc bằng đúng `industrial_parks.administrative_unit_id`
của KCN đó — kiểm tra ở Service/Form Request, không phải DB constraint.

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| company_id | bigint | ✓ | không | — | ✓ | — | companies.id | RESTRICT | |
| administrative_unit_id | bigint | ✓ | **có** | null | ✓ | — | administrative_units.id | RESTRICT | Nullable (ADR-045) — bắt buộc có giá trị này hoặc `address_detail` trước khi publish Job dùng location này |
| industrial_park_id | bigint | ✓ | có | null | ✓ | — | industrial_parks.id | RESTRICT | |
| name | string(150) | — | không | — | — | — | — | — | Tên nhà máy hoặc địa điểm làm việc của công ty khách hàng |
| address_detail | string(255) | — | **có** | null | — | — | — | — | Nullable (ADR-045) — xem điều kiện publish |
| latitude | decimal(10,7) | — | có | null | — | — | — | — | |
| longitude | decimal(10,7) | — | có | null | — | — | — | — | |
| status | enum(active,inactive) | — | không | active | ✓ | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |
| deleted_at | timestamp | — | có | null | — | — | — | — | Soft delete |

**`is_primary` đã bị loại khỏi cột này (ADR-064):** rà soát xác nhận không có use case Phase 1
nào đọc/ghi "primary location của Company" — điều kiện publish/tìm kiếm/hiển thị đều dùng
`job_locations.is_primary` (primary **cấp Job**, khác khái niệm). Giữ lại chỉ gây nhầm lẫn giữa 2
khái niệm "primary" ở 2 tầng khác nhau — không tạo cột dự phòng.

**Chính sách xóa:** soft delete.

**Thay đổi mục tiêu Phase 2 (ADR-080, chưa migrate):** PDF liệt bảng này vào nhóm không nên tồn
tại — địa điểm làm việc chuyển hẳn về `jobs.work_ward_id` (mục 9.9), company chỉ giữ 1 trụ sở
(`companies.headquarters_ward_id`, chưa thiết kế cột này ở mục 9.6 vì phụ thuộc quyết định chi
tiết khi thực thi batch 5/7). **Không xóa bảng này cho tới Contract (batch 9)** —
`job_locations`/`JobLocation` vẫn là cơ chế địa chỉ Job thật đang chạy. Batch 5/7 ở
`docs/PHASE-2-ARCHITECTURE-PROPOSAL.md`.

---

## 9.8. `company_contacts`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| company_id | bigint | ✓ | không | — | ✓ | — | companies.id | RESTRICT | |
| name | string(150) | — | không | — | — | — | — | — | |
| position | string(100) | — | có | null | — | — | — | — | |
| phone | string(20) | — | có | null | — | — | — | — | |
| phone_normalized | string(20) | — | có | null | ✓ | — | — | — | |
| zalo | string(20) | — | có | null | — | — | — | — | |
| email | string(191) | — | có | null | — | — | — | — | |
| is_primary | boolean | — | không | false | — | — | — | — | Tối đa 1 primary `active`/company (ADR-064) — enforce ở tầng Service (`store`/`update`), không thêm DB generated-unique (CRUD nội bộ, ít đồng thời hơn form public) |
| is_public | boolean | — | không | false | — | — | — | — | Chỉ hiển thị công khai khi `true` **và** được chọn làm `jobs.company_contact_id` — không phải nguồn CTA mặc định (`docs/CORE-FLOWS.md` mục 1) |
| status | varchar(20) **[varchar+enum]** | — | không | active | — | — | — | — | PHP backed enum: `active`, `inactive` (ADR-055) |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |
| deleted_at | timestamp | — | có | null | — | — | — | — | Soft delete |

**Chính sách xóa:** soft delete.

---

## 9.9. `jobs`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| public_id | string(26) | — | không | — | — | ✓ | — | — | |
| company_id | bigint | ✓ | không | — | ✓ | — | companies.id | RESTRICT | |
| company_contact_id | bigint | ✓ | có | null | — | — | company_contacts.id | SET NULL | Nếu có: contact phải thuộc đúng `jobs.company_id`, `status=active`, chưa soft-delete; chỉ public khi `is_public=true` (ADR-074) |
| owner_branch_id | bigint | ✓ | **không** | — | ✓ | — | branches.id | RESTRICT | **NOT NULL ngay từ lúc tạo Job (kể cả `draft`)** — sửa lại từ "nullable ở draft" (ADR-046). Staff: tự gán = `users.branch_id`. Admin: bắt buộc chọn tường minh khi tạo. Chỉ set lúc tạo Job hoặc đổi qua `ChangeJobBranchAction` khi `status ∈ {draft, paused}` và chưa soft-delete — không được đổi khi `published` hoặc `closed`; không sửa từ `hr.jobs.update`. Mỗi lần gán/đổi ghi 1 dòng `job_branch_histories` (mục 9.27, `docs/CORE-FLOWS.md` mục 1.0, 1.1) |
| code | string(30) | — | không | — | — | ✓ | — | — | Mã nội bộ, HR nhập tay hoặc tự sinh |
| title | string(200) | — | không | — | — | — | — | — | |
| slug | string(220) | — | không | — | ✓ | ✓ | — | — | |
| employment_type | varchar(20) **[varchar+enum]** | — | không | full_time | — | — | — | — | PHP backed enum: `full_time`, `part_time`, `seasonal`, `temporary` (ADR-055) |
| quantity | smallint | ✓ | có | null | — | — | — | — | Số lượng tuyển |
| gender_requirement | enum(male,female,any) | — | có | null | — | — | — | — | |
| min_age | tinyint | ✓ | có | null | — | — | — | — | `CHECK(min_age <= max_age)` khi cả hai có giá trị |
| max_age | tinyint | ✓ | có | null | — | — | — | — | |
| education_requirement | string(255) | — | có | null | — | — | — | — | |
| experience_requirement | string(255) | — | có | null | — | — | — | — | |
| salary_min | bigint | ✓ | có | null | — | — | — | — | VND, không dùng FLOAT; `CHECK(salary_min <= salary_max)` khi cả hai có giá trị |
| salary_max | bigint | ✓ | có | null | — | — | — | — | |
| salary_base | bigint | ✓ | có | null | — | — | — | — | |
| salary_period | enum(month,day,hour,piece,negotiable) | — | không | month | — | — | — | — | |
| currency | string(3) | — | không | VND | — | — | — | — | |
| salary_description | text | — | có | null | — | — | — | — | |
| job_description | text | — | **có** | null | — | — | — | — | Nullable (ADR-060 — sửa từ NOT NULL, mâu thuẫn với Job Draft Contract mục 1.0/ADR-046). Bắt buộc có nội dung thực trước publish (mục 1.2, điều kiện nội dung Job) |
| requirements | text | — | có | null | — | — | — | — | Bắt buộc có nội dung thực trước publish (điều kiện 12) |
| benefits | text | — | có | null | — | — | — | — | Bắt buộc có nội dung thực trước publish (điều kiện 13, ADR-060) |
| application_documents | text | — | có | null | — | — | — | — | |
| has_shuttle_bus | boolean | — | không | false | — | — | — | — | |
| shuttle_bus_details | text | — | có | null | — | — | — | — | |
| has_accommodation | boolean | — | không | false | — | — | — | — | |
| accommodation_details | text | — | có | null | — | — | — | — | |
| has_meal_support | boolean | — | không | false | — | — | — | — | |
| meal_support_details | text | — | có | null | — | — | — | — | |
| is_urgent | boolean | — | không | false | ✓ | — | — | — | |
| status | enum(draft,published,paused,closed) | — | không | draft | ✓ | — | — | — | Transition matrix: `docs/CORE-FLOWS.md` mục 1; đổi qua `ChangeJobStatusAction`, ghi `job_status_histories` |
| published_at | timestamp | — | có | null | — | — | — | — | |
| expires_at | timestamp | — | có | null | ✓ | — | — | — | |
| closed_at | timestamp | — | có | null | — | — | — | — | |
| close_reason | varchar(30) **[varchar+enum]** | — | có | null | — | — | — | — | PHP backed enum: `recruitment_filled`, `recruitment_stopped`, `expired`, `company_request`, `duplicate`, `other` (ADR-055) |
| last_checked_at | timestamp | — | có | null | — | — | — | — | **Mới (ADR-048).** Cập nhật ở **mọi** lần tạo `job_verifications`, bất kể `result`. Khác `last_verified_at` — xem `docs/CORE-FLOWS.md` mục 1.3 |
| last_verified_at | timestamp | — | có | null | — | — | — | — | Chỉ cập nhật khi `job_verifications.result = still_open` (ADR-048) — là mốc scheduler cảnh báo dùng để tính, và là mốc freshness hỗ trợ `PUB-VERIFY` (mục 1.2) |
| created_by | bigint | ✓ | không | — | — | — | users.id | RESTRICT | |
| updated_by | bigint | ✓ | có | null | — | — | users.id | SET NULL | |
| deleted_by | bigint | ✓ | có | null | — | — | users.id | SET NULL | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |
| deleted_at | timestamp | — | có | null | — | — | — | — | Soft delete |

Index bổ sung: `(company_id, status)`, `(owner_branch_id, status)`, `(status, last_verified_at)` —
hỗ trợ predicate cảnh báo xác minh (mục 1.3), dùng chung cho danh sách Job và Dashboard.

**Chính sách xóa:** đóng job trước (`status = closed`), soft delete khi cần. **Không hard
delete job đã có application.** Một job là một đợt tuyển dụng — không tái sử dụng job đã
đóng cho đợt mới (ADR-008). Job `draft` bỏ dùng được xử lý bằng soft delete, không phải
transition trạng thái.

**Thay đổi mục tiêu Phase 2 (ADR-080, chưa migrate):**
- `company_id` chuyển **nullable**, thêm `job_type enum(company,direct)` — `direct` không cần
  company, dùng `employer_display_name` thay thế; validation chi tiết cho luồng "tuyển trực tiếp"
  **[CẦN CHỐT VỚI CÔNG TY]** trước khi code batch 7 (PDF không mô tả đủ chi tiết).
- Thêm `work_ward_id` (FK `wards.id`, **bắt buộc**) và `industrial_park_id` (FK
  `industrial_parks.id`, tùy chọn — nếu có, `work_ward_id` phải thuộc KCN đó qua
  `industrial_park_wards`) — thay cho cơ chế `job_locations`/`company_locations` hiện tại.
- Thêm `industry_id` (FK `industries.id`, mục 9.32, bắt buộc trước publish).
- `employment_type` (varchar+PHP enum hiện tại) chuyển sang `employment_type_id` (FK
  `employment_types.id`, mục 9.33) — bảng danh mục có 5 giá trị (thêm `freelance`/`internship` so
  với enum hiện tại chỉ có 4).
- `salary_period=negotiable` hiện tại tương đương `salary_negotiable=true` của PDF — **không đổi
  field**, giữ nguyên `salary_min/max/base/period/currency` (đã bao quát hơn PDF).
- `job_locations`/`company_location_id` **giữ nguyên tới Contract (batch 9)** — batch 5 chỉ thêm
  cột mới song song (nullable), backfill từ `job_locations` hiện có, chưa xóa gì.

Batch 4 (`industries`/`employment_types`), 5 (`work_ward_id`/`industry_id`/`employment_type_id`),
7 (`company_id` nullable) ở `docs/PHASE-2-ARCHITECTURE-PROPOSAL.md`.

---

## 9.10. `job_locations`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| job_id | bigint | ✓ | không | — | ✓ | ✓ (composite: job_id+company_location_id) | jobs.id | CASCADE | |
| company_location_id | bigint | ✓ | không | — | ✓ | (composite) | company_locations.id | RESTRICT | |
| is_primary | boolean | — | không | false | — | — | — | — | Mỗi job phải có đúng 1 primary |
| primary_flag_job_id | bigint | ✓ | có | null (generated) | — | ✓ | — | — | Cột generated: `IF(is_primary, job_id, NULL)`. `UNIQUE` trên cột này chặn 2 request đồng thời cùng đặt `is_primary=true` cho cùng 1 job |
| created_at | timestamp | — | có | now | — | — | — | — | |

---

## 9.11. `work_shifts`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| code | string(30) | — | không | — | — | ✓ | — | — | |
| name | string(100) | — | không | — | — | — | — | — | |
| is_active | boolean | — | không | true | ✓ | — | — | — | |
| sort_order | smallint | ✓ | không | 0 | — | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

**Seeder bắt buộc:** `day`, `night`, `rotating`, `two_shift`, `three_shift`, `administrative`,
`flexible`.

**Chính sách xóa:** danh mục, không hard delete khi đã tham chiếu → `is_active = false`.

---

## 9.12. `job_work_shifts`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| job_id | bigint | ✓ | không | — | ✓ (composite PK) | — | jobs.id | CASCADE | |
| work_shift_id | bigint | ✓ | không | — | ✓ (composite PK) | — | work_shifts.id | RESTRICT | |
| description | string(255) | — | có | null | — | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | |

Khóa chính composite `(job_id, work_shift_id)`, không có cột `id` riêng.

---

## 9.13. `job_verifications`

Xác nhận Job còn tuyển (khác mục đích với `job_status_histories`, mục 9.26 — không dùng thay
thế cho nhau).

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| job_id | bigint | ✓ | không | — | ✓ | — | jobs.id | RESTRICT | |
| verified_by | bigint | ✓ | không | — | — | — | users.id | RESTRICT | |
| result | enum(still_open,paused,closed,needs_review) | — | không | — | — | — | — | — | |
| note | string(255) | — | có | null | — | — | — | — | |
| verified_at | timestamp | — | không | now | — | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | Append-only, không có updated_at |

**Quy tắc transaction (ADR-048, ADR-058, ADR-059 — không phải "mọi lần đều cập nhật `last_verified_at`"):**
tạo verification → **luôn** cập nhật `jobs.last_checked_at = now()`, bất kể `result` → **chỉ khi
`result = still_open`** cập nhật thêm `jobs.last_verified_at = now()` → áp dụng Ma trận Job
Status × Verification Result (`docs/CORE-FLOWS.md` mục 1.3.1) để quyết định có đổi `jobs.status`
hay không (qua `ChangeJobStatusAction`, ghi thêm `job_status_histories` nếu status đổi) — tất cả
trong 1 transaction. Publish/mở lại chỉ dựa vào **bản ghi mới nhất**, không phải "từng có
`still_open` trong lịch sử" (ADR-058). Job `draft` từ chối `result ∈ {paused, closed}`; Job
`closed` từ chối mọi verification mới qua route Staff/Admin thông thường (ADR-059).

---

## 9.14. `recruitment_sources`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| code | string(30) | — | không | — | — | ✓ | — | — | |
| name | string(100) | — | không | — | — | — | — | — | |
| type | enum(website,social,zalo,staff,referral,offline,other) | — | không | — | ✓ | — | — | — | |
| is_active | boolean | — | không | true | ✓ | — | — | — | |
| sort_order | smallint | ✓ | không | 0 | — | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

**Seeder bắt buộc** (`code` → `type`): `website`→website, `zalo`→zalo, `facebook`→social,
`staff`→staff, `referral`→referral, `other`→other. Giá trị `type=referral` chỉ phân loại
nguồn — không kéo theo module cộng tác viên (vẫn ngoài Phase 1).

**Nguồn thuộc `applications`, không thuộc `candidates`.**

---

## 9.15. `applications`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| public_id | string(26) | — | không | — | — | ✓ | — | — | |
| candidate_id | bigint | ✓ | không | — | ✓ | ✓ (composite: candidate_id+job_id) | candidates.id | RESTRICT | |
| job_id | bigint | ✓ | không | — | ✓ | (composite) | jobs.id | RESTRICT | |
| source_id | bigint | ✓ | có | null | ✓ | — | recruitment_sources.id | SET NULL | |
| owner_branch_id | bigint | ✓ | không | — | ✓ | — | branches.id | RESTRICT | Copy từ `jobs.owner_branch_id` lúc tạo, không suy ra động qua job; đổi qua `application_branch_histories` (`docs/CORE-FLOWS.md` mục 6.1) |
| stage | enum(new,contacting,consulted,interview_scheduled,interviewed,waiting_start,started,closed) | — | không | new | ✓ | — | — | — | Transition matrix: `docs/CORE-FLOWS.md` mục 5.1 |
| stage_changed_at | timestamp | — | không | now | — | — | — | — | Cập nhật ở **mọi** lần đổi stage (kể cả trong cùng chu kỳ) |
| close_reason | enum(unreachable,candidate_cancelled,employer_cancelled,unsuitable,duplicate,job_closed,other) | — | có | null | — | — | — | — | Bắt buộc khi `stage = closed`; reset về `null` khi mở lại (`closed → new`) |
| workflow_cycle | int unsigned | ✓ | không | 1 | ✓ | — | — | — | Số chu kỳ xử lý hiện tại; tăng mỗi lần mở lại (`docs/CORE-FLOWS.md` mục 5.4) |
| workflow_cycle_started_at | timestamp | — | không | now | — | — | — | — | Mốc bắt đầu chu kỳ hiện tại; reset khi mở lại |
| reopened_at | timestamp | — | có | null | — | — | — | — | Lần mở lại gần nhất |
| reopened_by | bigint | ✓ | có | null | — | — | users.id | SET NULL | Lần mở lại gần nhất |
| submission_token | string(64) | — | **không** | — | ✓ | ✓ | — | — | **NOT NULL, UNIQUE** — idempotency chống double-submit (`docs/CORE-FLOWS.md` mục 3). Không dùng bảng riêng — xem ADR-035 |
| needs_duplicate_review | boolean | — | không | false | ✓ | — | — | — | Summary: `true` khi còn ít nhất 1 `candidate_duplicate_reviews.status=pending`; bảng review là nguồn sự thật (ADR-075) |
| duplicate_reviewed_at | timestamp | — | có | null | — | — | — | — | Chỉ ghi khi review pending cuối cùng của Application được resolve; reset null nếu phát sinh review pending mới |
| duplicate_reviewed_by | bigint | ✓ | có | null | — | — | users.id | SET NULL | Admin resolve review pending cuối cùng; không phải nguồn sự thật chi tiết |
| last_reapplied_at | timestamp | — | có | null | — | — | — | — | Cập nhật khi candidate nộp lại form cho cùng job (case C, không tạo record mới, không tự mở lại) |
| submitted_full_name | string(150) | — | không | — | — | — | — | — | PII. NOT NULL giữ nguyên khi anonymize — **mask** bằng placeholder cố định, không set NULL (mục 7.2.1, ADR-056) |
| submitted_phone | string(20) | — | không | — | — | — | — | — | PII. NOT NULL giữ nguyên khi anonymize — **mask** bằng placeholder cố định (mục 7.2.1, ADR-056) |
| submitted_phone_normalized | string(20) | — | không | — | ✓ | — | — | — | PII. NOT NULL giữ nguyên khi anonymize — **mask** cùng placeholder; index không unique nên nhiều bản ghi trùng giá trị mask không vi phạm gì (mục 7.2.1, ADR-056) |
| submission_snapshot | json | — | không | — | — | — | — | — | Lịch sử, không dùng lọc/báo cáo. Có thể chứa PII. NOT NULL giữ nguyên khi anonymize — **thay thế** bằng JSON đã redact (không set NULL); nội dung redact cụ thể vẫn **[CẦN CHỐT VỚI CÔNG TY]** (mục 7.2, go-live blocker), cấu trúc cột đã chốt (mục 7.2.1, ADR-056) |
| job_snapshot | json | — | không | — | — | — | — | — | Lịch sử, không dùng lọc/báo cáo. Không chứa PII candidate — giữ nguyên vĩnh viễn, không cần chính sách anonymize (mục 7.1) |
| source_detail | string(255) | — | có | null | — | — | — | — | |
| utm_source | string(100) | — | có | null | — | — | — | — | |
| utm_medium | string(100) | — | có | null | — | — | — | — | |
| utm_campaign | string(100) | — | có | null | — | — | — | — | |
| landing_url | string(500) | — | có | null | — | — | — | — | |
| consent_version | string(20) | — | không | — | — | — | — | — | |
| consent_text_hash | string(64) | — | không | — | — | — | — | — | SHA-256 nội dung consent tại thời điểm gửi |
| consented_at | timestamp | — | không | — | — | — | — | — | |
| consent_ip | string(45) | — | có | null | — | — | — | — | Hỗ trợ IPv6. PII. Khi anonymize: **set NULL** (mục 7.2.1, ADR-056) |
| consent_user_agent | string(255) | — | có | null | — | — | — | — | PII. Khi anonymize: **set NULL** (mục 7.2.1, ADR-056) |
| expected_start_at | date | — | có | null | — | — | — | — | Reset về `null` khi mở lại (`closed → new`) |
| started_at | timestamp | — | có | null | — | — | — | — | |
| closed_at | timestamp | — | có | null | — | — | — | — | Reset về `null` khi mở lại |
| created_at | timestamp | — | có | now | ✓ | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

**Không có `assigned_to`** (ADR-021) và **không có `referral_code`** (ADR-029) trong Phase 1.

Index bắt buộc: `(stage, created_at)`, `(job_id, stage, created_at)`, `(source_id, created_at)`,
`(candidate_id, created_at)`, `(owner_branch_id, stage, updated_at)` (danh sách hồ sơ theo cơ
sở).

**Chính sách xóa:** không hard delete.

**Ghi chú PDF:** PDF mục 10.3 có `assigned_user_id` (nullable, "Phase 1 có thể chưa gán") —
**không phải gap mới**, đã nằm trong `docs/PHASE-2-BACKLOG.md` (mục Assignment/claim) từ trước,
loại trừ tường minh khỏi Phase 1 (ADR-021) lẫn khỏi baseline ADR-080. Chỉ thêm khi có quyết định
riêng mở Assignment, không tự động theo PDF.

---

## 9.16. `application_status_histories`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| application_id | bigint | ✓ | không | — | ✓ | — | applications.id | RESTRICT | |
| from_stage | enum (như `applications.stage`) | — | có | null | — | — | — | — | |
| to_stage | enum (như `applications.stage`) | — | không | — | — | — | — | — | |
| close_reason | enum (như `applications.close_reason`) | — | có | null | — | — | — | — | Chỉ có giá trị khi `to_stage = closed` |
| workflow_cycle | int unsigned | ✓ | không | — | ✓ | — | — | — | Chu kỳ mà `to_stage` thuộc về; dòng `closed→new` dùng chu kỳ **mới** (`docs/CORE-FLOWS.md` mục 5.4) |
| changed_by | bigint | ✓ | có | null | — | — | users.id | SET NULL | |
| actor_type | enum(user,system) | — | không | user | — | — | — | — | Không có `import` trong Phase 1 (chưa có tính năng import, ADR-029) |
| note | string(255) | — | có | null | — | — | — | — | **Bắt buộc** khi `from_stage=closed`, `to_stage=new` (lý do mở lại — tái dùng cột này, `docs/CORE-FLOWS.md` mục 5.5) |
| metadata | json | — | có | null | — | — | — | — | Dùng lưu `merge_kept_application_id`/`merge_target_candidate_id` khi đóng do merge (`docs/CORE-FLOWS.md` mục 6.3) |
| created_at | timestamp | — | có | now | ✓ | — | — | — | Không có `updated_at`, append-only |

**Quy tắc:** không xóa hoặc sửa lịch sử sau khi tạo.

---

## 9.17. `application_contact_attempts`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| application_id | bigint | ✓ | không | — | ✓ | — | applications.id | RESTRICT | |
| contacted_by | bigint | ✓ | không | — | — | — | users.id | RESTRICT | |
| channel | enum(phone,zalo,sms,email,other) | — | không | — | — | — | — | — | |
| result | enum(reached,no_answer,busy,wrong_number,consulted,callback_requested,interview_agreed,candidate_refused,unsuitable,message_sent,other) | — | không | — | — | — | — | — | Enum chính thức — đồng nhất với `docs/CORE-FLOWS.md` mục 5.2 |
| workflow_cycle | int unsigned | ✓ | không | — | ✓ | — | — | — | Gán = `applications.workflow_cycle` tại thời điểm tạo (`docs/CORE-FLOWS.md` mục 5.4) |
| contacted_at | timestamp | — | không | now | — | — | — | — | |
| note | string(255) | — | có | null | — | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | Append-only |

**Quy tắc:** kết quả cuộc gọi ghi ở đây, **không** ghi đè vào `applications.stage` (ADR-009).
`result ∈ {consulted, interview_agreed}` **thuộc chu kỳ hiện tại** mở khóa transition
`contacting → consulted`.

---

## 9.18. `application_notes`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| application_id | bigint | ✓ | không | — | ✓ | — | applications.id | RESTRICT | |
| user_id | bigint | ✓ | không | — | — | — | users.id | RESTRICT | |
| content | text | — | không | — | — | — | — | — | |
| edited_at | timestamp | — | có | null | — | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |
| deleted_at | timestamp | — | có | null | — | — | — | — | Soft delete |

**Chính sách xóa:** soft delete. Không public. Chỉ admin hoặc người tạo được sửa (Policy).

---

## 9.19. `export_logs`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| exported_by | bigint | ✓ | không | — | ✓ | — | users.id | RESTRICT | |
| export_type | string(50) | — | không | — | — | — | — | — | vd `applications_csv` |
| filters | json | — | có | null | — | — | — | — | |
| row_count | int unsigned | ✓ | không | 0 | — | — | — | — | |
| file_name | string(255) | — | có | null | — | — | — | — | Không lưu file CSV lâu dài trên server |
| created_at | timestamp | — | có | now | — | — | — | — | Append-only |

---

## 9.20. `pages`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| title | string(200) | — | không | — | — | — | — | — | |
| slug | string(220) | — | không | — | ✓ | ✓ | — | — | vd `chinh-sach-du-lieu-ca-nhan` |
| content | text (long) | — | không | — | — | — | — | — | |
| meta_title | string(255) | — | có | null | — | — | — | — | |
| meta_description | string(320) | — | có | null | — | — | — | — | |
| status | varchar(20) **[varchar+enum]** | — | không | draft | ✓ | — | — | — | PHP backed enum: `draft`, `published`, `hidden` (ADR-055) |
| published_at | timestamp | — | có | null | — | — | — | — | |
| created_by | bigint | ✓ | không | — | — | — | users.id | RESTRICT | |
| updated_by | bigint | ✓ | có | null | — | — | users.id | SET NULL | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

---

## 9.21. `faqs`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| question | string(255) | — | không | — | — | — | — | — | |
| answer | text | — | không | — | — | — | — | — | |
| is_active | boolean | — | không | true | ✓ | — | — | — | |
| sort_order | smallint | ✓ | không | 0 | — | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

---

## 9.22. `settings`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| key | string(100) | — | không | — | — | ✓ | — | — | |
| value | text | — | có | null | — | — | — | — | |
| type | varchar(20) **[varchar+enum]** | — | không | string | — | — | — | — | PHP backed enum: `string`, `integer`, `boolean`, `json` (ADR-055) — dùng để ép kiểu khi đọc |
| group_name | string(50) | — | không | general | ✓ | — | — | — | |
| is_public | boolean | — | không | false | — | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

**Quy tắc:** không lưu secret trong `settings` nếu không có mã hóa phù hợp.

**Seed bắt buộc cho Job Verification Scheduler** (`docs/CORE-FLOWS.md` mục 1.3):
`job_verification_warning_days` = `7`, `job_auto_pause_days` = `14`,
`job_auto_pause_enabled` = `false`, `job_verification_valid_days` = `null` (tắt kiểm tra độ mới —
ADR-058, giá trị cụ thể **[CẦN CHỐT VỚI CÔNG TY]**, không migration blocker).

---

## 9.23. `branches`

Cơ sở nội bộ của công ty cung ứng lao động (vieclam88) — **khác** `company_locations` (địa
điểm/nhà máy của công ty khách hàng, mục 9.7). Xem ADR-015.

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| code | string(20) | — | không | — | — | ✓ | — | — | Mã cơ sở, HR nhập tay |
| name | string(150) | — | không | — | — | — | — | — | |
| phone | string(20) | — | có | null | — | — | — | — | Số điện thoại công khai cho ứng viên (CTA "Gọi") — bắt buộc có `phone` hoặc `zalo` trước khi Job của cơ sở publish |
| phone_normalized | string(20) | — | có | null | ✓ | — | — | — | |
| zalo | string(20) | — | có | null | — | — | — | — | |
| email | string(191) | — | có | null | — | — | — | — | |
| administrative_unit_id | bigint | ✓ | không | — | ✓ | — | administrative_units.id | RESTRICT | |
| address_detail | string(255) | — | có | null | — | — | — | — | |
| status | enum(active,inactive) | — | không | active | ✓ | — | — | — | Phải `active` khi Job của cơ sở publish/reopen |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |
| deleted_at | timestamp | — | có | null | — | — | — | — | Soft delete |

**Chính sách xóa:** soft delete; không hard delete cơ sở đã có `users`/`jobs`/`applications`
tham chiếu. Ngừng hoạt động → `status = inactive` trước, soft delete sau nếu cần.

---

## 9.24. `application_branch_histories`

Lịch sử gán/chuyển cơ sở phụ trách của một Application — append-only. Xem
`docs/CORE-FLOWS.md` mục 6.1.

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| application_id | bigint | ✓ | không | — | ✓ | — | applications.id | RESTRICT | |
| from_branch_id | bigint | ✓ | có | null | — | — | branches.id | SET NULL | Null ở bản ghi đầu tiên (gán lúc Apply) |
| to_branch_id | bigint | ✓ | không | — | ✓ | — | branches.id | RESTRICT | |
| transferred_by | bigint | ✓ | có | null | — | — | users.id | SET NULL | Null = hệ thống tự gán lúc Apply; nếu chuyển thủ công luôn là `admin` |
| reason | string(255) | — | có | null | — | — | — | — | Bắt buộc ở tầng Service khi là chuyển cơ sở thủ công (không bắt buộc ở bản ghi đầu) |
| created_at | timestamp | — | có | now | — | — | — | — | Append-only, không có `updated_at` |

**Quy tắc:** mỗi lần tạo Application (gán lần đầu) và mỗi lần chuyển cơ sở thủ công đều tạo 1
bản ghi. Không sửa/xóa sau khi tạo. Chỉ `admin` được tạo bản ghi chuyển cơ sở thủ công, và chỉ
khi cơ sở đích khác cơ sở hiện tại, đang `active`, chưa `deleted_at` (`docs/CORE-FLOWS.md` mục
6.1).

---

## 9.25. `application_appointments`

Lịch gọi lại (`callback`) và lịch phỏng vấn (`interview`). Xem `docs/CORE-FLOWS.md` mục 5.3.
Không phải bảng append-only thuần vì `status`/`outcome` được cập nhật sau khi tạo — nhưng đổi
lịch (đổi giờ) luôn tạo bản ghi mới, không sửa `scheduled_at` của bản ghi cũ.

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| application_id | bigint | ✓ | không | — | ✓ | — | applications.id | RESTRICT | |
| type | enum(callback,interview) | — | không | — | ✓ | — | — | — | |
| scheduled_at | timestamp | — | không | — | ✓ | — | — | — | Không sửa sau khi tạo — đổi lịch = hủy bản ghi này (`status=cancelled`) + tạo bản ghi mới |
| location_detail | string(255) | — | có | null | — | — | — | — | Địa điểm phỏng vấn nếu khác trụ sở cơ sở |
| status | enum(scheduled,completed,cancelled,no_show) | — | không | scheduled | ✓ | — | — | — | |
| outcome | string(255) | — | có | null | — | — | — | — | Tóm tắt kết quả khi `completed` |
| note | text | — | có | null | — | — | — | — | |
| workflow_cycle | int unsigned | ✓ | không | — | ✓ | — | — | — | Gán = `applications.workflow_cycle` tại thời điểm tạo (`docs/CORE-FLOWS.md` mục 5.4) |
| created_by | bigint | ✓ | không | — | — | — | users.id | RESTRICT | |
| completed_by | bigint | ✓ | có | null | — | — | users.id | SET NULL | |
| completed_at | timestamp | — | có | null | — | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

Index bổ sung: `(application_id, type, status)`.

**Quy tắc:** `interview_scheduled → interviewed` (transition matrix) yêu cầu tồn tại 1
appointment `type=interview` với `status=completed` **thuộc chu kỳ hiện tại**. `no_show`/
`cancelled` không tự động đổi `applications.stage`.

---

## 9.26. `job_status_histories`

Lịch sử đầy đủ vòng đời `jobs.status` — append-only. Khác mục đích với `job_verifications`
(mục 9.13, xác nhận job còn tuyển theo lịch định kỳ) — **không dùng thay thế cho nhau**. Xem
`docs/CORE-FLOWS.md` mục 1.

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| job_id | bigint | ✓ | không | — | ✓ | — | jobs.id | RESTRICT | |
| from_status | enum (như `jobs.status`) | — | có | null | — | — | — | — | Null chỉ khi ghi lịch sử tạo mới (không bắt buộc — xem quy tắc) |
| to_status | enum (như `jobs.status`) | — | không | — | — | — | — | — | |
| reason | string(255) | — | có | null | — | — | — | — | Bắt buộc khi `to_status = closed` (đồng bộ giá trị với `jobs.close_reason`); **bắt buộc khi `to_status = published` do Admin publish ngoại lệ không qua verification** (mục điều kiện publish 11, ADR-047); khuyến khích khi `to_status = paused`, không bắt buộc |
| changed_by | bigint | ✓ | không | — | — | — | users.id | RESTRICT | |
| created_at | timestamp | — | có | now | — | — | — | — | Append-only, không có `updated_at` |

**Quy tắc:** mọi transition trong Job transition matrix (`docs/CORE-FLOWS.md` mục 1) tạo đúng 1
bản ghi, qua `ChangeJobStatusAction`, không sửa `jobs.status` trực tiếp từ controller. Tạo Job
mới (`draft`) **không bắt buộc** tạo bản ghi `null → draft` (đã có `jobs.created_by`/
`created_at` phục vụ mục đích tương tự) — quyết định giữ bảng này gọn, chỉ ghi các transition
thật sự thay đổi trạng thái vận hành công khai của Job.

---

## 9.27. `job_branch_histories`

Lịch sử gán/chuyển cơ sở phụ trách của một Job — append-only. Xem `docs/CORE-FLOWS.md` mục 1.1
(Job Branch Contract, ADR-038). Khác `application_branch_histories` (mục 9.24, theo dõi cơ sở
của từng Application) — bảng này theo dõi cơ sở của chính Job.

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| job_id | bigint | ✓ | không | — | ✓ | — | jobs.id | RESTRICT | |
| from_branch_id | bigint | ✓ | có | null | — | — | branches.id | SET NULL | Null ở bản ghi đầu tiên (gán lúc tạo Job) |
| to_branch_id | bigint | ✓ | không | — | ✓ | — | branches.id | RESTRICT | |
| reason | string(255) | — | có | null | — | — | — | — | Nullable ở bản ghi đầu, bắt buộc khi đổi cơ sở thủ công |
| changed_by | bigint | ✓ | không | — | — | — | users.id | RESTRICT | Luôn có giá trị — Job luôn do một người tạo/sửa qua form, không có "hệ thống tự gán" như `application_branch_histories` |
| created_at | timestamp | — | có | now | — | — | — | — | Append-only, không có `updated_at` |

**Quy tắc:** ghi 1 dòng khi Job được gán `owner_branch_id` lần đầu lúc tạo, và mỗi lần Admin
đổi cơ sở qua `ChangeJobBranchAction` (chỉ khi Job `draft`/`paused`, chưa `deleted_at`, cơ sở
đích `active`, chưa `deleted_at`, có lý do — `docs/CORE-FLOWS.md` mục 1.1, ADR-054).
`hr.jobs.update` (sửa nội dung Job khác) không được phép sửa `owner_branch_id`, do đó không tạo
bản ghi ở đây.

---

## 9.28. `candidate_duplicate_reviews`

**Mới (ADR-062).** Bảng thứ 28 — dữ liệu đủ để Admin thực sự xử lý nghi ngờ trùng thay vì chỉ có
cờ `applications.needs_duplicate_review`. Xem `docs/CORE-FLOWS.md` mục 6.2.2.

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| application_id | bigint | ✓ | không | — | ✓ | — | applications.id | RESTRICT | Application tạo ra review này; có thể có nhiều review/suspected root (ADR-075) |
| candidate_id | bigint | ✓ | không | — | ✓ | — | candidates.id | RESTRICT | Candidate mới của Application cần review |
| suspected_candidate_id | bigint | ✓ | không | — | ✓ | — | candidates.id | RESTRICT | Suspected root đã tồn tại cùng `phone_normalized` |
| reason_code | varchar(30) **[varchar+enum]** | — | không | — | — | — | — | — | PHP backed enum: `same_phone_missing_dob`, `same_phone_different_name`, `same_identity_conflicting_dob`, `multiple_exact_matches`, `other` |
| status | varchar(20) **[varchar+enum]** | — | không | pending | ✓ | — | — | — | PHP backed enum: `pending`, `confirmed_same`, `confirmed_distinct`, `dismissed` |
| pending_pair_key | varchar(80), generated | — | có (null khi không `pending`) | null (generated) | ✓ | ✓ | — | — | `IF(status='pending', CONCAT(candidate_id,'-',suspected_candidate_id,'-',reason_code), NULL)` STORED — chặn 2 review `pending` trùng cặp + lý do |
| reviewed_by | bigint | ✓ | có | null | — | — | users.id | SET NULL | |
| reviewed_at | timestamp | — | có | null | — | — | — | — | |
| review_note | string(255) | — | có | null | — | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | Không append-only thuần — `status`/`reviewed_by`/`reviewed_at`/`review_note` cập nhật sau khi Admin xử lý |

**Quy tắc:** tạo một bản ghi cho **mỗi suspected root** khi không có đúng một exact root; một Application có thể có nhiều review. Trường hợp nhiều exact root dùng `multiple_exact_matches` cho từng root. Tạo cùng transaction với Candidate/Application. **Không tự động merge** —
`confirmed_same` chỉ đánh dấu kết luận của Admin, merge vẫn là hành động riêng
(`hr.candidates.merge`). Chỉ **admin** truy cập.

**Chính sách xóa:** không xóa — cập nhật `status` khi Admin xử lý xong (không phải append-only
thuần, xem ghi chú `updated_at` ở trên).

---

## Mục 9.29–9.36 — Bảng target Phase 2 (ADR-080, CHƯA tồn tại trong database thật)

> Toàn bộ 7 bảng dưới đây **chưa có migration nào tạo ra**, chỉ là thiết kế mục tiêu theo PDF
> "cấu trúc lại". Không viết code tham chiếu các bảng/cột này trừ khi đang thực thi đúng batch
> tương ứng ở `docs/PHASE-2-ARCHITECTURE-PROPOSAL.md`. Format cột giữ đúng quy ước các mục 9.1–9.28
> ở trên để khi migrate thật có thể chuyển thẳng thành migration.

## 9.29. `provinces` (target — batch 1)

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| code | string(20) | — | không | — | — | ✓ | — | — | Mã GSO — backfill từ `administrative_units.official_code` cấp root |
| name | string(150) | — | không | — | — | — | — | — | |
| is_active | boolean | — | không | true | ✓ | — | — | — | Ẩn khỏi form mới khi không còn hoạt động |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

**Chính sách xóa:** không hard delete — `is_active=false`.

## 9.30. `wards` (target — batch 1)

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| province_id | bigint | ✓ | không | — | ✓ | — | provinces.id | RESTRICT | |
| code | string(20) | — | không | — | — | ✓ | — | — | Mã GSO — backfill từ `administrative_units.official_code` cấp lá |
| name | string(150) | — | không | — | — | — | — | — | |
| is_active | boolean | — | không | true | ✓ | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

**Chính sách xóa:** không hard delete — `is_active=false`. Index `(province_id, is_active)`.

## 9.31. `industrial_park_wards` (target — batch 2)

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| industrial_park_id | bigint | ✓ | không | — | ✓ | — | industrial_parks.id | CASCADE | |
| ward_id | bigint | ✓ | không | — | ✓ | — | wards.id | RESTRICT | |
| is_primary | boolean | — | không | false | — | — | — | — | Đánh dấu địa bàn chính |

`UNIQUE(industrial_park_id, ward_id)` — không tạo liên kết trùng. Pivot, có thể cascade delete
theo `industrial_park_id`.

## 9.32. `industries` (target — batch 4, độc lập, không xung đột bảng hiện có)

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| name | string(150) | — | không | — | — | — | — | — | |
| slug | string(170) | — | không | — | — | ✓ | — | — | |
| description | text | — | có | null | — | — | — | — | |
| sort_order | smallint | ✓ | không | 0 | — | — | — | — | |
| is_active | boolean | — | không | true | ✓ | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

**Chính sách xóa:** không hard delete khi đã có `jobs.industry_id` tham chiếu — `is_active=false`.

## 9.33. `employment_types` (target — batch 4, độc lập; thay `JobEmploymentType` PHP enum)

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| name | string(100) | — | không | — | — | — | — | — | Tên hiển thị tiếng Việt (vd "Việc chính thức") |
| slug | string(120) | — | không | — | — | ✓ | — | — | `full-time`, `part-time`, `temporary`, `freelance`, `internship` — 5 giá trị seed mặc định theo PDF |
| description | text | — | có | null | — | — | — | — | |
| sort_order | smallint | ✓ | không | 0 | — | — | — | — | |
| is_active | boolean | — | không | true | ✓ | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

**Chính sách xóa:** không hard delete khi đã có `jobs.employment_type_id` tham chiếu —
`is_active=false`. Seeder upsert theo `slug`, idempotent.

## 9.34. `job_images` (target — batch 6, độc lập, không xung đột bảng hiện có)

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| job_id | bigint | ✓ | không | — | ✓ | — | jobs.id | CASCADE | |
| file_path | string(255) | — | không | — | — | — | — | — | Đường dẫn đã chuẩn hóa, tên file ngẫu nhiên |
| original_name | string(255) | — | có | null | — | — | — | — | |
| mime_type | string(100) | — | không | — | — | — | — | — | `image/jpeg`, `image/png`, `image/webp` |
| file_size | bigint | ✓ | không | — | — | — | — | — | Byte; giới hạn 5MB kiểm ở Form Request |
| alt_text | string(255) | — | có | null | — | — | — | — | SEO/accessibility |
| is_primary | boolean | — | không | false | — | — | — | — | Tối đa 1 ảnh đại diện/job — khóa bằng generated unique key cùng pattern `job_locations.primary_flag_job_id` |
| sort_order | smallint | ✓ | không | 0 | — | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

Index `(job_id, sort_order)`. Tối đa 10 ảnh/job (giới hạn ở application, đặt trong config).

**Chính sách xóa:** cascade delete theo `job_id` (ảnh phụ thuộc hoàn toàn vào job, không có lịch sử
tham chiếu độc lập). Xóa file vật lý phải kiểm quyền trước, không để file rác.

## 9.35. `candidate_documents` (target — batch 6, độc lập; CV PDF/avatar chưa có ở Phase 1)

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| candidate_id | bigint | ✓ | không | — | ✓ | — | candidates.id | CASCADE | |
| application_id | bigint | ✓ | có | null | — | — | applications.id | SET NULL | Gắn CV đúng lần ứng tuyển; `avatar` không cần gắn application |
| document_type | varchar(20) [varchar+enum] | — | không | — | ✓ | — | — | — | PHP backed enum: `cv`, `avatar` (theo ADR-055 — enum phụ chưa chốt mở rộng) |
| file_path | string(255) | — | không | — | — | — | — | — | Private storage, không public URL trực tiếp |
| original_name | string(255) | — | không | — | — | — | — | — | |
| mime_type | string(100) | — | không | — | — | — | — | — | CV: `application/pdf` only; avatar: `image/jpeg,image/png,image/webp` |
| file_size | bigint | ✓ | không | — | — | — | — | — | CV giới hạn 5MB (kiểm MIME thật, không chỉ đuôi file) |
| uploaded_at | timestamp | — | không | now | — | — | — | — | |

**Chính sách xóa:** không hard delete khi `application_id` còn active — CV phải giữ đúng trạng thái
tại thời điểm ứng tuyển (không bị ghi đè khi candidate nộp job khác). Tải/xem chỉ qua controller
có kiểm quyền theo Branch, không lộ URL công khai.

## 9.36. `activity_logs` (target — batch 6, đã chốt: mở rộng ADR-019, không thay thế)

PDF liệt `activity_logs` là bảng "Hệ thống" ghi mọi thao tác nhạy cảm (mục IV.A: "Thay đổi quan
trọng phải có activity log"). **Đã chốt (xác nhận trực tiếp 23/07/2026):** thêm `activity_logs`
làm sổ chung, **bổ sung thêm** cho các thao tác chưa có bảng lịch sử riêng (vd sửa `companies`,
`industrial_parks`, `settings`, danh mục `industries`/`employment_types`) — **không thay thế**
các bảng audit trail chuyên biệt hiện có (`job_status_histories`, `application_status_histories`,
`application_branch_histories`, `job_branch_histories`, `export_logs`...), vì các bảng đó có cột
cấu trúc riêng theo đúng ngữ cảnh nghiệp vụ (from/to status, reason...), giàu thông tin hơn 1 dòng
`activity_logs` chung chung. ADR-019 được **mở rộng** (không đảo ngược) — xem ADR-080.

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| user_id | bigint | ✓ | không | — | ✓ | — | users.id | RESTRICT | Người thực hiện |
| action | string(100) | — | không | — | ✓ | — | — | — | Vd `company.updated`, `settings.updated` |
| subject_type | string(100) | — | không | — | ✓ | — | — | — | Polymorphic — tên model |
| subject_id | bigint | ✓ | không | — | ✓ | — | — | — | Polymorphic — id bản ghi bị sửa |
| changes | json | — | có | null | — | — | — | — | Before/after, chỉ field thay đổi |
| ip | string(45) | — | có | null | — | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | Append-only, không `updated_at` |

**Chính sách xóa:** không xóa, không sửa (append-only).

---

## Chính sách xóa — tổng hợp

| Bảng | Chính sách |
|---|---|
| users | khóa bằng `status` |
| candidates | soft delete, merge, hoặc anonymize (`docs/CORE-FLOWS.md` mục 7, 6.3); **không có route HTTP soft-delete/restore ở Phase 1** — `deleted_at` chỉ dùng cho can thiệp Admin/DB thủ công (ADR-068) |
| companies | soft delete, có route restore (`hr.companies.restore`) |
| company_locations | soft delete, có route restore (ADR-053) |
| company_contacts | soft delete, có route restore (ADR-053) |
| branches | `status=inactive` trước, soft delete khi cần; có route restore (`hr.branches.restore`, mới — ADR-068); không hard delete nếu đã có `users`/`jobs`/`applications` |
| jobs | đóng trước (`status=closed`), soft delete khi cần, có route restore (`hr.jobs.restore`); không hard delete nếu đã có application |
| applications | không hard delete |
| application_status_histories / application_contact_attempts / application_branch_histories / job_status_histories / job_branch_histories / job_verifications / export_logs | không xóa, không sửa (append-only) |
| application_appointments | không xóa; `status`/`outcome` cập nhật được sau khi tạo, `scheduled_at` không sửa (đổi lịch = tạo bản ghi mới) |
| application_notes | soft delete qua `hr.applications.notes.destroy`; **không có route restore ở Phase 1** — xóa nhầm cần Admin can thiệp DB thủ công (ghi chú nội bộ, rủi ro thấp — ADR-068) |
| candidate_duplicate_reviews | không xóa — cập nhật `status` khi Admin xử lý (mục 9.28, ADR-062) |
| administrative_units / industrial_parks / work_shifts / recruitment_sources | `is_active = false`, không hard delete khi đã tham chiếu |
| job_locations / job_work_shifts | có thể cascade delete (pivot) |

**Không dùng cascade delete cho:** `companies → jobs`, `jobs → applications`,
`candidates → applications`, `applications → *_histories`, `applications → application_appointments`,
`branches → jobs`, `branches → applications`, `jobs → job_status_histories`,
`jobs → job_branch_histories`. Dùng `RESTRICT` — xử lý nghiệp vụ (đóng/soft-delete) trước khi
có thể xóa.

**Dùng `SET NULL`** cho quan hệ "người thực hiện" khi cột đã nullable (`changed_by`,
`updated_by`, `users.branch_id`, `application_branch_histories.from_branch_id`/
`transferred_by`, `job_branch_histories.from_branch_id`, `application_appointments.completed_by`,
`applications.duplicate_reviewed_by`/`reopened_by`, `candidates.merged_by`/`anonymized_by`,
`candidate_duplicate_reviews.reviewed_by`...) — tài khoản hoặc cơ sở có thể bị khóa/ngừng hoạt
động nhưng lịch sử vẫn phải còn nguyên. Cột "người thực hiện" bắt buộc (không nullable, vd
`verified_by`, `exported_by`, `contacted_by`, `application_appointments.created_by`,
`job_status_histories.changed_by`, `job_branch_histories.changed_by`) dùng `RESTRICT` vì `users`
không bao giờ bị hard delete trong hệ thống này. `applications.owner_branch_id`,
`application_branch_histories.to_branch_id` và `job_branch_histories.to_branch_id` không
nullable nên dùng `RESTRICT`.

---

## Migration order chính thức (ADR-069)

Business tables (28), theo thứ tự dependency:

1. `administrative_units` 2. `branches` 3. `users` 4. `industrial_parks` 5. `work_shifts`
6. `recruitment_sources` 7. `settings` 8. `companies` 9. `company_locations`
10. `company_contacts` 11. `jobs` 12. `job_locations` 13. `job_work_shifts`
14. `job_verifications` 15. `job_status_histories` 16. `job_branch_histories` 17. `candidates`
18. `candidate_contacts` 19. `applications` 20. `candidate_duplicate_reviews`
21. `application_status_histories` 22. `application_contact_attempts`
23. `application_appointments` 24. `application_branch_histories` 25. `application_notes`
26. `export_logs` 27. `pages` 28. `faqs`

Ghi chú dependency không hiển nhiên: `branches` trước `users` (`users.branch_id` FK);
`administrative_units` trước `branches`/`industrial_parks`/`candidates`/`company_locations`;
`candidates` trước `candidate_contacts`/`applications`; `applications` trước
`candidate_duplicate_reviews` (FK `application_id`) — bảng này cũng cần `candidates` đã tồn tại
(2 FK `candidate_id`/`suspected_candidate_id`), nên đặt sau `applications` (thứ tự tạo bảng, không
phải thứ tự ghi dữ liệu — dữ liệu review luôn ghi sau khi cả candidate lẫn application đã tồn
tại). `job_verifications`/`job_status_histories`/`job_branch_histories` chỉ cần `jobs`+`users`,
đặt trước `candidates` cũng hợp lệ nhưng giữ theo nhóm Job cho dễ đọc. `pages`/`faqs` không phụ
thuộc bảng nghiệp vụ nào khác ngoài `users` — đặt cuối chỉ vì thuộc nhóm "Admin tools", không phải
vì có dependency bắt buộc.

Chia theo 7 nhóm triển khai (mỗi nhóm có migration + model + policy + request + action + test
riêng trước khi sang nhóm kế): xem `ROADMAP.md` mục "Giai đoạn 1".

**Thứ tự migration cho 7 bảng target Phase 2 (mục 9.29–9.35, ADR-080, chưa chạy):** không chèn
vào thứ tự 28 bảng ở trên — chạy ở migration riêng theo đúng 9 batch của
`docs/PHASE-2-ARCHITECTURE-PROPOSAL.md` (`provinces`→`wards`→`industrial_park_wards`→
`industries`/`employment_types`→cột mới trên `jobs`→`job_images`/`candidate_documents`).

## Hạ tầng Laravel Phase 1 (ADR-066)

Tách biệt khỏi 28 business tables ở trên — **không tính chung** khi báo cáo số lượng bảng:

- `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`.
- **Không cần** `password_reset_tokens` (không có luồng "quên mật khẩu qua email" — Phase 1 dùng
  Admin reset, `docs/ROUTE-MAP.md` mục "HR admin", ADR-067).
- **Không cần** `jobs`/`job_batches`/`failed_jobs` (không có Job class bất đồng bộ nào ở Phase 1
  — không gửi email, `docs/CORE-FLOWS.md` mục 1.3).
- **Không cần** Laravel Task Scheduler/cron entry hay cache lock cho `withoutOverlapping()` —
  cảnh báo Job Verification Scheduler là giá trị tính toán khi render, không phải cron job.
- Với cấu hình trên, migration hạ tầng Phase 1 chỉ còn bảng `migrations` mặc định của Laravel.
