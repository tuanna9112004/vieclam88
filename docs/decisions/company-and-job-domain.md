# Company và Job domain

> Nguồn ADR theo chủ đề. Không đổi mã ADR; ADR mới phải được thêm vào file chủ đề phù hợp và cập nhật `docs/decisions/INDEX.md`.

<a id="adr-008"></a>

## ADR-008 — Một `job` là một đợt tuyển dụng, không tái sử dụng job cũ

**Quyết định:** Unique constraint `candidate_id + job_id` trên `applications` để chống ứng
tuyển trùng cùng một đợt. Khi tuyển đợt mới cho cùng vị trí, nhân bản job thành bản ghi mới
(`job` ID mới), giữ nguyên job cũ trong lịch sử thay vì mở lại.

**Lý do:** Nếu mở lại job cũ cho đợt tuyển mới, unique constraint sẽ chặn nhầm candidate đã
từng ứng tuyển đợt trước nhưng hợp lệ để ứng tuyển đợt này. Nhân bản job giữ lịch sử rõ ràng
theo từng đợt tuyển.

<a id="adr-011"></a>

## ADR-011 — Không lặp địa điểm giữa `jobs` và `company_locations`

**Quyết định:** Không lưu `jobs.province_id`/`jobs.industrial_park_id` trực tiếp. Dùng
`company_locations` (địa điểm của công ty) và `job_locations` (bảng trung gian job ↔
location, hỗ trợ nhiều địa điểm, có `is_primary`).

**Lý do:** Lưu địa điểm ở cả `jobs` và `company_locations` sẽ tạo 2 nguồn sự thật có thể lệch
nhau (sửa địa chỉ công ty nhưng quên sửa job). `job_locations` cho phép 1 job tuyển ở nhiều
địa điểm mà không cần nhân bản job.

<a id="adr-015"></a>

## ADR-015 — Thêm `branches` (cơ sở nội bộ), tách khỏi `company_locations`

**Quyết định:** Tạo bảng `branches` đại diện cho cơ sở/văn phòng nội bộ của chính công ty cung
ứng lao động (vieclam88) — nơi nhân viên làm việc và xử lý hồ sơ. Tách hoàn toàn khỏi
`company_locations` (địa điểm làm việc/nhà máy của công ty khách hàng) và `company_contacts`
(đầu mối liên hệ tại công ty khách hàng, chủ yếu nội bộ).

**Lý do:** Yêu cầu nghiệp vụ xác nhận công ty có nhiều cơ sở nội bộ, mỗi cơ sở phụ trách xử lý
một tập hồ sơ riêng, nhân viên thuộc một cơ sở. Dùng `company_locations` cho việc này sẽ trộn
lẫn 2 khái niệm không liên quan (nơi khách hàng cần tuyển người và nơi công ty cung ứng lao
động vận hành), gây sai lệch khi công ty khách hàng có nhiều nhà máy nhưng chỉ 1 cơ sở nội bộ
phụ trách, hoặc ngược lại.

<a id="adr-023"></a>

## ADR-023 — CTA Gọi/Zalo luôn ưu tiên contact cơ sở, không dùng `company_contacts` làm CTA thay thế

**Quyết định:** CTA "Gọi"/"Nhắn Zalo" trên trang Job công khai luôn dùng `branches.phone`/
`branches.zalo` của `jobs.owner_branch_id`. `company_contacts.is_public = true` không còn là
nguồn CTA ưu tiên số 1 thay thế branch — chỉ hiển thị **thêm** như kênh liên hệ phụ khi được
chọn làm `jobs.company_contact_id`. Thay thế logic ưu tiên mô tả trước đây trong
`docs/CORE-FLOWS.md` (bản trước: `company_contacts.is_public` ưu tiên 1, branch là fallback).

**Lý do:** Nhân viên cơ sở (branch) là người trực tiếp xử lý ứng viên trong Phase 1; để CTA mặc
định trỏ tới đầu mối của công ty khách hàng (nhà máy) sẽ khiến ứng viên liên hệ sai người, bỏ
qua vai trò tư vấn của công ty cung ứng lao động. Vì `branches.phone`/`zalo` hợp lệ đã là điều
kiện bắt buộc trước khi publish (ADR liên quan `docs/CORE-FLOWS.md` mục 1), CTA luôn có dữ liệu
để hiển thị mà không cần fallback.

<a id="adr-025"></a>

## ADR-025 — Chốt Job transition matrix tường minh và quy tắc đổi lịch hẹn (appointment)

**Quyết định:** Job transition matrix chỉ cho phép đúng 5 transition: `draft→published`,
`published→paused`, `paused→published`, `published→closed`, `paused→closed`. `draft→closed`
không phải transition — Job nháp bỏ dùng được xử lý bằng soft delete (hành động khác, không
qua state machine). `application_appointments`: đổi lịch (đổi giờ/hủy) không sửa đè
`scheduled_at` của bản ghi cũ — chuyển bản ghi cũ sang `cancelled`/`no_show` rồi tạo bản ghi
Appointment mới, giữ nguyên lịch sử các lần hẹn.

**Lý do:** Bản đặc tả trước chỉ mô tả trạng thái Job dạng chuỗi (`draft → published → paused →
closed`) không liệt kê tường minh `paused → published` (mở lại) hay giới hạn transition nào bị
cấm — dẫn tới rủi ro code cho phép chuyển trạng thái tùy ý. Việc appointment không được sửa đè
`scheduled_at` giữ đúng nguyên tắc "lịch sử chỉ thêm, không ghi đè" (`CLAUDE.md`) — sửa đè giờ
hẹn cũ sẽ làm mất bằng chứng đã từng hẹn giờ nào, ai đổi, đổi khi nào.

<a id="adr-033"></a>

## ADR-033 — Thêm `job_status_histories`, tách khỏi `job_verifications`

**Quyết định:** Thêm bảng `job_status_histories` (append-only) ghi mọi transition trong Job
transition matrix, qua `ChangeJobStatusAction`. Không dùng `job_verifications` (xác nhận job
còn tuyển theo lịch định kỳ) thay thế — hai bảng khác mục đích. `paused → published` (mở lại)
phải re-check toàn bộ điều kiện publish, không giả định vẫn còn đúng như lần publish trước.

**Lý do:** `jobs.updated_by`/`updated_at` chỉ biết "ai sửa lần cuối", không biết đầy đủ chuỗi
transition trạng thái đã qua — không đủ để trả lời "job này đã publish/pause bao nhiêu lần, khi
nào, ai làm, vì sao". `job_verifications` ghi nhận việc xác nhận định kỳ, ngữ nghĩa khác hoàn
toàn với việc ghi lại 1 lần đổi trạng thái thật. Re-check điều kiện publish khi mở lại tránh
tình huống company/branch đã đổi trạng thái trong lúc job bị pause mà hệ thống không phát hiện.

<a id="adr-038"></a>

## ADR-038 — Job Branch Contract: quản lý `jobs.owner_branch_id`, thêm `job_branch_histories`

**Quyết định:** `owner_branch_id` chỉ set lúc tạo Job (staff: tự động = `users.branch_id`;
admin: chọn tường minh) và đổi qua `ChangeJobBranchAction` (chỉ admin, chỉ khi Job không
`published`, cơ sở đích `active`/chưa xóa, có lý do, `lockForUpdate`). `hr.jobs.update` không
được sửa cột này. Thêm bảng `job_branch_histories` (id, job_id, from_branch_id, to_branch_id,
reason, changed_by NOT NULL, created_at) — chọn **thêm bảng** thay vì "bất biến sau publish".
Application đã tạo giữ nguyên `owner_branch_id` cũ khi Job đổi cơ sở sau đó.

**Lý do:** Nhu cầu nghiệp vụ xác nhận Admin cần đổi cơ sở phụ trách Job sau khi đã publish (gán
nhầm, tái cấu trúc vận hành) — nếu chọn "bất biến sau publish" sẽ không đáp ứng được nhu cầu
này, chỉ còn cách xóa job và tạo lại (mất lịch sử, vi phạm ADR-008 tinh thần "một job một đợt
tuyển"). Thêm bảng lịch sử giữ nhất quán với `application_branch_histories`/
`job_status_histories` đã có — không để một thay đổi quan trọng (cơ sở phụ trách Job) không có
dấu vết. `changed_by` NOT NULL (khác `application_branch_histories` cho phép null=hệ thống) vì
Job luôn do một người thao tác qua form, không có luồng "hệ thống tự gán" nào cho Job.

<a id="adr-044"></a>

## ADR-044 — Sửa lỗi hướng quan hệ ERD: `administrative_units` ↔ `branches`, `candidates.current_administrative_unit_id`

**Quyết định:** Sửa `docs/ERD.md`: quan hệ đúng là `administrative_units ||--o{ branches`
(một đơn vị hành chính có nhiều cơ sở, vì `branches.administrative_unit_id →
administrative_units.id`) — bản trước vẽ ngược thành `branches ||--o{ administrative_units`.
Sửa cardinality `administrative_units ... candidates` (qua `current_administrative_unit_id`,
cột nullable) từ `||--o{` thành `|o--o{` cho đúng quy ước "đầu một có thể null" đã nêu trong
chính file ERD.

**Lý do:** Hướng quan hệ vẽ ngược khiến người đọc hiểu sai mô hình dữ liệu (tưởng 1 branch có
nhiều đơn vị hành chính, trong khi thực tế 1 branch chỉ thuộc đúng 1 đơn vị hành chính). Đây là
lỗi thuần túy ở tài liệu (không ảnh hưởng `docs/DATABASE-DICTIONARY.md`, vốn đã mô tả đúng FK từ
đầu) nhưng cần sửa trước khi tạo migration để tránh người đọc/AI sau này hiểu nhầm mô hình khi
chỉ tham chiếu ERD.

<a id="adr-045"></a>

## ADR-045 — Company & Company Location Quick Create Contract; `company_locations.administrative_unit_id`/`address_detail` đổi thành nullable

**Quyết định:** Chính thức hóa "tạo nhanh" cho `companies` và `company_locations` — nhân viên chỉ
cần biết tên là tạo được bản ghi, bổ sung dữ liệu sau, không được lưu chuỗi `"Chưa xác định"`
(luôn `NULL` cho dữ liệu chưa biết, UI tự hiển thị nhãn `"Chưa xác định"`). `companies` giữ
nguyên schema hiện có (`name`/`status`/`created_by` bắt buộc; `short_name`, `description`,
`logo_path`, `cover_path`, `industry`, `website` đã nullable từ trước — không thêm cột pháp lý
mới như mã số thuế/trụ sở chính, tránh biến Company thành module quản lý pháp nhân). Đổi
`company_locations.administrative_unit_id` và `company_locations.address_detail` từ **NOT NULL
sang NULLABLE** (khác quyết định ở bản dictionary trước) — bổ sung sau, bắt buộc trước khi Job
gắn location đó được publish (xem ADR-047, điều kiện publish mới). Thêm "Job Draft Contract"
chính thức (ADR-046) mô tả rõ Job nháp được phép thiếu công ty/địa điểm/lương/quyền lợi chưa
hoàn thiện.

**Lý do:** Yêu cầu nghiệp vụ cập nhật xác nhận luồng vận hành thực tế: nhân viên nhận nhu cầu qua
điện thoại/Zalo, phải tạo nhanh Company/Location/Job draft ngay trong lúc nói chuyện, chưa có đủ
địa chỉ hay thông tin pháp lý. Ép `administrative_unit_id`/`address_detail` NOT NULL (quyết định
cũ) chặn đứng chính luồng này — nhân viên phải bịa dữ liệu hoặc không lưu được nháp, cả hai đều
sai. Không lưu chuỗi `"Chưa xác định"` vì đó là dữ liệu giả trong cột định danh (khác `NULL` —
gây sai lệch khi lọc/thống kê theo tỉnh/thành sau này). Không mở rộng `companies` thành hồ sơ
pháp lý đầy đủ vì Phase 1 không cần xác minh pháp nhân sâu (ngoài phạm vi, xem
`docs/PHASE-1-SCOPE.md`).

<a id="adr-046"></a>

## ADR-046 — Job Draft Contract chính thức; chốt `jobs.owner_branch_id` NOT NULL từ lúc tạo (không còn nullable ở draft)

**Quyết định:** Thêm "Job Draft Contract" (`docs/CORE-FLOWS.md` mục 1.0): Job nháp (`status =
draft`) được phép thiếu company đầy đủ, location đầy đủ, lương/quyền lợi chưa chốt, chưa xác
minh nhu cầu — nhưng bắt buộc có `title` (tạm thời hoặc chính thức), `company_id`, `created_by`,
và **`owner_branch_id`**. Đổi `jobs.owner_branch_id` từ **nullable (bắt buộc trước publish)**
sang **NOT NULL ngay từ lúc tạo** — sửa lại phần "nullable ở draft" đã mô tả trong
`docs/DATABASE-DICTIONARY.md`/`docs/ERD.md` bản trước. Staff tạo Job: server tự gán
`owner_branch_id = users.branch_id`, không cho chọn khác. Admin tạo Job: bắt buộc chọn
`owner_branch_id` tường minh ngay tại form tạo — không có trạng thái "Job chưa có cơ sở phụ
trách" dưới bất kỳ hình thức nào.

**Lý do:** Yêu cầu nghiệp vụ (Job Owner Branch Contract) khẳng định rõ: Staff luôn tự động có cơ
sở của chính mình (không cần chờ), Admin "bắt buộc chọn branch hợp lệ" ngay khi tạo — không mô tả
trạng thái trung gian "chưa chọn cơ sở". Để cột này nullable tạo khoảng trống cho một luồng tạo
Job không có cơ sở phụ trách, trong khi mọi Job Phase 1 đều do một Staff/Admin thuộc một cơ sở cụ
thể tạo ra qua form HR — không có "hệ thống tạo Job tự động" cần giá trị mặc định null. Việc
thiếu company/location đầy đủ (được phép ở draft) là loại thiếu sót khác — thông tin cần xác minh
thêm với khách hàng — không giống cơ sở phụ trách nội bộ (luôn biết ngay tại thời điểm nhân viên
đăng nhập tạo Job).

<a id="adr-047"></a>

## ADR-047 — Job Publish Contract: thêm điều kiện xác minh còn tuyển và điều kiện địa điểm đủ rõ, kèm admin override có kiểm soát

**Status:** Partially superseded by ADR-058, ADR-060 — điều kiện 2 dưới đây ("có ít nhất một
`still_open` trong lịch sử") bị thay bằng "bản ghi mới nhất phải là `still_open`" (ADR-058); toàn
bộ danh sách điều kiện publish được đánh số lại và mở rộng thành 22 điều ở ADR-060
(`docs/CORE-FLOWS.md` mục 1.2). Điều kiện 1 (địa điểm đủ rõ) và cơ chế admin override (bắt buộc
`job_status_histories.reason`) vẫn là quyết định hiện hành, không đổi.

**Quyết định:** Bổ sung 2 điều kiện vào danh sách publish ở `docs/CORE-FLOWS.md` mục 1.2 (điều
kiện publish, giữ nguyên số mục 1.2, chỉ thêm điều kiện con):

1. **Địa điểm đủ rõ**: location `is_primary=true` của Job phải có `administrative_unit_id` khác
   null, hoặc (nếu chưa có) `address_detail` khác null/không rỗng — không publish được nếu
   location chỉ có tên chung chung, chưa có tỉnh/thành lẫn mô tả địa chỉ.
2. **Đã xác minh nhu cầu tuyển**: có ít nhất một `job_verifications.result = still_open` trong
   lịch sử của Job trước khi publish lần đầu (`draft → published`). Nếu chưa có: Staff bị từ
   chối; **Admin** được publish ngoại lệ nhưng bắt buộc nhập `job_status_histories.reason` (ghi
   rõ lý do bỏ qua xác minh) — tái dùng cột `reason` có sẵn, không thêm cột mới. `paused →
   published` áp dụng lại toàn bộ điều kiện publish (đã có từ ADR-033), gồm cả điều kiện này,
   nhưng vì Job đã từng publish nên luôn đã có tối thiểu 1 verification hợp lệ trong lịch sử —
   không cần xác minh lại bắt buộc, trừ khi Admin/Staff muốn xác minh lại vì thời gian pause dài
   (khuyến khích qua cảnh báo scheduler, không bắt buộc).

**Lý do:** Yêu cầu nghiệp vụ (Job Verification Contract, Job Publish Contract) khẳng định "Lần
publish đầu tiên phải có verification hợp lệ: result = still_open" và liệt kê "Nhu cầu tuyển đã
được xác minh thủ công" là điều kiện publish bắt buộc — bản đặc tả trước có bảng
`job_verifications` nhưng chưa từng gắn nó vào điều kiện publish, khiến việc xác minh trở thành
tùy chọn không ai bị chặn nếu bỏ qua. Điều kiện địa điểm đủ rõ đáp ứng yêu cầu "ứng viên phải xác
định được khu vực làm việc" trước khi Job hiển thị công khai — trước đó publish chỉ kiểm tra "có
đúng 1 primary location" mà không kiểm tra location đó có đủ thông tin định vị hay không, mâu
thuẫn trực tiếp với việc `company_locations.administrative_unit_id`/`address_detail` nay đã cho
phép null (ADR-045). Cho phép Admin override có lý do (không phải cấm tuyệt đối) phản ánh thực tế
vận hành: đôi khi cần publish gấp trước khi hoàn tất xác minh, nhưng phải có dấu vết ai chịu
trách nhiệm quyết định đó.

<a id="adr-048"></a>

## ADR-048 — Job Verification: tách `last_checked_at` (mọi lần xác nhận) khỏi `last_verified_at` (chỉ khi `still_open`)

**Quyết định:** Thêm cột `jobs.last_checked_at` (timestamp, nullable). Sửa lại ngữ nghĩa ở
`docs/CORE-FLOWS.md` mục 1.3 (Job Verification Scheduler — giữ nguyên số mục, chỉ sửa nội dung):
mỗi lần tạo `job_verifications` (`hr.jobs.verify`), **luôn** cập nhật `jobs.last_checked_at =
now()`, bất kể `result`. Chỉ khi `result = still_open` mới cập nhật thêm
`jobs.last_verified_at = now()`. `needs_review` cập nhật `last_checked_at` nhưng **không** đụng
`last_verified_at` — không được xem là xác minh hợp lệ. Cảnh báo scheduler (7/14 ngày) tính từ
`jobs.last_verified_at`, không phải `last_checked_at`.

**Lý do:** Bản đặc tả trước chỉ có 1 cột `last_verified_at` và mô tả "mỗi lần xác nhận lại (bất
kể result) đều cập nhật last_verified_at" — điều này làm sai lệch ý nghĩa của "đã xác minh": một
lần xác nhận có `result = needs_review` (nhân viên gọi nhưng chưa rõ kết quả, cần xem lại) vẫn bị
tính như đã xác minh hợp lệ, khiến cảnh báo "lâu chưa xác minh" tắt nhầm dù nhu cầu tuyển thực ra
chưa được xác nhận chắc chắn. Tách 2 cột theo đúng yêu cầu nghiệp vụ (Job Verification Contract)
giữ được cả hai tín hiệu: "lần cuối có ai đó thao tác xác nhận" (`last_checked_at`, phục vụ theo
dõi hoạt động) và "lần cuối xác nhận chắc chắn còn tuyển" (`last_verified_at`, phục vụ cảnh báo
và điều kiện publish ở ADR-047).

<a id="adr-052"></a>

## ADR-052 — Validation tỉnh/thành khớp với khu công nghiệp (`company_locations` ↔ `industrial_parks`)

**Quyết định:** Khi `company_locations.industrial_park_id` khác `null`:
`company_locations.administrative_unit_id` **bắt buộc khác `null`** và phải **bằng đúng**
`industrial_parks.administrative_unit_id` của KCN được chọn — không cho lưu Location tại tỉnh A
nhưng gắn KCN thuộc tỉnh B. Khi `industrial_park_id = null`, `administrative_unit_id` vẫn tuân
theo contract Quick Create hiện có (nullable, ADR-045), không bị ảnh hưởng bởi quyết định này.

- **Tầng UI**: khi người dùng chọn KCN, tự động điền `administrative_unit_id` bằng tỉnh của KCN
  đó (giảm khả năng nhập sai) — chỉ là gợi ý UX, không thay thế kiểm tra backend.
- **Tầng Backend (bắt buộc)**: Form Request/Service của
  `hr.company-locations.store`/`hr.company-locations.update` kiểm tra lại đúng quy tắc trên,
  từ chối nếu không khớp — **không** phải DB constraint (MariaDB không kiểm tra cross-column
  bằng nhau giữa 2 bảng khác nhau qua FK thông thường; có thể cân nhắc `CHECK`/trigger sau này
  nhưng Phase 1 dùng validation tầng Service là đủ, nhất quán với các bất biến cross-table khác
  đã liệt kê ở bảng "DB bảo vệ vs Service bảo vệ").

**Lý do:** Cả `company_locations` và `industrial_parks` đều có `administrative_unit_id` riêng
(không có ràng buộc nào buộc 2 giá trị này khớp nhau ở đặc tả trước) — một Location có thể vô
tình bị gán nhầm KCN của tỉnh khác (lỗi nhập liệu, hoặc dropdown KCN không lọc theo tỉnh đã
chọn), dẫn tới hiển thị sai khu vực làm việc cho ứng viên và lọc sai theo tỉnh/KCN ở Luồng 2.

<a id="adr-053"></a>

## ADR-053 — Company Location/Contact: tách quyền xóa/khôi phục về riêng Admin (sửa lỗ hổng Route Map)

**Quyết định:** `hr.company-locations.destroy`, `hr.company-contacts.destroy` đổi quyền từ
`staff/admin` thành **`admin`** — khớp đúng quy tắc đã có ở `docs/CORE-FLOWS.md` mục 0.2
("Staff và Admin đều tạo/sửa được... chỉ soft delete/restore dành riêng cho Admin") nhưng
`docs/ROUTE-MAP.md` bản trước lại gộp `PUT/DELETE` chung một dòng `staff/admin`, mâu thuẫn trực
tiếp với quy tắc đó. Thêm 2 route khôi phục còn thiếu:
`hr.company-locations.restore`/`hr.company-contacts.restore` (admin), vì cả 2 bảng đều
`SoftDeletes` (`docs/DATABASE-DICTIONARY.md` mục 9.7, 9.8) nhưng trước đó chỉ `companies` có
route restore.

**Lý do:** Đây là mâu thuẫn thật giữa 2 tài liệu (CORE-FLOWS nói admin-only, ROUTE-MAP cho phép
staff) — phải sửa một bên cho khớp; chọn sửa ROUTE-MAP theo đúng quy tắc CORE-FLOWS đã chốt vì
lý do nghiệp vụ (soft delete/restore dữ liệu dùng chung nhiều cơ sở là thao tác ảnh hưởng rộng,
cần vai trò cao hơn) vẫn còn nguyên giá trị. Thiếu route restore cho location/contact là một lỗ
hổng chức năng (xóa nhầm nhưng không có cách khôi phục qua hệ thống, phải can thiệp DB tay).

<a id="adr-054"></a>

## ADR-054 — Job Branch Transfer: chỉ cho phép khi `draft`/`paused`, cấm tuyệt đối khi `closed` hoặc đã xóa

**Quyết định:** Sửa lại điều kiện 1 của Job Branch Contract (`docs/CORE-FLOWS.md` mục 1.1):
`ChangeJobBranchAction` chỉ chấp nhận khi `jobs.status` ∈ {`draft`, `paused`} **và** `jobs`
chưa `deleted_at` — không còn diễn đạt "không `published`" (cách nói phủ định trước đây vô tình
vẫn cho phép `closed`, vì `closed` cũng "không published"). `docs/ROUTE-MAP.md` bản trước ghi
sai thành "Job phải `paused` hoặc `draft`/`closed`" (liệt kê nhầm `closed` vào nhóm được phép) —
sửa lại đúng thành chỉ `draft`/`paused`.

**Lý do:** Job `closed` là trạng thái cuối, đại diện một đợt tuyển đã kết thúc — đổi cơ sở phụ
trách của một đợt tuyển đã đóng không có ý nghĩa nghiệp vụ (không còn ai xử lý tiếp) và có thể
làm sai lệch báo cáo lịch sử theo cơ sở của đợt tuyển đó. Job đã `deleted_at` (soft-deleted)
không nên bị thao tác thêm dưới bất kỳ hình thức nào ngoài khôi phục. Yêu cầu vẫn giữ nguyên:
bắt buộc lý do, transaction, `lockForUpdate`, ghi `job_branch_histories` với `changed_by`/
`created_at` đầy đủ (đã đúng từ ADR-038, không đổi).

<a id="adr-058"></a>

## ADR-058 — Job Verification: publish chỉ dựa vào bản ghi mới nhất, thêm `job_verification_valid_days`

**Status:** Sửa lỗi ngữ nghĩa trong ADR-047/mục 1.2 điều kiện 11 (không phải mở rộng phạm vi).

**Quyết định:** Điều kiện publish "đã xác minh nhu cầu tuyển" **không** còn được thỏa mãn chỉ vì
*từng có* một bản ghi `job_verifications.result = still_open` trong lịch sử. Điều kiện đúng:
**bản ghi `job_verifications` mới nhất của Job** (sắp xếp theo `verified_at` DESC, `id` DESC làm
tie-break) phải có `result = still_open`. Một bản ghi `still_open` cũ bị vô hiệu hóa bởi bất kỳ
bản ghi mới hơn nào có `result` khác `still_open` (`needs_review`/`paused`/`closed`) — Job đó
không được publish/mở lại cho tới khi có một bản ghi `still_open` **mới hơn** bản ghi làm mất
hiệu lực đó.

Thêm cấu hình **độ mới (freshness)** của verification hợp lệ: `settings.job_verification_valid_days`
— nếu bật (giá trị số ngày), bản ghi `still_open` mới nhất còn phải nằm trong vòng
`job_verification_valid_days` ngày gần nhất tính từ `now()` mới được coi là hợp lệ cho publish;
nếu quá hạn, xử lý giống như chưa có `still_open` mới nhất (Staff bị từ chối publish, Admin
override có lý do). **Giá trị số ngày cụ thể chưa được công ty xác nhận — [CẦN CHỐT VỚI CÔNG TY]**
— đây là **go-live decision**, không phải migration blocker, vì schema (`settings` key/value,
`varchar` value ép kiểu qua `type`) đã đủ để lưu bất kỳ giá trị nào công ty chọn sau này, kể cả
"không áp dụng" (`NULL`/rỗng = tắt kiểm tra độ mới, chỉ dùng "mới nhất = still_open").

**Lý do:** Đặc tả trước (ADR-047, `docs/CORE-FLOWS.md` mục 1.2 điều kiện 11, và bảng "Ràng buộc"
ở `docs/DATABASE-DICTIONARY.md`) dùng cụm "có ít nhất một `still_open` trong lịch sử" — điều này
cho phép một Job từng được xác nhận còn tuyển cách đây rất lâu, sau đó bị xác nhận lại là
`needs_review`/`paused`/`closed` (nhu cầu đã thay đổi), vẫn publish được vì bản ghi `still_open`
cũ chưa bao giờ bị "xóa" khỏi lịch sử — đây là lỗi logic nghiệp vụ thật, không phải chi tiết
diễn đạt. Chốt lại theo "bản ghi mới nhất" phản ánh đúng ý nghĩa "hiện tại còn tuyển hay không".
Thêm cấu hình độ mới (thay vì hardcode số ngày trong migration/code) vì đây là chính sách vận
hành có thể thay đổi theo thỏa thuận với khách hàng, không phải hằng số kỹ thuật.

<a id="adr-059"></a>

## ADR-059 — Ma trận chính thức Job Status × Job Verification Result

**Quyết định:** Bổ sung ma trận đầy đủ (theo `jobs.status` **tại thời điểm ghi verification** ×
`job_verifications.result`) vào `docs/CORE-FLOWS.md` mục 1.3 — xem bảng đầy đủ tại đó. Tóm tắt
quy tắc mới (khác với đặc tả trước, vốn không phân biệt theo `jobs.status` hiện tại):

- Job `draft`: chấp nhận `still_open`/`needs_review` (chỉ ghi verification, không đổi status, vì
  Job chưa từng publish). **Từ chối** `result ∈ {paused, closed}` khi Job đang `draft` — trả lỗi
  validation rõ ràng ("Job chưa publish, không thể xác nhận tạm dừng/đóng") — một Job chưa từng
  hoạt động công khai không thể "tạm dừng"/"đóng" một hoạt động chưa từng có.
- Job `published`: `still_open` cập nhật mốc, giữ nguyên status; `needs_review` chỉ ghi nhận,
  **không** tự đổi status (auto-pause thuộc Phase 2, ADR-042/049); `paused`/`closed` chuyển status
  tương ứng qua `ChangeJobStatusAction` trong cùng transaction.
- Job `paused`: `still_open` chỉ ghi nhận, **không** tự publish lại (mở lại vẫn luôn là hành động
  tường minh riêng qua `hr.jobs.publish`, không phải hệ quả tự động của việc verify); `needs_review`
  giữ nguyên `paused`; `paused` (verify lại xác nhận vẫn tạm dừng) chỉ ghi bản ghi
  `job_verifications`, **không** tạo `job_status_histories` giả (vì `status` không đổi — tránh
  lịch sử "chuyển từ paused sang paused"); `closed` chuyển `paused → closed`.
- Job `closed`: **không** chấp nhận verification mới qua route Staff thông thường
  (`hr.jobs.verify` trả `403`/`422` khi Job `closed`) — Job đã đóng là một đợt tuyển đã kết thúc,
  không có ý nghĩa "xác nhận còn tuyển" cho một đợt đã đóng. Nếu công ty thực sự cần ghi nhận xác
  minh trên Job đã đóng vì lý do nghiệp vụ cụ thể (ví dụ đối soát), đó là một action quản trị
  riêng ngoài luồng verify thông thường — Phase 1 **không xây** action này (chưa có nhu cầu xác
  nhận). Việc từ chối này không làm Job tự hoạt động lại dưới bất kỳ hình thức nào.

**Lý do:** Đặc tả trước (mục 1.3 bản cũ) chỉ liệt kê hệ quả theo `result`, không phân biệt Job
đang ở status nào khi verify — dẫn tới các tình huống vô nghĩa không bị chặn: verify "paused" một
Job `draft` chưa từng publish, verify liên tục trên Job đã `closed` tạo lịch sử vô ích, hoặc ghi
`job_status_histories` "paused→paused" khi verify lại xác nhận vẫn tạm dừng. Ma trận tường minh
loại bỏ khoảng trống để lập trình viên/AI tự suy luận khi coding (đúng yêu cầu "không được suy
luận khi coding").

<a id="adr-060"></a>

## ADR-060 — Job Publish Predicate chính thức (22 điều kiện) + `jobs.job_description` đổi NULLABLE

**Status:** Sửa lỗi đồng bộ giữa `docs/DATABASE-DICTIONARY.md` và Job Draft Contract (ADR-046);
thay thế câu chung chung "các trường bắt buộc theo nghiệp vụ đã đầy đủ theo Form Request" ở mục
1.2 điều kiện 9 (bản cũ).

**Quyết định:**

1. **`jobs.job_description` đổi từ NOT NULL sang NULLABLE** (default `null`) — `docs/DATABASE-DICTIONARY.md`
   mục 9.9 trước đây khai `job_description` NOT NULL, mâu thuẫn trực tiếp với Job Draft Contract
   (mục 1.0, ADR-046) vốn cho phép Job draft chưa có mô tả công việc. `requirements`/`benefits`
   đã nullable sẵn — không đổi.
2. **Publish chỉ được thực hiện khi cả 22 điều kiện sau đều đúng** (thay thế danh sách 11 điều ở
   mục 1.2 bản cũ bằng danh sách đầy đủ hơn, đánh số lại — không đổi ý nghĩa các điều đã có, chỉ
   làm rõ và bổ sung điều còn thiếu):
   1. Job tồn tại. 2. Job chưa `deleted_at`. 3. `jobs.status` ∈ {`draft`,`paused`} (trạng thái cho
   phép publish). 4. `companies.status = active`. 5. `companies` chưa `deleted_at`. 6.
   `owner_branch_id` khác null (luôn đúng, NOT NULL từ ADR-046). 7. `branches.status = active`.
   8. `branches` chưa `deleted_at`. 9. Branch có `phone` hoặc `zalo` khác rỗng. 10. `jobs.title`
   khác rỗng. 11. `jobs.job_description` khác rỗng (không chỉ khác NULL — chuỗi toàn khoảng
   trắng cũng bị coi là rỗng). 12. `jobs.requirements` khác rỗng (cùng quy tắc). 13.
   `jobs.benefits` khác rỗng (cùng quy tắc) — **mới**, trước đây không được kiểm tra ở publish dù
   Job Draft Contract ngụ ý các trường này phải "chốt" trước khi xuất bản. 14. Có đúng 1
   `job_locations.is_primary = true`. 15. Location đó thuộc đúng `jobs.company_id`. 16. Location
   đó `status = active`, chưa `deleted_at`. 17. Location đó đủ rõ (`administrative_unit_id` khác
   null, hoặc `address_detail` khác null/không rỗng). 18. Nếu location có `industrial_park_id`:
   KCN đó `is_active = true` và `administrative_unit_id` khớp (đã đảm bảo tại thời điểm
   lưu Location — ADR-052 — điều kiện này chỉ tái xác nhận, không kiểm tra lại KCN có bị vô hiệu
   hóa sau đó). 19. Salary Predicate đúng (định nghĩa dưới). 20. Shift Predicate đúng (định nghĩa
   dưới). 21. Verification mới nhất `result = still_open` và (nếu freshness bật) còn trong hạn
   `job_verification_valid_days` — Staff bị từ chối nếu sai; Admin override được nhưng bắt buộc
   `job_status_histories.reason` (ADR-058). 22. Người thao tác có quyền publish Job này (Staff
   đúng cơ sở hoặc Admin — mục 1.1).
3. **Salary Predicate chính thức** — lương được coi là hợp lệ khi **ít nhất một** trong 4 điều
   kiện sau đúng (không yêu cầu tất cả, các điều kiện độc lập, không loại trừ nhau):
   - `salary_min` khác null, **hoặc** `salary_max` khác null; **hoặc**
   - `salary_base` khác null; **hoặc**
   - `salary_period = negotiable`; **hoặc**
   - `salary_description` khác null và khác chuỗi rỗng sau khi trim.
4. **Shift Predicate chính thức** — ca làm được coi là hợp lệ khi: **có ít nhất 1 bản ghi
   `job_work_shifts`** gắn với Job (`COUNT(job_work_shifts WHERE job_id = ?) >= 1`). Đây là điều
   kiện duy nhất (single contract) — Phase 1 **không** thêm trường mô tả ca làm tự do cấp Job làm
   đường thay thế song song, vì schema hiện tại không có cột như vậy ở `jobs` và thêm mới sẽ vượt
   phạm vi tối thiểu cần thiết (mô tả riêng từng ca đã có ở `job_work_shifts.description`, đủ
   dùng).

**Lý do:** "Các trường bắt buộc theo nghiệp vụ (lương, ca...) đã đầy đủ theo Form Request" là câu
chung chung không đủ cụ thể để chuyển thành migration/validation/test — vi phạm nguyên tắc "mỗi
quy tắc phải đủ cụ thể để lập trình viên có thể chuyển thành domain action/test" của vòng rà soát
này. `job_description` NOT NULL ở dictionary trong khi Job Draft Contract (đã chốt từ ADR-046)
cho phép draft thiếu mô tả là một lỗi đồng bộ thật — Job draft sẽ không insert được nếu giữ NOT
NULL, chặn đứng chính luồng "tạo nhanh Job nháp" mà ADR-045/046 đã thiết kế. `requirements`/
`benefits` chưa từng là điều kiện publish tường minh dù Job Draft Contract mô tả chúng là "chưa
chốt ở draft, phải hoàn thiện trước publish" — thêm 2 điều kiện này đóng đúng khoảng trống đó.

<a id="adr-064"></a>

## ADR-064 — Primary field semantics: bỏ `company_locations.is_primary`, khóa DB `candidate_contacts.is_primary`, chốt `company_contacts.is_primary`

**Quyết định:**

1. **`job_locations.is_primary`** — giữ nguyên, không đổi (đã đúng: primary cấp Job, phục vụ
   hiển thị/tìm kiếm, bảo vệ bằng generated-column-unique có sẵn).
2. **`candidate_contacts.is_primary`** — thêm cột generated `primary_flag_key` (varchar(70),
   `IF(is_primary, CONCAT(candidate_id,'-',type), NULL)` STORED, **UNIQUE**) — khóa ở tầng DB quy
   tắc "tối đa 1 primary/type/candidate" (trước đây chỉ enforce ở tầng ứng dụng, không có DB
   constraint, dù bảng "Ràng buộc" ở dictionary yêu cầu mọi bất biến quan trọng phải có DB
   constraint). Đổi primary contact đi qua Action có `lockForUpdate` trên các `candidate_contacts`
   cùng `(candidate_id, type)` trong 1 transaction (bỏ `is_primary` cũ = false, đặt bản ghi mới =
   true) — 2 request đồng thời đổi primary cho cùng candidate+type: request thứ hai đợi lock, áp
   dụng sau, kết quả cuối cùng chỉ 1 bản ghi `is_primary=true` (không có khoảng trống race).
3. **`company_contacts.is_primary`** — chốt: **một Company tối đa 1 primary contact đang
   `status=active`** — enforce ở tầng Service khi `store`/`update` (không thêm DB generated-unique
   như candidate_contacts, vì đây là thao tác CRUD nội bộ ít đồng thời hơn nhiều so với form ứng
   tuyển public — rủi ro race thấp, validation tầng Service đủ). Đặt `is_primary=true` cho 1
   contact mới tự động bỏ `is_primary` của contact `active` khác đang là primary (cùng company),
   trong 1 transaction.
4. **`company_locations.is_primary`** — **loại bỏ khỏi schema Phase 1** — rà soát toàn bộ
   `docs/CORE-FLOWS.md`, `docs/ROUTE-MAP.md`, `docs/ACCEPTANCE-CRITERIA.md` xác nhận **không có
   use case Phase 1 nào** đọc/ghi cột này (điều kiện publish, tìm kiếm, hiển thị đều dùng
   `job_locations.is_primary`, không dùng cột này) — giữ lại chỉ gây nhầm lẫn với
   `job_locations.is_primary` (2 khái niệm "primary" ở 2 tầng khác nhau: company vs job) đúng như
   rủi ro mà nguyên tắc "primary field semantics" cảnh báo.

**Lý do:** Xem phần "Quyết định" — đây là rà soát khép kín 4 cột `is_primary` trong schema để mỗi
cột có đúng 1 ý nghĩa rõ ràng, tránh tình trạng "giữ cột phòng khi sau này dùng" (vi phạm nguyên
tắc không tạo cột dự phòng, `.claude/rules/architecture.md`).

<a id="adr-068"></a>

## ADR-068 — Soft delete/restore: rà soát toàn bộ bảng có `deleted_at`, thêm `hr.branches.restore`

**Quyết định:** Rà soát 7 bảng có `deleted_at` (`candidates`, `companies`, `company_locations`,
`company_contacts`, `branches`, `jobs`, `application_notes`) theo đúng 1 trong 3 hướng dưới đây
cho từng bảng — không được vừa có `deleted_at` + có route destroy vừa không có cách khôi phục nào
được ghi rõ:

| Bảng | `deleted_at` | Route destroy | Route restore | Kết luận |
|---|---|---|---|---|
| `companies` | có | `hr.companies.destroy` | `hr.companies.restore` (có sẵn) | Đủ — không đổi |
| `company_locations` | có | `hr.company-locations.destroy` | `hr.company-locations.restore` (ADR-053) | Đủ — không đổi |
| `company_contacts` | có | `hr.company-contacts.destroy` | `hr.company-contacts.restore` (ADR-053) | Đủ — không đổi |
| `jobs` | có | `hr.jobs.destroy` | `hr.jobs.restore` (có sẵn) | Đủ — không đổi |
| `branches` | có | `hr.branches.destroy` | **thiếu** | **Thêm `POST /hr/co-so/{branch}/khoi-phuc` → `hr.branches.restore`, admin** |
| `candidates` | có | **không có route destroy nào ở Phase 1** | không có | Quyết định: `candidates.deleted_at` **chỉ dùng cho can thiệp Admin/DB thủ công** ngoài HTTP route (ví dụ dữ liệu rác/spam nghiêm trọng cần gỡ khỏi danh sách nhưng chưa đủ căn cứ anonymize) — Phase 1 **không** xây route soft-delete Candidate qua UI; cột vẫn giữ vì Merge/Reopen contract đã kiểm tra điều kiện "candidate chưa `deleted_at`" như lớp phòng vệ trước can thiệp DB thủ công đó |
| `application_notes` | có | `hr.applications.notes.destroy` | không có | Quyết định: Note là ghi chú nội bộ (không phải dữ liệu tuyển dụng cốt lõi) — xóa nhầm cần Admin can thiệp DB trực tiếp, **không** xây route restore qua UI ở Phase 1 (rủi ro thấp, tần suất thấp, không đáng thêm route cho một thao tác hiếm) |

**Lý do:** `.claude/rules/docs-governance.md`/nguyên tắc rà soát yêu cầu mọi bảng SoftDeletes phải
có 1 trong 3 kết luận rõ ràng — trạng thái "có `deleted_at` + có destroy + không có gì về khôi
phục" (đúng tình trạng của `branches` trước bản rà soát này) là một lỗ hổng chức năng thật (xóa
nhầm 1 cơ sở nội bộ không có cách khôi phục qua hệ thống, phải can thiệp DB tay) — cùng loại lỗi
đã từng sửa cho `company_locations`/`company_contacts` ở ADR-053. `candidates`/`application_notes`
không có route destroy nào cả (khác `branches`, vốn có destroy nhưng thiếu restore) nên xử lý
bằng cách ghi rõ quyết định "chỉ can thiệp Admin/DB thủ công" thay vì thêm route mới không có nhu
cầu nghiệp vụ xác nhận — tránh mở rộng phạm vi Phase 1 không cần thiết.

<a id="adr-074"></a>

## ADR-074 — Final publish consistency: Salary modes, Admin verification override và Company Contact ownership

**Quyết định (supersede phần Salary Predicate của ADR-060):** (1) `PUB-SALARY` có đúng hai mode loại trừ nhau: `negotiable` yêu cầu mọi cột
lương số `NULL`; mode còn lại yêu cầu `salary_period != negotiable` và có ít nhất một số lương
dương hoặc `salary_description` thực. (2) `PUB-VERIFY` đạt khi latest verification là
`still_open` còn hạn **hoặc** actor Admin có override reason không rỗng; Staff không override,
reason phải ghi vào status history. (3) `jobs.company_contact_id`, nếu có, phải thuộc đúng
`jobs.company_id`, active, chưa soft-delete; public còn cần `is_public=true`.

**Lý do:** Loại bỏ mâu thuẫn “4 điều kiện nhưng liệt kê 5”, ngăn lưu `negotiable` cùng lương số,
làm câu “22 điều kiện đều đúng” tương thích với Admin override và chặn chọn contact Company khác.
