# Route Map — Phase 1

6 luồng nghiệp vụ mà các route dưới đây phải hỗ trợ đúng: `docs/CORE-FLOWS.md`.

> Toàn bộ route dưới đây là route **thật đang chạy**. ADR-080 (kiến trúc mục tiêu Phase 2,
> `docs/PHASE-2-ARCHITECTURE-PROPOSAL.md`) chưa xác định route/UI cụ thể cho `industries`,
> `employment_types`, `job_images`, `candidate_documents` — PDF chỉ đặc tả dữ
> liệu, không đặc tả route. Không thêm route theo suy đoán; route mới sẽ được ghi vào bảng dưới
> khi task migrate tương ứng thực sự triển khai.

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
`/hr/dang-nhap` bên dưới là route đăng nhập riêng cho ba role HR, không liên quan.

## HR auth/dashboard

| Method | Path | Name | Controller@method | Middleware |
|---|---|---|---|---|
| GET | `/hr/dang-nhap` | `hr.login` | `HrAuthController@create` | guest |
| POST | `/hr/dang-nhap` | `hr.login.store` | `HrAuthController@store` | guest, throttle |
| POST | `/hr/dang-xuat` | `hr.logout` | `HrAuthController@destroy` | auth, role:staff,branch_admin,super_admin |
| GET | `/hr` | `hr.dashboard` | `DashboardController@index` | auth, role:staff,branch_admin,super_admin |

Các route bên dưới đều có middleware `auth`, `EnsureUserIsActive`,
`role:staff,branch_admin,super_admin`, sau đó `EnsurePasswordChanged`; quyền chi tiết dùng Policy.
`EnsureUserIsActive` cũng logout role theo branch khi branch thiếu/inactive/deleted.

## HR danh mục

| Method | Path | Name | Controller@method | Quyền |
|---|---|---|---|---|
| GET | `/hr/don-vi-hanh-chinh` | `hr.administrative-units.index` | `AdministrativeUnitController@index` | super_admin |
| POST | `/hr/don-vi-hanh-chinh` | `hr.administrative-units.store` | `AdministrativeUnitController@store` | super_admin |
| PUT | `/hr/don-vi-hanh-chinh/{administrativeUnit}` | `hr.administrative-units.update` | `AdministrativeUnitController@update` | super_admin |
| GET | `/hr/khu-cong-nghiep` | `hr.industrial-parks.index` | `IndustrialParkController@index` | super_admin |
| POST | `/hr/khu-cong-nghiep` | `hr.industrial-parks.store` | `IndustrialParkController@store` | super_admin |
| PUT | `/hr/khu-cong-nghiep/{industrialPark}` | `hr.industrial-parks.update` | `IndustrialParkController@update` | super_admin |
| GET | `/hr/co-so` | `hr.branches.index` | `BranchController@index` | super_admin; branch_admin chỉ cơ sở mình |
| GET | `/hr/co-so/tao-moi` | `hr.branches.create` | `BranchController@create` | super_admin |
| POST | `/hr/co-so` | `hr.branches.store` | `BranchController@store` | super_admin |
| GET | `/hr/co-so/{branch}/sua` | `hr.branches.edit` | `BranchController@edit` | super_admin; branch_admin đúng cơ sở |
| PUT | `/hr/co-so/{branch}` | `hr.branches.update` | `BranchController@update` | super_admin; branch_admin đúng cơ sở |
| DELETE | `/hr/co-so/{branch}` | `hr.branches.destroy` | `BranchController@destroy` | super_admin |
| POST | `/hr/co-so/{branch}/khoi-phuc` | `hr.branches.restore` | `BranchController@restore` | super_admin |

`branches` = cơ sở nội bộ vieclam88. Super Admin quản lý toàn hệ thống; Branch Admin chỉ xem/sửa
cơ sở mình. Gán
nhân viên vào cơ sở làm ở form tài khoản staff (`hr.staff.*`, mục "HR admin"), không có route
riêng.

## HR công ty

Quick Create (`docs/CORE-FLOWS.md` mục 0.2, 0.3, ADR-045): `hr.companies.store` chỉ bắt buộc
`name`; `hr.company-locations.store` chỉ bắt buộc `name` (`administrative_unit_id`/
`address_detail` tùy chọn, bổ sung sau).

| Method | Path | Name | Controller@method | Quyền |
|---|---|---|---|---|
| GET | `/hr/cong-ty` | `hr.companies.index` | `CompanyController@index` | staff/branch_admin/super_admin |
| GET | `/hr/cong-ty/tao-moi` | `hr.companies.create` | `CompanyController@create` | staff/branch_admin/super_admin |
| POST | `/hr/cong-ty` | `hr.companies.store` | `CompanyController@store` | staff/branch_admin/super_admin |
| GET | `/hr/cong-ty/{company}/sua` | `hr.companies.edit` | `CompanyController@edit` | staff/branch_admin/super_admin |
| PUT | `/hr/cong-ty/{company}` | `hr.companies.update` | `CompanyController@update` | staff/branch_admin/super_admin |
| DELETE | `/hr/cong-ty/{company}` | `hr.companies.destroy` | `CompanyController@destroy` | super_admin |
| POST | `/hr/cong-ty/{company}/khoi-phuc` | `hr.companies.restore` | `CompanyController@restore` | super_admin |
| GET | `/hr/cong-ty/{company}/dia-diem` | `hr.company-locations.index` | `CompanyLocationController@index` | staff/branch_admin/super_admin |
| POST | `/hr/cong-ty/{company}/dia-diem` | `hr.company-locations.store` | `CompanyLocationController@store` | staff/branch_admin/super_admin — validate tỉnh khớp KCN |
| PUT | `/hr/cong-ty/{company}/dia-diem/{location}` | `hr.company-locations.update` | `CompanyLocationController@update` | staff/branch_admin/super_admin — validate tỉnh khớp KCN |
| DELETE | `/hr/cong-ty/{company}/dia-diem/{location}` | `hr.company-locations.destroy` | `CompanyLocationController@destroy` | super_admin |
| POST | `/hr/cong-ty/{company}/dia-diem/{location}/khoi-phuc` | `hr.company-locations.restore` | `CompanyLocationController@restore` | super_admin |
| GET | `/hr/cong-ty/{company}/dau-moi` | `hr.company-contacts.index` | `CompanyContactController@index` | staff/branch_admin/super_admin |
| POST | `/hr/cong-ty/{company}/dau-moi` | `hr.company-contacts.store` | `CompanyContactController@store` | staff/branch_admin/super_admin |
| PUT | `/hr/cong-ty/{company}/dau-moi/{contact}` | `hr.company-contacts.update` | `CompanyContactController@update` | staff/branch_admin/super_admin |
| DELETE | `/hr/cong-ty/{company}/dau-moi/{contact}` | `hr.company-contacts.destroy` | `CompanyContactController@destroy` | super_admin |
| POST | `/hr/cong-ty/{company}/dau-moi/{contact}/khoi-phuc` | `hr.company-contacts.restore` | `CompanyContactController@restore` | super_admin |

## HR việc làm

`hr.jobs.publish/pause/close` dùng chung `JobWorkflowController` gọi `ChangeJobStatusAction`,
ghi `job_status_histories` mỗi lần (`docs/CORE-FLOWS.md` mục 1). "Mở lại" job (`paused →
published`) dùng lại chính route `hr.jobs.publish` — action tự re-check toàn bộ điều kiện
publish, không có route riêng cho "reopen".

| Method | Path | Name | Controller@method | Quyền |
|---|---|---|---|---|
| GET | `/hr/viec-lam` | `hr.jobs.index` | `JobController@index` | super_admin toàn hệ thống; branch_admin/staff chỉ cơ sở mình |
| GET | `/hr/viec-lam/tao-moi` | `hr.jobs.create` | `JobController@create` | staff/branch_admin/super_admin |
| POST | `/hr/viec-lam` | `hr.jobs.store` | `JobController@store` | staff/branch_admin/super_admin |
| GET | `/hr/viec-lam/{job}/sua` | `hr.jobs.edit` | `JobController@edit` | super_admin; branch_admin/staff đúng cơ sở |
| PUT | `/hr/viec-lam/{job}` | `hr.jobs.update` | `JobController@update` | super_admin; branch_admin/staff đúng cơ sở |
| POST | `/hr/viec-lam/{job}/xuat-ban` | `hr.jobs.publish` | `JobWorkflowController@publish` | super_admin; branch_admin/staff đúng cơ sở |
| POST | `/hr/viec-lam/{job}/tam-dung` | `hr.jobs.pause` | `JobWorkflowController@pause` | super_admin; branch_admin/staff đúng cơ sở |
| POST | `/hr/viec-lam/{job}/dong` | `hr.jobs.close` | `JobWorkflowController@close` | super_admin; branch_admin/staff đúng cơ sở |
| POST | `/hr/viec-lam/{job}/nhan-ban` | `hr.jobs.duplicate` | `JobWorkflowController@duplicate` | super_admin; branch_admin/staff đúng cơ sở |
| POST | `/hr/viec-lam/{job}/xac-nhan` | `hr.jobs.verify` | `JobVerificationController@store` | super_admin; branch_admin/staff đúng cơ sở |
| POST | `/hr/viec-lam/{job}/chuyen-co-so` | `hr.jobs.transfer-branch` | `JobBranchTransferController@store` | super_admin |
| DELETE | `/hr/viec-lam/{job}` | `hr.jobs.destroy` | `JobController@destroy` | super_admin |
| POST | `/hr/viec-lam/{job}/khoi-phuc` | `hr.jobs.restore` | `JobController@restore` | super_admin |

`hr.jobs.update` không được phép sửa `owner_branch_id` — chỉ `hr.jobs.store` (gán lần đầu) và
`hr.jobs.transfer-branch` (đổi cơ sở, chỉ super_admin) được ghi cột này (`docs/CORE-FLOWS.md` mục
1.1).

## HR hồ sơ

`hr.applications.index`/`show` và mọi thao tác bên dưới chỉ dành cho **Branch Admin/Staff thuộc
đúng cơ sở của Application, hoặc Super Admin** (403 khi role theo branch truy cập chéo cơ sở).
Xem `docs/CORE-FLOWS.md` mục 4. Phase 1 không có route "nhận xử lý"/"gán nhân
viên" — bất kỳ staff thuộc đúng cơ sở đều xử lý được mọi hồ sơ của cơ sở đó (ADR-021).

| Method | Path | Name | Controller@method | Quyền |
|---|---|---|---|---|
| GET | `/hr/ho-so/xuat-csv` | `hr.applications.export` | `ApplicationExportController@index` | super_admin toàn hệ thống; branch_admin/staff chỉ cơ sở mình |
| GET | `/hr/ho-so` | `hr.applications.index` | `ApplicationController@index` | super_admin; branch_admin/staff đúng cơ sở |
| GET | `/hr/ho-so/{application}` | `hr.applications.show` | `ApplicationController@show` | super_admin; branch_admin/staff đúng cơ sở |
| POST | `/hr/ho-so/{application}/doi-giai-doan` | `hr.applications.stage` | `ApplicationStageController@store` | super_admin; branch_admin/staff đúng cơ sở |
| POST | `/hr/ho-so/{application}/lien-he` | `hr.applications.contacts.store` | `ContactAttemptController@store` | super_admin; branch_admin/staff đúng cơ sở |
| POST | `/hr/ho-so/{application}/lich-hen` | `hr.applications.appointments.store` | `AppointmentController@store` | super_admin; branch_admin/staff đúng cơ sở |
| PUT | `/hr/ho-so/{application}/lich-hen/{appointment}` | `hr.applications.appointments.update` | `AppointmentController@update` | super_admin; branch_admin/staff đúng cơ sở |
| POST | `/hr/ho-so/{application}/chuyen-co-so` | `hr.applications.transfer-branch` | `ApplicationBranchTransferController@store` | super_admin |
| POST | `/hr/ho-so/{application}/ghi-chu` | `hr.applications.notes.store` | `ApplicationNoteController@store` | super_admin; branch_admin/staff đúng cơ sở |
| PUT | `/hr/ho-so/{application}/ghi-chu/{note}` | `hr.applications.notes.update` | `ApplicationNoteController@update` | note creator hoặc super_admin, qua Branch Policy |
| DELETE | `/hr/ho-so/{application}/ghi-chu/{note}` | `hr.applications.notes.destroy` | `ApplicationNoteController@destroy` | note creator hoặc super_admin, qua Branch Policy |

**Đã bỏ khỏi Phase 1** (chuyển Phase 2 — ADR-021): `hr.applications.claim`,
`hr.applications.assign` (không còn khái niệm phân công nhân viên cụ thể);
`hr.leads.index`/`hr.leads.show`/`hr.leads.convert` và toàn bộ route `/hr/yeu-cau-tu-van`
(không còn `lead_requests` trong Phase 1).

## HR candidate

Trang chi tiết Candidate hiển thị "merged family" (`docs/CORE-FLOWS.md` mục 6.3) — với role theo branch,
danh sách Application trong family vẫn lọc theo cơ sở như trên, không phải ngoại lệ.

| Method | Path | Name | Controller@method | Quyền |
|---|---|---|---|---|
| GET | `/hr/ung-vien/{candidate}` | `hr.candidates.show` | `CandidateController@show` | super_admin; branch_admin/staff đúng cơ sở |
| POST | `/hr/ung-vien/{candidate}/gop` | `hr.candidates.merge` | `CandidateMergeController@store` | super_admin |
| POST | `/hr/ung-vien/{candidate}/an-danh` | `hr.candidates.anonymize` | `CandidateAnonymizeController@store` | super_admin |

## HR duplicate review (mới, ADR-062)

Chỉ **super_admin** truy cập. Xem
`docs/CORE-FLOWS.md` mục 6.2.2.

| Method | Path | Name | Controller@method | Quyền |
|---|---|---|---|---|
| GET | `/hr/nghi-ngo-trung` | `hr.duplicate-reviews.index` | `DuplicateReviewController@index` | super_admin |
| GET | `/hr/nghi-ngo-trung/{duplicateReview}` | `hr.duplicate-reviews.show` | `DuplicateReviewController@show` | super_admin |
| POST | `/hr/nghi-ngo-trung/{duplicateReview}/xu-ly` | `hr.duplicate-reviews.resolve` | `DuplicateReviewController@resolve` | super_admin |

## HR admin

Super Admin truy cập toàn bộ; Branch Admin chỉ truy cập quản lý Staff/Branch đúng cơ sở.
Không dùng route gộp
`GET,POST,PUT` trên cùng một path.

### Đổi mật khẩu (bắt buộc trước khi vào HR — ADR-067)

| Method | Path | Name | Controller@method | Quyền |
|---|---|---|---|---|
| GET | `/hr/doi-mat-khau` | `hr.password.change` | `PasswordChangeController@edit` | auth (ba role HR) |
| PUT | `/hr/doi-mat-khau` | `hr.password.update` | `PasswordChangeController@update` | auth (ba role HR) |

### Tài khoản Staff

| Method | Path | Name | Controller@method | Quyền |
|---|---|---|---|---|
| GET | `/hr/nhan-vien` | `hr.staff.index` | `StaffController@index` | super_admin; branch_admin chỉ cơ sở mình |
| GET | `/hr/nhan-vien/tao-moi` | `hr.staff.create` | `StaffController@create` | super_admin/branch_admin |
| POST | `/hr/nhan-vien` | `hr.staff.store` | `StaffController@store` | super_admin; branch_admin chỉ gán cơ sở mình |
| GET | `/hr/nhan-vien/{staff}/sua` | `hr.staff.edit` | `StaffController@edit` | super_admin; branch_admin đúng cơ sở |
| PUT | `/hr/nhan-vien/{staff}` | `hr.staff.update` | `StaffController@update` | super_admin; branch_admin đúng cơ sở |
| POST | `/hr/nhan-vien/{staff}/khoa` | `hr.staff.lock` | `StaffController@lock` | super_admin; branch_admin đúng cơ sở |
| POST | `/hr/nhan-vien/{staff}/mo-khoa` | `hr.staff.unlock` | `StaffController@unlock` | super_admin; branch_admin đúng cơ sở |
| POST | `/hr/nhan-vien/{staff}/dat-lai-mat-khau` | `hr.staff.reset-password` | `StaffController@resetPassword` | super_admin; branch_admin đúng cơ sở |

### Cấu hình

| Method | Path | Name | Controller@method | Quyền |
|---|---|---|---|---|
| GET | `/hr/cau-hinh` | `hr.settings.index` | `SettingController@index` | super_admin |
| PUT | `/hr/cau-hinh` | `hr.settings.update` | `SettingController@update` | super_admin |

### Trang tĩnh (Pages) và FAQ

| Method | Path | Name | Controller@method | Quyền |
|---|---|---|---|---|
| GET | `/hr/trang` | `hr.pages.index` | `PageController@index` | super_admin |
| GET | `/hr/trang/tao-moi` | `hr.pages.create` | `PageController@create` | super_admin |
| POST | `/hr/trang` | `hr.pages.store` | `PageController@store` | super_admin |
| GET | `/hr/trang/{page}/sua` | `hr.pages.edit` | `PageController@edit` | super_admin |
| PUT | `/hr/trang/{page}` | `hr.pages.update` | `PageController@update` | super_admin |
| DELETE | `/hr/trang/{page}` | `hr.pages.destroy` | `PageController@destroy` | super_admin |
| GET | `/hr/cau-hoi-thuong-gap` | `hr.faqs.index` | `FaqController@index` | super_admin |
| GET | `/hr/cau-hoi-thuong-gap/tao-moi` | `hr.faqs.create` | `FaqController@create` | super_admin |
| POST | `/hr/cau-hoi-thuong-gap` | `hr.faqs.store` | `FaqController@store` | super_admin |
| GET | `/hr/cau-hoi-thuong-gap/{faq}/sua` | `hr.faqs.edit` | `FaqController@edit` | super_admin |
| PUT | `/hr/cau-hoi-thuong-gap/{faq}` | `hr.faqs.update` | `FaqController@update` | super_admin |
| DELETE | `/hr/cau-hoi-thuong-gap/{faq}` | `hr.faqs.destroy` | `FaqController@destroy` | super_admin |
