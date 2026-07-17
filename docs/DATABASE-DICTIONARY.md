# Database Dictionary — vieclam88 (Phase 1)

Mô tả đầy đủ 25 bảng Phase 1. Xem quan hệ tổng quan ở `docs/ERD.md`, nguyên tắc thiết kế ở
`.claude/rules/data-model.md`, quyết định kiến trúc ở `docs/DECISIONS.md`.

## Quy ước chung

- Mọi bảng dùng `id` dạng `bigint unsigned auto_increment` làm khóa chính, trừ khi ghi chú
  khác (vd `job_work_shifts` dùng composite key).
- `created_at`/`updated_at`: `timestamp nullable` (chuẩn Laravel), trừ bảng lịch sử
  (`*_histories`, `*_attempts`, `job_verifications`, `export_logs`) chỉ có `created_at`,
  không có `updated_at` — vì không được sửa sau khi tạo.
- `deleted_at`: `timestamp nullable` (Laravel `SoftDeletes`), chỉ khai báo ở bảng có soft
  delete (xem "Chính sách xóa" cuối file).
- Tiền VND luôn `bigint unsigned`, không dùng `FLOAT`/`DOUBLE` (`.claude/rules/data-model.md`).
- Một số giá trị enum đề xuất (`employment_type`, `jobs.close_reason`, `pages.status`,
  `settings.type`) chưa được đặc tả chính xác ở yêu cầu gốc — đánh dấu **[đề xuất]**, cần xác
  nhận nghiệp vụ thực tế trước khi viết migration chính thức.

---

## 9.1. `users`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | Khóa chính |
| role | enum(candidate,staff,admin) | — | không | — | ✓ | — | — | — | Vai trò tài khoản; `guest` không phải giá trị hợp lệ |
| name | string(150) | — | không | — | — | — | — | — | Tên hiển thị |
| email | string(191) | — | có | null | — | ✓ (khi có giá trị) | — | — | Dùng đăng nhập cho staff/admin, tùy chọn cho candidate |
| phone_normalized | string(20) | — | có | null | — | ✓ (khi có giá trị) | — | — | Số điện thoại chuẩn hóa, có thể dùng đăng nhập cho candidate |
| password | string(255) | — | không | — | — | — | — | — | Hash bcrypt/argon2 |
| status | enum(active,locked) | — | không | active | ✓ | — | — | — | Khóa tài khoản thay vì xóa |
| last_login_at | timestamp | — | có | null | — | — | — | — | |
| password_changed_at | timestamp | — | có | null | — | — | — | — | |
| remember_token | string(100) | — | có | null | — | — | — | — | Laravel remember-me |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

**Chính sách xóa:** không hard delete. Ngừng sử dụng → `status = locked`.

---

## 9.2. `candidates`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| public_id | string(26) | — | không | — | — | ✓ | — | — | ULID public-facing, không lộ ID tuần tự |
| user_id | bigint | ✓ | có | null | ✓ | ✓ (khi có giá trị) | users.id | SET NULL | Candidate có thể chưa có tài khoản |
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
| merged_into_candidate_id | bigint | ✓ | có | null | — | — | candidates.id (self) | SET NULL | |
| merged_at | timestamp | — | có | null | — | — | — | — | |
| merged_by | bigint | ✓ | có | null | — | — | users.id | SET NULL | |
| anonymized_at | timestamp | — | có | null | — | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |
| deleted_at | timestamp | — | có | null | — | — | — | — | Soft delete |

**Chính sách xóa:** soft delete, merge, hoặc anonymize. Candidate `status = merged` không được
dùng để tạo application mới (kiểm tra ở Service/Form Request, không chỉ ở DB). Không hard
delete candidate đang có application.

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
kiểm tra thêm họ tên và ngày sinh (xem `.claude/rules/data-model.md`).

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

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| company_id | bigint | ✓ | không | — | ✓ | — | companies.id | RESTRICT | |
| administrative_unit_id | bigint | ✓ | không | — | ✓ | — | administrative_units.id | RESTRICT | |
| industrial_park_id | bigint | ✓ | có | null | ✓ | — | industrial_parks.id | RESTRICT | |
| name | string(150) | — | không | — | — | — | — | — | Tên nhà máy/chi nhánh |
| address_detail | string(255) | — | không | — | — | — | — | — | |
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
| is_public | boolean | — | không | false | — | — | — | — | Có hiển thị công khai không |
| status | enum(active,inactive) **[đề xuất]** | — | không | active | — | — | — | — | |
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
| code | string(30) | — | không | — | — | ✓ | — | — | Mã nội bộ, HR nhập tay hoặc tự sinh |
| title | string(200) | — | không | — | — | — | — | — | |
| slug | string(220) | — | không | — | ✓ | ✓ | — | — | |
| employment_type | enum(full_time,part_time,seasonal,temporary) **[đề xuất]** | — | không | full_time | — | — | — | — | Xác nhận giá trị thực tế trước khi migrate |
| quantity | smallint | ✓ | có | null | — | — | — | — | Số lượng tuyển |
| gender_requirement | enum(male,female,any) | — | có | null | — | — | — | — | |
| min_age | tinyint | ✓ | có | null | — | — | — | — | |
| max_age | tinyint | ✓ | có | null | — | — | — | — | |
| education_requirement | string(255) | — | có | null | — | — | — | — | |
| experience_requirement | string(255) | — | có | null | — | — | — | — | |
| salary_min | bigint | ✓ | có | null | — | — | — | — | VND, không dùng FLOAT |
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
| status | enum(draft,published,paused,closed) | — | không | draft | ✓ | — | — | — | |
| published_at | timestamp | — | có | null | — | — | — | — | |
| expires_at | timestamp | — | có | null | ✓ | — | — | — | |
| closed_at | timestamp | — | có | null | — | — | — | — | |
| close_reason | enum(filled,cancelled,expired,other) **[đề xuất]** | — | có | null | — | — | — | — | |
| last_verified_at | timestamp | — | có | null | — | — | — | — | |
| created_by | bigint | ✓ | không | — | — | — | users.id | RESTRICT | |
| updated_by | bigint | ✓ | có | null | — | — | users.id | SET NULL | |
| deleted_by | bigint | ✓ | có | null | — | — | users.id | SET NULL | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |
| deleted_at | timestamp | — | có | null | — | — | — | — | Soft delete |

Index bổ sung: `(company_id, status)`.

**Chính sách xóa:** đóng job trước (`status = closed`), soft delete khi cần. **Không hard
delete job đã có application.** Một job là một đợt tuyển dụng — không tái sử dụng job đã
đóng cho đợt mới, dùng chức năng nhân bản job (xem ADR-008).

---

## 9.10. `job_locations`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| job_id | bigint | ✓ | không | — | ✓ | ✓ (composite: job_id+company_location_id) | jobs.id | CASCADE | |
| company_location_id | bigint | ✓ | không | — | ✓ | (composite) | company_locations.id | RESTRICT | |
| is_primary | boolean | — | không | false | — | — | — | — | Mỗi job phải có đúng 1 primary (enforce ở Service) |
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
`jobs.status` nếu cần, trong 1 transaction (xem "Verify job" trong `.claude/rules/data-model.md`).

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
`staff`→staff, `referral`→referral, `other`→other.

**Nguồn thuộc `applications`/`lead_requests`, không thuộc `candidates`** (1 candidate có thể
ứng tuyển qua nhiều nguồn khác nhau ở các lần khác nhau).

---

## 9.15. `applications`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| public_id | string(26) | — | không | — | — | ✓ | — | — | |
| candidate_id | bigint | ✓ | không | — | ✓ | ✓ (composite: candidate_id+job_id) | candidates.id | RESTRICT | |
| job_id | bigint | ✓ | không | — | ✓ | (composite) | jobs.id | RESTRICT | |
| source_id | bigint | ✓ | có | null | ✓ | — | recruitment_sources.id | SET NULL | |
| assigned_to | bigint | ✓ | có | null | ✓ | — | users.id | SET NULL | |
| stage | enum(new,contacting,consulted,interview_scheduled,interviewed,waiting_start,started,closed) | — | không | new | ✓ | — | — | — | |
| stage_changed_at | timestamp | — | không | now | — | — | — | — | |
| close_reason | enum(unreachable,candidate_cancelled,employer_cancelled,unsuitable,duplicate,job_closed,other) | — | có | null | — | — | — | — | Bắt buộc khi `stage = closed` (validate ở Form Request) |
| submitted_full_name | string(150) | — | không | — | — | — | — | — | |
| submitted_phone | string(20) | — | không | — | — | — | — | — | |
| submitted_phone_normalized | string(20) | — | không | — | ✓ | — | — | — | |
| submission_snapshot | json | — | không | — | — | — | — | — | Lịch sử, không dùng lọc/báo cáo |
| job_snapshot | json | — | không | — | — | — | — | — | Lịch sử, không dùng lọc/báo cáo |
| source_detail | string(255) | — | có | null | — | — | — | — | |
| referral_code | string(30) | — | có | null | — | — | — | — | Chưa có FK — module CTV bổ sung sau (ADR-012) |
| utm_source | string(100) | — | có | null | — | — | — | — | |
| utm_medium | string(100) | — | có | null | — | — | — | — | |
| utm_campaign | string(100) | — | có | null | — | — | — | — | |
| landing_url | string(500) | — | có | null | — | — | — | — | |
| consent_version | string(20) | — | không | — | — | — | — | — | |
| consent_text_hash | string(64) | — | không | — | — | — | — | — | SHA-256 nội dung consent tại thời điểm gửi |
| consented_at | timestamp | — | không | — | — | — | — | — | |
| consent_ip | string(45) | — | có | null | — | — | — | — | Hỗ trợ IPv6 |
| consent_user_agent | string(255) | — | có | null | — | — | — | — | |
| expected_start_at | date | — | có | null | — | — | — | — | |
| started_at | timestamp | — | có | null | — | — | — | — | |
| closed_at | timestamp | — | có | null | — | — | — | — | |
| created_at | timestamp | — | có | now | ✓ | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

Index bắt buộc theo yêu cầu gốc: `(stage, created_at)`, `(assigned_to, stage, updated_at)`,
`(job_id, stage, created_at)`, `(source_id, created_at)`, `(candidate_id, created_at)`.

**Chính sách xóa:** không hard delete.

---

## 9.16. `application_status_histories`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| application_id | bigint | ✓ | không | — | ✓ | — | applications.id | RESTRICT | |
| from_stage | enum (như `applications.stage`) | — | có | null | — | — | — | — | |
| to_stage | enum (như `applications.stage`) | — | không | — | — | — | — | — | |
| close_reason | enum (như `applications.close_reason`) | — | có | null | — | — | — | — | |
| changed_by | bigint | ✓ | có | null | — | — | users.id | SET NULL | |
| actor_type | enum(user,system,import) | — | không | user | — | — | — | — | |
| note | string(255) | — | có | null | — | — | — | — | |
| metadata | json | — | có | null | — | — | — | — | |
| created_at | timestamp | — | có | now | ✓ | — | — | — | Không có `updated_at`, append-only |

**Quy tắc:** không xóa hoặc sửa lịch sử sau khi tạo.

---

## 9.17. `application_assignment_histories`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| application_id | bigint | ✓ | không | — | ✓ | — | applications.id | RESTRICT | |
| from_user_id | bigint | ✓ | có | null | — | — | users.id | SET NULL | |
| to_user_id | bigint | ✓ | có | null | — | — | users.id | SET NULL | |
| assigned_by | bigint | ✓ | không | — | — | — | users.id | RESTRICT | |
| reason | string(255) | — | có | null | — | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | Append-only |

**Quy tắc:** mỗi lần phân công lại (kể cả tự nhận) phải ghi 1 bản ghi history.

---

## 9.18. `application_contact_attempts`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| application_id | bigint | ✓ | không | — | ✓ | — | applications.id | RESTRICT | |
| contacted_by | bigint | ✓ | không | — | — | — | users.id | RESTRICT | |
| channel | enum(phone,zalo,sms,email,other) | — | không | — | — | — | — | — | |
| result | enum(answered,no_answer,busy,wrong_number,callback_requested,message_sent,other) | — | không | — | — | — | — | — | |
| contacted_at | timestamp | — | không | now | — | — | — | — | |
| note | string(255) | — | có | null | — | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | Append-only |

**Quy tắc:** kết quả cuộc gọi ghi ở đây, **không** ghi đè vào `applications.stage` (xem
ADR-009).

---

## 9.19. `application_notes`

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
Hiển thị dấu hiệu "đã chỉnh sửa" khi `edited_at` khác null.

---

## 9.20. `lead_requests`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| public_id | string(26) | — | không | — | — | ✓ | — | — | |
| candidate_id | bigint | ✓ | có | null | ✓ | — | candidates.id | SET NULL | |
| requested_job_id | bigint | ✓ | có | null | — | — | jobs.id | SET NULL | |
| preferred_administrative_unit_id | bigint | ✓ | có | null | — | — | administrative_units.id | SET NULL | |
| preferred_industrial_park_id | bigint | ✓ | có | null | — | — | industrial_parks.id | SET NULL | |
| source_id | bigint | ✓ | có | null | — | — | recruitment_sources.id | SET NULL | |
| assigned_to | bigint | ✓ | có | null | ✓ | — | users.id | SET NULL | |
| status | enum(new,contacting,converted,closed) | — | không | new | ✓ | — | — | — | |
| full_name | string(150) | — | không | — | — | — | — | — | |
| phone | string(20) | — | không | — | — | — | — | — | |
| phone_normalized | string(20) | — | không | — | ✓ | — | — | — | |
| message | string(500) | — | có | null | — | — | — | — | |
| consent_version | string(20) | — | không | — | — | — | — | — | |
| consent_text_hash | string(64) | — | không | — | — | — | — | — | |
| consented_at | timestamp | — | không | — | — | — | — | — | |
| consent_ip | string(45) | — | có | null | — | — | — | — | |
| consent_user_agent | string(255) | — | có | null | — | — | — | — | |
| converted_application_id | bigint | ✓ | có | null | — | ✓ (khi có giá trị) | applications.id | SET NULL | |
| converted_at | timestamp | — | có | null | — | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

**Quy tắc:** không chuyển đổi 1 lead thành application 2 lần (kiểm tra `converted_application_id`
đã có giá trị trước khi chuyển).

---

## 9.21. `favorites`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| user_id | bigint | ✓ | không | — | ✓ | ✓ (composite: user_id+job_id) | users.id | CASCADE | |
| job_id | bigint | ✓ | không | — | ✓ | (composite) | jobs.id | CASCADE | |
| created_at | timestamp | — | có | now | — | — | — | — | |

---

## 9.22. `export_logs`

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

## 9.23. `pages`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| title | string(200) | — | không | — | — | — | — | — | |
| slug | string(220) | — | không | — | ✓ | ✓ | — | — | |
| content | text (long) | — | không | — | — | — | — | — | |
| meta_title | string(255) | — | có | null | — | — | — | — | |
| meta_description | string(320) | — | có | null | — | — | — | — | |
| status | enum(draft,published) **[đề xuất]** | — | không | draft | ✓ | — | — | — | |
| published_at | timestamp | — | có | null | — | — | — | — | |
| created_by | bigint | ✓ | không | — | — | — | users.id | RESTRICT | |
| updated_by | bigint | ✓ | có | null | — | — | users.id | SET NULL | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

---

## 9.24. `faqs`

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

## 9.25. `settings`

| Column | Type | Unsigned | Nullable | Default | Index | Unique | Foreign key | On delete | Description |
|---|---|---|---|---|---|---|---|---|---|
| id | bigint | ✓ | không | auto_increment | PK | ✓ | — | — | |
| key | string(100) | — | không | — | — | ✓ | — | — | |
| value | text | — | có | null | — | — | — | — | |
| type | enum(string,number,boolean,json) **[đề xuất]** | — | không | string | — | — | — | — | Dùng để ép kiểu khi đọc |
| group_name | string(50) | — | không | general | ✓ | — | — | — | |
| is_public | boolean | — | không | false | — | — | — | — | |
| created_at | timestamp | — | có | now | — | — | — | — | |
| updated_at | timestamp | — | có | now | — | — | — | — | |

**Quy tắc:** không lưu secret trong `settings` nếu không có mã hóa phù hợp.

---

## Chính sách xóa — tổng hợp

| Bảng | Chính sách |
|---|---|
| users | khóa bằng `status` |
| candidates | soft delete, merge, hoặc anonymize |
| companies | soft delete |
| company_locations | soft delete |
| company_contacts | soft delete |
| jobs | đóng trước (`status=closed`), soft delete khi cần; không hard delete nếu đã có application |
| applications | không hard delete |
| application_status_histories / application_assignment_histories / application_contact_attempts / job_verifications / export_logs | không xóa, không sửa (append-only) |
| application_notes | soft delete |
| administrative_units / industrial_parks / work_shifts / recruitment_sources | `is_active = false`, không hard delete khi đã tham chiếu |
| favorites / job_locations / job_work_shifts | có thể cascade delete (pivot/quan hệ phụ) |

**Không dùng cascade delete cho:** `companies → jobs`, `jobs → applications`,
`candidates → applications`, `applications → *_histories`. Dùng `RESTRICT` — xử lý nghiệp vụ
(đóng/soft-delete) trước khi có thể xóa, không để DB tự động xóa dây chuyền dữ liệu nghiệp vụ.

**Dùng `SET NULL`** cho quan hệ "người thực hiện" khi cột đã nullable (`assigned_to`,
`changed_by`, `from_user_id`/`to_user_id`, `updated_by`...) — tài khoản có thể bị khóa nhưng
lịch sử vẫn phải còn nguyên. Cột "người thực hiện" bắt buộc (không nullable, vd
`assigned_by`, `verified_by`, `exported_by`, `contacted_by`) dùng `RESTRICT` vì `users` không
bao giờ bị hard delete trong hệ thống này.
