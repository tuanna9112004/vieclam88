# Kiến trúc mục tiêu Phase 2 (PDF v1.1) — ĐÃ DUYỆT BASELINE, CHƯA MIGRATE CODE

> **TRẠNG THÁI: Đã duyệt làm baseline kiến trúc Phase 2 (ADR-080).** Công ty đã xác nhận đi theo
> hướng này. **Vẫn CHƯA migrate schema/code** — `docs/DATABASE-DICTIONARY.md`, `docs/ERD.md`,
> `docs/ROUTE-MAP.md`, `docs/CORE-FLOWS.md` **tiếp tục là nguồn sự thật cho database/route đang
> chạy thật** cho tới khi từng batch migration ở mục "Kế hoạch migration" bên dưới hoàn tất và các
> file đó được cập nhật theo. Không tự ý code theo bảng/cột ở file này khi task không thuộc đúng
> batch migration đang thực thi.

## Nguồn

`bao_cao_cau_truc_lai_du_an_vieclam88_v1.1.pdf` — "Báo cáo cấu trúc lại dự án tuyển dụng
Vieclam88", chốt 23/07/2026, PHP 8.3/Laravel 13/MariaDB/Blade/Bootstrap 5/AlpineJS/Vite.

## Vì sao chưa ghi đè thẳng vào `docs/DATABASE-DICTIONARY.md`/`ERD.md`/`ROUTE-MAP.md`

Các tài liệu "nguồn sự thật" đó mô tả đúng schema/route **đang chạy thật** (811/817 test pass) —
nếu ghi đè ngay bằng schema đề xuất trong PDF trước khi migration thật sự chạy, mọi phiên Claude
Code sau sẽ viết code tham chiếu cột/bảng/route **chưa tồn tại trong database**
(`jobs.work_ward_id`, `users.role=branch_admin`, `industrial_park_wards`...) và phá vỡ ứng dụng.
Theo đúng quy tắc "Khi nguồn mâu thuẫn: dừng phần liên quan, ghi blocker; không tự chọn một
phương án" (`CLAUDE.md`), quyết định baseline được ghi ở ADR-080 + file này; các file "nguồn sự
thật" chỉ được cập nhật theo **sau khi từng batch migration bên dưới thật sự chạy xong**, không
phải ngay khi ADR được duyệt.

## Ma trận đối chiếu PDF ↔ hiện trạng

| Miền | PDF đề xuất | Hiện trạng thật | Loại khoảng cách | Mức độ |
|---|---|---|---|---|
| Role | `users.role`: `super_admin`/`branch_admin`/`staff` (3 cấp) | `role`: `admin`/`staff` (2 cấp) — [`app/Models/User.php`](../app/Models/User.php) | Thiếu 1 role, đổi ý nghĩa "admin" | CRITICAL |
| Địa chỉ hành chính | 2 bảng cứng `provinces`+`wards`, bỏ cấp huyện | `administrative_units` tự tham chiếu (N cấp qua `parent_id`) | Khác cấu trúc bảng. **Data đã tương đương** (tỉnh→xã, mã GSO) — xem ADR-079 | HIGH (schema), data đã đóng |
| KCN ↔ địa chỉ | N-N qua `industrial_park_wards`, KCN có `branch_id` | `industrial_parks.administrative_unit_id` (1-N), không có `branch_id` | Đổi quan hệ + thêm cột | HIGH |
| Seed KCN | 10 KCN cụ thể theo 4 chi nhánh (Vĩnh Phúc/Phú Thọ/Hòa Bình/Bắc Giang-Bắc Ninh) | `DemoSeeder` seed KCN demo khác hoàn toàn (Hà Nội/Bắc Ninh/Bắc Giang/Thái Nguyên) | Dữ liệu vận hành thật khác dữ liệu demo | HIGH (data) |
| Company ↔ Job | `jobs.company_id` nullable, có `job_type=direct` không cần company | `company_id` **NOT NULL bắt buộc** trên mọi Job — [`app/Models/Job.php`](../app/Models/Job.php) | Đổi ràng buộc cốt lõi | CRITICAL |
| Địa điểm Job | `jobs.work_ward_id` (FK trực tiếp) | Không có; qua `JobLocation → CompanyLocation.administrative_unit_id` | Khác cơ chế hoàn toàn | CRITICAL |
| Chuyên ngành | Bảng `industries`, `jobs.industry_id` bắt buộc | Không tồn tại | Gap thuần túy | MEDIUM |
| Loại hình công việc | Bảng `employment_types` (5 giá trị, có freelance/internship), `jobs.employment_type_id` FK | `JobEmploymentType` PHP backed enum, 4 giá trị (`full_time/part_time/seasonal/temporary`) — cố ý dùng enum theo ADR-055, không FK | Thiếu 2 giá trị + khác cơ chế lưu | MEDIUM |
| Lương | `salary_negotiable` boolean + `salary_min/max` | `salary_min/max/base/period/currency`, `salary_period=negotiable` — phong phú hơn PDF | Khác field, không phải gap | LOW |
| Ảnh Job | Bảng `job_images` (nhiều ảnh, primary, sort) | Không tồn tại | Gap thuần túy | MEDIUM |
| CV/hồ sơ ứng viên | Bảng `candidate_documents` (CV PDF, avatar) + `candidates` thêm `marital_status`/`foreign_language`/`ethnicity`/`citizen_id_*`/`personal_introduction` | Không tồn tại — form ứng tuyển không có upload file, `candidates` chưa có các cột trên | Gap thuần túy | MEDIUM |
| Activity log | Bảng `activity_logs` chung, ghi mọi thay đổi quan trọng | **Không có** — ADR-019 dùng audit trail theo từng action (`job_status_histories`, `application_status_histories`...) | **Đã chốt (23/07/2026):** thêm `activity_logs` bổ sung, không thay thế audit trail hiện có — mở rộng ADR-019 | MEDIUM — xem `DATABASE-DICTIONARY.md` mục 9.36 |
| Candidate/Application matching | Chống trùng theo phone, source_type, branch assignment cơ bản | **Đã có, chín hơn nhiều**: `workflow_cycle`, `CandidateDuplicateReview`, `MergeCandidateAction`, named lock, consent/snapshot (ADR-007/030/031/075/076) | PDF mô tả subset đơn giản hơn hiện tại | Không phải gap — giữ nguyên |
| `company_locations` | PDF liệt vào "không nên tạo" (mục 17.2) | Bảng lõi đang chạy, FK từ `JobLocation`, Quick Create (ADR-045/052) | Xung đột trực tiếp bất biến CLAUDE.md | CRITICAL |
| `administrative_units` | PDF liệt vào "không nên tạo" (mục 17.2) | Bảng lõi, FK từ 4 bảng, vừa hoàn thiện provenance (ADR-079) | Xung đột trực tiếp ADR-010 | CRITICAL |

## Phần không xung đột — có thể làm độc lập nếu muốn, không cần quyết định về phần còn lại

- `industries` (bảng chuyên ngành + `jobs.industry_id`).
- `job_images` (nhiều ảnh/job).
- `candidate_documents` (CV PDF + avatar, upload có kiểm MIME/private storage).
- Chống trùng Company theo tên gần giống (hiện chỉ có cho Candidate).

Ba mục đầu là khoảng trống thật của hệ thống hiện tại, không mâu thuẫn bất biến nào — nếu muốn
triển khai, dùng `/vibe-task` hoặc `/db-task` bình thường như một feature mới, không cần đợi quyết
định về các mục CRITICAL ở trên.

## Kế hoạch migration (Expand → Backfill → Switch → Contract, theo ADR-080)

> **Vai trò của bảng Batch dưới đây đã thu hẹp:** đây là tài liệu kiến trúc/gap-analysis (lý do
> đổi, mức rủi ro), **không còn dùng để quyết định thứ tự thi công**. Thứ tự thi công chính thức
> theo `TASK x.y` ở `docs/refactor/TASK-INDEX.md`; đối chiếu Batch ↔ Task và các chỗ lệch thứ tự
> đã biết xem `docs/refactor/BATCH-TASK-MAP.md` — đọc file đó trước khi bắt đầu bất kỳ batch nào.

Mỗi batch là một `/db-task` + `/vibe-task` riêng, có test, chạy độc lập, **không gộp nhiều batch
vào một lần sửa**. Không batch nào được xóa bảng/cột cũ (Contract) trước khi Switch ổn định và
được theo dõi. Sau mỗi batch, cập nhật đúng phần liên quan của `DATABASE-DICTIONARY.md`/`ERD.md`/
`ROUTE-MAP.md`/`CORE-FLOWS.md` — không cập nhật trước khi code chạy thật.

| Batch | Nội dung (Expand + Backfill) | Bảng/cột liên quan | Ghi chú |
|---|---|---|---|
| 1 | `provinces`, `wards` (bảng mới, song song `administrative_units`); command backfill từ `administrative_units` (đã có dữ liệu chuẩn GSO nhờ ADR-079) | `provinces`, `wards` | Không xóa `administrative_units` ở batch này |
| 2 | `industrial_park_wards` (N-N mới); backfill từ `industrial_parks.administrative_unit_id` hiện có; thêm `industrial_parks.branch_id` nullable trước, backfill, rồi NOT NULL | `industrial_parks`, `industrial_park_wards` | Giữ `administrative_unit_id` cũ cho tới Contract |
| 3 | `users.role` mở rộng thêm `branch_admin` (vẫn giữ `admin`/`staff` cũ hoạt động); Policy mới cho `branch_admin` | `users` | Backward-compatible: `admin` hiện tại tương đương `super_admin` |
| 4 | `industries`, `employment_types` (bảng mới, độc lập, không xung đột) — **có thể làm trước, không phụ thuộc batch khác** | `industries`, `employment_types` | Xem "Phần không xung đột" bên dưới |
| 5 | `jobs.work_ward_id`/`industry_id`/`employment_type_id` nullable trước; backfill từ `job_locations`/`company_locations`/`employment_type` enum; sau đó mới xét NOT NULL | `jobs`, `job_locations`, `company_locations` | Batch rủi ro cao nhất — cần kế hoạch rollback riêng |
| 6 | `job_images`, `candidate_documents`, `activity_logs` (bảng mới, độc lập); thêm cột `candidates.marital_status`/`foreign_language`/`ethnicity`/`citizen_id_*`/`personal_introduction` (nullable) | — | Có thể làm sớm, song song batch khác |
| 7 | `jobs.company_id` nullable + `job_type` — **chỉ làm sau khi nghiệp vụ xác nhận rõ luồng "tuyển trực tiếp"** (chưa có trong PDF chi tiết đủ để tự suy ra validation) | `jobs` | Cần AC cụ thể trước khi code |
| 8 (Switch) | Đổi code đọc/ghi sang bảng mới, giữ fallback | tất cả trên | Chỉ sau khi batch 1-7 backfill xong + test pass |
| 9 (Contract) | Xóa `administrative_units`/`company_locations`/cột cũ | — | Release riêng, sau thời gian theo dõi ổn định, có backup xác nhận restore được |

## Ghi chú

Nguyên tắc an toàn migration của PDF (mục 3, P01-P10: additive-first, backup bắt buộc, không xóa
cứng, rollback thật sự) **khớp tinh thần** với "An toàn thao tác" đã có trong `CLAUDE.md` — bảng
trên áp dụng đúng tinh thần đó vào tên bảng/cột thật của dự án.
