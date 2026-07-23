# Kiến trúc và nền tảng

> Nguồn ADR theo chủ đề. Không đổi mã ADR; ADR mới phải được thêm vào file chủ đề phù hợp và cập nhật `docs/decisions/INDEX.md`.

<a id="adr-001"></a>

## ADR-001 — Laravel monolith, một codebase một database

**Quyết định:** Một codebase Laravel + một database MariaDB dùng chung cho cả website công
khai và khu vực HR. Không tách microservice.

**Lý do:** Quy mô nghiệp vụ (1 công ty cung ứng nhân sự) không cần độ phức tạp của
microservice. Route, controller, view, middleware tách riêng theo khu vực trong cùng
codebase là đủ.

<a id="adr-002"></a>

## ADR-002 — HR dùng path `/hr`, không dùng subdomain

**Quyết định:** Khu vực HR truy cập qua `https://tencongty.vn/hr` (production) và
`http://localhost/vieclam88/public/hr` (local). Không dùng `hr.tencongty.vn` trong Phase 1.

**Lý do:** Subdomain cần cấu hình DNS/vhost riêng, không cần thiết cho quy mô hiện tại. Path
đơn giản hơn khi deploy, không cần SSL certificate riêng cho subdomain.

<a id="adr-003"></a>

## ADR-003 — MariaDB, không dùng SQLite làm database chính

**Quyết định:** MariaDB cho cả local và production.

**Lý do:** SQLite không phù hợp cho concurrent write của nhiều nhân viên HR thao tác đồng
thời, và không khớp môi trường production (VPS chạy MariaDB/MySQL thực tế).

<a id="adr-004"></a>

## ADR-004 — PHP 8.4.x, Laravel 13.x, khóa major version

**Quyết định:** PHP 8.4.x, Laravel 13.x. Khóa major version Laravel trong `composer.json`
(`"laravel/framework": "^13.0"`) ngay khi khởi tạo project.

**Lý do:** Production chạy VPS riêng, không bị giới hạn phiên bản như shared hosting. Khóa
major version tránh nâng cấp đột ngột phá vỡ code khi chạy `composer update`.

<a id="adr-010"></a>

## ADR-010 — Dùng `administrative_units` phân cấp, không lưu chuỗi tự do

**Quyết định:** Địa điểm hành chính (tỉnh/thành phố/xã/phường) lưu trong bảng
`administrative_units` phân cấp (self-referencing `parent_id`), có `type` phân loại và
`is_active`/`valid_from`/`valid_to` để giữ lịch sử khi địa giới hành chính thay đổi. Không
lưu tên huyện/xã bằng chuỗi tự do trong các bảng khác.

**Lý do:** Việt Nam đang trong giai đoạn sáp nhập đơn vị hành chính (bỏ cấp huyện, sáp nhập
xã/phường) — lưu chuỗi tự do sẽ không nhất quán và không lọc được chính xác. Cấu trúc phân
cấp cho phép lọc theo tỉnh mà không cần liệt kê hết các xã/phường con.

<a id="adr-014"></a>

## ADR-014 — `users.role` dạng enum đơn giản, chưa xây RBAC

**Status:** Partially superseded by ADR-028 — giá trị `candidate` bị bỏ khỏi `users.role`
trong Phase 1 (`users` Phase 1 chỉ phục vụ staff/admin). Phần "1 cột enum, không xây RBAC nhiều
bảng" vẫn là quyết định hiện hành, chỉ còn 2 giá trị `staff`/`admin`.

**Quyết định:** Phase 1 dùng 1 cột `users.role` ∈ {`candidate`, `staff`, `admin`}. Không tạo
bảng `roles`, `permissions`, `role_user`, `permission_role`. `guest` không phải giá trị của
`users.role` — guest là người chưa có tài khoản.

**Lý do:** RBAC đầy đủ (nhiều role, nhiều permission tùy biến) chỉ cần thiết khi có nhiều
loại nhân viên với quyền khác nhau đáng kể. Với 2 role nội bộ (staff, admin), 1 cột enum +
Policy đủ để kiểm soát quyền, tránh xây hệ thống thừa không ai cấu hình.

<a id="adr-039"></a>

## ADR-039 — Khóa phiên bản MariaDB 11.4 LTS

**Quyết định:** Dùng thống nhất **MariaDB 11.4 LTS** (phát hành 05/2024, hỗ trợ tới 05/2029)
cho local development, automated test, staging và VPS production — không ghi chung chung
"MariaDB" nữa.

**Lý do:** Schema Phase 1 phụ thuộc các tính năng: generated column + unique index trên
generated column (`job_locations.primary_flag_job_id`), `CHECK` constraint
(`jobs.salary_min<=salary_max`, `min_age<=max_age`), `WITH RECURSIVE` (truy vấn "merged
family"), JSON (`submission_snapshot`/`job_snapshot`/`filters`), transaction + row locking
(`lockForUpdate` xuyên suốt các Action). Toàn bộ tính năng này đã có từ MariaDB 10.2, nhưng
10.2 đã hết hỗ trợ (EOL). Giữa 2 bản LTS còn được hỗ trợ dài (10.11 LTS tới 02/2028, 11.4 LTS
tới 05/2029), chọn 11.4 vì có thời gian hỗ trợ dài hơn kể từ thời điểm dự án khởi động, và dự
án đã chọn PHP 8.4/Laravel 13.x (bản mới) nên nhất quán khi cũng chọn bản MariaDB mới thay vì
bản sắp hết hỗ trợ. VPS riêng (ADR-004) nên không bị giới hạn bởi shared hosting.

<a id="adr-050"></a>

## ADR-050 — Initial Admin Bootstrap Contract (`php artisan app:create-admin`)

**Quyết định:** Phương án chính thức để tạo tài khoản Admin đầu tiên trên production là lệnh
Artisan tương tác **`php artisan app:create-admin`** (chưa viết code — chỉ chốt hợp đồng hành
vi). Lệnh:

- Hỏi `name`, `email`, `password` (hoặc nhận qua flag `--name=`/`--email=`/`--password=` cho
  chạy không tương tác trong script deploy), không đọc từ biến môi trường mặc định sẵn và không
  có giá trị mặc định cứng cho email/mật khẩu.
- Validate `email` unique trong `users` trước khi tạo (dùng lại rule unique như Form Request
  tạo staff).
- Hash mật khẩu bằng cơ chế hash mặc định của Laravel (bcrypt/argon2 theo `config/hashing.php`)
  — không bao giờ lưu plaintext.
- Tạo `users` với `role = admin`, `status = active`, `branch_id = null` (admin không thuộc cơ
  sở — nhất quán với `docs/DATABASE-DICTIONARY.md` mục 9.1).
- Đặt `users.password_changed_at = null` sau khi tạo — dùng làm cờ "bắt buộc đổi mật khẩu ở lần
  đăng nhập đầu tiên" (middleware kiểm tra cột này, không thêm cột boolean riêng).
- Không chạy được nếu **đã tồn tại** ít nhất 1 user `role = admin` trừ khi truyền `--force` —
  chống tạo admin thừa do chạy nhầm lệnh 2 lần trên production.
- Không phải Seeder (`DatabaseSeeder` **không** gọi lệnh này và **không** tạo admin thật) — tách
  bạch hoàn toàn khỏi seeding (ADR-051).

**Lý do:** Seeder demo (`AdminSeeder`/`UserSeeder` kiểu cũ) thường hardcode email/mật khẩu mẫu
(`admin@example.com`/`password`) — nếu vô tình chạy trên production sẽ tạo tài khoản Admin với
mật khẩu ai cũng đoán được, một lỗ hổng bảo mật nghiêm trọng. Lệnh Artisan tương tác/one-shot
buộc người vận hành **tự nhập** thông tin thật tại thời điểm triển khai, không có gì để hardcode
hay commit nhầm vào Git. Bắt buộc đổi mật khẩu sau lần đăng nhập đầu giảm rủi ro nếu mật khẩu ban
đầu bị lộ qua kênh truyền đạt (chat, email) trước khi Admin đổi.

<a id="adr-051"></a>

## ADR-051 — Seeder Classification: production-safe / demo-test / dữ liệu vận hành thật

**Quyết định:** Tách 3 loại dữ liệu khởi tạo, không được lẫn vào nhau trong cùng 1 seeder chạy
mặc định:

1. **Production-safe seed** (chạy trên **mọi** môi trường, kể cả production, qua
   `php artisan db:seed`): `settings` (3 key scheduler ở `docs/CORE-FLOWS.md` mục 1.3),
   `work_shifts` (7 giá trị chuẩn), `recruitment_sources` (6 giá trị chuẩn),
   `administrative_units` (import dữ liệu hành chính thật của Việt Nam — không phải dữ liệu
   giả). Đây là danh mục hệ thống bắt buộc phải có để ứng dụng chạy đúng, không chứa dữ liệu
   nghiệp vụ giả (không company/job/candidate giả).
2. **Demo/test seed** (chỉ chạy khi `app.env` ∈ {`local`, `testing`}, hoặc qua factory trong
   test — **không** đăng ký trong `DatabaseSeeder` chạy mặc định production): Branch mẫu, Staff
   mẫu, Company mẫu, Job mẫu, Candidate/Application mẫu. `DatabaseSeeder::run()` phải tự kiểm
   tra `app()->environment('production')` trước khi gọi các seeder demo này, hoặc tách hẳn
   thành 1 seeder riêng (`DemoDataSeeder`) không nằm trong danh sách seed mặc định của lệnh
   deploy production.
3. **Dữ liệu vận hành thật** (không phải seeder — tạo qua giao diện HR sau khi hệ thống chạy):
   Branch thật, Staff thật, Company/Location/Job thật — do Admin hoặc Staff có quyền tạo qua
   route HR bình thường (`docs/ROUTE-MAP.md`), không qua Artisan/seeder.

**Lý do:** Sửa một khoảng trống cụ thể trong `ROADMAP.md` Giai đoạn 1 (bản trước gộp chung
"Seeder (`branches`, `work_shifts`, `recruitment_sources`, `administrative_units` dữ liệu mẫu —
cần ít nhất 2 cơ sở mẫu...)" — cụm "branches ... dữ liệu mẫu" là demo data bị trộn lẫn cùng câu
với danh mục hệ thống thật, dễ khiến quy trình deploy production chạy nhầm seeder tạo Branch giả
kèm Staff giả có mật khẩu biết trước. Tách rõ 3 nhóm loại bỏ rủi ro này mà không đổi bất kỳ bảng
nào — chỉ ảnh hưởng cách tổ chức class Seeder và quy trình deploy.

<a id="adr-055"></a>

## ADR-055 — Enum Strategy: VARCHAR + PHP backed enum cho enum phụ chưa chốt, gỡ bỏ migration blocker

**Quyết định:** 5 cột từng đánh dấu **[đề xuất]** (`company_contacts.status`,
`jobs.employment_type`, `jobs.close_reason`, `pages.status`, `settings.type`) đổi kiểu lưu trữ
từ DB `enum(...)` sang **`varchar`** (độ dài theo giá trị dài nhất + biên độ — vd
`varchar(30)`), ràng buộc giá trị bằng **PHP backed enum** (`App\Enums\...`) + Form
Request/Domain validation (`Rule::in(...)`), **không** còn dùng DB `enum()` cho các cột này.
Danh sách giá trị đề xuất giữ nguyên làm giá trị mặc định của backed enum — công ty vẫn có thể
góp ý/đổi sau, nhưng thay đổi đó chỉ cần sửa class enum + validation, **không cần viết migration
mới** (khác với DB `enum()`, đổi giá trị bắt buộc `ALTER TABLE`). Các trạng thái cốt lõi
**`jobs.status`** và **`applications.stage`** (đã chốt transition matrix chặt ở mục 1.2, 5.1)
**giữ nguyên DB `enum()`** — không áp dụng chiến lược này, vì đây là state machine trung tâm đã
ổn định, không phải "đề xuất chờ duyệt".

**Lý do:** Yêu cầu nghiệp vụ mới xác nhận: "không để enum phụ tiếp tục chặn toàn bộ migration".
Nguyên nhân 5 cột này từng là migration blocker là vì DB `enum()` khóa cứng danh sách giá trị
ngay trong schema — đổi một giá trị sau này (thêm/bớt/sửa chính tả) bắt buộc `ALTER TABLE`, nên
đội đặc tả trước đó (hợp lý) muốn công ty duyệt trước khi khóa cứng. Chuyển sang `varchar` +
validation tầng ứng dụng loại bỏ hoàn toàn chi phí đó — sai lệch một giá trị đề xuất chỉ là một
PR nhỏ sửa code, không phải migration, nên không còn lý do phải chờ xác nhận trước khi tạo
migration ban đầu. Đây là quyết định kiến trúc thuần túy (chọn cơ chế ràng buộc dữ liệu), không
phải quyết định nghiệp vụ cần công ty ký duyệt.

<a id="adr-065"></a>

## ADR-065 — Administrative unit root uniqueness: thêm `root_slug_key` generated + unique

**Quyết định:** `UNIQUE(parent_id, slug)` hiện có trên `administrative_units` **không** chặn
trùng `slug` ở cấp root, vì mọi bản ghi root đều có `parent_id = NULL`, và MariaDB coi mỗi `NULL`
là một giá trị riêng biệt trong unique index (2 bản ghi cùng `parent_id=NULL` và cùng `slug` vẫn
được chèn, không vi phạm constraint) — đây là lỗi ràng buộc thật, không phải chi tiết diễn giải.
Thêm cột generated **`root_slug_key`** (`varchar(170)`, `IF(parent_id IS NULL, slug, NULL)`
STORED, **UNIQUE**) — chặn 2 đơn vị hành chính cấp root có cùng `slug`, không ảnh hưởng
`UNIQUE(parent_id, slug)` hiện có (vẫn đúng cho các cấp có `parent_id` khác null). Giữ nguyên
`official_code UNIQUE (khi có giá trị)` đã có làm định danh chính cho dữ liệu import từ nguồn nhà
nước (mục provenance, ADR-070) — 2 constraint bổ trợ nhau: `official_code` bảo vệ danh tính pháp
lý khi có mã, `root_slug_key` bảo vệ URL slug ở mọi trường hợp kể cả khi `official_code` còn
trống lúc nhập liệu thủ công.

**Lý do:** Import/upsert dữ liệu hành chính phải dựa trên khóa ổn định, không dùng tên hiển thị —
`official_code` đã đúng hướng nhưng chỉ unique "khi có giá trị" (cho phép nhiều `NULL`), nên chưa
đủ để tự nó chặn trùng khi dữ liệu nhập tay chưa có mã ngay từ đầu; thêm khóa slug ở cấp root làm
lớp bảo vệ thứ hai không phụ thuộc `official_code`.

<a id="adr-066"></a>

## ADR-066 — Quy ước timestamp và hạ tầng Laravel Phase 1 tối giản

**Quyết định:**

1. **Timestamp convention (làm rõ, không đổi hành vi):** Giá trị "now" ghi ở cột "Default" trong
   các bảng của `docs/DATABASE-DICTIONARY.md` là **quy ước diễn đạt** ("cột này luôn có giá trị
   sau khi tạo bản ghi") — **không** phải chỉ thị dùng `DEFAULT CURRENT_TIMESTAMP` ở tầng DB.
   `created_at`/`updated_at` luôn do **Eloquent ghi** qua `$table->timestamps()` (migration
   helper chuẩn của Laravel) và model tự động set khi `save()`/`create()` — không khai
   `->useCurrent()`/`DEFAULT CURRENT_TIMESTAMP` thủ công ở bất kỳ migration nào trừ khi có ADR
   riêng cho trường hợp đó. Timestamp nghiệp vụ (`published_at`, `verified_at`, `contacted_at`,
   `started_at`, `submission_snapshot`/`applications.created_at` đóng vai trò "submitted_at"...)
   đã có ngữ nghĩa riêng biệt ở từng bảng từ trước, không đổi.
2. **Hạ tầng Laravel Phase 1 (tối giản, không migration/config thừa):** `SESSION_DRIVER=file`,
   `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`. Không có luồng "quên mật khẩu qua email" (Phase 1
   dùng Admin reset — mục 1 dưới, ADR-067) nên **không** cần migration `password_reset_tokens`.
   Không có Job class bất đồng bộ nào ở Phase 1 (không gửi email — mục 1.3 CORE-FLOWS) nên
   **không** cần `jobs`/`job_batches`/`failed_jobs`. Cảnh báo Job Verification Scheduler là giá
   trị **tính toán khi render** (không phải cron job — đã chốt ở mục 1.3), nên Phase 1 **không**
   cần Laravel Task Scheduler/cron entry, không cần cache lock cho `withoutOverlapping()`. Với
   `SESSION_DRIVER=file`/`CACHE_STORE=file`, migration hạ tầng Phase 1 chỉ còn bảng `migrations`
   mặc định của Laravel — không có bảng hạ tầng nào khác cần tạo.

**Lý do:** Phần 1 — "Default: now" ở dictionary dễ bị đọc nhầm thành yêu cầu DB-level default,
trong khi nguyên tắc rà soát này yêu cầu tránh `DEFAULT CURRENT_TIMESTAMP` khác nhau giữa các
bảng trừ khi có ADR — làm rõ ngay tại đây tránh 2 cách hiểu khi viết migration thật. Phần 2 —
tách rõ 27+1 business tables khỏi bảng hạ tầng Laravel, tránh gộp chung "toàn bộ database" khi
báo cáo tiến độ; chọn cấu hình tối giản nhất phù hợp quy mô VPS đơn của Phase 1 (không cần
Redis/queue worker riêng — nhất quán `.claude/rules/architecture.md`, "không tự thêm Redis").

<a id="adr-069"></a>

## ADR-069 — Migration order chính thức và Roadmap Giai đoạn 1 chia 7 nhóm triển khai

**Quyết định:** Chốt thứ tự migration theo dependency (28 bảng business, xem đầy đủ ở
`docs/DATABASE-DICTIONARY.md` mục "Migration order" và `ROADMAP.md`) và **chia Giai đoạn 1 (bản
cũ: 1 khối lớn "toàn bộ 27 bảng + model + factory + seeder + test" trong 1 giai đoạn duy nhất)
thành 7 nhóm triển khai tuần tự**, mỗi nhóm có migration + model + policy + request + action +
test + manual acceptance test riêng trước khi sang nhóm kế tiếp (chi tiết đầy đủ: `ROADMAP.md`).
Không bắt buộc Factory cho các bảng lịch sử (`*_histories`, `*_attempts`, `job_verifications`,
`export_logs`) — factory tạo trực tiếp dữ liệu bất biến (`from_status`/`to_status` tùy ý) có thể
sinh ra state không hợp lệ theo transition matrix; test dùng domain action thật (`ChangeJobStatusAction`,
`ChangeApplicationStageAction`...) để tạo dữ liệu lịch sử, đảm bảo dữ liệu test luôn hợp lệ theo
đúng contract.

**Lý do:** Gộp toàn bộ 27+ bảng vào 1 giai đoạn duy nhất "viết migration cho tất cả rồi mới code"
không khớp cách làm việc theo vertical slice nhỏ đã chốt ở `CLAUDE.md` ("Mỗi session xử lý một
vertical slice nhỏ") — chia nhóm giúp mỗi session review/test được ngay, giảm rủi ro migration
sai schema bị phát hiện muộn sau khi đã viết xong toàn bộ code phụ thuộc. Factory cho bảng lịch
sử dễ tạo dữ liệu "hợp lệ về mặt cột" nhưng vi phạm transition matrix/workflow cycle (vd tạo thẳng
1 `application_status_histories` với `from_stage=new, to_stage=started` bỏ qua toàn bộ bước ở
giữa) — nguy hiểm hơn lợi ích tiện lợi mang lại.

<a id="adr-070"></a>

## ADR-070 — Administrative dataset provenance contract

**Quyết định:** Dữ liệu `administrative_units` khi import/seed production-safe (ADR-051) phải ghi
kèm: nguồn dữ liệu (tên cơ quan/văn bản pháp lý ban hành), phiên bản/ngày hiệu lực văn bản, thời
điểm import thực tế (`created_at`/`updated_at` sẵn có đã đủ, không thêm cột), `official_code` làm
khóa upsert chính (đã có, unique-khi-có-giá-trị), `valid_from`/`valid_to`/`is_active` (đã có) xử
lý đơn vị hết hiệu lực sau sáp nhập — đơn vị cũ **không** bị xóa, chuyển `is_active = false`,
`valid_to` = ngày hết hiệu lực, **không** dùng cho dữ liệu mới (`company_locations`/`branches`/
`candidates.current_administrative_unit_id` mới tạo không được chọn đơn vị `is_active = false`,
kiểm tra ở Form Request) nhưng vẫn giữ để tra cứu lịch sử. Cơ chế upsert: `official_code` khi có
giá trị, `(parent_id, slug)`/`root_slug_key` (ADR-065) khi đơn vị chưa có mã chính thức. **Nguồn
dữ liệu hành chính cụ thể đã chốt: API `provinces.open-api.vn` (v2) — xem ADR-079.** Không phải
migration blocker vì schema (`official_code`, `valid_from`, `valid_to`, `is_active`) đã đủ hỗ trợ
bất kỳ nguồn nào công ty chọn.

**Lý do:** Việt Nam đang trong giai đoạn sáp nhập đơn vị hành chính (đã ghi nhận từ ADR-010) —
nếu không có quy tắc rõ về nguồn/khóa upsert, việc import lại dữ liệu khi có thay đổi địa giới sẽ
dễ tạo trùng hoặc mất liên kết với dữ liệu cũ (`company_locations`/`branches` đang trỏ tới đơn vị
đã bị thay thế). Chốt cơ chế nhưng không tự chọn nguồn cụ thể — đúng nguyên tắc không tự suy đoán
quyết định thuộc về công ty khi không ảnh hưởng khả năng viết migration.

<a id="adr-073"></a>

## ADR-073 — ERD cardinality: tách các edge gộp nhiều FK khác nullability

**Quyết định:** Sửa `docs/ERD.md` — 4 edge trước đây mô tả **nhiều foreign key khác nullability
bằng 1 đường quan hệ duy nhất** (vi phạm chính quy ước cardinality mà file ERD tự đặt ra ở đầu
file), tách thành các edge riêng:

- `users ↔ pages`: tách `created_by` (NOT NULL, `||--o{`) và `updated_by` (nullable, `|o--o{`) —
  trước đây gộp `"created_by / updated_by (nullable)"` trên 1 đường `||--o{`.
- `users ↔ jobs`: tách `created_by` (NOT NULL), `updated_by` (nullable), `deleted_by` (nullable)
  thành 3 edge riêng — trước đây gộp cả 3 trên 1 đường `||--o{`.
- `users ↔ companies`: tách `created_by` (NOT NULL) và `updated_by` (nullable) — tương tự.
- `branches ↔ users`: đổi từ `||--o{` (bắt buộc) sang `|o--o{` (optional) cho `branch_id` — cột
  này **nullable ở DB** (chỉ bắt buộc ở tầng Service khi `role=staff`, `admin` không cần) — dùng
  `||--o{` mô tả sai một FK nullable thành bắt buộc.

Thêm entity `candidate_duplicate_reviews` và các quan hệ liên quan (ADR-062):
`applications ||--o{ candidate_duplicate_reviews`, `candidates ||--o{ candidate_duplicate_reviews`
(2 lần: `candidate_id` và `suspected_candidate_id`, ghi rõ tên FK trên từng edge để không nhầm
lẫn 2 quan hệ cùng hướng tới `candidates`), `users |o--o{ candidate_duplicate_reviews : "reviewed_by
(nullable)"`.

**Lý do:** Chính file `docs/ERD.md` đã định nghĩa quy ước "`||--o{` = một-nhiều, bắt buộc ở đầu
một" và "`|o--o{` = một-nhiều, đầu một có thể null" — nhưng 3 edge `users↔pages/jobs/companies`
tự vi phạm quy ước đó bằng cách gộp cả FK bắt buộc lẫn FK nullable vào chung 1 ký hiệu (chọn
`||--o{`, đúng cho `created_by` nhưng sai cho `updated_by`/`deleted_by` đi kèm) — đây chính là
tình huống "một edge duy nhất mô tả nhiều FK khác nullability gây hiểu sai" mà vòng rà soát này
yêu cầu sửa. `branches↔users` bị vẽ nhầm theo hướng ngược lại (đánh dấu nullable FK là bắt buộc).

<a id="adr-078"></a>

## ADR-078 — Final Consistency Patch và semantic checker mở rộng

**Quyết định:** Route Map tách từng HTTP method/action, dùng mã `PUB-*`, ERD sửa parent
Administrative Unit nullable. Checker chặn live rule verification cũ, Scope 11 điều kiện, branch
transfer “chỉ cần không published”, route gộp method, Salary Predicate cũ, thiếu multi-root/
family invariant, thiếu active-user middleware và ownership Company Contact. Content baseline
frozen; Git baseline chỉ hoàn tất sau commit/tag được người dùng cho phép.

<a id="adr-079"></a>

## ADR-079 — Administrative dataset source: import từ `provinces.open-api.vn` (resolve ADR-070)

**Quyết định:** Chốt nguồn dữ liệu hành chính chính thức (go-live blocker nêu ở ADR-070) là API
công khai `provinces.open-api.vn` (v2 — dữ liệu sau sáp nhập 07/2025, 2 cấp tỉnh/thành →
phường/xã, field `code` là mã GSO). Nhập dữ liệu qua lệnh console
`php artisan administrative-units:import` (`app/Console/Commands/ImportAdministrativeUnitsCommand.php`),
tái sử dụng nguyên `UpsertAdministrativeUnitAction` (khóa `official_code` = `(string) code` từ
API — đúng cơ chế upsert ADR-070 đã thiết kế sẵn, không đổi schema). Command chỉ tạo/cập nhật,
**không** tự `is_active = false` các bản ghi vắng mặt trong response (tránh khoá nhầm đơn vị đang
được `branches`/`company_locations`/`candidates` tham chiếu chỉ vì một lần gọi API thiếu dữ
liệu). Website (Public/HR) tiếp tục đọc từ `administrative_units` nội bộ như hiện tại — **không**
gọi API lúc runtime; import là thao tác admin tự chạy khi cần (giống `db:backup`), không chạy tự
động trong migration/seeder/CI để tránh phụ thuộc mạng khi test/deploy. Trang CRUD thủ công
`hr/administrative-units` vẫn giữ nguyên làm công cụ override cục bộ (vd tắt `is_active` một đơn
vị vì lý do nghiệp vụ riêng, không liên quan thay đổi địa giới từ nguồn nhà nước).

<a id="adr-080"></a>

## ADR-080 — Chốt baseline kiến trúc Phase 2: áp dụng đề xuất "cấu trúc lại" (PDF v1.1)

**Quyết định:** Công ty xác nhận áp dụng đề xuất trong `bao_cao_cau_truc_lai_du_an_vieclam88_v1.1.pdf`
làm baseline nghiệp vụ/kiến trúc cho Phase 2 — ma trận đối chiếu đầy đủ và lộ trình migration ở
`docs/PHASE-2-ARCHITECTURE-PROPOSAL.md`. Thay đổi chính: role 3 cấp (`super_admin`/`branch_admin`/
`staff` thay `admin`/`staff`); địa chỉ hành chính chuyển từ `administrative_units` tự tham chiếu
sang 2 bảng `provinces`+`wards`; `industrial_parks` chuyển quan hệ 1-N sang N-N với `wards` qua
`industrial_park_wards`, thêm `branch_id` quản lý chính; bỏ `company_locations`, `jobs` mang địa
chỉ riêng qua `work_ward_id` (bắt buộc) + `industrial_park_id` (tùy chọn); `jobs.company_id`
chuyển nullable (thêm `job_type=direct` không cần company); thêm bảng mới `industries`,
`employment_types` (thay `JobEmploymentType` enum), `job_images`, `candidate_documents`
(CV PDF/avatar); thêm cột hồ sơ ứng viên (`marital_status`/`foreign_language`/`ethnicity`/
`citizen_id_*`/`personal_introduction` trên `candidates`); thêm `activity_logs` (chốt bổ sung
23/07/2026 — xem chi tiết bên dưới).

**Bổ sung `activity_logs` (mở rộng ADR-019, không thay thế):** công ty xác nhận thêm bảng
`activity_logs` chung cho các thao tác chưa có audit trail riêng (sửa `companies`,
`industrial_parks`, `settings`, danh mục `industries`/`employment_types`) — giữ nguyên toàn bộ
audit trail chuyên biệt hiện có (`job_status_histories`, `application_status_histories`,
`application_branch_histories`, `job_branch_histories`, `export_logs`) vì các bảng đó có cấu trúc
cột giàu ngữ cảnh nghiệp vụ hơn. ADR-019 vẫn đúng cho phần đã áp dụng, chỉ không còn là quy tắc
"duy nhất" — hành động mới không có bảng lịch sử riêng thì dùng `activity_logs`.

**Supersede/ảnh hưởng** (giữ nguyên nội dung ADR cũ để tra cứu lịch sử, không xóa — chỉ không còn
là baseline hiện hành sau khi migration Phase 2 hoàn tất): ADR-010 (`administrative_units` phân
cấp — dữ liệu tỉnh/xã đã tương đương nhờ ADR-079, chỉ đổi cấu trúc bảng), ADR-014 (`users.role`
enum đơn giản), ADR-011 (không lặp địa điểm `jobs`/`company_locations` — nay địa điểm chuyển hẳn
về `jobs`), ADR-015 (`branches` tách khỏi `company_locations`), ADR-045/052 (`company_locations`
Quick Create/khớp KCN — bảng nguồn không còn), ADR-055 phần `employment_type` (thay bằng bảng FK
`employment_types`; phần "enum phụ khác chưa chốt" của ADR-055 vẫn còn hiệu lực).

**Trạng thái thực thi:** Quyết định baseline đã chốt, **CHƯA migrate code/schema**. Không sửa
migration/model/route hiện có trong cùng đợt ghi ADR này — đúng nguyên tắc Additive-first (P01)
mà chính PDF đề xuất, khớp "An toàn thao tác" của `CLAUDE.md`. Thực thi theo lộ trình
Expand→Backfill→Switch→Contract ở `docs/PHASE-2-ARCHITECTURE-PROPOSAL.md` mục "Kế hoạch
migration", qua các task/slice riêng (`/db-task`, `/vibe-task`) từng bước, không gộp một lần.

**Lý do:** Công ty đã xác nhận trực tiếp áp dụng PDF làm hướng đi chính thức. Ghi nhận bằng ADR để
không "âm thầm chọn một bên" và để migration sau này có căn cứ tường minh. Nguồn sự thật schema
**hiện tại** (`DATABASE-DICTIONARY.md`/`ERD.md`/`ROUTE-MAP.md`) **giữ nguyên không đổi** cho tới
khi migration thực sự chạy — tránh mọi phiên code sau viết nhầm vào cột/bảng chưa tồn tại trong
database thật.

**Lý do:** Schema (`official_code` unique-khi-có-giá-trị, cơ chế upsert theo ADR-070) đã được
thiết kế sẵn đúng cho trường hợp có nguồn dữ liệu ngoài cung cấp mã chuẩn — chỉ cần bổ sung cơ chế
nhập, không cần đổi 4 bảng có FK (`branches`, `industrial_parks`, `company_locations`,
`candidates`) hay CRUD hiện có. Giữ nhập-về-DB-nội-bộ (thay vì gọi API mỗi request) để không thêm
phụ thuộc runtime vào dịch vụ bên thứ ba, giữ đúng nguyên tắc kiến trúc Phase 1 (`.claude/rules/architecture.md`).
