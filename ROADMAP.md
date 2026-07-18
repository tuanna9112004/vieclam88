# Roadmap — vieclam88

Lộ trình theo Giai đoạn 0–4 (Phase 1) + backlog Phase 2. Trạng thái tiến độ chi tiết nhất luôn
nằm ở `docs/PROJECT-STATUS.md` — file này chỉ giữ checklist theo giai đoạn, cập nhật `[x]` khi
hoàn thành, không viết thêm ghi chú tiến độ dài ở đây. Nguồn sự thật nghiệp vụ cho mọi giai
đoạn: `docs/CORE-FLOWS.md`.

**Không migration nào được viết trước khi Giai đoạn 0 hoàn thành** — xem điều kiện ở cuối mục
Giai đoạn 0.

## Giai đoạn 0 — Chốt plan/spec

- [x] Sáu Critical Business Flows (`docs/CORE-FLOWS.md`).
- [x] Phạm vi Phase 1 chính thức — không Lead, không assignment/claim, không Candidate Account,
      không Favorites (ADR-021, ADR-028).
- [x] Branch (cơ sở nội bộ), phân biệt với `company_locations`/`company_contacts` (ADR-015).
- [x] Duplicate Candidate Contract — 4 trường hợp tường minh, không fuzzy/AI (ADR-040) — mục 6.2.
- [x] Idempotency / Submission Token Lifecycle — session đa-token, diễn đạt lại quy tắc dùng
      token (ADR-041) — mục 3.
- [x] Transition matrix Application (bao gồm `closed → new` mở lại có kiểm soát) — mục 5.1.
- [x] Workflow cycle contract (chống dữ liệu chu kỳ cũ mở khóa trạng thái mới) — mục 5.4.
- [x] Reopen Application contract đầy đủ (7 điều kiện, reset dữ liệu dẫn xuất) — mục 5.5.
- [x] Contact Result enum chính thức (11 giá trị) — mục 5.2.
- [x] Appointment — quy tắc đổi lịch (tạo mới, không sửa đè) — mục 5.3.
- [x] Job transition matrix chính thức + Job Status History — mục 1.
- [x] Job Branch Contract + `job_branch_histories` (ADR-038) — mục 1.1.
- [x] Job Verification Scheduler Contract — chỉ cảnh báo, không tự pause mặc định (ADR-042) —
      mục 1.3.
- [x] Quy tắc hiển thị Job `closed`/`paused` (ADR-043) — mục 2.1.
- [x] Transfer Branch validation đầy đủ (Application) — mục 6.1.
- [x] Candidate Merge visibility ("merged family", chống vòng lặp, merge nhiều tầng) — mục 6.3.
- [x] Quyền theo cơ sở (Staff thuộc đúng cơ sở hoặc Admin) — mục 4, `.claude/rules/roles-business-rules.md`.
- [x] Khóa phiên bản MariaDB 11.4 LTS (ADR-039).
- [x] Sửa lỗi hướng quan hệ ERD (ADR-044).
- [x] Company & Company Location Quick Create Contract; `company_locations.administrative_unit_id`/
      `address_detail` đổi thành nullable (ADR-045) — mục 0.2, 0.3.
- [x] Job Draft Contract chính thức; `jobs.owner_branch_id` chốt NOT NULL từ lúc tạo (ADR-046) —
      mục 1.0, 1.1.
- [x] Job Publish Contract mở rộng: điều kiện địa điểm đủ rõ + đã xác minh còn tuyển, kèm admin
      override có kiểm soát (ADR-047) — mục 1.2.
- [x] Job Verification: tách `last_checked_at`/`last_verified_at` theo từng `result` (ADR-048) —
      mục 1.3.
- [x] Candidate Access Policy — 403 khi merged family không có Application thuộc cơ sở Staff
      (mục 6.4).
- [x] Dashboard Phase 1 và Application Filters Contract chính thức (mục 9).
- [x] Bootstrap Sequence + Initial Admin Contract (ADR-050) + Seeder Classification (ADR-051).
- [x] Validation tỉnh/KCN (ADR-052); quyền xóa/khôi phục Company Location/Contact (ADR-053); Job
      Branch Transfer chỉ `draft`/`paused` (ADR-054).
- [x] Enum Strategy — 5 enum phụ chuyển sang `varchar` + PHP backed enum, **không còn là
      migration blocker** (ADR-055).
- [x] PII schema tối thiểu cho `applications` — nullable + cơ chế anonymize đã chốt, tách khỏi
      retention (ADR-056).
- [x] Phase 1 Plan Baseline v1.0 — freeze chính thức (ADR-057, `docs/PHASE-1-SCOPE.md`,
      `docs/PHASE-2-BACKLOG.md`).
- [ ] Cài PHP 8.4, kiểm tra Composer, Node LTS, MariaDB 11.4.

**Điều kiện chuyển sang Giai đoạn 1:** sau ADR-055, **không còn migration blocker nào** trong
`docs/CORE-FLOWS.md` mục 8 — chỉ còn môi trường code chưa cài đặt (dòng trên). Toàn bộ phần kỹ
thuật (workflow cycle, reopen, transfer-branch, job branch, job draft/publish/verification
contract, quick create, merge, duplicate contract, submission token, scheduler, MariaDB version,
enum strategy, PII schema, bootstrap/seeder) đã chốt xong. Mục 8.1 (data retention, mask
`submission_snapshot`, `job_auto_pause_enabled`) vẫn **không** là điều kiện ở đây — xem "Phân
loại blocker" bên dưới (ADR-049).

Chưa tạo mã nguồn trong giai đoạn này.

## Phân loại blocker (ADR-049, cập nhật ADR-055)

Ba nhóm tách biệt — sau ADR-055 **không còn nhóm nào chặn việc viết migration Phase 1**:

### 1. Migration blockers (chặn Giai đoạn 1)

**Không còn mục nào.** 5 enum đề xuất (`docs/CORE-FLOWS.md` mục 8.2) không còn là migration
blocker sau ADR-055 (chuyển sang `varchar` + PHP backed enum — đổi giá trị sau này không cần
migration, nên không cần chờ công ty duyệt trước khi viết migration ban đầu). Các mục từng bị
treo ở vòng đặc tả trước — nullability `company_locations`, `jobs.owner_branch_id`,
`last_checked_at`/`last_verified_at`, validation tỉnh/KCN, quyền xóa Location/Contact, Job
Branch Transfer khi `closed` — đã được chốt (ADR-045, ADR-046, ADR-048, ADR-052, ADR-053,
ADR-054).

### 2. Go-live blockers (chặn vận hành thật, KHÔNG chặn migration/code)

- Thời hạn lưu dữ liệu ứng viên (`docs/CORE-FLOWS.md` mục 7.4).
- Mức mask cụ thể cho `submission_snapshot` khi anonymize (mục 7.2) — ảnh hưởng nội dung Action
  anonymize ở Giai đoạn 3/4, không ảnh hưởng schema (`candidates.anonymized_at`/`anonymized_by`,
  `applications.submission_snapshot` JSON đã đủ cho mọi phương án).
- Nội dung chính sách bảo mật/consent hiển thị qua `pages` (nội dung văn bản, không phải schema).
- Backup production, monitoring cơ bản (hạ tầng vận hành — Giai đoạn 4).

Phải xong trước khi go-live thật (cuối Giai đoạn 4), không phải trước Giai đoạn 1.

### 3. Phase 2 decisions (không thuộc Phase 1, không thiết kế schema trước)

- Có bật `job_auto_pause_enabled` hay không (`docs/CORE-FLOWS.md` mục 1.3) — mặc định `false`,
  không code path nào thực thi ở Phase 1.
- Toàn bộ danh sách Phase 2 ở mục "Phase 2 — ngoài phạm vi Phase 1" bên dưới (Lead, Assignment,
  Candidate Account, Favorites, Zalo API, CTV/hoa hồng, import hàng loạt, AI matching...).

Không có thời hạn, không chặn migration hay go-live Phase 1.

## Bootstrap Sequence — khởi tạo hệ thống lần đầu

Đây là **luồng triển khai/deployment**, không phải luồng nghiệp vụ thứ 7 (6 luồng cốt lõi vẫn ở
`docs/CORE-FLOWS.md`). Thứ tự bắt buộc khi đưa hệ thống lên môi trường mới (staging/production):

1. Cài đặt dự án (`composer install --no-dev`, `npm run build`, cấu hình `.env`).
2. `php artisan migrate` (schema production, **không** dùng `migrate:fresh` trên production).
3. `php artisan db:seed` — chỉ chạy **production-safe seed** (ADR-051): `settings`,
   `work_shifts`, `recruitment_sources`, `administrative_units`.
4. `php artisan app:create-admin` — tạo Admin đầu tiên (ADR-050), đổi mật khẩu ở lần đăng nhập
   đầu.
5. Import/xác nhận dữ liệu `administrative_units` đầy đủ (nếu seed ở bước 3 chưa phủ hết, bổ
   sung qua `/hr/don-vi-hanh-chinh`, admin-only).
6. Tạo `industrial_parks` thật qua `/hr/khu-cong-nghiep` (admin-only).
7. Admin tạo `branches` nội bộ thật qua `/hr/co-so` — dữ liệu vận hành thật, không phải seed.
8. Cấu hình `phone`/`zalo` cho từng `branches` — bắt buộc trước khi Job của cơ sở đó publish
   được (điều kiện publish, `docs/CORE-FLOWS.md` mục 1.2).
9. Admin tạo tài khoản `staff` thật và gán `branch_id` qua `/hr/nhan-vien` (tên route theo
   `docs/ROUTE-MAP.md` mục "HR admin").
10. Từ đây vận hành bình thường: Staff/Admin tạo `companies`/`company_locations`/`jobs` thật
    qua giao diện HR (Quick Create — mục 0.2, 0.3), không qua seeder/artisan.

Bước 1–4 thuộc Giai đoạn 1; bước 5–10 thực hiện được ngay sau khi Giai đoạn 2–3 (HR CRUD) hoàn
thành, không cần chờ hết Giai đoạn 4.

## Giai đoạn 1 — Database lõi

- [ ] Khởi tạo Laravel 13.x project, PHP 8.4.x (`.claude/rules/tech-stack.md`).
- [ ] Migration cho toàn bộ 27 bảng theo đúng `docs/DATABASE-DICTIONARY.md`: Branch, User
      (staff/admin, bắt buộc email, staff bắt buộc branch), Company (quick create — chỉ
      `name` bắt buộc)/location (`administrative_unit_id`/`address_detail` nullable,
      ADR-045)/contact, Job (`owner_branch_id` NOT NULL từ lúc tạo — ADR-046;
      `last_checked_at`/`last_verified_at` tách riêng — ADR-048) + `job_status_histories` +
      `job_branch_histories`, Candidate (không `user_id`, có `merge_reason`, `anonymized_by`),
      Application (snapshot, consent, `submission_token` NOT NULL, `workflow_cycle`,
      `reopened_at/by`), Status history (kèm `workflow_cycle`), Branch history, Contact log
      (kèm `workflow_cycle`), Appointment (kèm `workflow_cycle`), Note, Export log. **Không
      tạo** `lead_requests`, `favorites`, `application_assignment_histories`,
      `applications.assigned_to`, `applications.referral_code`, `candidates.user_id`, giá trị
      `candidate` trong `users.role` (ADR-021, ADR-028, ADR-029).
- [ ] PHP backed enum cho mọi cột trạng thái: `jobs.status`/`applications.stage` dùng DB
      `enum()` + backed enum khớp transition matrix; 5 cột trước đây `[đề xuất]`
      (`company_contacts.status`, `jobs.employment_type`, `jobs.close_reason`, `pages.status`,
      `settings.type`) dùng `varchar` + backed enum + validation, không dùng DB `enum()`
      (Enum Strategy, ADR-055).
- [ ] Model + relationship khớp `docs/ERD.md`.
- [ ] Factory cho toàn bộ 27 bảng.
- [ ] **Production-safe seeder** (chạy trên mọi môi trường — ADR-051): `settings`, `work_shifts`,
      `recruitment_sources`, `administrative_units` (dữ liệu hành chính thật, không phải mẫu).
- [ ] **Demo/test seeder** (chỉ `local`/`testing`, không đăng ký trong seeder chạy mặc định
      production — ADR-051): Branch mẫu (≥ 2 cơ sở để test phân quyền), Staff mẫu, Company mẫu,
      Job mẫu, Candidate/Application mẫu.
- [ ] Console command `php artisan app:create-admin` (bootstrap Admin đầu tiên, ADR-050) — tách
      biệt hoàn toàn khỏi seeder.
- [ ] Database test: foreign key, unique constraint (`submission_token`, `candidate_id+job_id`,
      `job_locations` primary), soft delete, Job/Application transition matrix, workflow cycle
      scoping, duplicate contract, merged family, branch scoping, validation tỉnh/KCN khớp nhau
      (`company_locations.administrative_unit_id` = `industrial_parks.administrative_unit_id`
      khi có `industrial_park_id` — ADR-052).

**Điều kiện hoàn thành:**

```bash
php artisan migrate:fresh --seed
php artisan test
```

## Giai đoạn 2 — Website public và Job

- [ ] Authentication staff/admin (`/hr/dang-nhap`), `users.branch_id` bắt buộc khi tạo staff.
- [ ] Company CRUD (Quick Create — chỉ `name` bắt buộc, mục 0.2), Location CRUD (Quick Create —
      chỉ `name` bắt buộc, mục 0.3), Contact CRUD, Branch CRUD.
- [ ] Job CRUD, Job Draft Contract (mục 1.0 — cho phép thiếu company/location/lương chưa hoàn
      thiện), `owner_branch_id` bắt buộc ngay lúc tạo (mục 1.1), điều kiện publish đầy đủ 11
      mục gồm địa điểm đủ rõ + đã xác minh còn tuyển (mục 1.2).
- [ ] Publish/pause/reopen (`paused → published`, re-check điều kiện publish)/close job qua
      `ChangeJobStatusAction`, ghi `job_status_histories`; nhân bản job.
- [ ] Đổi cơ sở Job (`ChangeJobBranchAction`, chỉ admin, chỉ khi không `published`), ghi
      `job_branch_histories` (mục 1.1).
- [ ] Danh sách và chi tiết Job public (`/viec-lam`), tìm kiếm/lọc (Luồng 2), quy tắc hiển thị
      `closed`/`paused` (mục 2.1).
- [ ] CTA Gọi/Zalo trên Job — luôn dùng contact của `owner_branch_id` (ADR-023), kể cả trên Job
      `closed`/`paused`.
- [ ] Job verification (`job_verifications`) + Job Verification Scheduler (`last_checked_at` vs
      `last_verified_at`, cảnh báo 7/14 ngày, không tự pause — mục 1.3); gắn với điều kiện
      publish (mục 1.2 điều kiện 11).
- [ ] Form ứng tuyển guest (`ApplicationController@store`), toàn bộ transaction Luồng 3 gồm
      Submission Token Lifecycle (session đa-token, mục 3), Duplicate Candidate Contract 4
      trường hợp (mục 6.2).

## Giai đoạn 3 — HR xử lý hồ sơ

- [ ] Danh sách hồ sơ theo cơ sở (Luồng 4).
- [ ] Chi tiết hồ sơ — bất kỳ staff thuộc đúng cơ sở đều xử lý được, không có "nhận xử lý"/"gán
      nhân viên" (ADR-021).
- [ ] Contact Log (`application_contact_attempts`, kèm `workflow_cycle`).
- [ ] Appointment — callback và interview, tạo/cập nhật kết quả, đổi lịch = hủy cũ + tạo mới,
      kèm `workflow_cycle` (Luồng 5 mục 5.3).
- [ ] Đổi stage qua `ChangeApplicationStageAction`, validate transition matrix + workflow cycle
      scoping (Luồng 5 mục 5.1, 5.4).
- [ ] Reopen Application (`closed → new`) qua đúng contract mục 5.5 (điều kiện, reset dữ liệu).
- [ ] Close reason bắt buộc khi đóng hồ sơ.
- [ ] Chuyển cơ sở ngoại lệ theo đúng contract mục 6.1 (validation đầy đủ), chỉ admin.
- [ ] Trang chi tiết Candidate + merged family (mục 6.3) + Candidate Access Policy — 403 nếu
      family không có Application thuộc cơ sở Staff (mục 6.4); merge candidate qua
      `hr.candidates.merge`, chỉ admin.
- [ ] Bộ lọc `hr.applications.index` đầy đủ (mục 9.2).

## Giai đoạn 4 — Hoàn thiện

- [ ] Dashboard cơ bản — KPI Staff/Admin đã chốt ở `docs/CORE-FLOWS.md` mục 9.1.
- [ ] Export CSV + `export_logs`.
- [ ] Security review, bao gồm test 403 xem chéo cơ sở và test mass-assignment
      `owner_branch_id`/`stage`.
- [ ] SEO, Responsive.
- [ ] Feature test đầy đủ theo `docs/ACCEPTANCE-CRITERIA.md`.
- [ ] Backup.
- [ ] Cron/Scheduler xác nhận còn tuyển (`.claude/rules/roles-business-rules.md`).
- [ ] SSL, Log rotation.
- [ ] Deploy VPS, cấu hình path `/hr` theo `.claude/rules/tech-stack.md`.

## Phase 2 — ngoài phạm vi Phase 1 (không code, không thiết kế trước ở Phase 1)

- Lead (mọi kênh: điện thoại, Zalo, form "yêu cầu tư vấn" trên website); chuyển đổi Lead thành
  Application.
- Assignment: "Nhận xử lý" (claim), gán nhân viên (assign), tự động phân công, round-robin,
  vai trò "trưởng cơ sở" phân công, `application_assignment_histories`.
- Candidate Account: đăng ký/đăng nhập/`/tai-khoan` cho ứng viên, dashboard, theo dõi trạng
  thái hồ sơ qua tài khoản, `candidates.user_id`, giá trị `candidate` trong `users.role`
  (ADR-028).
- Favorites.
- Tích hợp Zalo API; tự động gọi/gửi tin nhắn.
- Cộng tác viên, hoa hồng, referral (`applications.referral_code` thêm lại bằng migration mới
  khi module này được duyệt — ADR-029).
- Import dữ liệu hàng loạt (`actor_type = import` thêm lại khi có luồng import thật).
- AI matching, CRM đa kênh.

## Ghi chú áp dụng lộ trình

- Có phụ thuộc thứ tự: Giai đoạn 1 (schema) phải xong trước 2–4; Giai đoạn 2 (job/public) nên
  xong trước Giai đoạn 3 (application cần job tồn tại).
- Mỗi giai đoạn nên là 1 hoặc vài session riêng.
- Cập nhật `docs/PROJECT-STATUS.md` sau khi hoàn thành mỗi giai đoạn.
- Không mở rộng sang các hạng mục "Ngoài phạm vi" (`.claude/rules/scope-standards.md`) hoặc
  Phase 2 trong lộ trình này.
