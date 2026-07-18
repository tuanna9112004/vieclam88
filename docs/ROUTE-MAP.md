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
| POST | `/viec-lam/{job:slug}/ung-tuyen` | `applications.store` | `ApplicationController@store` | web, throttle, honeypot | Ứng tuyển guest/candidate |
| GET | `/cong-ty` | `companies.index` | `CompanyController@index` | web | Danh sách công ty |
| GET | `/cong-ty/{company:slug}` | `companies.show` | `CompanyController@show` | web | Chi tiết công ty |
| GET | `/khu-cong-nghiep/{industrialPark:slug}` | `industrial-parks.show` | `IndustrialParkController@show` | web | Việc theo KCN |
| GET | `/gioi-thieu` | `pages.about` | `PageController@about` | web | Trang giới thiệu |
| GET | `/lien-he` | `contact.show` | `ContactController@show` | web | Trang liên hệ |
| POST | `/lien-he/tu-van` | `leads.store` | `LeadRequestController@store` | web, throttle, honeypot | Yêu cầu tư vấn |
| GET | `/cau-hoi-thuong-gap` | `faqs.index` | `FaqController@index` | web | FAQ |
| GET | `/sitemap.xml` | `sitemap` | `SitemapController@index` | throttle | Sitemap |

## Candidate account — làm sau guest + HR

| Method | Path | Name | Controller@method | Middleware |
|---|---|---|---|---|
| GET/POST | `/dang-ky` | `register.*` | Auth controllers | guest |
| GET/POST | `/dang-nhap` | `login.*` | Auth controllers | guest |
| POST | `/dang-xuat` | `logout` | `AuthenticatedSessionController@destroy` | auth |
| GET/POST | `/quen-mat-khau` | `password.request/email` | Password controllers | guest |
| GET/POST | `/dat-lai-mat-khau/{token}` | `password.reset/update` | Password controllers | guest |
| GET | `/tai-khoan` | `account.show` | `AccountController@show` | auth, role:candidate |
| PUT | `/tai-khoan` | `account.update` | `AccountController@update` | auth, role:candidate |
| POST | `/viec-lam/{job:slug}/luu` | `favorites.store` | `FavoriteController@store` | auth, role:candidate |
| DELETE | `/viec-lam/{job:slug}/luu` | `favorites.destroy` | `FavoriteController@destroy` | auth, role:candidate |
| GET | `/tai-khoan/viec-da-luu` | `account.favorites` | `AccountController@favorites` | auth, role:candidate |
| GET | `/tai-khoan/da-ung-tuyen` | `account.applications` | `AccountController@applications` | auth, role:candidate |

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

| Method | Path | Name | Controller@method | Quyền |
|---|---|---|---|---|
| GET | `/hr/cong-ty` | `hr.companies.index` | `CompanyController@index` | staff/admin |
| GET | `/hr/cong-ty/tao-moi` | `hr.companies.create` | `CompanyController@create` | staff/admin |
| POST | `/hr/cong-ty` | `hr.companies.store` | `CompanyController@store` | staff/admin |
| GET | `/hr/cong-ty/{company}/sua` | `hr.companies.edit` | `CompanyController@edit` | staff/admin |
| PUT | `/hr/cong-ty/{company}` | `hr.companies.update` | `CompanyController@update` | staff/admin |
| DELETE | `/hr/cong-ty/{company}` | `hr.companies.destroy` | `CompanyController@destroy` | admin |
| POST | `/hr/cong-ty/{company}/khoi-phuc` | `hr.companies.restore` | `CompanyController@restore` | admin |
| GET/POST | `/hr/cong-ty/{company}/dia-diem` | `hr.company-locations.index/store` | `CompanyLocationController@index/store` | staff/admin |
| PUT/DELETE | `/hr/cong-ty/{company}/dia-diem/{location}` | `hr.company-locations.update/destroy` | `CompanyLocationController@update/destroy` | staff/admin |
| GET/POST | `/hr/cong-ty/{company}/dau-moi` | `hr.company-contacts.index/store` | `CompanyContactController@index/store` | staff/admin |
| PUT/DELETE | `/hr/cong-ty/{company}/dau-moi/{contact}` | `hr.company-contacts.update/destroy` | `CompanyContactController@update/destroy` | staff/admin |

## HR việc làm

| Method | Path | Name | Controller@method | Quyền |
|---|---|---|---|---|
| GET | `/hr/viec-lam` | `hr.jobs.index` | `JobController@index` | staff/admin |
| GET | `/hr/viec-lam/tao-moi` | `hr.jobs.create` | `JobController@create` | staff/admin |
| POST | `/hr/viec-lam` | `hr.jobs.store` | `JobController@store` | staff/admin |
| GET | `/hr/viec-lam/{job}/sua` | `hr.jobs.edit` | `JobController@edit` | staff/admin |
| PUT | `/hr/viec-lam/{job}` | `hr.jobs.update` | `JobController@update` | staff/admin |
| POST | `/hr/viec-lam/{job}/xuat-ban` | `hr.jobs.publish` | `JobWorkflowController@publish` | staff/admin |
| POST | `/hr/viec-lam/{job}/tam-dung` | `hr.jobs.pause` | `JobWorkflowController@pause` | staff/admin |
| POST | `/hr/viec-lam/{job}/dong` | `hr.jobs.close` | `JobWorkflowController@close` | staff/admin |
| POST | `/hr/viec-lam/{job}/nhan-ban` | `hr.jobs.duplicate` | `JobWorkflowController@duplicate` | staff/admin |
| POST | `/hr/viec-lam/{job}/xac-nhan` | `hr.jobs.verify` | `JobVerificationController@store` | staff/admin |
| DELETE | `/hr/viec-lam/{job}` | `hr.jobs.destroy` | `JobController@destroy` | admin |
| POST | `/hr/viec-lam/{job}/khoi-phuc` | `hr.jobs.restore` | `JobController@restore` | admin |

## HR hồ sơ và lead

`hr.applications.index`/`show` và mọi thao tác bên dưới **scope theo cơ sở**: `staff` chỉ
truy cập Application có `owner_branch_id = users.branch_id` của mình (403 nếu cố truy cập
URL của cơ sở khác); `admin` không bị giới hạn. Xem `docs/CORE-FLOWS.md` mục 4.

| Method | Path | Name | Controller@method | Quyền |
|---|---|---|---|---|
| GET | `/hr/ho-so/xuat-csv` | `hr.applications.export` | `ApplicationExportController@index` | admin |
| GET | `/hr/ho-so` | `hr.applications.index` | `ApplicationController@index` | staff/admin (scope cơ sở) |
| GET | `/hr/ho-so/{application}` | `hr.applications.show` | `ApplicationController@show` | staff/admin (scope cơ sở) |
| POST | `/hr/ho-so/{application}/nhan-xu-ly` | `hr.applications.claim` | `AssignmentController@claim` | staff/admin (scope cơ sở) |
| POST | `/hr/ho-so/{application}/gan-nhan-vien` | `hr.applications.assign` | `AssignmentController@assign` | admin |
| POST | `/hr/ho-so/{application}/doi-giai-doan` | `hr.applications.stage` | `ApplicationStageController@store` | staff/admin (scope cơ sở) |
| POST | `/hr/ho-so/{application}/lien-he` | `hr.applications.contacts.store` | `ContactAttemptController@store` | staff/admin (scope cơ sở) |
| POST | `/hr/ho-so/{application}/lich-hen` | `hr.applications.appointments.store` | `AppointmentController@store` | staff/admin (scope cơ sở) |
| PUT | `/hr/ho-so/{application}/lich-hen/{appointment}` | `hr.applications.appointments.update` | `AppointmentController@update` | staff/admin (scope cơ sở) — cập nhật `status`/`outcome` |
| POST | `/hr/ho-so/{application}/chuyen-co-so` | `hr.applications.transfer-branch` | `ApplicationBranchTransferController@store` | admin |
| POST | `/hr/ho-so/{application}/ghi-chu` | `hr.applications.notes.store` | `ApplicationNoteController@store` | staff/admin (scope cơ sở) |
| PUT/DELETE | `/hr/ho-so/{application}/ghi-chu/{note}` | `hr.applications.notes.update/destroy` | `ApplicationNoteController@update/destroy` | owner/admin |
| GET | `/hr/yeu-cau-tu-van` | `hr.leads.index` | `LeadRequestController@index` | staff/admin |
| GET | `/hr/yeu-cau-tu-van/{leadRequest}` | `hr.leads.show` | `LeadRequestController@show` | staff/admin |

**Đã bỏ khỏi Phase 1** (chuyển Phase 2 — xem ADR-018, `ROADMAP.md`):
`hr.leads.convert`/`LeadConversionController` — Phase 1 không chuyển đổi `lead_requests`
thành `applications`. Nhân viên xử lý lead thủ công, không có route/action chuyển đổi.

## HR admin

Tài khoản staff, pages, FAQs và settings dùng resource routes tách method/ID tương tự các nhóm trên; chỉ `admin` truy cập. Không dùng route gộp `GET,POST,PUT` trên cùng một path.
