# Database Dictionary — vieclam88 (Phase 1)

Mô tả đầy đủ 27 bảng Phase 1. Xem quan hệ tổng quan ở `docs/ERD.md`, nguyên tắc thiết kế ở
`.claude/rules/data-model.md`, quyết định kiến trúc ở `docs/DECISIONS.md`, và 6 luồng nghiệp vụ
mà các bảng này phải hỗ trợ ở `docs/CORE-FLOWS.md`. Database: **MariaDB 11.4 LTS** (ADR-039) —
mọi tính năng dùng trong file này (generated column, unique index trên generated column, CHECK
constraint, recursive CTE, JSON, row locking) đều được hỗ trợ đầy đủ từ bản này.

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
- `deleted_at`: `timestamp nullable` (Laravel `SoftDeletes`), chỉ khai báo ở bảng có soft
  delete (xem "Chính sách xóa" cuối file).
- Tiền VND luôn `bigint unsigned`, không dùng `FLOAT`/`DOUBLE` (`.claude/rules/data-model.md`).
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
| Đã xác minh còn tuyển (`job_verifications.result=still_open`) trước lần publish đầu, trừ Admin override có lý do | — | `ChangeJobStatusAction` kiểm tra khi `to_status=published`; Admin override ghi `job_status_histories.reason` | `docs/CORE-FLOWS.md` mục 1.2, 1.3, ADR-047 |
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

**Chính sách xóa:** soft delete, merge, hoặc anonymize. Candidate `status = merged` không được
dùng để tạo application mới, không được sửa, không được làm nguồn/đích của merge khác. Candidate
`status = anonymized` không được làm nguồn/đích của merge. Không hard delete candidate đang có
application. Truy vấn "merged family" và quy tắc đầy đủ: `docs/CORE-FLOWS.md` mục 6.3.

---

## 9.3. `candidate_contacts`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| candidate_id | bigint | ✓ | không | — | ✓ | — | candidates.id | CASCADE | |
| type | enum(phone,email,zalo,other) | — | không | — | ✓ | — | — | — | |
| value | string(191) | — | không | — | — | — | — | — | Giá trị gốc người dùng nhập |
| normalized_value | string(191) | — | không | — | ✓ | — | — | — | Dùng để tìm kiếm/phát hiện trùng |
| is_primary | boolean | — | không | false | ✓ | — | — | — | Chỉ 1 primary/type/candidate (enforce ở tầng ứng dụng) |
| is_verified | boolean | — | không | false | — | — | — | — | |
| verified_at | timestamp | — | có | null | — | — | — | — | |
| is_active | boolean | — | không | true | ✓ | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

Index bắt buộc theo yêu cầu gốc: `(type, normalized_value)`, `(candidate_id, is_primary)`,
`(candidate_id, is_active)`, `UNIQUE(candidate_id, type, normalized_value)`.

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
| type | enum(province,city,commune,ward,special_zone,legacy_district) | — | không | — | ✓ | — | — | — | |
| is_active | boolean | — | không | true | ✓ | — | — | — | |
| valid_from | date | — | có | null | — | — | — | — | |
| valid_to | date | — | có | null | — | — | — | — | Đơn vị cũ sau sáp nhập địa giới |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

**Chính sách xóa:** không hard delete. Đơn vị cũ → `is_active = false`, giữ lại phục vụ dữ
liệu lịch sử.

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
(`docs/CORE-FLOWS.md` mục 1.2, điều kiện 10) — không phải điều kiện tạo location.

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
| is_primary | boolean | — | không | false | — | — | — | — | |
| status | enum(active,inactive) | — | không | active | ✓ | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |
| deleted_at | timestamp | — | có | null | — | — | — | — | Soft delete |

**Chính sách xóa:** soft delete.

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
| is_primary | boolean | — | không | false | — | — | — | — | |
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
| company_contact_id | bigint | ✓ | có | null | — | — | company_contacts.id | SET NULL | |
| owner_branch_id | bigint | ✓ | **không** | — | ✓ | — | branches.id | RESTRICT | **NOT NULL ngay từ lúc tạo Job (kể cả `draft`)** — sửa lại từ "nullable ở draft" (ADR-046). Staff: tự gán = `users.branch_id`. Admin: bắt buộc chọn tường minh khi tạo. Chỉ set lúc tạo Job hoặc đổi qua `ChangeJobBranchAction` (Job không được `published`) — không sửa từ `hr.jobs.update`. Mỗi lần gán/đổi ghi 1 dòng `job_branch_histories` (mục 9.27, `docs/CORE-FLOWS.md` mục 1.0, 1.1) |
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
| job_description | text | — | không | — | — | — | — | — | |
| requirements | text | — | có | null | — | — | — | — | |
| benefits | text | — | có | null | — | — | — | — | |
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
| last_verified_at | timestamp | — | có | null | — | — | — | — | Chỉ cập nhật khi `job_verifications.result = still_open` (ADR-048) — là mốc scheduler cảnh báo dùng để tính, và là điều kiện publish (mục 1.2, điều kiện 11) |
| created_by | bigint | ✓ | không | — | — | — | users.id | RESTRICT | |
| updated_by | bigint | ✓ | có | null | — | — | users.id | SET NULL | |
| deleted_by | bigint | ✓ | có | null | — | — | users.id | SET NULL | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |
| deleted_at | timestamp | — | có | null | — | — | — | — | Soft delete |

Index bổ sung: `(company_id, status)`, `(owner_branch_id, status)`.

**Chính sách xóa:** đóng job trước (`status = closed`), soft delete khi cần. **Không hard
delete job đã có application.** Một job là một đợt tuyển dụng — không tái sử dụng job đã
đóng cho đợt mới (ADR-008). Job `draft` bỏ dùng được xử lý bằng soft delete, không phải
transition trạng thái.

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

**Quy tắc transaction:** tạo verification → cập nhật `jobs.last_verified_at` → cập nhật
`jobs.status` nếu cần (qua `ChangeJobStatusAction`, ghi thêm `job_status_histories`), trong 1
transaction.

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
| needs_duplicate_review | boolean | — | không | false | ✓ | — | — | — | Đặt `true` ở trường hợp 2/3/4 của Duplicate Candidate Contract (`docs/CORE-FLOWS.md` mục 6.2, ADR-040) |
| duplicate_reviewed_at | timestamp | — | có | null | — | — | — | — | |
| duplicate_reviewed_by | bigint | ✓ | có | null | — | — | users.id | SET NULL | |
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
`job_auto_pause_enabled` = `false`.

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

## Chính sách xóa — tổng hợp

| Bảng | Chính sách |
|---|---|
| users | khóa bằng `status` |
| candidates | soft delete, merge, hoặc anonymize (`docs/CORE-FLOWS.md` mục 7, 6.3) |
| companies | soft delete |
| company_locations | soft delete |
| company_contacts | soft delete |
| branches | `status=inactive` trước, soft delete khi cần; không hard delete nếu đã có `users`/`jobs`/`applications` |
| jobs | đóng trước (`status=closed`), soft delete khi cần; không hard delete nếu đã có application |
| applications | không hard delete |
| application_status_histories / application_contact_attempts / application_branch_histories / job_status_histories / job_branch_histories / job_verifications / export_logs | không xóa, không sửa (append-only) |
| application_appointments | không xóa; `status`/`outcome` cập nhật được sau khi tạo, `scheduled_at` không sửa (đổi lịch = tạo bản ghi mới) |
| application_notes | soft delete |
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
`applications.duplicate_reviewed_by`/`reopened_by`, `candidates.merged_by`/`anonymized_by`...)
— tài khoản hoặc cơ sở có thể bị khóa/ngừng hoạt động nhưng lịch sử vẫn phải còn nguyên. Cột
"người thực hiện" bắt buộc (không nullable, vd `verified_by`, `exported_by`, `contacted_by`,
`application_appointments.created_by`, `job_status_histories.changed_by`,
`job_branch_histories.changed_by`) dùng `RESTRICT` vì `users` không bao giờ bị hard delete
trong hệ thống này. `applications.owner_branch_id`, `application_branch_histories.to_branch_id`
và `job_branch_histories.to_branch_id` không nullable nên dùng `RESTRICT`.
