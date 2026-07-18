# Route Map — Phase 1

6 luồng nghiệp vụ mà các route dưới đây phải hỗ trợ đúng: `docs/CORE-FLOWS.md`.

Quy ước:
- Public ở `routes/web.php`; HR ở `routes/hr.php` với prefix/name `hr.`.
- Route động dùng implicit binding (`{job:slug}`, `{company:slug}`) hoặc `whereNumber()`.
- Route tĩnh như `/xuat-csv` phải khai báo trước `/{application}`.

## Public

| Method | Path | Name | Controller@method | Middleware | Mục đích |
|---|---|---|---|---|---|
| GET | `/` | `home` | `HomeController@index` | web | Trang chủ |
| GET | `/viec-lam` | `jobs.index` | `JobController@index` | web | Danh sách, tìm kiếm, lọc |
| GET | `/viec-lam/{job:slug}` | `jobs.show` | `JobController@show` | web | Chi tiết việc làm |
| POST | `/viec-lam/{job:slug}/ung-tuyen` | `applications.store` | `ApplicationController@store` | web, throttle, honeypot | Ứng tuyển guest/candidate; form render kèm `submission_token` ẩn (idempotency, `docs/CORE-FLOWS.md` mục 3) |
| GET | `/cong-ty` | `companies.index` | `CompanyController@index` | web | Danh sách công ty |
| GET | `/cong-ty/{company:slug}` | `companies.show` | `CompanyController@show` | web | Chi tiết công ty |
| GET | `/khu-cong-nghiep/{industrialPark:slug}` | `industrial-parks.show` | `IndustrialParkController@show` | web | Việc theo KCN |
| GET | `/gioi-thieu` | `pages.about` | `PageController@about` | web | Trang giới thiệu |
| GET | `/lien-he` | `contact.show` | `ContactController@show` | web | Trang liên hệ (thông tin tĩnh, không có form gửi Lead — Phase 1 không có Lead, xem `docs/CORE-FLOWS.md` mục 0) |
| GET | `/cau-hoi-thuong-gap` | `faqs.index` | `FaqController@index` | web | FAQ |
| GET | `/sitemap.xml` | `sitemap` | `SitemapController@index` | throttle | Sitemap |

**Đã bỏ khỏi Phase 1** (chuyển Phase 2 — ADR-021): `POST /lien-he/tu-van` (`leads.store`) —
không có form tạo Lead trong Phase 1, kể cả form "yêu cầu tư vấn". **Toàn bộ Candidate
Account** (`register.*`, `login.*` cho candidate, `password.*`, `/tai-khoan`,
`/tai-khoan/da-ung-tuyen`, và `favorites.*`/`account.favorites`) cũng chuyển Phase 2 (ADR-028)
— Phase 1 không có route đăng ký/đăng nhập/tài khoản cho ứng viên; ứng viên luôn là guest.
`/hr/dang-nhap` bên dưới là route đăng nhập riêng cho staff/admin, không liên quan.

## HR auth/dashboard

| Method | Path | Name | Controller@method | Middleware |
|---|---|---|---|---|
| GET | `/hr/dang-nhap` | `hr.login` | `HrAuthController@create` | guest |
| POST | `/hr/dang-nhap` | `hr.login.store` | `HrAuthController@store` | guest, throttle |
| POST | `/hr/dang-xuat` | `hr.logout` | `HrAuthController@destroy` | auth, role:staff,admin |
| GET | `/hr` | `hr.dashboard` | `DashboardController@index` | auth, role:staff,admin |

Các route bên dưới đều có middleware `auth`, `role:staff,admin`; route admin-only thêm `can:*`.

## HR danh mục

| Method | Path | Name | Controller@method | Quyền |
|---|---|---|---|---|
| GET | `/hr/don-vi-hanh-chinh` | `hr.administrative-units.index` | `AdministrativeUnitController@index` | admin |
| POST | `/hr/don-vi-hanh-chinh` | `hr.administrative-units.store` | `AdministrativeUnitController@store` | admin |
| PUT | `/hr/don-vi-hanh-chinh/{administrativeUnit}` | `hr.administrative-units.update` | `AdministrativeUnitController@update` | admin |
| GET | `/hr/khu-cong-nghiep` | `hr.industrial-parks.index` | `IndustrialParkController@index` | admin |
| POST | `/hr/khu-cong-nghiep` | `hr.industrial-parks.store` | `IndustrialParkController@store` | admin |
| PUT | `/hr/khu-cong-nghiep/{industrialPark}` | `hr.industrial-parks.update` | `IndustrialParkController@update` | admin |
| GET | `/hr/co-so` | `hr.branches.index` | `BranchController@index` | admin |
| GET | `/hr/co-so/tao-moi` | `hr.branches.create` | `BranchController@create` | admin |
| POST | `/hr/co-so` | `hr.branches.store` | `BranchController@store` | admin |
| GET | `/hr/co-so/{branch}/sua` | `hr.branches.edit` | `BranchController@edit` | admin |
| PUT | `/hr/co-so/{branch}` | `hr.branches.update` | `BranchController@update` | admin |
| DELETE | `/hr/co-so/{branch}` | `hr.branches.destroy` | `BranchController@destroy` | admin |

`branches` = cơ sở nội bộ vieclam88 (xem `docs/CORE-FLOWS.md`), quản lý bởi admin. Gán
nhân viên vào cơ sở làm ở form tài khoản staff (`hr.staff.*`, mục "HR admin"), không có route
riêng.

## HR công ty

Quick Create (`docs/CORE-FLOWS.md` mục 0.2, 0.3, ADR-045): `hr.companies.store` chỉ bắt buộc
`name`; `hr.company-locations.store` chỉ bắt buộc `name` (`administrative_unit_id`/
`address_detail` tùy chọn, bổ sung sau).

| Method | Path | Name | Controller@method | Quyền |
|---|---|---|---|---|
| GET | `/hr/cong-ty` | `hr.companies.index` | `CompanyController@index` | staff/admin |
| GET | `/hr/cong-ty/tao-moi` | `hr.companies.create` | `CompanyController@create` | staff/admin |
| POST | `/hr/cong-ty` | `hr.companies.store` | `CompanyController@store` | staff/admin |
| GET | `/hr/cong-ty/{company}/sua` | `hr.companies.edit` | `CompanyController@edit` | staff/admin |
| PUT | `/hr/cong-ty/{company}` | `hr.companies.update` | `CompanyController@update` | staff/admin |
| DELETE | `/hr/cong-ty/{company}` | `hr.companies.destroy` | `CompanyController@destroy` | admin |
| POST | `/hr/cong-ty/{company}/khoi-phuc` | `hr.companies.restore` | `CompanyController@restore` | admin |
| GET/POST | `/hr/cong-ty/{company}/dia-diem` | `hr.company-locations.index/store` | `CompanyLocationController@index/store` | staff/admin — `store` validate tỉnh khớp KCN (ADR-052) |
| PUT | `/hr/cong-ty/{company}/dia-diem/{location}` | `hr.company-locations.update` | `CompanyLocationController@update` | staff/admin — validate tỉnh khớp KCN (ADR-052) |
| DELETE | `/hr/cong-ty/{company}/dia-diem/{location}` | `hr.company-locations.destroy` | `CompanyLocationController@destroy` | **admin** (ADR-053 — sửa lỗi cho phép staff ở bản trước) |
| POST | `/hr/cong-ty/{company}/dia-diem/{location}/khoi-phuc` | `hr.company-locations.restore` | `CompanyLocationController@restore` | **admin** (mới, ADR-053) |
| GET/POST | `/hr/cong-ty/{company}/dau-moi` | `hr.company-contacts.index/store` | `CompanyContactController@index/store` | staff/admin |
| PUT | `/hr/cong-ty/{company}/dau-moi/{contact}` | `hr.company-contacts.update` | `CompanyContactController@update` | staff/admin |
| DELETE | `/hr/cong-ty/{company}/dau-moi/{contact}` | `hr.company-contacts.destroy` | `CompanyContactController@destroy` | **admin** (ADR-053 — sửa lỗi cho phép staff ở bản trước) |
| POST | `/hr/cong-ty/{company}/dau-moi/{contact}/khoi-phuc` | `hr.company-contacts.restore` | `CompanyContactController@restore` | **admin** (mới, ADR-053) |

## HR việc làm

`hr.jobs.publish/pause/close` dùng chung `JobWorkflowController` gọi `ChangeJobStatusAction`,
ghi `job_status_histories` mỗi lần (`docs/CORE-FLOWS.md` mục 1). "Mở lại" job (`paused →
published`) dùng lại chính route `hr.jobs.publish` — action tự re-check toàn bộ điều kiện
publish, không có route riêng cho "reopen".

| Method | Path | Name | Controller@method | Quyền |
|---|---|---|---|---|
| GET | `/hr/viec-lam` | `hr.jobs.index` | `JobController@index` | staff/admin |
| GET | `/hr/viec-lam/tao-moi` | `hr.jobs.create` | `JobController@create` | staff/admin |
| POST | `/hr/viec-lam` | `hr.jobs.store` | `JobController@store` | staff/admin |
| GET | `/hr/viec-lam/{job}/sua` | `hr.jobs.edit` | `JobController@edit` | staff/admin |
| PUT | `/hr/viec-lam/{job}` | `hr.jobs.update` | `JobController@update` | staff/admin |
| POST | `/hr/viec-lam/{job}/xuat-ban` | `hr.jobs.publish` | `JobWorkflowController@publish` | staff/admin — dùng cho cả `draft→published` và `paused→published`; lần publish đầu bắt buộc đã có `job_verifications.result=still_open` trừ Admin override có lý do (`docs/CORE-FLOWS.md` mục 1.2 điều kiện 11, mục 1.3, ADR-047) |
| POST | `/hr/viec-lam/{job}/tam-dung` | `hr.jobs.pause` | `JobWorkflowController@pause` | staff/admin |
| POST | `/hr/viec-lam/{job}/dong` | `hr.jobs.close` | `JobWorkflowController@close` | staff/admin |
| POST | `/hr/viec-lam/{job}/nhan-ban` | `hr.jobs.duplicate` | `JobWorkflowController@duplicate` | staff/admin |
| POST | `/hr/viec-lam/{job}/xac-nhan` | `hr.jobs.verify` | `JobVerificationController@store` | staff/admin |
| POST | `/hr/viec-lam/{job}/chuyen-co-so` | `hr.jobs.transfer-branch` | `JobBranchTransferController@store` | admin — điều kiện đầy đủ: `docs/CORE-FLOWS.md` mục 1.1 (Job phải `draft` hoặc `paused`, chưa `deleted_at` — **không** `published` hoặc `closed`, ADR-054) |
| DELETE | `/hr/viec-lam/{job}` | `hr.jobs.destroy` | `JobController@destroy` | admin |
| POST | `/hr/viec-lam/{job}/khoi-phuc` | `hr.jobs.restore` | `JobController@restore` | admin |

`hr.jobs.update` không được phép sửa `owner_branch_id` — chỉ `hr.jobs.store` (gán lần đầu) và
`hr.jobs.transfer-branch` (đổi cơ sở, chỉ admin) được ghi cột này (`docs/CORE-FLOWS.md` mục
1.1).

## HR hồ sơ

`hr.applications.index`/`show` và mọi thao tác bên dưới chỉ dành cho **Staff thuộc đúng cơ sở
của Application, hoặc Admin** (403 nếu Staff cố truy cập URL của cơ sở khác; Admin không bị
giới hạn). Xem `docs/CORE-FLOWS.md` mục 4. Phase 1 không có route "nhận xử lý"/"gán nhân
viên" — bất kỳ staff thuộc đúng cơ sở đều xử lý được mọi hồ sơ của cơ sở đó (ADR-021).

| Method | Path | Name | Controller@method | Quyền |
|---|---|---|---|---|
| GET | `/hr/ho-so/xuat-csv` | `hr.applications.export` | `ApplicationExportController@index` | admin |
| GET | `/hr/ho-so` | `hr.applications.index` | `ApplicationController@index` | Staff thuộc đúng cơ sở hoặc Admin — bộ lọc đầy đủ và Dashboard KPI: `docs/CORE-FLOWS.md` mục 9 |
| GET | `/hr/ho-so/{application}` | `hr.applications.show` | `ApplicationController@show` | Staff thuộc đúng cơ sở hoặc Admin |
| POST | `/hr/ho-so/{application}/doi-giai-doan` | `hr.applications.stage` | `ApplicationStageController@store` | Staff thuộc đúng cơ sở hoặc Admin — dùng chung cho mọi transition trong `docs/CORE-FLOWS.md` mục 5.1, kể cả mở lại `closed → new` (điều kiện riêng theo mục 5.5, một số trường hợp chỉ Admin) |
| POST | `/hr/ho-so/{application}/lien-he` | `hr.applications.contacts.store` | `ContactAttemptController@store` | Staff thuộc đúng cơ sở hoặc Admin |
| POST | `/hr/ho-so/{application}/lich-hen` | `hr.applications.appointments.store` | `AppointmentController@store` | Staff thuộc đúng cơ sở hoặc Admin — dùng cả khi tạo lịch mới lẫn khi đổi lịch (kèm hủy lịch cũ, `docs/CORE-FLOWS.md` mục 5.3) |
| PUT | `/hr/ho-so/{application}/lich-hen/{appointment}` | `hr.applications.appointments.update` | `AppointmentController@update` | Staff thuộc đúng cơ sở hoặc Admin — chỉ cập nhật `status`/`outcome`, không sửa `scheduled_at` |
| POST | `/hr/ho-so/{application}/chuyen-co-so` | `hr.applications.transfer-branch` | `ApplicationBranchTransferController@store` | admin — điều kiện đầy đủ: `docs/CORE-FLOWS.md` mục 6.1 |
| POST | `/hr/ho-so/{application}/ghi-chu` | `hr.applications.notes.store` | `ApplicationNoteController@store` | Staff thuộc đúng cơ sở hoặc Admin |
| PUT/DELETE | `/hr/ho-so/{application}/ghi-chu/{note}` | `hr.applications.notes.update/destroy` | `ApplicationNoteController@update/destroy` | owner/admin |

**Đã bỏ khỏi Phase 1** (chuyển Phase 2 — ADR-021): `hr.applications.claim`,
`hr.applications.assign` (không còn khái niệm phân công nhân viên cụ thể);
`hr.leads.index`/`hr.leads.show`/`hr.leads.convert` và toàn bộ route `/hr/yeu-cau-tu-van`
(không còn `lead_requests` trong Phase 1).

## HR candidate

Trang chi tiết Candidate hiển thị "merged family" (`docs/CORE-FLOWS.md` mục 6.3) — với Staff,
danh sách Application trong family vẫn lọc theo cơ sở như trên, không phải ngoại lệ.

| Method | Path | Name | Controller@method | Quyền |
|---|---|---|---|---|
| GET | `/hr/ung-vien/{candidate}` | `hr.candidates.show` | `CandidateController@show` | Staff thuộc đúng cơ sở (chỉ thấy Application cơ sở mình trong family) hoặc Admin (toàn bộ) |
| POST | `/hr/ung-vien/{candidate}/gop` | `hr.candidates.merge` | `CandidateMergeController@store` | admin — điều kiện đầy đủ: `docs/CORE-FLOWS.md` mục 6.3 |

## HR admin

Tài khoản staff, pages, FAQs và settings dùng resource routes tách method/ID tương tự các nhóm trên; chỉ `admin` truy cập. Không dùng route gộp `GET,POST,PUT` trên cùng một path.
