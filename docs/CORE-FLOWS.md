# Core Flows — vieclam88 (Phase 1)

Nguồn sự thật duy nhất cho 6 luồng nghiệp vụ cốt lõi (CRITICAL BUSINESS FLOWS). Database
(`docs/ERD.md`, `docs/DATABASE-DICTIONARY.md`), route (`docs/ROUTE-MAP.md`), rule
(`.claude/rules/*`), acceptance criteria (`docs/ACCEPTANCE-CRITERIA.md`) và ADR
(`docs/decisions/INDEX.md`) phải khớp file này. Nếu phát hiện mâu thuẫn, dừng lại và cập nhật file
này trước, không tự chọn diễn giải khác ở nơi khác.

Một chức năng chỉ tính là hoàn thành khi luồng tương ứng chạy đúng từ đầu đến cuối, không tạo
trùng, không sai cơ sở, không mất lịch sử, **không để dữ liệu của một chu kỳ xử lý cũ mở khóa
trạng thái của chu kỳ mới** (mục 5.4), và có test.

**Quy ước diễn đạt phân quyền:** "Staff thuộc đúng cơ sở hoặc Admin" nghĩa là: `users.role =
staff` VÀ `users.branch_id = applications.owner_branch_id`, HOẶC `users.role = admin` (admin
không bị giới hạn cơ sở). Không dùng cụm "Staff/admin cùng cơ sở" (không chính xác — admin
không cần cùng cơ sở).

## Định hướng Phase 2 (ADR-080, CHƯA áp dụng)

> Toàn bộ nội dung mục 0–9 bên dưới mô tả đúng luồng nghiệp vụ **đang chạy thật**. Đoạn này chỉ
> tóm tắt các thay đổi luồng mà ADR-080 (`docs/decisions/architecture-and-platform.md`) đã duyệt
> làm mục tiêu Phase 2 — chi tiết đầy đủ ở `docs/PHASE-2-ARCHITECTURE-PROPOSAL.md`, schema target
> ở `docs/DATABASE-DICTIONARY.md` mục 9.29–9.35. Không code theo đoạn này khi task không thuộc
> đúng batch migration đang thực thi.

- **Phân quyền**: thêm vai trò `branch_admin` (batch 3) giữa `staff` và `super_admin` (= `admin`
  hiện tại) — quy ước "Staff thuộc đúng cơ sở hoặc Admin" ở trên **chưa đổi** cho tới khi batch 3
  chạy và có Policy cho `branch_admin`.
- **Địa chỉ**: nhân viên chọn tỉnh/xã từ `provinces`/`wards` thay `administrative_units` (batch 1)
  — luồng Quick Create Company Location mục 0.3 giữ nguyên tinh thần "chưa biết thì để trống",
  chỉ đổi bảng nguồn.
- **KCN**: một KCN có thể trải nhiều xã/phường (batch 2) — luồng xác nhận nhu cầu tuyển tại KCN
  không đổi, chỉ đổi cách khớp ward hợp lệ.
- **Company/Job**: thêm luồng "tuyển trực tiếp" (`job_type=direct`, không cần Company) — **điều
  kiện xác nhận nhu cầu, ai được tạo, hiển thị public thế nào [CẦN CHỐT VỚI CÔNG TY]** trước khi
  code batch 7; địa chỉ làm việc chuyển hẳn vào Job (`work_ward_id`) thay vì qua Company Location
  (batch 5).
- **Chuyên ngành/loại hình**: Job bắt buộc chọn `industry_id` và `employment_type_id` từ danh mục
  (batch 4/5) thay vì loại hình tự do hiện tại — thêm điều kiện publish mới, không thay các điều
  kiện `PUB-*` hiện có.
- **Ảnh Job, CV ứng viên**: 2 tính năng hoàn toàn mới (batch 6), không thay đổi luồng nộp hồ sơ
  hiện tại (mục 3) — chỉ thêm bước upload tùy chọn.
- **Không đổi**: chống trùng Candidate/Application, `workflow_cycle`, Duplicate Review/Merge,
  Reopen, Transfer Branch, Anonymize, Verification Scheduler — PDF không đề cập các luồng này,
  giữ nguyên nội dung mục 5–7 bên dưới.

## 0. Phạm vi Phase 1

Phase 1 chỉ xử lý hồ sơ ứng viên gửi về qua **form ứng tuyển trên website**
(`applications.store`), nộp dạng **guest** (không cần tài khoản). Luồng tổng:

```
Công ty/nhà máy có nhu cầu tuyển (điện thoại/Zalo/gặp trực tiếp — ngoài hệ thống)
→ nhân viên tạo nhanh Company nếu chưa có (mục 0.2) → tạo nhanh Company Location (mục 0.3)
→ tạo Job (draft, mục 1.0) → xác minh thủ công nhu cầu tuyển (job_verifications)
→ hoàn thiện thông tin còn thiếu → đủ điều kiện publish (mục 1.2) → Job status=published
→ ứng viên tìm việc → xem chi tiết → gửi form ứng tuyển
→ hệ thống kiểm tra dữ liệu và chống trùng → tạo Application đúng cơ sở
→ nhân viên gọi điện/Zalo thủ công (ngoài hệ thống) → ghi kết quả liên hệ
→ cập nhật trạng thái → hẹn phỏng vấn → cập nhật kết quả phỏng vấn
→ chờ đi làm → xác nhận đã đi làm hoặc đóng hồ sơ.
```

**Phase 1 KHÔNG triển khai** (chuyển Phase 2 — xem `ROADMAP.md`):

- **Lead** dưới mọi hình thức (không `lead_requests`, không form "yêu cầu tư vấn").
- **Assignment/claim hồ sơ** dưới mọi hình thức (không "nhận xử lý", không "gán nhân viên",
  không tự động chia hồ sơ, không round-robin, không bảng lịch sử phân công).
- **Candidate Account** dưới mọi hình thức — không đăng ký/đăng nhập cho ứng viên, không
  `/tai-khoan`, không dashboard ứng viên, không theo dõi trạng thái hồ sơ qua tài khoản, không
  `candidates.user_id`, không giá trị `candidate` trong `users.role` (mục 0.1, ADR-028). Ứng
  viên Phase 1 **luôn là guest**.
- **Favorites** — không có bảng, không có route.
- Tích hợp Zalo API, tự động gọi/gửi tin nhắn, CRM đa kênh, AI matching, cộng tác viên/hoa hồng
  (kèm theo đó: không có `applications.referral_code` trong schema Phase 1, xem ADR-029).

Nút Gọi/Zalo trên website chỉ mở kênh liên lạc (`tel:`/`https://zalo.me/...`); không tự tạo
bản ghi nào trong hệ thống.

### 0.1. `users` Phase 1 chỉ phục vụ staff/admin

Vì không có Candidate Account, bảng `users` Phase 1 chỉ có 2 vai trò: `staff`, `admin`. Không
tạo tài khoản `users` cho ứng viên. `candidates` **không có** cột `user_id` trong Phase 1 —
liên kết candidate ↔ user chỉ có ý nghĩa khi Candidate Account tồn tại (Phase 2), thêm bằng
migration mới lúc đó, không tạo trước (ADR-028).

### 0.2. Company Quick Create Contract (chính thức, ADR-045)

Mục tiêu: nhân viên nhận nhu cầu tuyển qua điện thoại/Zalo phải tạo được `companies` **ngay
trong lúc nói chuyện**, chỉ biết tên công ty. Phase 1 **không** mở rộng `companies` thành module
quản lý pháp nhân (không mã số thuế, không hồ sơ pháp lý đầy đủ, không quy trình duyệt doanh
nghiệp nhiều bước).

**Trường bắt buộc để tạo `companies`:** `name`, `status` (mặc định `active`), `created_by`. Mọi
trường còn lại (`short_name`, `description`, `logo_path`, `cover_path`, `industry`, `website`)
đã nullable sẵn trong `docs/DATABASE-DICTIONARY.md` — không cần thay đổi schema.

- `slug`/`public_id` do server tự sinh từ `name` — không phải trường người dùng nhập.
- Dữ liệu chưa biết **luôn lưu `NULL`** ở tầng DB — **không** lưu chuỗi `"Chưa xác định"` hay bất
  kỳ placeholder văn bản nào vào cột dữ liệu. Nhãn `"Chưa xác định"` (nếu cần) chỉ được render ở
  tầng view khi giá trị là `null`, không lưu vào database.
- Chỉ biết tên công ty → vẫn tạo được `companies` → vẫn tạo được `company_locations` (mục 0.3)
  → vẫn tạo được Job draft (mục 1.0).
- **Không bắt buộc** trước khi publish Job của công ty đó: mã số thuế, trụ sở chính, website,
  logo, hồ sơ pháp lý đầy đủ, mô tả doanh nghiệp chi tiết — Job Publish Contract (mục 1.2) chỉ
  yêu cầu `companies.status = active` và chưa `deleted_at`.
- Staff và Admin đều tạo/sửa được `companies`/`company_locations`/`company_contacts` — chỉ soft
  delete/restore dành riêng cho Admin (mục P, xem `docs/ROUTE-MAP.md`).

### 0.3. Company Location Quick Create Contract (chính thức, ADR-045)

`company_locations.name` **NOT NULL**; `company_locations.administrative_unit_id` và
`company_locations.address_detail` **NULLABLE** (đổi từ NOT NULL — ADR-045, sửa
`docs/DATABASE-DICTIONARY.md`/`docs/ERD.md`). Cho phép:

```
Chỉ biết tên nhà máy/địa điểm ("Nhà máy ABC")
→ tạo company_locations (administrative_unit_id=null, address_detail=null)
→ tạo Job draft gắn location này
→ bổ sung tỉnh/thành và địa chỉ chi tiết sau, trước khi publish.
```

**Điều kiện để một location được dùng làm primary location của Job publish** (kiểm tra trong
điều kiện publish, mục 1.2 — không phải điều kiện tạo location):

- Có `name`.
- Thuộc đúng `company_id` của Job.
- **Đủ rõ khu vực**: `administrative_unit_id` khác `null`, hoặc (nếu chưa xác định được tỉnh cụ
  thể) `address_detail` khác `null`/không rỗng.
- Job có đúng một `job_locations.is_primary = true`.

Không publish được nếu location chỉ có tên chung chung (vd "Nhà máy 1") mà chưa có cả tỉnh/thành
lẫn mô tả địa chỉ — ứng viên không thể xác định khu vực làm việc. Khuyến nghị luôn ưu tiên nhập
`administrative_unit_id` vì đây là dữ liệu có cấu trúc dùng cho bộ lọc "tỉnh/thành" ở Luồng 2 —
Job publish chỉ với `address_detail` (thiếu `administrative_unit_id`) vẫn hợp lệ nhưng sẽ không
xuất hiện khi ứng viên lọc theo tỉnh/thành cụ thể.

**Validation tỉnh/thành khớp khu công nghiệp (chính thức, ADR-052):** khi
`company_locations.industrial_park_id` khác `null`, `administrative_unit_id` của location đó
**bắt buộc khác `null` và bằng đúng** `industrial_parks.administrative_unit_id` của KCN được
chọn — không cho lưu Location tại tỉnh A nhưng gắn KCN thuộc tỉnh B. Khi `industrial_park_id =
null`, quy tắc này không áp dụng (giữ nguyên contract Quick Create ở trên). UI có thể tự điền
`administrative_unit_id` khi người dùng chọn KCN (giảm khả năng nhập sai) — đây chỉ là hỗ trợ
UX, **Backend vẫn bắt buộc kiểm tra lại** ở Form Request/Service của
`hr.company-locations.store`/`update`, từ chối nếu không khớp; không phải DB constraint (không
có cách diễn đạt "2 cột ở 2 bảng phải bằng nhau" bằng FK thông thường).

## 1. Luồng 1 — Tạo và xuất bản việc làm

```
Công ty/nhà máy gửi nhu cầu (điện thoại/Zalo/Excel/gặp trực tiếp — ngoài hệ thống)
→ nhân viên đăng nhập HR → tạo Job (status=draft)
→ chọn company → chọn company_location(s) → chọn owner_branch_id (cơ sở nội bộ phụ trách)
→ chọn company_contact_id (nếu có, để liên hệ nội bộ) → nhập nội dung/lương/ca/quyền lợi
→ lưu nháp → kiểm tra điều kiện publish → người có quyền publish → Job status=published.
```

`company_locations` là **địa điểm làm việc/nhà máy của công ty khách hàng** — không gọi là
"chi nhánh" trong bất kỳ tài liệu nào (dễ nhầm với `branches`, xem ADR-015). Mô tả chuẩn:
"Tên nhà máy hoặc địa điểm làm việc của công ty khách hàng".

### 1.0. Job Draft Contract (chính thức, ADR-046)

Job nháp (`status = draft`) được phép **thiếu** dữ liệu chưa hoàn thiện — không dùng điều kiện
publish (mục 1.2) để chặn việc lưu nháp. Cho phép tạo/lưu Job draft khi:

- Company chỉ có tên (mục 0.2).
- Location chưa có tỉnh/thành hoặc địa chỉ đầy đủ (mục 0.3).
- `job_description` còn trống (`NULLABLE`, ADR-060 — sửa lại từ NOT NULL ở bản dictionary trước,
  vốn mâu thuẫn trực tiếp với Job Draft Contract).
- `requirements` còn trống (đã nullable sẵn).
- Chưa chốt hoàn toàn lương (`salary_min`/`salary_max`/`salary_description` còn trống).
- Chưa chốt toàn bộ quyền lợi (`benefits` còn trống, xe đưa đón, chỗ ở...).
- Chưa có `job_work_shifts` nào (ca làm).
- Chưa xác minh xong nhu cầu tuyển (`job_verifications` chưa có bản ghi nào).

Khi publish, `job_description`/`requirements`/`benefits` bắt buộc có nội dung thực (không rỗng
sau khi trim) — kiểm tra tại điều kiện 11–13 của Job Publish Predicate (mục 1.2, ADR-060), thực
thi trong `PublishJobAction`/`ChangeJobStatusAction`. Không được ép Staff nhập dữ liệu giả để
vượt validation ở bước lưu draft, và không được lưu chuỗi placeholder (vd "Đang cập nhật") vào
các cột này chỉ để vượt điều kiện publish — nội dung phải là mô tả thật.

**Job draft bắt buộc phải có** (không được thiếu, kể cả ở draft):

- `title` (tạm thời hoặc chính thức — có thể sửa lại trước khi publish).
- `company_id` (Company phải tồn tại, kể cả khi chỉ có tên — mục 0.2).
- `owner_branch_id` **(NOT NULL ngay từ lúc tạo — không có trạng thái "Job chưa có cơ sở phụ
  trách", xem mục 1.1)**.
- `created_by`.
- `status = draft`.

Các thông tin còn thiếu (địa chỉ location, lương, quyền lợi, xác minh nhu cầu) được bổ sung dần
trước khi publish — điều kiện publish đầy đủ: mục 1.2.

### 1.1. Job Branch Contract — quản lý `jobs.owner_branch_id` (chính thức, ADR-038, ADR-046)

`owner_branch_id` là **NOT NULL** ngay từ lúc tạo Job (ADR-046 — không còn nullable ở `draft`
như bản đặc tả trước). Chỉ được set lúc tạo Job (`hr.jobs.store`) và đổi qua
`ChangeJobBranchAction` — sửa các trường khác của Job (`hr.jobs.update`) **không** được phép
sửa `owner_branch_id`.

**Staff:**

- Khi tạo Job, `owner_branch_id` do server tự gán bằng `users.branch_id` của staff đang đăng
  nhập — không đọc từ input, form không cho staff chọn cơ sở khác. Vì staff đăng nhập luôn có
  `users.branch_id`, giá trị này luôn có ngay khi Job (kể cả draft) được tạo.
- Không có quyền/route đổi `owner_branch_id` của Job đã tạo dưới bất kỳ hình thức nào.
- Chỉ sửa/publish/pause/close được Job có `owner_branch_id = users.branch_id` của mình — truy
  cập trực tiếp URL của Job thuộc cơ sở khác trả `403` (không chỉ ẩn nút ở view).
- Vẫn xem được (read-only) Job `published` của cơ sở khác — không đổi so với quy tắc đã có.

**Admin:**

- Tạo Job cho bất kỳ cơ sở nào — **bắt buộc** chọn `owner_branch_id` tường minh khi tạo (Form
  Request từ chối nếu thiếu; không có giá trị mặc định null).
- Đổi cơ sở phụ trách của Job đã tồn tại qua `ChangeJobBranchAction`, đủ điều kiện:
  1. `jobs.status` **∈ {`draft`, `paused`}** — không được `published` (nếu đang `published`,
     phải pause trước qua `ChangeJobStatusAction`, `published→paused`, như một bước riêng,
     **không** gộp chung transaction với việc đổi cơ sở), và **không được `closed`** (Job đã
     đóng là đợt tuyển đã kết thúc, không đổi cơ sở phụ trách của một đợt đã đóng — ADR-054).
  2. Job chưa `deleted_at` (Job đã soft-delete không bị thao tác thêm ngoài khôi phục —
     ADR-054).
  3. Cơ sở đích tồn tại, `status = active`, chưa `deleted_at`.
  4. Có lý do (`reason` bắt buộc, không rỗng).
  5. `lockForUpdate` trên Job trong suốt transaction.
- Publish lại (`paused → published`) sau khi đổi cơ sở phải qua lại toàn bộ điều kiện publish
  (mục 1.2) bên dưới — đã là hành vi mặc định của `ChangeJobStatusAction` (không cần thêm logic
  riêng).

**Application đã tạo trước khi Job đổi cơ sở:** giữ nguyên `owner_branch_id` cũ — không tự
động chuyển theo (nhất quán với ADR-016: Application copy `owner_branch_id` tại thời điểm tạo,
không suy ra động qua Job). **Application tạo sau** thời điểm Job đổi cơ sở tự nhiên copy
`owner_branch_id` **mới** (hành vi có sẵn của Luồng 3, không cần logic đặc biệt).

**Bảng `job_branch_histories` (mới) — quyết định: THÊM bảng này**, không chọn phương án
"`owner_branch_id` bất biến sau publish", vì quy tắc Admin ở trên xác nhận nhu cầu đổi cơ sở
Job thật sự tồn tại sau khi publish (gán nhầm, tái cấu trúc vận hành) — để bất biến sẽ không
đáp ứng được. Thêm bảng để nhất quán với nguyên tắc "lịch sử chỉ thêm, không ghi đè" đã áp
dụng cho mọi thay đổi quan trọng khác (`application_branch_histories`, `job_status_histories`).

- Cột: `id`, `job_id`, `from_branch_id` (nullable — null ở lần gán đầu tiên lúc tạo Job),
  `to_branch_id`, `reason` (nullable ở lần gán đầu, bắt buộc khi đổi thủ công), `changed_by`
  (**NOT NULL** — Job luôn do một người tạo/sửa qua form, không có "hệ thống tự gán" như
  Application), `created_at` (append-only).
- Ghi 1 dòng khi: (a) Job được gán `owner_branch_id` lần đầu lúc tạo (staff tự động, hoặc admin
  chọn tường minh); (b) mỗi lần Admin đổi cơ sở qua `ChangeJobBranchAction`.

**Job Authorization Matrix:**

| Hành động | Staff trên Job cơ sở mình | Staff trên Job cơ sở khác | Admin |
|---|---|---|---|
| Tạo Job | ✓ (`owner_branch_id` tự gán) | — | ✓ (chọn `owner_branch_id` bất kỳ) |
| Sửa nội dung Job | ✓ | ✗ `403` | ✓ |
| Publish/Pause/Close | ✓ | ✗ `403` | ✓ |
| Đổi `owner_branch_id` | ✗ (không có quyền) | ✗ | ✓ (đủ điều kiện ở trên) |
| Xem chi tiết (read-only) | ✓ | ✓ nếu `published` | ✓ toàn bộ |

Phải có test truy cập trực tiếp URL, không chỉ ẩn nút giao diện — ví dụ: Staff cơ sở A gửi
`PUT`/`POST` cho Job thuộc cơ sở B → `403`.

### 1.2. Job Publish Predicate chính thức — 22 điều kiện (Service kiểm tra, không chỉ ẩn nút ở view) (ADR-047, ADR-060)

Publish (`draft→published` hoặc `paused→published`) chỉ được thực hiện khi **toàn bộ 22 điều
kiện sau** đều đúng. Các mã `PUB-*` là định danh ổn định để tài liệu khác tham chiếu, tránh lệch
số thứ tự khi danh sách được chỉnh lý; không dùng câu chung chung ("đầy đủ theo nghiệp vụ"):

1. Job tồn tại.
2. Job chưa `deleted_at`.
3. `jobs.status` ∈ {`draft`, `paused`} (trạng thái cho phép publish).
4. `companies.status = active`.
5. `companies` chưa `deleted_at`.
6. `jobs.owner_branch_id` khác null — **luôn đúng** vì cột này NOT NULL ngay từ lúc tạo Job (mục
   1.0, 1.1, ADR-046); liệt kê ở đây chỉ để tường minh, không phải điều kiện có thể sai.
7. `branches.status = active` cho cơ sở ở bước 6.
8. `branches` ở bước 6 chưa `deleted_at`.
9. **`PUB-BRANCH-CTA`** — Cơ sở ở bước 6 có `phone` hoặc `zalo` khác rỗng — xem "Quy tắc contact CTA" bên dưới.
10. `jobs.title` khác rỗng (khác NULL và khác chuỗi toàn khoảng trắng).
11. `jobs.job_description` khác rỗng (cùng quy tắc — cột này **nullable** ở DB, mục 1.0, ADR-060,
    nhưng bắt buộc có nội dung thực trước khi publish).
12. `jobs.requirements` khác rỗng (cùng quy tắc).
13. `jobs.benefits` khác rỗng (cùng quy tắc) — **mới so với bản đặc tả trước** (ADR-060): trước
    đây `requirements`/`benefits` chưa từng là điều kiện publish tường minh dù Job Draft Contract
    coi đây là dữ liệu "chưa chốt ở draft, phải hoàn thiện trước publish".
14. Có đúng một `job_locations.is_primary = true`.
15. Location đó (`company_location_id`) thuộc đúng `jobs.company_id`.
16. Location đó `status = active`.
17. Location đó chưa `deleted_at`.
18. **`PUB-LOCATION-CLEAR` — Địa điểm đủ rõ** (mục 0.3): location đó có `administrative_unit_id` khác null, hoặc (nếu
    chưa xác định tỉnh cụ thể) `address_detail` khác null/không rỗng.
19. **`PUB-SALARY` — Salary Predicate đúng** theo đúng **một trong hai mode loại trừ nhau**:
    - **Negotiable mode:** `salary_period = negotiable`; `salary_min`, `salary_max`,
      `salary_base` đều `NULL`; `salary_description` được phép null hoặc có nội dung thực.
    - **Numeric/described mode:** `salary_period != negotiable`; có ít nhất một trong
      `salary_min`, `salary_max`, `salary_base` là số dương **hoặc** `salary_description` có nội
      dung thực sau khi trim. Nếu cả `salary_min` và `salary_max` có giá trị thì
      `salary_min <= salary_max`.
    Không được vừa chọn `negotiable` vừa lưu lương số; Form Request/Service phải chuẩn hóa các
    cột không thuộc mode về `NULL` trước khi lưu.
20. **`PUB-SHIFT` — Shift Predicate đúng** — có ít nhất 1 bản ghi `job_work_shifts` gắn với Job
    (`COUNT(job_work_shifts WHERE job_id = ?) >= 1`). Đây là điều kiện duy nhất (không có đường
    thay thế song song bằng trường mô tả tự do cấp Job — schema không có cột đó, ADR-060).
21. **`PUB-VERIFY` — verification hợp lệ** khi một trong hai nhánh đúng:
    - Bản ghi `job_verifications` mới nhất là `still_open` và, nếu bật
      `job_verification_valid_days`, còn trong hạn; **hoặc**
    - Actor là **Admin** và cung cấp `verification_override_reason` không rỗng. Action phải ghi
      nguyên văn lý do này vào `job_status_histories.reason` của transition publish.
    Staff không có quyền override. Một `still_open` cũ không bao giờ làm nhánh đầu đạt.
22. Người thao tác có quyền publish Job này (Staff thuộc đúng cơ sở của Job, hoặc Admin — mục
    1.1).

**Job transition matrix chính thức** — không cho phép chuyển trạng thái ngoài bảng này:

| From | To |
|---|---|
| `draft` | `published` |
| `published` | `paused` |
| `paused` | `published` |
| `published` | `closed` |
| `paused` | `closed` |

`draft → closed` không nằm trong matrix — Job nháp bỏ dùng đóng bằng soft delete
(`hr.jobs.destroy`), một hành động khác, không phải transition trạng thái. `closed` là trạng
thái cuối trong Phase 1 (không có transition đi ra khỏi `closed`).

**Job Status History (`job_status_histories`, mới):** mọi transition ở bảng trên đi qua
`ChangeJobStatusAction`, không sửa `jobs.status` trực tiếp từ controller. Action xử lý:
authorization (Staff thuộc đúng cơ sở của Job hoặc Admin), validate transition theo matrix
trên, ghi `job_status_histories` (`from_status`, `to_status`, `reason` — bắt buộc khi
`to_status = closed`, dùng chung giá trị với `jobs.close_reason`), cập nhật `jobs`, trong 1
transaction. **`paused → published` (mở lại) phải kiểm tra lại toàn bộ điều kiện publish (mục
1.2) ở trên từ đầu** — không giả định các điều kiện vẫn còn đúng như lần publish trước (company
hoặc branch có thể đã đổi trạng thái trong lúc Job bị pause). Job tạo mới (`draft`) không bắt buộc
tạo history `null → draft` (đã có `jobs.created_by`/`created_at`). `job_verifications` (Luồng
xác nhận job còn tuyển) là bảng khác mục đích khác — không dùng thay cho
`job_status_histories`.

### 1.3. Job Verification Scheduler Contract (chính thức, ADR-042)

Settings seed sẵn trong bảng `settings`:

| Key | Giá trị mặc định | Ý nghĩa |
|---|---|---|
| `job_verification_warning_days` | `7` | Sau bao nhiêu ngày kể từ lần xác nhận gần nhất thì hiển thị cảnh báo mức thường |
| `job_auto_pause_days` | `14` | Sau bao nhiêu ngày thì hiển thị cảnh báo mức cao |
| `job_auto_pause_enabled` | `false` | Có tự động pause Job khi quá hạn hay không |
| `job_verification_valid_days` | `null` (tắt) | Độ mới tối đa (ngày) của bản ghi `still_open` mới nhất để còn được coi là hợp lệ cho publish (ADR-058). `null`/rỗng = không kiểm tra độ mới, chỉ cần "mới nhất = still_open". **Giá trị số ngày cụ thể: [CẦN CHỐT VỚI CÔNG TY]** — go-live decision, không chặn migration |

**`last_checked_at` vs `last_verified_at` (chính thức, ADR-048):** `jobs` có 2 cột riêng biệt —
`last_checked_at` (mọi lần xác nhận, bất kể kết quả) và `last_verified_at` (chỉ khi xác nhận
hợp lệ). Mỗi lần tạo `job_verifications` (`hr.jobs.verify`), trong cùng transaction:

| `result` | `jobs.last_checked_at` | `jobs.last_verified_at` | Xem là "đã xác minh hợp lệ"? | Đổi `jobs.status` |
|---|---|---|---|---|
| `still_open` | cập nhật `= now()` | cập nhật `= now()` | Có | Không đổi |
| `needs_review` | cập nhật `= now()` | **không đổi** | Không | Không đổi |
| `paused` | cập nhật `= now()` | **không đổi** | Không | → `paused` qua `ChangeJobStatusAction`, ghi `job_status_histories` |
| `closed` | cập nhật `= now()` | **không đổi** | Không | → `closed` qua `ChangeJobStatusAction`, ghi `job_status_histories` |

Hành vi Phase 1:

- Sau `job_verification_warning_days` ngày kể từ `jobs.last_verified_at` (hoặc `published_at`
  nếu chưa từng có `still_open`) mà Job vẫn `published`: hiển thị cảnh báo mức thường trên danh
  sách `/hr/viec-lam` cho Staff thuộc đúng cơ sở của Job và Admin. **Cảnh báo luôn tính từ
  `last_verified_at`, không phải `last_checked_at`** — một lần xác nhận `needs_review` không
  làm tắt cảnh báo, vì chưa chắc chắn Job còn tuyển. Đây là **giá trị tính toán khi hiển thị**
  (so sánh ngày hiện tại với `last_verified_at` + settings) — không ghi bản ghi nào vào
  `job_verifications`/`job_status_histories` chỉ vì hiển thị cảnh báo.
- Sau `job_auto_pause_days` ngày (tính từ `last_verified_at`): cảnh báo chuyển mức cao hơn (nổi
  bật hơn trong danh sách).
- Cảnh báo chỉ hiển thị khi Staff/Admin đăng nhập xem danh sách — Phase 1 **không gửi email**
  (chưa có hạ tầng gửi mail được thiết kế).
- `job_auto_pause_enabled = false` mặc định — Phase 1 **không tự động pause Job** dưới bất kỳ
  điều kiện nào; đây là code path không được thực thi ở Phase 1 (không cần build/test). Đây là
  **Phase 2 decision** (ADR-049) — việc công ty chưa quyết định bật/tắt không chặn migration
  hay go-live Phase 1.
- Job `is_urgent = true` dùng **cùng quy tắc** — không rút ngắn thời hạn riêng ở Phase 1.
- **Xác minh gắn với điều kiện publish — chỉ bản ghi mới nhất được tính (chính thức, ADR-058)**:
  mọi lần publish (`draft → published` hoặc `paused → published`) yêu cầu **bản ghi
  `job_verifications` mới nhất của Job** (sắp xếp `verified_at` DESC, `id` DESC làm tie-break) có
  `result = still_open`, **và** (nếu `job_verification_valid_days` khác null) bản ghi đó còn
  trong hạn số ngày cấu hình tính từ `now()`. **Không** còn được diễn đạt là "có ít nhất một
  `still_open` trong lịch sử" — một bản ghi `still_open` cũ bị vô hiệu hóa ngay khi có bản ghi
  mới hơn với `result` khác `still_open` (Job cần được xác nhận lại `still_open` một lần nữa mới
  đủ điều kiện publish). Thiếu điều kiện này: Staff bị từ chối publish; **Admin** publish được
  ngoại lệ nhưng bắt buộc nhập `job_status_histories.reason` mô tả lý do bỏ qua xác minh (tái
  dùng cột có sẵn, không thêm cột mới) — hành động này vẫn ghi `job_status_histories` bình thường
  qua `ChangeJobStatusAction`.
- **[Phase 2 decision — ADR-049]**: có bật `job_auto_pause_enabled = true` ở giai đoạn sau hay
  không. Nếu bật: hành động tự động pause phải có actor `system`, phải tạo
  `job_status_histories` với lý do tự động — nhưng schema Phase 1 hiện **chưa** có
  `actor_type`/`changed_by` nullable cho `job_status_histories` (khác
  `application_status_histories` đã có `actor_type`), vì auto-pause không thực thi ở Phase 1.
  Nếu công ty xác nhận muốn bật, cần bổ sung 2 cột này bằng migration riêng **trước khi bật**,
  không thêm trước ở Phase 1 (tránh cột dự phòng không dùng). Không chặn migration Phase 1.

#### 1.3.1. Ma trận chính thức Job Status × Verification Result (ADR-059)

`hr.jobs.verify` (`JobVerificationController@store`) áp dụng đúng bảng sau theo `jobs.status`
**tại thời điểm ghi verification** — không suy luận khi coding, không có hành vi nào ngoài bảng
này:

| `jobs.status` hiện tại | `result` gửi lên | Hành vi |
|---|---|---|
| `draft` | `still_open` | Ghi `job_verifications`; cập nhật `last_checked_at`/`last_verified_at`; **giữ `draft`** |
| `draft` | `needs_review` | Ghi `job_verifications`; cập nhật `last_checked_at`; **giữ `draft`** |
| `draft` | `paused` | **Từ chối** (`422`) — Job chưa từng publish, không thể "tạm dừng" một hoạt động chưa từng có |
| `draft` | `closed` | **Từ chối** (`422`) — cùng lý do; đóng Job draft dùng soft delete (`hr.jobs.destroy`), không phải verification |
| `published` | `still_open` | Ghi `job_verifications`; cập nhật `last_checked_at`/`last_verified_at`; **giữ `published`** |
| `published` | `needs_review` | Ghi `job_verifications`; cập nhật `last_checked_at`; **giữ `published`** (không tự đổi status — auto-pause thuộc Phase 2, ADR-042/049) |
| `published` | `paused` | Ghi `job_verifications`; chuyển `published → paused` qua `ChangeJobStatusAction`, ghi `job_status_histories`, cùng transaction |
| `published` | `closed` | Ghi `job_verifications`; chuyển `published → closed` qua `ChangeJobStatusAction`, ghi `job_status_histories`, cùng transaction |
| `paused` | `still_open` | Ghi `job_verifications`; cập nhật `last_checked_at`/`last_verified_at`; **giữ `paused`** — không tự publish lại (mở lại luôn là hành động tường minh riêng qua `hr.jobs.publish`) |
| `paused` | `needs_review` | Ghi `job_verifications`; cập nhật `last_checked_at`; **giữ `paused`** |
| `paused` | `paused` | Ghi `job_verifications`; cập nhật `last_checked_at`; **giữ `paused`**, **không** tạo `job_status_histories` (status không đổi — tránh lịch sử "paused→paused" vô nghĩa) |
| `paused` | `closed` | Ghi `job_verifications`; chuyển `paused → closed` qua `ChangeJobStatusAction`, ghi `job_status_histories` |
| `closed` | (bất kỳ) | **Từ chối** (`403`) qua luồng Staff/Admin thông thường — Job đã đóng là một đợt tuyển đã kết thúc, không nhận verification mới; không có action quản trị riêng nào ở Phase 1 để làm việc này (chưa có nhu cầu nghiệp vụ); tuyệt đối không làm Job tự hoạt động lại |

#### 1.3.2. Quy tắc contact CTA (Gọi/Zalo) trên Job

- CTA Gọi/Zalo trên trang Job công khai **luôn dùng `branches.phone`/`branches.zalo` của
  `jobs.owner_branch_id`** — cơ sở đã bắt buộc có contact hợp lệ trước khi publish (`PUB-BRANCH-CTA`), nên CTA luôn có dữ liệu để hiển thị.
- `company_contacts` (đầu mối công ty/nhà máy khách hàng) **không** phải nguồn CTA thay thế
  mặc định. Khi `jobs.company_contact_id` khác null, Service phải kiểm tra contact **thuộc đúng
  `jobs.company_id`**, `status=active` và chưa soft-delete; chọn contact của Company khác bị từ
  chối `422`. Contact chỉ hiển thị công khai khi thêm điều kiện `is_public = true` — khi đó là
  kênh phụ, không thay CTA cơ sở. Không tự động suy ra hoặc mặc định lộ số điện thoại nhà máy.

## 2. Luồng 2 — Ứng viên tìm và chọn việc

Website chỉ hiển thị Job trong danh sách/tìm kiếm/sitemap khi `status = published`, chưa
`expires_at` (hoặc null), chưa `deleted_at`. Job `draft` không có trang chi tiết công khai
(chưa từng publish). Job `paused`/`closed`/hết hạn (`published` + `expires_at < now()`) có quy
tắc hiển thị trang chi tiết riêng dưới đây (mục 2.1, 2.2).

CTA trên trang chi tiết (Job `published`): **Ứng tuyển ngay** (luồng 3), **Gọi điện**, **Nhắn
Zalo** (mở kênh thủ công dùng contact cơ sở — mục 1, không tạo bản ghi). Chỉ "Ứng tuyển ngay"
tạo dữ liệu trong hệ thống.

### 2.1. Quy tắc hiển thị Job `closed`/`paused` (chính thức, ADR-043)

**Job `closed`:**

- Không xuất hiện trong danh sách Job đang tuyển, không xuất hiện trong kết quả tìm kiếm thông
  thường, không xuất hiện trong sitemap Job đang hoạt động.
- URL chi tiết cũ **vẫn truy cập được** (`200`, không `404`) — giữ SEO và liên kết cũ.
- Trang chi tiết hiển thị rõ **"Đã ngừng tuyển"**; ẩn form và nút "Ứng tuyển ngay" — server vẫn
  từ chối submit nếu cố gửi trực tiếp (Luồng 3 đã có sẵn kiểm tra Job còn active).
- Có thể hiển thị "Việc làm liên quan" đang `published` (cùng công ty/ngành).
- **CTA Gọi/Zalo giữ nguyên hiển thị** (dùng contact cơ sở như Job đang published) — không ẩn,
  **không** xây tính năng "liên hệ tư vấn chung" mới (đó thuộc phạm vi Lead, ngoài Phase 1,
  ADR-021). Đây chỉ là kênh liên lạc thủ công (`tel:`/`zalo.me`), không tạo bản ghi.

**Job `paused`:**

- Không xuất hiện trong danh sách public, không nhận Application (server từ chối).
- URL chi tiết **xử lý giống `closed`** (hiển thị `200`, không `404`) — hiển thị **"Tạm ngừng
  tuyển"**, ẩn nút ứng tuyển. Quyết định này áp dụng vì `paused` thường là tạm thời (job có thể
  publish lại), 404 sẽ làm gãy liên kết cho một trạng thái không vĩnh viễn.
- CTA Gọi/Zalo giữ nguyên hiển thị, cùng lý do như Job `closed`.

Cả hai trạng thái đều **không** gắn `noindex` (khác trang HR/login) — giữ khả năng index để
không mất traffic/backlink cũ, nhất quán với mục tiêu SEO đã có.

### 2.2. Job hết hạn — `effective_status = expired` (chính thức, ADR-072)

Khi `jobs.status = published` **và** `jobs.expires_at < now()`: định nghĩa giá trị tính toán ở
tầng ứng dụng **`effective_status = expired`** — **không** phải giá trị cột DB, `jobs.status`
vẫn giữ nguyên `published` cho tới khi có hành động tường minh (`pause`/`close`) đổi trạng thái.
Phase 1 **không tự động** chuyển `status` khi hết hạn (nhất quán với việc không auto-pause,
ADR-042/049).

- Không xuất hiện trong danh sách/tìm kiếm/sitemap Job đang hoạt động (đã áp dụng — điều kiện
  `expires_at` ở đầu mục 2).
- **Không nhận Application**: server từ chối submit trực tiếp (bỏ qua UI) — điều kiện "Job còn
  active" ở Luồng 3 (mục 3) kiểm tra cả `status = published` **và** (`expires_at` là null hoặc
  `expires_at >= now()`), không chỉ kiểm tra `status`.
- URL chi tiết vẫn `200` (không `404`), hiển thị **"Đã hết hạn tuyển"** (khác thông điệp với
  `closed`/`paused` để không gây hiểu nhầm là bị chủ động đóng/tạm dừng), ẩn nút "Ứng tuyển
  ngay", **giữ nguyên CTA Gọi/Zalo** (cùng quy tắc mục 2.1).
- Danh sách `/hr/viec-lam` hiển thị rõ Job `published` đã hết hạn cần Staff/Admin xử lý (tính
  trực tiếp từ `expires_at` đã có, không thêm cột).
- Không gắn `noindex`, cùng lý do mục 2.1.

## 3. Luồng 3 — Ứng viên gửi form ứng tuyển

```
Bấm "Ứng tuyển ngay" → server sinh submission_token, render form kèm token ẩn
→ điền form → đồng ý chính sách dữ liệu → gửi
→ server đọc lại Job từ DB (không tin dữ liệu client gửi kèm) → kiểm tra Job còn active
→ validate (Form Request, bắt buộc có submission_token hợp lệ) → chuẩn hóa họ tên/số điện thoại
→ kiểm tra trùng Candidate (mục 6) → kiểm tra Application cùng Job đã tồn tại (case C, mục 6)
→ tạo hoặc tái sử dụng Candidate → tạo Application (workflow_cycle=1), copy owner_branch_id
  từ Job, lưu submission_token
→ lưu submission_snapshot + job_snapshot → lưu consent
→ tạo application_status_histories (null → new) → tạo application_branch_histories
  (from_branch_id=null → to_branch_id=owner_branch_id, transferred_by=null = hệ thống)
→ commit transaction → thông báo thành công → hồ sơ xuất hiện ở đúng cơ sở phụ trách.
```

**"Job còn active"** ở bước trên nghĩa là: `jobs.status = published` **và** chưa `deleted_at`
**và** (`expires_at` là null hoặc `expires_at >= now()`) — Job `draft`/`paused`/`closed` hoặc đã
hết hạn (`effective_status = expired`, mục 2.2) đều bị từ chối nhận Application ở bước này, kể cả
khi request gửi trực tiếp bỏ qua UI.

Toàn bộ nằm trong **1 transaction** (Action, không phải Controller) — nếu bất kỳ bước nào lỗi,
rollback toàn bộ, không để lại `candidates`/`applications` rác. Xem thêm Submission Concurrency
Contract (mục 3.1, ADR-061) cho trường hợp 2 request đồng thời khác `submission_token`.

**Trường bắt buộc tối thiểu:** họ tên, số điện thoại, Job, đồng ý chính sách dữ liệu.
**Trường tùy chọn:** ngày sinh, giới tính, nơi ở hiện tại, học vấn, kinh nghiệm. Không có trường
số điện thoại phụ — mỗi Application chỉ gắn đúng 1 số điện thoại, tránh 1 Candidate có nhiều
`candidate_contacts` type=phone dẫn tới 2 lock key khác nhau cho cùng 1 người (rủi ro race khi 2
request dùng 2 số khác nhau của cùng 1 người submit đồng thời — xem mục 3.1).

**Bắt buộc kỹ thuật:** server-side validation, CSRF, rate limit, honeypot/chống spam,
transaction, và **client không được tự gửi `stage`, `owner_branch_id`** — các trường này luôn
do server tính/copy, không đọc từ input (mass-assignment allowlist ở Form Request; không có
`assigned_to` trong Phase 1).

**Submission Token Lifecycle chính thức (`applications.submission_token`, ADR-041):**

Cột **`submission_token` (string(64), NOT NULL, UNIQUE)** trên `applications` — Phase 1 chỉ tạo
Application qua form website nên mọi bản ghi luôn có token, không cần cho phép null. Không tạo
bảng `application_submissions` riêng — Phase 1 chỉ có 1 luồng tạo Application duy nhất (form
guest, submit 1 bước, đủ dữ liệu ngay trong request), không có kịch bản "giữ chỗ token trước
rồi hoàn tất dữ liệu sau" cần bảng trung gian (ADR-035).

**Diễn đạt chính thức** (thay cho cách nói dễ gây hiểu lầm "token chỉ dùng một lần"): *"Một
token chỉ được tạo tối đa một Application, nhưng request lặp với cùng token được phép nhận lại
kết quả Application đã tạo."*

Quy trình:

1. Khi người dùng mở form ứng tuyển của một Job, server sinh `submission_token` ngẫu nhiên an
   toàn (đủ entropy để không đoán được), gắn với đúng `job_id` đó.
2. Token được lưu vào session. **Session lưu được nhiều token cùng lúc** (mảng/tập hợp
   `{token, job_id, issued_at}`, không phải 1 khóa duy nhất bị ghi đè) — vì người dùng có thể mở
   nhiều Job ở nhiều tab cùng lúc, mỗi tab giữ token riêng.
3. Token được render vào form dưới dạng hidden input.
4. Khi submit, client gửi token kèm theo. Server kiểm tra token có trong session hiện tại và
   token đó **được phát hành cho đúng `job_id`** đang submit hay không — token của Job A dùng
   để submit Job B bị từ chối.
5. Nếu **chưa có** Application nào gắn với token này: bắt đầu transaction → kiểm tra Job còn
   active → kiểm tra Candidate trùng (mục 6.2) → kiểm tra Application cùng Job đã tồn tại (case
   C) → tạo Candidate/Application (lưu `submission_token`) → snapshot + consent → status/branch
   history → commit.
6. Nếu **đã có** Application gắn với token này (double-click, F5, request lặp lại): không tạo
   Candidate/Application mới — đọc lại và trả về kết quả của Application đã tạo — không lỗi
   500.
7. Nếu xảy ra **unique conflict** ở tầng DB khi INSERT (2 request đồng thời cùng token cùng lúc
   vượt qua bước kiểm tra 5 do race condition): rollback toàn bộ Candidate/Application vừa tạo
   trong transaction đó → đọc lại Application theo `submission_token` → trả về kết quả thành
   công của request đã thắng.
8. Token **không dùng được cho Job khác** — validate ở bước 4.
9. Token **không có cột `expires_at` riêng** trong Phase 1 — vòng đời token gắn với vòng đời
   session (`config/session.php`, Laravel mặc định); khi session hết hạn, token trong session
   cũng hết hiệu lực theo (không submit được nữa với token đó vì server không tìm thấy trong
   session). Token đã dùng để tạo Application thành công thì giá trị vẫn giữ nguyên vĩnh viễn
   trên `applications.submission_token` (không "hết hạn" sau khi đã tạo bản ghi).
10. 2 request đồng thời **khác token** nhưng cùng candidate + cùng job (2 tab, 2 lần mở form
    riêng biệt — mỗi tab có token riêng hợp lệ): chốt chặn cuối là unique `(candidate_id,
    job_id)` — xử lý như case C (mục 6.2), không lỗi 500. Nếu 2 request khác token còn khác cả
    `candidate_id` (vì mỗi request tự tạo Candidate riêng do race điều kiện đọc-rồi-ghi ở tầng
    Service — chưa kịp đụng unique `(candidate_id, job_id)` vì `candidate_id` khác nhau), unique
    này **không đủ** để chặn — xem Submission Concurrency Contract (mục 3.1, ADR-061).

### 3.1. Submission Concurrency Contract — khác `submission_token`, cùng identity (ADR-061)

**Vấn đề:** unique `(candidate_id, job_id)` chỉ chặn được trùng **sau khi** đã xác định xong
`candidate_id`. Hai request đồng thời, dùng hai `submission_token` khác nhau, nhưng cùng
`phone_normalized` + họ tên chuẩn hóa + `date_of_birth` + cùng Job — nếu cả hai cùng đọc "chưa có
Candidate nào khớp" trước khi request nào kịp ghi xong, cả hai sẽ tạo 2 Candidate khác nhau, mỗi
Candidate lại tạo 1 Application riêng cho cùng Job (không vi phạm unique vì `candidate_id` khác
nhau) — tạo ra 2 hồ sơ trùng thật cho cùng một người.

**Cơ chế:** named/advisory lock của MariaDB (`GET_LOCK()`), khóa theo `phone_normalized` đã
chuẩn hóa (không phải theo Job) — xem đầy đủ hợp đồng 10 bước, xử lý timeout, và lý do chọn
phương án này ở ADR-061. Tóm tắt luồng xử lý (`SubmitApplicationAction`):

1. Chuẩn hóa `phone_normalized` trước khi chạm DB.
2. Tính `lock_key = 'app_submit_phone:' . hash('sha256', $phoneNormalized)` — không bao giờ dùng
   số điện thoại thô làm khóa hay ghi log.
3. `GET_LOCK(lock_key, 5)` (timeout 5 giây). Hết hạn/lỗi → dừng, rollback, trả lỗi thân thiện
   ("vui lòng thử lại sau vài giây") — không `500`.
4. Bắt đầu transaction.
5. Query lại Candidate theo `phone_normalized` (không còn race vì đã giữ lock) → áp dụng
   Duplicate Candidate Contract (mục 6.2) → resolve candidate `merged` về root nếu trúng (mục
   6.2.1, ADR-063) → tạo hoặc tái sử dụng Candidate.
6. Kiểm tra lại Application theo `(candidate_id, job_id)` (case C).
7. Tạo Application nếu chưa có.
8. Commit.
9. `RELEASE_LOCK(lock_key)` trong `finally` — giải phóng dù thành công hay lỗi; nếu tiến trình
   PHP crash trước đó, MariaDB tự giải phóng lock khi đóng kết nối.
10. Lock chỉ đảm bảo Duplicate Candidate Contract được áp dụng **tuần tự**, **không** thay đổi 4
    trường hợp đã chốt ở mục 6.2 và **không** tự động gộp (merge) 2 Candidate có cùng phone.

## 4. Luồng 4 — Nhân viên cơ sở xử lý hồ sơ

Application luôn thuộc đúng một `owner_branch_id` (copy từ Job lúc tạo, không suy ra động qua
Job — Job có thể đổi cơ sở sau này mà không ảnh hưởng Application đã tạo).

- Staff thuộc đúng cơ sở của Application (mục "Quy ước diễn đạt phân quyền") mới xem/xử lý
  được; Admin xem/xử lý toàn bộ cơ sở.
- **Phase 1 không có khái niệm "phân công cho từng nhân viên"** — Application chỉ gán về cơ
  sở, không gán về người. Không có route/action "Nhận xử lý" (claim) hay "Gán nhân viên"
  (assign), không có bảng lịch sử phân công. Bất kỳ staff nào thuộc đúng cơ sở đều xem và xử
  lý được mọi Application của cơ sở đó.
- Trách nhiệm từng thao tác được theo dõi qua **người thực hiện trên chính bảng lịch sử của
  thao tác đó**, không qua một cột "người phụ trách": người tạo Contact Log
  (`application_contact_attempts.contacted_by`), người đổi trạng thái
  (`application_status_histories.changed_by`), người tạo/hoàn thành Appointment
  (`application_appointments.created_by`/`completed_by`), người thêm Note
  (`application_notes.user_id`). Đây là audit trail theo từng action (ADR-019), không cần
  bảng "audit log" tổng quát riêng.

Danh sách hồ sơ theo cơ sở phải hiển thị tối thiểu: trạng thái hiện tại, người liên hệ gần
nhất, thời gian liên hệ gần nhất, kết quả liên hệ gần nhất, lịch gọi lại/phỏng vấn gần nhất,
thời gian cập nhật gần nhất — mục đích giảm 2 nhân viên gọi trùng dù không có phân công cứng.

## 5. Luồng 5 — Cập nhật trạng thái và kết quả

### 5.1. Transition matrix chính thức

Mọi điều kiện "Có Contact Log/Appointment ..." dưới đây **bắt buộc thuộc chu kỳ xử lý hiện tại**
(`workflow_cycle = applications.workflow_cycle` — xem mục 5.4). Dữ liệu của chu kỳ trước (trước
lần mở lại gần nhất) không được dùng làm bằng chứng mở khóa transition.

| From stage | To stage | Điều kiện bắt buộc | Ai được thực hiện |
|---|---|---|---|
| (mới) | `new` | Application vừa được tạo bởi Luồng 3 | Hệ thống (Action Apply) |
| `new` | `contacting` | Có ít nhất 1 `application_contact_attempts` **thuộc chu kỳ hiện tại** | Staff thuộc đúng cơ sở hoặc Admin |
| `new` | `closed` | `close_reason` khác null | Staff thuộc đúng cơ sở hoặc Admin |
| `contacting` | `consulted` | Có `application_contact_attempts.result` ∈ {`consulted`, `interview_agreed`} **thuộc chu kỳ hiện tại** | Staff thuộc đúng cơ sở hoặc Admin |
| `contacting` | `closed` | `close_reason` khác null | Staff thuộc đúng cơ sở hoặc Admin |
| `consulted` | `interview_scheduled` | Có `application_appointments(type=interview, status=scheduled)` **thuộc chu kỳ hiện tại** | Staff thuộc đúng cơ sở hoặc Admin |
| `consulted` | `closed` | `close_reason` khác null | Staff thuộc đúng cơ sở hoặc Admin |
| `interview_scheduled` | `interviewed` | Appointment interview tương ứng có `status=completed`, **thuộc chu kỳ hiện tại** | Staff thuộc đúng cơ sở hoặc Admin |
| `interview_scheduled` | `closed` | `close_reason` khác null | Staff thuộc đúng cơ sở hoặc Admin |
| `interviewed` | `waiting_start` | Appointment interview `completed` **thuộc chu kỳ hiện tại** (tái xác nhận) và `applications.expected_start_at` khác null | Staff thuộc đúng cơ sở hoặc Admin |
| `interviewed` | `closed` | `close_reason` khác null | Staff thuộc đúng cơ sở hoặc Admin |
| `waiting_start` | `started` | `applications.started_at` khác null | Staff thuộc đúng cơ sở hoặc Admin |
| `waiting_start` | `closed` | `close_reason` khác null | Staff thuộc đúng cơ sở hoặc Admin |
| `closed` | `new` | Xem "Reopen Application contract" — mục 5.5 | Staff thuộc đúng cơ sở hoặc Admin (một số điều kiện chỉ Admin — mục 5.5) |
| `started` | (bất kỳ) | Trạng thái cuối trong Phase 1; không có transition tiếp | — |

Quy tắc chung:

- Mọi transition đi qua `ChangeApplicationStageAction` (không sửa cột `stage` trực tiếp từ
  controller). Action xử lý: authorization, validate transition theo bảng trên (kèm điều kiện
  chu kỳ, mục 5.4), kiểm tra dữ liệu bắt buộc, khóa row (`lockForUpdate`) chống concurrent
  update, ghi `application_status_histories`, cập nhật `applications`, trong 1 transaction.
- Appointment bị `cancelled`/`no_show` **không** tự động lùi hoặc đổi stage. Stage giữ nguyên
  cho tới khi staff chủ động tạo appointment mới hoặc chuyển `closed`.
- Contact result và Application stage là 2 khái niệm khác nhau (ADR-009): ghi nhận
  `application_contact_attempts` không bao giờ tự động đổi `applications.stage`.

### 5.2. Contact Result — enum chính thức

`reached`, `no_answer`, `busy`, `wrong_number`, `consulted`, `callback_requested`,
`interview_agreed`, `candidate_refused`, `unsuitable`, `message_sent`, `other`
(cột `application_contact_attempts.result`; đồng nhất với `docs/DATABASE-DICTIONARY.md`).

Nhóm mở khóa `contacting → consulted` (đã chốt): `consulted`, `interview_agreed` — với điều
kiện bản ghi đó thuộc chu kỳ hiện tại (mục 5.4). Các kết quả khác không mở khóa vì chưa tư vấn
được thật sự. `candidate_refused`/`unsuitable` không tự động đóng hồ sơ — staff phải chủ động
chuyển `closed` kèm lý do.

### 5.3. Appointment (lịch gọi lại / phỏng vấn)

Bảng `application_appointments`:

- `type`: `callback`, `interview`.
- `status`: `scheduled`, `completed`, `cancelled`, `no_show`.
- Hẹn gọi lại bắt buộc có `scheduled_at`. Phỏng vấn hoàn thành phải cập nhật `outcome`/`note`
  và `status = completed`, `completed_by`, `completed_at` trước khi Application được phép
  chuyển `interview_scheduled → interviewed` (xem 5.1), **và bản ghi đó phải thuộc chu kỳ hiện
  tại** (mục 5.4).
- **Đổi lịch không sửa đè bản ghi cũ.** Khi cần đổi giờ/hủy lịch đã có: chuyển bản ghi cũ sang
  `status = cancelled` (hoặc `no_show` nếu đã quá hạn không đến), sau đó tạo 1 bản ghi
  Appointment **mới** cho lịch mới (cùng chu kỳ hiện tại). Giữ nguyên toàn bộ lịch sử các lần
  hẹn, không ghi đè `scheduled_at` của bản ghi đang tồn tại.

### 5.4. Workflow cycle contract (chính thức)

**Vấn đề cần chặn:** Application đã có Contact Log/Appointment ở lần xử lý trước, hồ sơ bị
đóng, sau đó được mở lại (`closed → new`) — nếu không đánh dấu ranh giới, dữ liệu cũ (thuộc lần
xử lý đã đóng) có thể tiếp tục được dùng làm bằng chứng để mở khóa `new → contacting` hay
`interview_scheduled → interviewed` mà không có tương tác thật nào trong lần xử lý mới.

**Giải pháp đã chọn:** thêm bộ đếm `workflow_cycle` trên `applications`, denormalize (sao chép)
giá trị này sang từng bản ghi bằng chứng tại thời điểm tạo — không dùng điều kiện thời gian đơn
thuần (so sánh timestamp) vì mốc thời gian dễ sai lệch khi có chỉnh sửa dữ liệu thủ công/import
sau này; bộ đếm nguyên là ranh giới rõ ràng, không mơ hồ.

- `applications.workflow_cycle` (int unsigned, NOT NULL, default `1`): số thứ tự chu kỳ xử lý
  hiện tại. Bắt đầu = `1` khi Application được tạo (Luồng 3).
- `applications.workflow_cycle_started_at` (timestamp, NOT NULL): thời điểm chu kỳ hiện tại bắt
  đầu — = `created_at` lúc tạo; cập nhật lại mỗi lần mở lại (mục 5.5).
- `application_contact_attempts.workflow_cycle`, `application_appointments.workflow_cycle`
  (int unsigned, NOT NULL): gán = `applications.workflow_cycle` **tại thời điểm tạo bản ghi**,
  trong cùng transaction/lock với việc ghi Contact Log hoặc Appointment.
- `application_status_histories.workflow_cycle` (int unsigned, NOT NULL): gán = chu kỳ mà
  `to_stage` thuộc về — với riêng dòng `closed → new`, dùng giá trị **sau khi tăng** (chu kỳ
  mới).
- Transition bình thường (không phải reopen) **không** thay đổi `workflow_cycle`/
  `workflow_cycle_started_at`.
- `closed → new` (reopen) **tăng `workflow_cycle` thêm 1** và đặt lại
  `workflow_cycle_started_at = now()` — xem mục 5.5.
- Mọi điều kiện transition ở mục 5.1 cần bằng chứng phải lọc thêm `AND workflow_cycle =
  applications.workflow_cycle` khi truy vấn — đây là kiểm tra ở **tầng Service**
  (`ChangeApplicationStageAction`), không phải constraint tĩnh ở DB (không thể diễn đạt "chu kỳ
  mới nhất" bằng CHECK constraint).
- Dữ liệu chu kỳ cũ **không bị xóa hay ẩn** — vẫn hiển thị đầy đủ trong lịch sử/timeline của
  Application (nhóm theo `workflow_cycle` để người xem phân biệt "lần xử lý trước" và "lần xử
  lý hiện tại"), chỉ không được dùng làm điều kiện mở khóa.

### 5.5. Reopen Application contract chính thức (`closed → new`)

Chỉ thực hiện thủ công, có kiểm soát — không có cơ chế tự động mở lại.

**Điều kiện bắt buộc (tất cả, kiểm tra trong `ChangeApplicationStageAction`, có `lockForUpdate`
trên Application trước khi kiểm tra):**

1. Người thực hiện: Staff thuộc đúng cơ sở của Application, hoặc Admin.
2. Có lý do mở lại — lưu ở `application_status_histories.note` của chính dòng `closed → new`
   này (tái dùng cột có sẵn, không thêm cột `reopen_reason` riêng); thiếu lý do bị từ chối.
3. Candidate của Application: chưa `deleted_at`, và `status` không phải `anonymized` hoặc
   `merged` — không mở lại hồ sơ của một danh tính đã ẩn danh hoặc đã bị gộp vào candidate
   khác.
4. Application hiện tại: `close_reason` **khác** `duplicate` — Application từng bị đóng vì
   trùng (case C hoặc do merge, mục 6.2–6.3) **không bao giờ** được mở lại, dù bằng thao tác
   nào.
5. Job: chưa `deleted_at`.
6. Job: nếu `status = published` và chưa hết hạn — Staff thuộc đúng cơ sở hoặc Admin đều mở lại
   được. Nếu Job không còn nhận hồ sơ (`paused`/`closed`/hết hạn) — **chỉ Admin** được mở lại
   (ngoại lệ có kiểm soát), Staff bị từ chối.
7. Không được vi phạm unique `(candidate_id, job_id)` — kiểm tra phòng hờ dù về lý thuyết đang
   mở lại chính bản ghi đó, không tạo bản ghi mới (bảo vệ khỏi race condition khi lock).
8. Toàn bộ trong 1 transaction; ghi `application_status_histories` (`from_stage=closed`,
   `to_stage=new`, `note`=lý do, `changed_by`=người thực hiện, `workflow_cycle`=chu kỳ mới) là
   bằng chứng audit — không cần bảng "audit log" tổng quát riêng (ADR-019).

**Khi mở lại, reset đúng các trường dẫn xuất của chu kỳ trước:**

- `stage = new`
- `close_reason = null`
- `closed_at = null`
- `expected_start_at = null` (tránh dữ liệu ngày dự kiến đi làm cũ vô tình thỏa điều kiện
  `interviewed → waiting_start` ở chu kỳ mới mà chưa có tương tác thật)
- `workflow_cycle = workflow_cycle + 1`
- `workflow_cycle_started_at = now()`
- `reopened_at = now()`
- `reopened_by = người thực hiện`

`started_at` không cần reset — theo transition matrix, `closed` không bao giờ tới từ `started`
(started là trạng thái cuối), nên Application đang `closed` luôn có `started_at = null`.

**Ứng viên gửi lại form (reapply) KHÔNG tự động mở lại hồ sơ:** rơi vào case C (mục 6.2) —
chỉ cập nhật `applications.last_reapplied_at`. HR nhận biết qua cột này trong danh sách hồ sơ
(sắp xếp/đánh dấu theo `last_reapplied_at`) — Phase 1 không xây notification/email riêng cho
sự kiện này (tránh vượt phạm vi). HR tự quyết định có mở lại hay không dựa trên các điều kiện
ở trên.

## 6. Luồng 6 — Chuyển cơ sở ngoại lệ và duplicate handling contract

### 6.1. Chuyển cơ sở (application_branch_histories) — contract chính thức

Chỉ dùng cho ngoại lệ: Job gán sai cơ sở, cơ sở phụ trách đổi, bàn giao vận hành, quản lý điều
chuyển. Không phải luồng thường xuyên. Chỉ `admin` thực hiện (staff không có quyền chuyển cơ sở
trong Phase 1, kể cả cơ sở hiện tại của chính mình).

**Điều kiện bắt buộc trước khi chuyển (tất cả, kiểm tra trong `ChangeApplicationBranchAction`,
có `lockForUpdate` trên Application trước khi kiểm tra):**

1. Cơ sở đích (`to_branch_id`) tồn tại.
2. Cơ sở đích `status = active`.
3. Cơ sở đích chưa `deleted_at`.
4. Cơ sở đích **khác** cơ sở hiện tại của Application (`to_branch_id != owner_branch_id` hiện
   tại) — chuyển sang chính cơ sở đang phụ trách bị từ chối (không tạo lịch sử vô nghĩa).
5. Người thực hiện là `admin`.
6. Có lý do chuyển (`reason` bắt buộc, không rỗng).
7. `lockForUpdate` trên Application trong suốt transaction — 2 request chuyển cơ sở đồng thời
   cho cùng 1 Application: request thứ hai đợi lock, đọc lại `owner_branch_id` mới nhất sau khi
   request đầu commit, áp dụng điều kiện 4 với giá trị mới (không ghi đè mù dữ liệu cũ).

**Thực hiện (1 transaction):**

```
lockForUpdate Application → kiểm tra 7 điều kiện trên
→ cập nhật applications.owner_branch_id = to_branch_id
→ thêm application_branch_histories (from_branch_id=cơ sở cũ, to_branch_id=cơ sở mới,
  transferred_by=admin, reason bắt buộc)
→ giữ nguyên toàn bộ contact attempts/status histories/appointments/notes/snapshot
→ commit → cơ sở mới nhìn thấy hồ sơ ngay; staff cơ sở cũ mất quyền xem/sửa ngay (vì Policy so
  sánh users.branch_id với owner_branch_id mới).
```

Không tạo Application mới khi chuyển cơ sở. Không có route/action nào khác được phép sửa
`applications.owner_branch_id` ngoài Action này.

### 6.2. Duplicate Candidate Contract chính thức (4 trường hợp, ADR-040)

**Full Name Normalization Contract (chính thức, `candidates.full_name_normalized`, ADR-063):**
cột `string(150)`, NOT NULL, có index (không unique), sinh tự động ở tầng Model từ `full_name`
mỗi khi tạo/sửa — không nhận trực tiếp từ client. Thuật toán:

1. Trim khoảng trắng đầu/cuối.
2. Gộp nhiều khoảng trắng liên tiếp thành đúng 1 khoảng trắng.
3. Chuẩn hóa Unicode dạng NFC.
4. Lowercase theo Unicode, **giữ nguyên dấu tiếng Việt** (không bỏ dấu — khác đặc tả trước).
5. Loại bỏ mọi ký tự không phải chữ cái Unicode/chữ số/khoảng trắng (xóa dấu câu), sau đó gộp
   khoảng trắng liên tiếp lần nữa.
6. **Không dùng fuzzy matching, Levenshtein hay AI** để tự động gộp Candidate trong Phase 1, dưới
   bất kỳ hình thức nào. So khớp trong 4 trường hợp dưới đây dùng **`full_name_normalized` khớp
   tuyệt đối** (bằng chuỗi, không phải điểm tương đồng).

#### 6.2.1. Merged-root resolution khi matching (chính thức, ADR-063)


Mọi thao tác dưới đây chạy **sau khi đã nhận named lock theo `phone_normalized`** (mục 3.1,
ADR-061). Không được dùng `first()`/`firstOrCreate()` để chọn ngẫu nhiên một Candidate theo số
điện thoại.

1. Query **tất cả** Candidate có contact `phone_normalized` khớp.
2. Với từng Candidate match, resolve theo `merged_into_candidate_id` tới root, tối đa 20 bước;
   phát hiện vòng lặp/chain lỗi thì ghi log kỹ thuật và loại chain lỗi khỏi tập so khớp (không
   chọn ngẫu nhiên, không dùng candidate merged làm đích Application).
3. Loại root trùng nhau theo `root_candidate_id` để một merged family chỉ xuất hiện một lần.
4. So sánh `full_name_normalized` và `date_of_birth` với **toàn bộ** root còn lại:
   - Có đúng **1 exact root** (phone + tên khớp tuyệt đối và DOB cùng khớp hoặc cả hai cùng thiếu)
     → tái sử dụng root đó.
   - Có **nhiều exact root** → dữ liệu nội bộ bất thường; không tự chọn một root. Tạo Candidate
     mới + Application và tạo một Duplicate Review `multiple_exact_matches` với từng exact root.
   - Không có exact root nhưng có suspected roots → tạo Candidate mới + Application và tạo một
     Duplicate Review cho từng root theo reason phù hợp.
   - Không có root nào → tạo Candidate mới bình thường, không bật review.

**Phân loại reason theo từng suspected root:**

- `same_phone_missing_dob`: phone + tên giống, chỉ một bên có DOB.
- `same_phone_different_name`: phone giống, tên chuẩn hóa khác.
- `same_identity_conflicting_dob`: phone + tên giống, cả hai có DOB nhưng khác nhau.
- `multiple_exact_matches`: có từ hai root trở lên cùng thỏa exact match; tạo một review cho mỗi
  root exact.
- `other`: chỉ dùng cho anomaly không thuộc bốn reason trên, bắt buộc có ghi chú kỹ thuật.

Cả trường hợp tạo Candidate mới có suspected roots đều đặt
`applications.needs_duplicate_review = true`; các cột summary trên Application được quản lý theo
mục 6.2.2. Không dùng fuzzy matching, Levenshtein hay AI.

#### 6.2.2. Duplicate Review Contract (bảng `candidate_duplicate_reviews`, ADR-062, ADR-075)

`candidate_duplicate_reviews` là **nguồn sự thật chi tiết**. Mỗi dòng liên kết Application mới,
Candidate mới và một suspected root. Một Application có thể có nhiều review khi cùng số điện
thoại khớp nhiều root.

Cột chính: `application_id`, `candidate_id`, `suspected_candidate_id`, `reason_code`, `status`,
`reviewed_by`, `reviewed_at`, `review_note`. Chặn review pending trùng cùng cặp + reason bằng
`pending_pair_key` generated unique. Cặp Candidate phải lưu theo đúng vai trò (Candidate mới →
`suspected root`), không đảo ngẫu nhiên.

Quy tắc:

- Không tự merge. `confirmed_same` chỉ là kết luận; Admin phải gọi `hr.candidates.merge` riêng.
- Trang detail hiển thị hai Candidate cạnh nhau và toàn bộ review khác của cùng Application.
- Khi resolve một dòng: cập nhật dòng review trong transaction, rồi query lại số review
  `pending` của Application.
  - Nếu còn pending: giữ `applications.needs_duplicate_review=true`; giữ
    `duplicate_reviewed_at/by = NULL`.
  - Nếu không còn pending: đặt `needs_duplicate_review=false`,
    `duplicate_reviewed_at=now()`, `duplicate_reviewed_by=admin hiện tại`.
- Nếu sau này tạo thêm review pending cho Application (chỉ qua action kiểm soát), phải reset
  summary về `needs_duplicate_review=true`, `duplicate_reviewed_at/by=NULL`.
- Chỉ Admin truy cập; Staff bị `403`.

**Case C — đã có Application cùng Job** (invariant theo merged family, ADR-076):

Trước khi tạo Application mới, hệ thống phải lấy **toàn bộ candidate id trong merged family**
của Candidate root được chọn (Candidate mới chỉ có family gồm chính nó) và tìm Application cùng
`job_id` trên toàn family, không chỉ kiểm tra `(root_candidate_id, job_id)`.

- Không tìm thấy → được tạo Application mới; unique `(candidate_id, job_id)` vẫn là chốt chặn DB
  cấp bản ghi.
- Tìm thấy một Application → không tạo mới; cập nhật `last_reapplied_at` và trả kết quả đã có.
- Tìm thấy nhiều Application do merge conflict lịch sử → chọn Application canonical theo thứ tự:
  1. ưu tiên bản ghi không có `close_reason=duplicate`;
  2. nếu còn nhiều, ưu tiên stage tiến xa nhất theo rank pipeline;
  3. tie-break bằng `id` nhỏ nhất; đồng thời ghi log data anomaly để Admin rà soát.
  Chỉ cập nhật `last_reapplied_at` trên canonical Application.
- Không tự reopen Application `closed` và không tạo Application mới chỉ vì bản ghi cũ nằm trên
  Candidate nguồn đã merged.

Thông báo public: "Bạn đã ứng tuyển vị trí này, chúng tôi sẽ liên hệ sớm." Không trả lỗi 500.

### 6.3. Merge Candidate — contract đầy đủ (bao gồm "merged family")

**Quan hệ đích/nguồn:** `candidates.merged_into_candidate_id` (self-FK, nullable) trỏ từ
candidate **nguồn** sang candidate **đích** ngay tại thời điểm merge — quan hệ 1 chiều, 1 cấp
mỗi lần merge. Không cho merge candidate vào chính nó (`source_id != target_id`, kiểm tra tường
minh trước transaction). Không cho merge candidate **đã** `status = merged` làm **nguồn** một
lần nữa (một candidate chỉ là nguồn của đúng 1 lần merge). Không cho merge candidate có
`status = anonymized` hoặc đã `deleted_at`, dù làm nguồn hay đích — không đủ dữ liệu định danh
để merge có ý nghĩa, và tránh thao tác trên dữ liệu đã ẩn danh.

**Merge nhiều tầng (A → B, sau đó B → C):** hợp lệ — đây là 2 sự kiện merge độc lập, mỗi
candidate chỉ làm nguồn đúng 1 lần (A là nguồn của lần 1, B là nguồn của lần 2). Hệ thống
**không** cập nhật lại `A.merged_into_candidate_id` thành `C` — A vẫn trỏ thẳng tới B như tại
thời điểm merge. Việc "biết A cuối cùng thuộc về C" là trách nhiệm của **truy vấn merged
family** dưới đây, không phải của việc ghi đè con trỏ lịch sử.

**Chống vòng lặp (cycle):** trước khi merge X vào Y, đi theo chuỗi `merged_into_candidate_id`
xuất phát từ Y — nếu gặp lại X ở bất kỳ bước nào, từ chối (sẽ tạo chu trình). Trong thực tế khó
xảy ra vì mỗi candidate chỉ merge 1 lần duy nhất (không thể vừa là nguồn vừa là đích của cùng 1
chuỗi), nhưng vẫn kiểm tra tường minh làm lớp bảo vệ cuối.

**Truy vấn "merged family" của 1 candidate X (dùng để hiển thị lịch sử hợp nhất):**

1. Tìm candidate gốc còn hoạt động (root): đi theo `merged_into_candidate_id` liên tiếp từ X
   cho tới khi gặp candidate có `merged_into_candidate_id IS NULL`.
2. Tập hợp family = root + mọi candidate mà chuỗi `merged_into_candidate_id` (đệ quy, nhiều
   tầng) dẫn tới root.
3. Trang chi tiết Candidate luôn hiển thị theo **root** — `applications` hiển thị = UNION của
   `applications.candidate_id IN (family)`, sắp xếp theo thời gian, đánh dấu rõ bản ghi nào
   `candidate_id != root.id` kèm tên candidate nguồn tương ứng. **Không phải ngoại lệ của scope
   theo cơ sở**: Staff xem trang này vẫn chỉ thấy các Application trong family có
   `owner_branch_id = users.branch_id` của mình — Application thuộc cơ sở khác trong cùng
   family vẫn ẩn với Staff, chỉ Admin thấy toàn bộ family không giới hạn cơ sở.
4. Không dùng trực tiếp `WHERE applications.candidate_id = X` ở bất kỳ báo cáo/danh sách nào
   liên quan tới "toàn bộ hồ sơ của 1 người" — luôn phải qua truy vấn family ở trên, vì candidate
   nguồn không bị đổi `candidate_id` trên các Application trùng job (xem bên dưới).

**Candidate nguồn sau merge (`status = merged`):** không được sửa (`full_name`,
`date_of_birth`, `candidate_contacts`...) — khóa chỉnh sửa ở Policy. Không xuất hiện trong danh
sách tìm kiếm/candidate thông thường. Truy cập trực tiếp URL chi tiết của candidate nguồn →
redirect sang trang chi tiết của **root** (không 404, tránh gãy link cũ).

**Application không trùng job của candidate nguồn:** đổi `candidate_id` sang candidate đích
(việc dồn dữ liệu không rủi ro vì không đụng unique `(candidate_id, job_id)`).

**Application trùng job (cả 2 candidate đều có Application cho cùng 1 Job) — merge conflict:**

1. **Application được giữ**: **admin chọn thủ công** — hệ thống chỉ đề xuất gợi ý (Application
   có `stage` tiến xa hơn theo thứ tự `new < contacting < consulted < interview_scheduled <
   interviewed < waiting_start < started`; `closed` không tính là "tiến xa"), admin xác nhận
   hoặc chọn bản ghi khác. **`candidate_id` của Application được giữ KHÔNG bị đổi** — dù vốn
   thuộc candidate đích hay candidate nguồn, giữ nguyên như đã tạo.
2. **Application còn lại**: chuyển `stage = closed`, `close_reason = duplicate` qua
   `ChangeApplicationStageAction` bình thường (vẫn ghi `application_status_histories`).
   **`candidate_id` cũng KHÔNG bị đổi.** Bản ghi này **không bao giờ được mở lại** (`closed →
   new` bị từ chối tuyệt đối — mục 5.5 điều kiện 4).
3. **Vì cả 2 Application trùng job đều giữ nguyên `candidate_id` gốc** (không có bản nào bị đổi
   sang candidate đích), không có xung đột với unique `(candidate_id, job_id)` ở bất kỳ bước
   nào — mỗi Application vẫn giữ đúng cặp `(candidate_id, job_id)` như lúc tạo.
4. **Metadata ghi lại**: dòng `application_status_histories` đóng Application còn lại ghi thêm
   vào cột `metadata` (json, đã có sẵn) ví dụ `{"merge_kept_application_id": <id>,
   "merge_target_candidate_id": <id>}` — tái dùng cột có sẵn, không thêm cột mới.
5. **Contact Log, Note, Appointment, Status History, Branch History**: giữ nguyên gắn với
   `application_id` gốc của từng bản ghi — không di chuyển, không xóa. Cả 2 Application (giữ
   và đóng) vẫn hiển thị được lịch sử đầy đủ của mình, và cả hai đều xuất hiện khi xem theo
   "merged family" của candidate đích (mục truy vấn ở trên).
6. Toàn bộ transaction: lock cả 2 candidate + 2 application liên quan trước khi thao tác.
7. **Ai được merge**: chỉ `admin` (staff không có quyền merge candidate).
8. Thêm `candidates.merge_reason` (nullable) ghi lý do merge do admin nhập.
9. Sau merge, mọi lần ứng tuyển lại phải kiểm tra cùng Job trên **toàn merged family** theo Case C
   mục 6.2.2; unique `(candidate_id, job_id)` không thay thế được invariant cấp family này.

### 6.4. Candidate Access Policy (chính thức)

`candidates` **không có** `branch_id` — Candidate không thuộc cố định về một cơ sở (một người có
thể có nhiều Application ở nhiều cơ sở khác nhau qua thời gian). Quyền xem trang chi tiết
Candidate (`hr.candidates.show`, mục truy vấn "merged family" — mục 6.3) tính theo dữ liệu
Application, không theo một cột cố định trên Candidate:

- **Staff** chỉ mở được trang chi tiết Candidate khi **merged family** của Candidate đó (mục
  6.3) có **ít nhất một** Application với `owner_branch_id = users.branch_id` của Staff. Nếu
  không có Application nào trong family thuộc cơ sở của Staff → `GET
  /hr/ung-vien/{candidate}` trả **403** (không hiển thị trang rỗng, không redirect lộ thông tin
  Candidate tồn tại hay không).
- Sau khi mở được trang (đủ điều kiện trên), Staff **chỉ thấy** các Application trong family
  thuộc cơ sở của mình — Application của family thuộc cơ sở khác vẫn ẩn (đã mô tả ở mục 6.3,
  bước 3).
- **Admin** không bị giới hạn — mở được mọi Candidate, thấy toàn bộ merged family không phân
  biệt cơ sở.
- Kiểm tra quyền này bắt buộc thực hiện ở **backend** (Policy/Action), không chỉ ẩn nút hoặc
  route ở tầng view — truy cập trực tiếp URL phải bị chặn đúng như trên.

## 7. Chính sách dữ liệu cá nhân

**Sửa lại (ADR-037, thay thế ADR-036):** vòng trước đã ghi các mục dưới đây là "đã chốt" dựa
trên câu trả lời nhanh qua công cụ hỏi trong phiên làm việc — đây **không phải bằng chứng xác
nhận chính thức từ công ty** cho một quyết định ảnh hưởng trực tiếp tới nghĩa vụ pháp lý về dữ
liệu cá nhân. Các mục đó được chuyển lại thành **[CẦN CHỐT VỚI CÔNG TY]**. Phần dưới đây phân
biệt rõ: mục nào là **quyết định kiến trúc** (được quyết định ở đây, rủi ro thấp, không cần
công ty ký duyệt) và mục nào **bắt buộc công ty xác nhận**.

### 7.1. Phân biệt `job_snapshot` và `submission_snapshot`

- **`job_snapshot`**: dữ liệu Job (tên, công ty, lương, địa điểm, quyền lợi...) tại thời điểm
  ứng tuyển — **không chứa dữ liệu định danh cá nhân của ứng viên**. Giữ nguyên vĩnh viễn,
  không cần chính sách anonymize riêng — đây là dữ liệu về công việc, không phải về ứng viên.
- **`submission_snapshot`**: dữ liệu form ứng viên đã nhập tại thời điểm nộp — **có thể chứa
  họ tên, số điện thoại, ngày sinh, địa chỉ và các trường định danh cá nhân khác**. Đây là mục
  cần chính sách anonymize riêng, xem 7.2.

### 7.2. Xử lý `submission_snapshot` khi Candidate được anonymize — đề xuất mặc định

**[CẦN CHỐT VỚI CÔNG TY]** — dưới đây là đề xuất, chưa phải quyết định cuối cùng:

- **Mask hoặc xóa** họ tên (`full_name`).
- **Mask** số điện thoại (`phone`/`phone_normalized`).
- **Xóa hoặc mask** ngày sinh (`date_of_birth`).
- **Xóa hoặc tổng quát hóa** địa chỉ (`address_detail`) — có thể giữ lại cấp tỉnh nếu cần cho
  thống kê, xóa phần chi tiết.
- **Xóa** các trường định danh khác nếu form thu thập thêm (số điện thoại phụ...).
- **Giữ nguyên** dữ liệu nghiệp vụ không định danh (`education_level`, `experience_summary`,
  nguồn ứng tuyển, thời điểm nộp...) để phục vụ thống kê.

Đây là điểm khác biệt so với đề xuất vòng trước (vốn đề xuất giữ nguyên toàn bộ snapshot) —
sau khi rà soát lại, việc `submission_snapshot` chứa nguyên văn họ tên/SĐT/ngày sinh của một
Candidate đã anonymize là rủi ro thực sự (anonymize `candidates` nhưng JSON lịch sử vẫn còn
đầy đủ thông tin định danh thì việc anonymize không có ý nghĩa). Công ty cần xác nhận mức độ
mask phù hợp (ví dụ mask 1 phần số điện thoại hay xóa hẳn) trước khi thiết kế Action chi tiết.

### 7.2.1. PII schema tối thiểu cho `applications` (chính thức, ADR-056 — tách khỏi retention)

Khác với mục 7.2 (nội dung mask **bên trong** `submission_snapshot` — vẫn CẦN CHỐT), phần dưới
đây là **quyết định cấu trúc schema** (nullable, cơ chế ghi đè, ảnh hưởng index) cho 6 cột PII
trực tiếp trên `applications` — **khóa được ngay, không cần công ty ký duyệt**:

| Cột | Nullable | Khi anonymize | Ảnh hưởng index/unique | Giữ audit |
|---|---|---|---|---|
| `submitted_full_name` | NOT NULL (không đổi) | Mask — ghi đè placeholder cố định | Không đánh index | Không giữ giá trị gốc |
| `submitted_phone` | NOT NULL (không đổi) | Mask — ghi đè placeholder cố định | Không đánh index | Không giữ giá trị gốc |
| `submitted_phone_normalized` | NOT NULL (không đổi) | Mask — ghi đè cùng placeholder đã chuẩn hóa | Có index, không unique — nhiều bản ghi trùng giá trị mask chấp nhận được | Không giữ giá trị gốc |
| `submission_snapshot` | NOT NULL, json (không đổi) | **Thay thế** bằng JSON đã redact (không set NULL) — nội dung redact cụ thể: mục 7.2, vẫn CẦN CHỐT | Không đánh index | Giữ key nghiệp vụ không định danh |
| `consent_ip` | nullable (không đổi) | Set NULL | Không đánh index | Không giữ |
| `consent_user_agent` | nullable (không đổi) | Set NULL | Không đánh index | Không giữ |

Không thêm cột `applications.anonymized_at` riêng — nguồn sự thật duy nhất vẫn là
`candidates.anonymized_at`/`status=anonymized`; Action anonymize cascade ghi đè các cột trên của
toàn bộ `applications` thuộc candidate đó trong cùng transaction.

### 7.3. Các mục đã quyết định (kiến trúc, không cần công ty ký duyệt)

- **Ai có quyền anonymize**: chỉ `admin`, qua 1 Action riêng, ghi `candidates.anonymized_at`
  (nhất quán với merge candidate/chuyển cơ sở — cũng chỉ admin).
- **Có hoàn tác được anonymize không**: **không** — đây là thực tế kỹ thuật chứ không phải lựa
  chọn chính sách: một khi dữ liệu định danh đã bị mask/xóa (7.2), bản gốc không còn lưu ở đâu
  để khôi phục.
- **Candidate `anonymized` có xuất hiện trong tìm kiếm/danh sách candidate mặc định không**:
  **không** — loại khỏi kết quả tìm kiếm/danh sách thông thường, nhất quán với cách candidate
  `merged` đã bị loại.
- **Contact Log/Note chứa dữ liệu cá nhân**: **không tự động xử lý** khi candidate anonymize —
  đây là nhật ký thao tác nội bộ của nhân viên (không phải hồ sơ ứng viên), và việc tự động dò
  tìm/xóa PII trong văn bản tự do (`note`) không đáng tin cậy (không có NLP/regex nào đảm bảo
  đúng 100%). Thay vào đó là **quy tắc phòng ngừa**: staff không được ghi CCCD hoặc thông tin
  định danh nhạy cảm vào `note` ngoài phạm vi cần thiết (nhắc trong hướng dẫn sử dụng nội bộ,
  không phải ràng buộc kỹ thuật).
- **Xử lý yêu cầu xóa dữ liệu của ứng viên**: ứng viên liên hệ công ty ngoài hệ thống (điện
  thoại/email tới công ty — Phase 1 không có form "yêu cầu xóa dữ liệu" riêng, tránh tạo tính
  năng giống Lead ngoài phạm vi); Admin xác minh yêu cầu rồi thực hiện Action anonymize.
- **Xử lý khi anonymize**: `candidates.status = anonymized`; các trường định danh cá nhân trên
  `candidates`/`candidate_contacts` được mask/xóa; `candidates.id`/quan hệ với `applications`
  giữ nguyên để không phá vỡ lịch sử tuyển dụng và báo cáo. Candidate `anonymized` không được
  làm nguồn hay đích của merge mới (mục 6.3), và Application của candidate đó không được mở lại
  nếu đang `closed` (mục 5.5 điều kiện 3).
- **Trường dữ liệu cấm thu thập từ form public**: xem `.claude/rules/application-domain.md` —
  nguồn duy nhất, không lặp lại ở đây.
- **Consent version**: `applications.consent_version`; nội dung đầy đủ hiển thị qua `pages`
  (slug cố định, ví dụ `chinh-sach-du-lieu-ca-nhan`) — không cần bảng version riêng.
- **Audit log cần giữ**: đáp ứng đầy đủ bởi các bảng lịch sử append-only đã có
  (`application_status_histories`, `application_contact_attempts`, `application_branch_histories`,
  `job_status_histories`, `job_verifications`, `export_logs`) — không cần bảng `audit_logs`
  tổng quát (ADR-019). Action anonymize tự thân cũng ghi `candidates.anonymized_at` +
  `anonymized_by` (xem `docs/DATABASE-DICTIONARY.md`) làm bằng chứng ai/khi nào.

### 7.3.1. PII trong nội dung free-text ngoài `submission_snapshot` (rà soát, ADR-071)

Mục 7.2/7.2.1 chỉ xét `submission_snapshot`. Rà soát mở rộng sang các trường free-text khác có
thể chứa PII: `application_notes.content`, `application_contact_attempts.note`,
`application_appointments.note`/`outcome`, `candidates.experience_summary` — `job_snapshot`
không chứa PII candidate (mục 7.1), không cần xét.

- **Quy tắc phòng ngừa** (không phải ràng buộc kỹ thuật): nhân viên không ghi CCCD/thông tin định
  danh nhạy cảm ngoài phạm vi cần thiết vào các trường trên (hướng dẫn sử dụng nội bộ).
- **Không tự động redact** bất kỳ trường nào ở trên khi Candidate anonymize — dò PII trong văn
  bản tự do bằng regex/NLP không đủ tin cậy để tự động hóa an toàn (đã chốt ở mục 7.3).
- **Review thủ công**: Admin có thể sửa/xóa một note cụ thể qua route đã có
  (`hr.applications.notes.update`/`destroy`) nếu phát hiện PII không cần thiết — hành động thủ
  công theo từng trường hợp, không phải quy trình tự động.
- `application_contact_attempts`/`application_appointments` là bảng lịch sử (gần) append-only —
  không sửa/xóa nội dung sau khi tạo. `application_notes` cho phép owner/admin sửa nhưng **không**
  lưu lịch sử đầy đủ các lần sửa — chỉ giữ `edited_at` (mốc gần nhất, đã có) — quyết định có chủ
  đích, Note là ghi chú làm việc, không phải hồ sơ cần audit trail đầy đủ.
- **Người xem**: không đổi quyền hiện có — Staff thuộc đúng cơ sở hoặc Admin xem Note/Contact Log
  như bình thường kể cả sau khi Candidate anonymize (anonymize chỉ mask dữ liệu định danh của
  Candidate, không tạo mức quyền xem mới).

Danh sách + quy tắc phòng ngừa ở trên là quyết định kiến trúc, khóa được ngay. Có xây thêm cơ chế
redact/kiểm duyệt mạnh hơn cho nội dung free-text hay không là **go-live/chính sách vận hành —
[CẦN CHỐT VỚI CÔNG TY]** nếu công ty yêu cầu mức kiểm soát cao hơn — không ảnh hưởng schema hiện
tại, không phải migration blocker.

### 7.4. [CẦN CHỐT VỚI CÔNG TY] — thời hạn lưu dữ liệu

Thời hạn lưu dữ liệu ứng viên trước khi đủ điều kiện anonymize (nếu công ty muốn có cơ chế tự
động theo thời gian, ngoài việc anonymize theo yêu cầu chủ động của ứng viên — quyền này luôn
tồn tại bất kể thời hạn lưu mặc định là gì). **Chưa có con số nào được chọn** — không tự suy
đoán.

## 8. Danh sách [CẦN CHỐT]

Phân loại 3 nhóm đầy đủ (Migration / Go-live / Phase 2 decision) và điều kiện chuyển Giai đoạn 1:
`ROADMAP.md` mục "Phân loại blocker" (ADR-049). Sau ADR-055 (Enum Strategy), **không còn mục nào
trong danh sách dưới đây là migration blocker** — toàn bộ đều là go-live blocker hoặc Phase 2
decision, không chặn việc viết migration Phase 1.

### 8.1. [CẦN CHỐT VỚI CÔNG TY] — chính sách/vận hành, KHÔNG chặn migration (ADR-049)

| # | Quyết định | Nhóm | Ảnh hưởng nếu chọn sai/chậm |
|---|---|---|---|
| 1 | Thời hạn lưu dữ liệu ứng viên (mục 7.4) | Go-live blocker | Không có scheduler anonymize tự động cho tới khi có quyết định; không ảnh hưởng schema (không cần cột `retention_days` để viết migration) |
| 2 | Mức độ mask/xóa cụ thể cho `submission_snapshot` khi anonymize (mục 7.2) | Go-live blocker | Ảnh hưởng nội dung **Action** anonymize (mask 1 phần hay xóa hẳn từng trường); cấu trúc schema đã chốt ở mục 7.2.1 (ADR-056) không đổi dù chọn phương án nào |
| 3 | Có bật `job_auto_pause_enabled = true` ở giai đoạn sau hay không (mục 1.3) | Phase 2 decision | Mặc định `false`, không code path nào thực thi ở Phase 1; chỉ cần bổ sung 2 cột (`actor_type`/`changed_by` nullable cho `job_status_histories`) bằng migration riêng **nếu và khi** công ty xác nhận bật |
| 4 | Giá trị `job_verification_valid_days` (mục 1.3, ADR-058) | Go-live blocker | Mặc định tắt (`null` = không kiểm tra độ mới, chỉ cần "mới nhất = still_open"); schema (`settings`) đã đủ cho mọi giá trị công ty chọn sau này |
| 5 | ~~Nguồn dữ liệu `administrative_units` chính thức~~ — **đã chốt (ADR-079):** API `provinces.open-api.vn`, nhập qua `php artisan administrative-units:import` | Resolved | Không còn blocker; `official_code` khớp mã GSO từ API |
| 6 | Có cần cơ chế redact/kiểm duyệt mạnh hơn cho nội dung free-text (Note/Contact Log/Appointment) hay không (mục 7.3.1, ADR-071) | Go-live blocker (tùy chọn, không bắt buộc phải có) | Không ảnh hưởng schema — các cột free-text đã đúng kiểu dữ liệu cần thiết |

### 8.2. Enum phụ — đề xuất mặc định, KHÔNG còn là migration blocker (ADR-055)

| Cột | Giá trị đề xuất (mặc định PHP backed enum) | Kiểu lưu trữ |
|---|---|---|
| `company_contacts.status` | `active`, `inactive` | `varchar` (không phải DB `enum()`) |
| `jobs.employment_type` | `full_time`, `part_time`, `seasonal`, `temporary` | `varchar` |
| `jobs.close_reason` | `recruitment_filled`, `recruitment_stopped`, `expired`, `company_request`, `duplicate`, `other` | `varchar` |
| `pages.status` | `draft`, `published`, `hidden` | `varchar` |
| `settings.type` | `string`, `integer`, `boolean`, `json` | `varchar` |

Từ ADR-055, 5 cột này dùng `varchar` + PHP backed enum + validation thay vì DB `enum()` — đổi
giá trị đề xuất sau này chỉ cần sửa code (class enum + `Rule::in`), **không cần migration mới**.
Vì vậy **không còn lý do chặn migration ban đầu** để chờ công ty duyệt từng giá trị — migration
tạo cột `varchar` với danh sách trên làm mặc định làm việc, công ty góp ý sau vẫn áp dụng được
mà không phá vỡ schema. `jobs.status`/`applications.stage` (state machine trung tâm) không nằm
trong nhóm này — giữ nguyên DB `enum()` như đã chốt.

Mục 8.1 (go-live/Phase 2 decision) **không chặn** việc viết migration — xem `ROADMAP.md` mục
"Phân loại blocker" (ADR-049).

## 9. Dashboard và bộ lọc hồ sơ Phase 1 (chính thức)

### 9.1. Dashboard

**Dashboard Staff** (theo cơ sở của Staff, `owner_branch_id = users.branch_id`):

- Hồ sơ mới hôm nay (`applications.created_at` = hôm nay).
- Hồ sơ chưa liên hệ (`stage = new`, chưa có `application_contact_attempts` nào).
- Hồ sơ đang xử lý (`stage` ∈ {`contacting`, `consulted`, `interview_scheduled`, `interviewed`,
  `waiting_start`}).
- Lịch gọi lại hôm nay (`application_appointments.type=callback`, `status=scheduled`,
  `scheduled_at` = hôm nay).
- Lịch phỏng vấn hôm nay (tương tự, `type=interview`).
- Hồ sơ chờ đi làm (`stage = waiting_start`).
- Hồ sơ đã đi làm (`stage = started`).
- Hồ sơ đã đóng (`stage = closed`).

**Dashboard Admin**: toàn bộ số liệu trên nhưng không giới hạn cơ sở, có filter chọn 1 hoặc
nhiều cơ sở; thêm thống kê cơ bản theo Job (số Application/Job), theo Company (số Job/Company),
và tỷ lệ chuyển đổi Application → `started` (đếm theo `stage`, không cần bảng thống kê riêng —
tính trực tiếp từ `applications`).

Phase 1 **không xây**: BI nâng cao, dự báo, dashboard realtime phức tạp, AI analytics — các mục
này thuộc Phase 2 (`ROADMAP.md`). Toàn bộ số liệu trên tính trực tiếp từ các bảng đã có
(`applications`, `application_appointments`), không cần bảng thống kê/materialized view riêng ở
Phase 1.

### 9.2. Application Filters (`hr.applications.index`)

Danh sách hồ sơ HR (`docs/ROUTE-MAP.md` — `hr.applications.index`) hỗ trợ lọc kết hợp theo:

- Tên candidate (`candidates.full_name`, LIKE), số điện thoại (`submitted_phone_normalized`
  hoặc `candidate_contacts.normalized_value`).
- Job (`job_id`), Company (qua `jobs.company_id`).
- Cơ sở (`owner_branch_id`) — **Staff: cố định theo cơ sở của mình, không đổi được qua query
  string**; **Admin: chọn được một hoặc nhiều cơ sở**.
- Stage (`applications.stage`), khoảng ngày (`created_at BETWEEN`).
- Hồ sơ mới (`stage = new`), hồ sơ chưa liên hệ (`stage = new` và chưa có contact attempt).
- Có lịch gọi lại/phỏng vấn (join `application_appointments` where `status = scheduled`).
- Chờ đi làm (`stage = waiting_start`), đã đi làm (`stage = started`).
- Nghi ngờ trùng (`needs_duplicate_review = true`).

Mọi filter đều là điều kiện `WHERE` trên các cột/quan hệ đã có sẵn trong schema Phase 1 — không
cần cột hay bảng mới. Kết hợp nhiều filter không được tạo bản ghi trùng (dùng `EXISTS`/`whereHas`
cho điều kiện join `application_appointments`, không `JOIN` trực tiếp gây nhân bản dòng).
