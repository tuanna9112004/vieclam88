# Quyết định kiến trúc (ADR)

Ghi lại các quyết định kỹ thuật quan trọng và lý do. Không sửa lại quyết định cũ — nếu thay
đổi, thêm bản ghi mới bên dưới và ghi rõ bản ghi nào bị thay thế.

## ADR-001 — Laravel monolith, một codebase một database

**Quyết định:** Một codebase Laravel + một database MariaDB dùng chung cho cả website công
khai và khu vực HR. Không tách microservice.

**Lý do:** Quy mô nghiệp vụ (1 công ty cung ứng nhân sự) không cần độ phức tạp của
microservice. Route, controller, view, middleware tách riêng theo khu vực trong cùng
codebase là đủ.

## ADR-002 — HR dùng path `/hr`, không dùng subdomain

**Quyết định:** Khu vực HR truy cập qua `https://tencongty.vn/hr` (production) và
`http://localhost/vieclam88/public/hr` (local). Không dùng `hr.tencongty.vn` trong Phase 1.

**Lý do:** Subdomain cần cấu hình DNS/vhost riêng, không cần thiết cho quy mô hiện tại. Path
đơn giản hơn khi deploy, không cần SSL certificate riêng cho subdomain.

## ADR-003 — MariaDB, không dùng SQLite làm database chính

**Quyết định:** MariaDB cho cả local và production.

**Lý do:** SQLite không phù hợp cho concurrent write của nhiều nhân viên HR thao tác đồng
thời, và không khớp môi trường production (VPS chạy MariaDB/MySQL thực tế).

## ADR-004 — PHP 8.4.x, Laravel 13.x, khóa major version

**Quyết định:** PHP 8.4.x, Laravel 13.x. Khóa major version Laravel trong `composer.json`
(`"laravel/framework": "^13.0"`) ngay khi khởi tạo project.

**Lý do:** Production chạy VPS riêng, không bị giới hạn phiên bản như shared hosting. Khóa
major version tránh nâng cấp đột ngột phá vỡ code khi chạy `composer update`.

## ADR-005 — Không dùng phone number làm định danh duy nhất của candidate

**Quyết định:** Tách `candidates` (hồ sơ nghiệp vụ) khỏi `candidate_contacts` (số điện
thoại/email/Zalo, nhiều bản ghi per candidate). Không unique cứng theo số điện thoại ở cấp
candidate.

**Lý do:** Một người có thể đổi số điện thoại, dùng nhiều số, hoặc nộp hồ sơ bằng số của
người thân. Coi 1 số điện thoại = 1 danh tính duy nhất sẽ gộp nhầm hoặc tách nhầm candidate.
Cần cơ chế phát hiện trùng dựa trên nhiều tín hiệu (số điện thoại + họ tên + ngày sinh), có
kiểm tra thủ công trước khi gộp.

## ADR-006 — `users` là tài khoản đăng nhập, `candidates` là hồ sơ nghiệp vụ

**Status:** Partially superseded by ADR-028 — phần "candidate tự tạo tài khoản,
`candidates.user_id` trỏ `users.id`" không còn trong Phase 1 (dời Phase 2). Phần "guest không
cần `users`; staff/admin có `users` nhưng không có `candidates`" vẫn là quyết định hiện hành.

**Quyết định:** Guest ứng tuyển tạo `candidates` không cần `users`. Staff/admin có `users`
nhưng không có `candidates`. Khi candidate tự tạo tài khoản, `candidates.user_id` trỏ tới
`users.id`.

**Lý do:** Guest chiếm phần lớn lượng ứng tuyển (`.claude/rules/roles-business-rules.md`:
không bắt buộc đăng nhập để ứng tuyển).
Bắt buộc tạo `users` cho mọi candidate sẽ phát sinh tài khoản rác không ai đăng nhập, và làm
authentication logic phức tạp không cần thiết cho Phase 1.

## ADR-007 — Application lưu snapshot tại thời điểm ứng tuyển

**Quyết định:** `applications` lưu `submission_snapshot` (JSON) và `job_snapshot` (JSON) tại
thời điểm nộp. Sửa `candidates`/`jobs`/`companies` sau này không làm thay đổi dữ liệu lịch sử
của application đã tồn tại. JSON snapshot chỉ dùng để tra cứu lịch sử, không dùng làm nguồn
lọc/báo cáo chính (lọc/báo cáo dùng cột quan hệ thật: `candidate_id`, `job_id`, `stage`...).

**Lý do:** HR cần biết chính xác thông tin ứng viên đã khai báo và điều kiện công việc tại
thời điểm ứng tuyển, kể cả khi job đã đổi lương hoặc candidate đã sửa hồ sơ sau đó. Dùng JSON
làm nguồn lọc sẽ chậm và không index được — chỉ dùng cho hiển thị lịch sử.

## ADR-008 — Một `job` là một đợt tuyển dụng, không tái sử dụng job cũ

**Quyết định:** Unique constraint `candidate_id + job_id` trên `applications` để chống ứng
tuyển trùng cùng một đợt. Khi tuyển đợt mới cho cùng vị trí, nhân bản job thành bản ghi mới
(`job` ID mới), giữ nguyên job cũ trong lịch sử thay vì mở lại.

**Lý do:** Nếu mở lại job cũ cho đợt tuyển mới, unique constraint sẽ chặn nhầm candidate đã
từng ứng tuyển đợt trước nhưng hợp lệ để ứng tuyển đợt này. Nhân bản job giữ lịch sử rõ ràng
theo từng đợt tuyển.

## ADR-009 — Tách pipeline xử lý khỏi lịch sử liên hệ (contact attempts)

**Quyết định:** `applications.stage` chỉ dùng 8 giá trý pipeline chính (`new`, `contacting`,
`consulted`, `interview_scheduled`, `interviewed`, `waiting_start`, `started`, `closed`).
Kết quả từng cuộc gọi (nghe máy, không nghe máy, sai số...) lưu riêng trong
`application_contact_attempts`, không trộn vào `stage`.

**Lý do:** "Không nghe máy" là kết quả 1 lần gọi, có thể gọi lại nhiều lần trong cùng 1 stage
`contacting`. Trộn 2 khái niệm này vào 1 cột trạng thái làm pipeline không phản ánh đúng tiến
trình xử lý và khó báo cáo (không biết đã gọi bao nhiêu lần).

## ADR-010 — Dùng `administrative_units` phân cấp, không lưu chuỗi tự do

**Quyết định:** Địa điểm hành chính (tỉnh/thành phố/xã/phường) lưu trong bảng
`administrative_units` phân cấp (self-referencing `parent_id`), có `type` phân loại và
`is_active`/`valid_from`/`valid_to` để giữ lịch sử khi địa giới hành chính thay đổi. Không
lưu tên huyện/xã bằng chuỗi tự do trong các bảng khác.

**Lý do:** Việt Nam đang trong giai đoạn sáp nhập đơn vị hành chính (bỏ cấp huyện, sáp nhập
xã/phường) — lưu chuỗi tự do sẽ không nhất quán và không lọc được chính xác. Cấu trúc phân
cấp cho phép lọc theo tỉnh mà không cần liệt kê hết các xã/phường con.

## ADR-011 — Không lặp địa điểm giữa `jobs` và `company_locations`

**Quyết định:** Không lưu `jobs.province_id`/`jobs.industrial_park_id` trực tiếp. Dùng
`company_locations` (địa điểm của công ty) và `job_locations` (bảng trung gian job ↔
location, hỗ trợ nhiều địa điểm, có `is_primary`).

**Lý do:** Lưu địa điểm ở cả `jobs` và `company_locations` sẽ tạo 2 nguồn sự thật có thể lệch
nhau (sửa địa chỉ công ty nhưng quên sửa job). `job_locations` cho phép 1 job tuyển ở nhiều
địa điểm mà không cần nhân bản job.

## ADR-012 — Không tạo `referrer_id` mơ hồ, chưa xây module cộng tác viên

**Status:** Partially superseded by ADR-029 — phần giữ `applications.referral_code` (nullable)
trong Phase 1 bị bỏ; cột này không còn trong schema Phase 1, thêm lại bằng migration khi module
cộng tác viên được duyệt. Phần "không tạo `referrer_id`/bảng `referrers`" vẫn là quyết định
hiện hành.

**Quyết định:** Phase 1 chỉ có `applications.source_id` (khóa ngoại tới
`recruitment_sources`), `source_detail` (text), `referral_code` (nullable, chưa có bảng
tham chiếu). Không tạo `referrer_id` hay bảng `referrers` cho tới khi module cộng tác viên
được duyệt xây dựng.

**Lý do:** Xây `referrer_id` trỏ tới 1 bảng chưa tồn tại tạo foreign key mơ hồ, không rõ
ngữ nghĩa. `recruitment_sources` + `source_detail` đủ để phân loại nguồn (website, Zalo,
nhân viên...) mà không cam kết trước thiết kế cho tính năng ngoài phạm vi Phase 1 (xem
`.claude/rules/scope-standards.md`).

## ADR-013 — CSV cho xuất dữ liệu, ghi log mỗi lần xuất

**Quyết định:** Xuất danh sách ứng viên dùng CSV (không dùng Excel binary). Mỗi lần xuất ghi
1 bản ghi vào `export_logs` (người xuất, thời gian, số dòng, điều kiện lọc). Không lưu file
CSV đã xuất lâu dài trên server.

**Lý do:** CSV đơn giản, không cần thư viện nặng như PhpSpreadsheet cho MVP. Ghi log xuất dữ
liệu là yêu cầu bảo mật tối thiểu khi dữ liệu chứa thông tin cá nhân của ứng viên.

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

## ADR-016 — Application copy `owner_branch_id` từ Job lúc tạo, không suy ra động

**Quyết định:** `applications.owner_branch_id` được copy từ `jobs.owner_branch_id` tại thời
điểm Application được tạo và lưu cố định. Không JOIN động qua `jobs` để suy ra cơ sở phụ
trách. Thay đổi cơ sở cho một Application đã tồn tại phải đi qua bảng lịch sử riêng
`application_branch_histories` bằng một hành động tường minh ("chuyển cơ sở"), không tự động
theo khi Job đổi `owner_branch_id`.

**Lý do:** Nếu suy ra động qua Job, một thay đổi ở Job (vd sửa nhầm cơ sở phụ trách sau khi đã
có hàng trăm hồ sơ) sẽ âm thầm chuyển toàn bộ hồ sơ đang xử lý sang cơ sở khác, làm nhân viên
đang xử lý đột ngột mất quyền truy cập mà không có dấu vết. Snapshot tại thời điểm tạo +
lịch sử chuyển tường minh giữ được nguyên tắc "lịch sử chỉ thêm, không ghi đè" đã có trong
`CLAUDE.md`.

## ADR-017 — Duplicate handling contract: 3 trường hợp tách biệt, không gộp chung logic

**Status:** Partially superseded by ADR-040 — Case B (chỉ trùng số điện thoại) được tách chi
tiết thành 3 trường hợp riêng (thiếu ngày sinh 1 bên / tên khác / ngày sinh mâu thuẫn), và
"tên tương đồng mạnh" ở Case A đổi thành khớp chính xác (đã có ở ADR-026). Case C và cấu trúc
tổng thể (merge conflict) vẫn là quyết định hiện hành.

**Quyết định:** Phát hiện trùng khi nộp hồ sơ tách thành 3 trường hợp xử lý khác nhau (chi
tiết: `docs/CORE-FLOWS.md` mục 6.2–6.3):

- **Case A** (khớp mạnh: số điện thoại chuẩn hóa + tên tương đồng mạnh + ngày sinh khớp nếu có
  cả 2 bên) → tái sử dụng Candidate, không tạo mới.
- **Case B** (chỉ trùng số điện thoại) → tạo Candidate mới bình thường, đánh dấu
  `needs_duplicate_review` để HR xem lại thủ công, không tự động gộp.
- **Case C** (đã có Application cùng Job) → không tạo Application mới, cập nhật
  `last_reapplied_at`, thông báo thân thiện.
- **Merge conflict** (2 Candidate cùng có Application cho cùng Job) → quy tắc chọn Application
  giữ lại (stage tiến xa hơn, hoặc tạo trước nếu bằng stage), Application còn lại chuyển
  `closed`/`duplicate`; toàn bộ Contact Log/Note/Appointment/Status History giữ nguyên gắn với
  `application_id` gốc, không di chuyển.

**Lý do:** ADR-005 đã quyết định không dùng số điện thoại làm định danh duy nhất nhưng chưa mô
tả chính xác phải làm gì ở từng tình huống — để lập trình viên/AI tự đoán sẽ dẫn tới hành vi
không nhất quán (có nơi tự gộp, có nơi chặn cứng). Tách 3 trường hợp + quy tắc merge tường minh
loại bỏ khoảng trống này. Ngưỡng "tên tương đồng mạnh" cụ thể vẫn để **[CẦN CHỐT]** — ADR này
chỉ chốt kiến trúc xử lý, không chốt thuật toán so khớp.

## ADR-018 — Chuyển đổi Lead (`lead_requests`) thành Application dời sang Phase 2

**Status:** Superseded by ADR-021 — không chỉ phần *chuyển đổi* Lead→Application mà **toàn bộ**
`lead_requests` (kể cả tính năng ghi nhận số điện thoại) đã bị bỏ khỏi Phase 1.

**Quyết định:** Phase 1 không triển khai bất kỳ cơ chế nào chuyển `lead_requests` thành
`candidates`/`applications` — bỏ `converted_application_id`, `converted_at`, và giá trị
`converted` khỏi `lead_requests.status` so với đặc tả trước. `lead_requests` Phase 1 chỉ ghi
nhận yêu cầu tư vấn (số điện thoại) để nhân viên gọi lại thủ công, tách biệt hoàn toàn khỏi
pipeline `applications`.

**Lý do:** Yêu cầu nghiệp vụ hiện tại giới hạn Phase 1 chỉ xử lý hồ sơ đến từ form ứng tuyển
trên website; lead từ điện thoại/Zalo và cơ chế chuyển đổi lead → application được xác nhận
thuộc Phase 2. Đây là thay đổi phạm vi so với `docs/ACCEPTANCE-CRITERIA.md`/`docs/ROUTE-MAP.md`
bản trước (đã cập nhật đồng bộ). Tránh xây cơ chế chuyển đổi (và các quy tắc chống chuyển đổi 2
lần, xử lý trùng giữa lead-converted-application và application-thường) khi chưa chắc nghiệp vụ
cần ngay ở Phase 1.

## ADR-019 — "Audit trail theo từng action", không xây "audit log" tổng quát

**Cập nhật danh sách bảng (không đổi quyết định):** `application_assignment_histories` không
còn tồn tại (ADR-021); bổ sung `job_status_histories` (ADR-033) vào danh sách bảng lịch sử
đáp ứng yêu cầu audit trail.

**Quyết định:** Yêu cầu "mọi thao tác phải ghi rõ người thực hiện/thời gian/nội dung" được đáp
ứng bằng các bảng lịch sử append-only chuyên biệt đã có
(`application_status_histories`, `application_contact_attempts`,
`application_branch_histories`, `job_verifications`, `job_status_histories`,
`export_logs`) — mỗi hành động nghiệp vụ tự ghi lại lịch sử của chính nó. Phase 1 **không**
tạo bảng `audit_logs`/`activities` tổng quát ghi mọi thay đổi trên mọi model.

**Lý do:** `.claude/rules/scope-standards.md` đã liệt kê "full audit log" là ngoài phạm vi
Phase 1. Yêu cầu "Action phải ghi Audit log" trong đặc tả luồng nghiệp vụ mới không mâu thuẫn
với giới hạn này — nó được thỏa mãn bởi audit trail per-action đã tồn tại, không cần thêm hạ
tầng audit tổng quát (dual-write, listener toàn cục) mà Phase 1 chưa cần.

## ADR-020 — Staff chỉ xem Application thuộc cơ sở phụ trách (thay vì toàn bộ)

**Status:** Partially superseded by ADR-021 — phần "tự nhận hồ sơ (claim)" ở quyết định này bị
thay thế hoàn toàn (Phase 1 bỏ hẳn khái niệm claim/assign, không chỉ bỏ phân công cứng). Phần
"staff chỉ xem Application thuộc cơ sở mình, admin không giới hạn" vẫn là quyết định hiện hành
— diễn đạt chuẩn: "Staff thuộc đúng cơ sở hoặc Admin" (không dùng "staff/admin cùng cơ sở").

**Quyết định:** Staff chỉ truy cập được `applications` có `owner_branch_id` trùng
`users.branch_id` của mình; truy cập URL của Application thuộc cơ sở khác trả về 403. Admin
không bị giới hạn cơ sở. Đây là thay đổi so với giả định trước đó ("Staff Phase 1 xem toàn bộ
application") trong `.claude/rules/roles-business-rules.md`.

**Lý do:** Khi hệ thống có khái niệm cơ sở nội bộ (ADR-015), để staff xem toàn bộ hồ sơ của mọi
cơ sở sẽ vô hiệu hóa mục đích phân vùng dữ liệu theo cơ sở và có nguy cơ lộ dữ liệu ứng viên
giữa các cơ sở không liên quan. Việc "tự nhận hồ sơ" (claim) trong phạm vi cơ sở của mình vẫn
giữ nguyên, không cần phân công cứng.

## ADR-021 — Bỏ Lead, Assignment và Favorites khỏi phạm vi database Phase 1 (siết phạm vi lần 2)

**Quyết định:** Ba nhóm sau bị loại hoàn toàn khỏi database và route Phase 1, dời sang Phase 2:

- **Lead**: không có `lead_requests`, không có form "yêu cầu tư vấn" trên website
  (`/lien-he/tu-van`), không có trang quản lý lead ở HR (`/hr/yeu-cau-tu-van`). Thay thế phần
  quyết định trước đó ở ADR-018 (vốn chỉ bỏ *chuyển đổi* Lead → Application nhưng vẫn giữ
  `lead_requests` làm nơi ghi nhận số điện thoại) — nay bỏ hẳn cả bảng và tính năng ghi nhận.
- **Assignment**: không có route "Nhận xử lý" (claim) hay "Gán nhân viên" (assign), không có
  bảng `application_assignment_histories`, không có cột `applications.assigned_to`. Thay thế
  phần "tự nhận hồ sơ (claim)" đã mô tả ở ADR-020 — Phase 1 chỉ phân quyền theo cơ sở, không có
  khái niệm phân công ở mức nhân viên dưới bất kỳ hình thức nào (kể cả tự nguyện).
- **Favorites**: không có bảng `favorites`, không có route lưu việc làm.

Bảng Phase 1 giảm từ 28 xuống còn 25 (bỏ `lead_requests`, `application_assignment_histories`,
`favorites`).

**Lý do:** Yêu cầu nghiệp vụ cập nhật xác nhận Phase 1 chỉ cần xử lý hồ sơ đến từ form ứng
tuyển, xử lý theo cơ sở (không theo từng nhân viên), và ưu tiên guest application (không cần
tính năng tài khoản nâng cao như lưu việc làm). Giữ các bảng/route này ở Phase 1 dù không dùng
sẽ tạo cột/bảng dự phòng không ai ghi dữ liệu vào — vi phạm nguyên tắc "không tạo bảng/cột dự
phòng" (`.claude/rules/tech-stack.md`). Trách nhiệm xử lý hồ sơ vẫn được theo dõi đầy đủ qua
audit trail theo từng action đã có (ADR-019): người tạo Contact Log, người đổi trạng thái,
người tạo/hoàn thành Appointment, người thêm Note — không cần cột "người phụ trách" để biết ai
đã làm gì.

## ADR-022 — Idempotency contract: `applications.submission_token`

**Quyết định:** Thêm `applications.submission_token` (string, unique khi có giá trị), sinh 1
lần khi server render form ứng tuyển, gửi kèm khi submit. Đây là cơ chế chống **cùng một lần
gửi form bị lặp** (double-click, F5 sau khi đã submit thành công), tách biệt với unique
`(candidate_id, job_id)` vốn chống **ứng tuyển lại** (lần submit mới, hợp lệ về nghiệp vụ,
nhưng trùng candidate + job).

**Lý do:** Unique `(candidate_id, job_id)` một mình không đủ phân biệt "double-click cùng 1 lần
submit" (nên coi là thành công, trả lại bản ghi đã tạo) với "submit lần 2 độc lập cho cùng job"
(case C, thông báo đã ứng tuyển). Không có token, server không biết 2 request unique-conflict
có phải cùng 1 hành động người dùng hay không — không ảnh hưởng tới tính đúng của constraint,
nhưng ảnh hưởng tới thông báo/trải nghiệm trả về. `submission_token` giải quyết rõ ràng ở tầng
dữ liệu, không đoán qua timing.

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

## ADR-024 — Chốt Application transition matrix mở rộng và Contact Result enum chính thức

**Quyết định:** Application transition matrix liệt kê tường minh mọi transition hợp lệ, gồm
đường `closed` từ từng stage (không chỉ 1 dòng gộp chung như bản trước) và bổ sung `closed →
new` (mở lại có kiểm soát: bắt buộc lý do, không được vi phạm unique `(candidate_id, job_id)`).
Contact Result chốt đúng 11 giá trị: `reached`, `no_answer`, `busy`, `wrong_number`,
`consulted`, `callback_requested`, `interview_agreed`, `candidate_refused`, `unsuitable`,
`message_sent`, `other` — đồng nhất giữa `docs/CORE-FLOWS.md` và
`docs/DATABASE-DICTIONARY.md` (bản trước 2 tài liệu này liệt kê enum khác nhau). Nhóm mở khóa
`contacting → consulted` chốt là {`consulted`, `interview_agreed`}.

**Lý do:** Bản đặc tả trước để "closed → (bất kỳ): không cho phép mở lại — [CẦN CHỐT]" và để
enum Contact Result mô tả không nhất quán giữa 2 tài liệu (tiếng Việt có dấu ở
`docs/CORE-FLOWS.md` cũ, tiếng Anh khác ở dictionary) — đây chính là loại mâu thuẫn tài liệu mà
`.claude/rules/docs-governance.md` yêu cầu tránh. Cho phép mở lại có kiểm soát phản ánh thực tế
vận hành (ứng viên từng bị đóng hồ sơ có thể quay lại sau); ràng buộc chống vi phạm unique khi
mở lại ngăn một lỗi dữ liệu thật (mở lại bản ghi từng bị đóng do trùng sẽ tạo 2 Application
active cho cùng candidate + job).

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

## ADR-026 — Tinh chỉnh duplicate handling contract: khớp tên chính xác, merge do admin chọn thủ công

**Quyết định:** Thay thế 2 điểm mở trong ADR-017:

- Case A (khớp mạnh): tiêu chí "tên tương đồng mạnh" (ngưỡng chưa xác định) đổi thành "tên sau
  chuẩn hóa (bỏ dấu, lowercase, gộp khoảng trắng) khớp **chính xác**" — không dùng thuật toán
  khoảng cách chuỗi hay ngưỡng tương đồng.
- Merge conflict (mục 6.3): Application được giữ lại do **admin chọn thủ công**; hệ thống chỉ
  đề xuất gợi ý theo stage tiến xa hơn, không tự động quyết định.

**Lý do:** Đây là 2 trong số các mục **[CẦN CHỐT]** còn tồn đọng ở vòng đặc tả trước — nay có
quyết định rõ từ yêu cầu nghiệp vụ cập nhật, xóa khỏi danh sách CẦN CHỐT. Khớp tên chính xác dễ
kiểm thử và dự đoán hơn ngưỡng tương đồng mờ; để admin chọn thủ công khi merge tránh rủi ro hệ
thống tự đóng nhầm Application đang có tiến triển thực sự tốt hơn tiêu chí "stage xa hơn" đo
được (vd Application A đang `interview_scheduled` nhưng ứng viên thực chất đã từ chối ngầm,
trong khi Application B ở `contacting` nhưng đang được xử lý tích cực — chỉ người thực sự đọc
lịch sử mới biết chọn đúng).

## ADR-027 — Khung chính sách dữ liệu cá nhân tối thiểu (thời hạn lưu vẫn [CẦN CHỐT])

**Quyết định:** Thêm khung chính sách dữ liệu cá nhân tối thiểu vào `docs/CORE-FLOWS.md` mục 7:
ai được anonymize (đề xuất chỉ admin), anonymize xử lý thế nào với `candidates` (mask định danh,
giữ `id`/quan hệ), Contact Log/Note không tự động bị ảnh hưởng, nội dung chính sách hiển thị qua
`pages` (không cần bảng version riêng). **Không** tự đặt thời hạn lưu dữ liệu cụ thể, và **không**
tự quyết định có anonymize nội dung `submission_snapshot`/`job_snapshot` lịch sử hay không — cả
hai đánh dấu **[CẦN CHỐT]**, chặn Giai đoạn 1 cho tới khi công ty xác nhận.

**Lý do:** Yêu cầu nghiệp vụ cập nhật đòi hỏi có tài liệu chính sách tối thiểu nhưng cấm tự đặt
thời hạn lưu khi công ty chưa xác nhận (`docs/CORE-FLOWS.md` không được tự suy đoán nghiệp vụ,
`CLAUDE.md`). Tách rõ phần có thể quyết định ở mức kiến trúc (ai anonymize, xử lý dữ liệu quan
hệ) khỏi phần bắt buộc phải do công ty quyết định (thời hạn cụ thể, đánh đổi giữa xóa triệt để
và giữ bằng chứng lịch sử) để không chặn toàn bộ tài liệu chỉ vì 1 con số chưa có.

## ADR-028 — Bỏ Candidate Account khỏi schema Phase 1 (`users.role=candidate`, `candidates.user_id`)

**Quyết định:** `users.role` Phase 1 chỉ nhận `staff`/`admin` — bỏ giá trị `candidate`.
`candidates` không có cột `user_id`. Bỏ `users.phone_normalized` (mục đích duy nhất trước đây
là đăng nhập cho candidate). `users.email` chuyển thành bắt buộc (NOT NULL) vì mọi user Phase 1
đều cần đăng nhập `/hr/dang-nhap`. Toàn bộ route Candidate Account (`/dang-ky`, `/dang-nhap` cho
candidate, `/quen-mat-khau`, `/tai-khoan`, `/tai-khoan/da-ung-tuyen`) bỏ khỏi Phase 1.

**Lý do:** Yêu cầu nghiệp vụ cập nhật xác nhận ứng viên Phase 1 luôn là guest, không có tính
năng đăng nhập nào cho ứng viên. Giữ `role=candidate`/`candidates.user_id` trong schema mà
không có route/action nào ghi dữ liệu vào đó tạo ra cột/giá trị enum dự phòng không ai dùng —
vi phạm nguyên tắc "không tạo cột/bảng dự phòng" (`.claude/rules/tech-stack.md`). Khi Phase 2
triển khai Candidate Account, thêm lại bằng migration mới, không giữ trước "phòng khi cần".

## ADR-029 — Bỏ `applications.referral_code` và `actor_type=import` khỏi schema Phase 1

**Quyết định:** `applications` không có cột `referral_code` trong Phase 1.
`application_status_histories.actor_type` chỉ nhận `user`/`system` — bỏ giá trị `import`.

**Lý do:** Cả hai đều là cột/giá trị chuẩn bị trước cho tính năng chưa tồn tại ở Phase 1 (module
cộng tác viên — Phase 2; tính năng import dữ liệu hàng loạt — không nằm trong 6 luồng cốt lõi).
Rà soát schema Phase 1 theo nguyên tắc "không giữ cột dự phòng chỉ vì sau này có thể dùng" —
thêm lại bằng migration mới khi tính năng tương ứng thực sự được duyệt xây dựng.

## ADR-030 — Workflow cycle contract: chống dữ liệu chu kỳ xử lý cũ mở khóa trạng thái mới

**Quyết định:** Thêm `applications.workflow_cycle` (int, bắt đầu = 1, tăng mỗi lần mở lại) và
`workflow_cycle_started_at`. Denormalize `workflow_cycle` sang `application_contact_attempts`,
`application_appointments`, `application_status_histories` tại thời điểm tạo từng bản ghi. Mọi
điều kiện transition cần bằng chứng (Contact Log/Appointment) phải lọc thêm `workflow_cycle =
applications.workflow_cycle` hiện tại — chỉ ở tầng Service, không phải DB constraint.

**Lý do:** Không có ranh giới chu kỳ, dữ liệu Contact Log/Appointment từ lần xử lý trước (đã
đóng) có thể tiếp tục mở khóa transition sau khi Application được mở lại mà không có tương tác
thật nào trong lần xử lý mới — vi phạm trực tiếp nguyên tắc "không dùng dữ liệu cũ để vượt qua
trạng thái mới". Chọn bộ đếm nguyên thay vì so sánh timestamp vì ranh giới rõ ràng, không phụ
thuộc đồng hồ hệ thống hay chỉnh sửa dữ liệu thủ công sau này.

## ADR-031 — Hoàn thiện Reopen Application contract (`closed → new`)

**Quyết định:** Mở lại Application yêu cầu đủ 7 điều kiện (người thực hiện, lý do, candidate còn
hoạt động, không phải đóng do duplicate, job chưa xóa, job còn nhận hồ sơ hoặc admin xác nhận
ngoại lệ, không vi phạm unique) — chi tiết đầy đủ: `docs/CORE-FLOWS.md` mục 5.5. Reset
`close_reason`, `closed_at`, `expected_start_at` về `null`; tăng `workflow_cycle`; ghi
`reopened_at`/`reopened_by`.

**Lý do:** Đặc tả trước chỉ nói "cho phép mở lại có kiểm soát" mà chưa liệt kê điều kiện cụ thể
— để lập trình viên/AI tự đoán sẽ bỏ sót ít nhất 1 trong các trường hợp rủi ro thật (mở lại hồ
sơ duplicate, mở lại hồ sơ của candidate đã anonymize, mở lại khi job đã bị xóa). Reset
`expected_start_at` ngăn dữ liệu ngày dự kiến đi làm cũ vô tình thỏa điều kiện
`interviewed → waiting_start` ở chu kỳ mới — cùng nhóm lỗi với ADR-030.

## ADR-032 — Hoàn thiện validation khi chuyển cơ sở (Transfer Branch)

**Quyết định:** Chuyển cơ sở yêu cầu đủ 7 điều kiện: cơ sở đích tồn tại, `active`, chưa xóa,
khác cơ sở hiện tại, người thực hiện là admin, có lý do, và `lockForUpdate` chống 2 request
đồng thời — chi tiết: `docs/CORE-FLOWS.md` mục 6.1.

**Lý do:** Đặc tả trước chỉ nói "kiểm tra quyền, cập nhật, ghi lịch sử" mà chưa liệt kê điều
kiện đầu vào cụ thể — có thể dẫn tới chuyển sang cơ sở không hợp lệ (inactive/đã xóa) hoặc
"chuyển" sang chính cơ sở hiện tại (tạo lịch sử vô nghĩa). Yêu cầu `lockForUpdate` chống trường
hợp 2 admin cùng chuyển 1 Application gần như đồng thời tới 2 cơ sở khác nhau.

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

## ADR-034 — "Merged family": Candidate nguồn không di chuyển dữ liệu, hiển thị hợp nhất qua truy vấn

**Quyết định:** Khi merge, chỉ Application **không trùng job** của candidate nguồn được đổi
`candidate_id` sang candidate đích. Application trùng job (merge conflict) — cả 2 bản ghi giữ
nguyên `candidate_id` gốc. Lịch sử đầy đủ của 1 "gia đình" candidate đã merge (kể cả merge
nhiều tầng) hiển thị qua truy vấn đệ quy theo `merged_into_candidate_id` ("merged family"), không
qua việc ghi đè `candidate_id` hàng loạt. Chi tiết: `docs/CORE-FLOWS.md` mục 6.3.

**Lý do:** Ghi đè `candidate_id` của Application trùng job sang candidate đích sẽ vi phạm ngay
unique `(candidate_id, job_id)` với Application được giữ (đã có candidate_id đích cho cùng job)
— đây chính là lỗi dữ liệu cần tránh. Giải pháp truy vấn hợp nhất (thay vì di chuyển dữ liệu)
vừa tránh xung đột unique, vừa giữ đúng nguyên tắc "lịch sử chỉ thêm, không ghi đè" — mỗi
Application vẫn phản ánh đúng candidate nào đã tạo ra nó tại thời điểm nộp đơn.

## ADR-035 — Chốt `submission_token` NOT NULL, lưu trực tiếp trên `applications`

**Quyết định:** `applications.submission_token` là `NOT NULL, UNIQUE` (bỏ trạng thái nullable
tạm thời ở vòng đặc tả trước). Không tạo bảng `application_submissions` riêng — token sinh và
tiêu thụ trong đúng 1 request tạo Application, không có kịch bản cần bảng trung gian.

**Lý do:** Phase 1 chỉ có duy nhất 1 luồng tạo Application (form guest, 1 bước, đủ dữ liệu ngay
trong request) — không có trường hợp hợp lệ nào Application được tạo mà thiếu token, nên
nullable chỉ tạo khoảng trống cho phép bỏ sót kiểm tra. Bảng riêng chỉ hợp lý nếu có luồng
"giữ chỗ trước, hoàn tất dữ liệu sau" (vd nhập liệu nhiều bước, import) — chưa tồn tại ở Phase 1
(ADR-029 đã loại bỏ `actor_type=import`), nên không tạo trước.

## ADR-036 — Chốt chính sách dữ liệu cá nhân và duyệt 5 enum đề xuất

**Status:** Superseded by ADR-037 — quyết định dưới đây dựa trên câu trả lời nhanh qua công cụ
hỏi trong phiên làm việc, **không phải bằng chứng xác nhận chính thức từ công ty** cho các mục
ảnh hưởng trực tiếp tới nghĩa vụ pháp lý về dữ liệu cá nhân. Toàn bộ 3 mục dưới đây đã được
chuyển lại thành **[CẦN CHỐT VỚI CÔNG TY]**/**[đề xuất]** ở ADR-037.

**Quyết định (đã bị thay thế):** Công ty xác nhận 3 mục còn mở ở `docs/CORE-FLOWS.md` mục 8
(bản trước):

1. **Thời hạn lưu dữ liệu ứng viên**: không giới hạn theo thời gian. Phase 1 không xây scheduler
   tự động anonymize theo thời hạn cố định — chỉ anonymize khi ứng viên chủ động yêu cầu, hoặc
   admin chủ động thực hiện vì lý do nghiệp vụ cụ thể.
2. **Anonymize snapshot**: giữ nguyên nội dung `submission_snapshot`/`job_snapshot` khi
   candidate được anonymize — không xóa/che dữ liệu bên trong JSON lịch sử. Chỉ dữ liệu "sống"
   (`candidates`, `candidate_contacts`) bị mask.
3. **5 enum đề xuất** (`company_contacts.status`, `jobs.employment_type`, `jobs.close_reason`,
   `pages.status`, `settings.type`): duyệt nguyên trạng theo giá trị đã đề xuất trong
   `docs/DATABASE-DICTIONARY.md` — xóa đánh dấu **[đề xuất]**, coi là giá trị chính thức.

Sau ADR này, `docs/CORE-FLOWS.md` mục 8 (danh sách [CẦN CHỐT]) không còn mục nào đang mở —
Giai đoạn 0 hoàn thành về mặt nghiệp vụ/database, chỉ còn chờ cài môi trường code trước khi
sang Giai đoạn 1.

**Lý do:** Không giới hạn thời gian lưu giữ đơn giản hóa Phase 1 (không cần scheduler, không
cần job định kỳ xử lý anonymize hàng loạt) trong khi vẫn tôn trọng quyền yêu cầu xóa chủ động
của ứng viên — đây là quyền pháp lý cốt lõi, không phụ thuộc chính sách lưu trữ mặc định. Giữ
nguyên snapshot lịch sử vì đây là dữ liệu đã đóng băng tại thời điểm nộp đơn (không phải hồ sơ
đang hoạt động của ứng viên), có giá trị làm bằng chứng quy trình tuyển dụng, và việc anonymize
nội dung JSON lồng nhau phức tạp hơn nhiều so với mask các cột quan hệ thông thường mà lợi ích
tăng thêm không rõ ràng. 5 enum đã có giá trị hợp lý, rủi ro thấp — duyệt ngay để không chặn
Giai đoạn 1 vì các quyết định ít quan trọng.

## ADR-037 — Sửa lại: chuyển chính sách dữ liệu cá nhân và enum về [CẦN CHỐT]/[đề xuất] (thay thế ADR-036)

**Quyết định:** 3 mục ADR-036 từng ghi "đã chốt"/"đã duyệt" nay chuyển lại:

1. **Thời hạn lưu dữ liệu ứng viên**: về **[CẦN CHỐT VỚI CÔNG TY]** — chưa có con số, không tự
   đặt (`docs/CORE-FLOWS.md` mục 7.4).
2. **Anonymize `submission_snapshot`**: về **[CẦN CHỐT VỚI CÔNG TY]**, kèm đề xuất mặc định mới
   (khác đề xuất cũ) — mask/xóa từng trường định danh cụ thể (`full_name`, `phone`,
   `date_of_birth`, `address_detail`...), không còn đề xuất "giữ nguyên toàn bộ" (mục 7.2).
   `job_snapshot` không cần chính sách riêng vì không chứa PII candidate (mục 7.1).
3. **5 enum đề xuất**: quay lại đánh dấu **[đề xuất]** trong `docs/DATABASE-DICTIONARY.md`,
   chưa coi là giá trị chính thức (`docs/CORE-FLOWS.md` mục 8.2).

Bổ sung phần đã quyết định được ở mức kiến trúc (không cần công ty ký duyệt, tách bạch khỏi 2
mục CẦN CHỐT trên): chỉ admin được anonymize; không thể hoàn tác; candidate anonymized bị loại
khỏi tìm kiếm mặc định; Contact Log/Note không tự động xử lý PII (mục 7.3).

**Lý do:** Trả lời nhanh qua công cụ hỏi (AskUserQuestion) trong một phiên làm việc — bao gồm 1
câu trả lời dạng tự do không khớp với các lựa chọn đưa ra ("không xóa") — không đủ để coi là
"công ty đã xác nhận" cho quyết định ảnh hưởng trực tiếp tới nghĩa vụ pháp lý về bảo vệ dữ liệu
cá nhân (Nghị định 13/2023/NĐ-CP và tương đương). `CLAUDE.md`/`docs/CORE-FLOWS.md` đều yêu cầu
không tự kết luận thay công ty cho loại quyết định này. Đồng thời, rà soát lại phát hiện đề
xuất "giữ nguyên toàn bộ `submission_snapshot`" ở ADR-036 có rủi ro thực sự — snapshot chứa
nguyên văn họ tên/SĐT/ngày sinh khiến việc anonymize `candidates` mất ý nghĩa nếu JSON lịch sử
vẫn còn đầy đủ — nên đề xuất mặc định mới (mask từng trường) thay thế, dù vẫn ở trạng thái chờ
duyệt chứ không tự chốt.

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

## ADR-040 — Duplicate Candidate Contract: 4 trường hợp tường minh (thay thế phần liên quan của ADR-017/026)

**Quyết định:** Tách rõ 4 trường hợp so khớp Candidate: (1) khớp chắc chắn (phone + tên khớp +
ngày sinh khớp hoặc cả 2 đều không có) → tái sử dụng; (2) thiếu dữ liệu ngày sinh (chỉ 1 bên
có) → không tái sử dụng, đánh dấu review; (3) trùng phone, tên khác → không tái sử dụng, đánh
dấu review; (4) tên + phone khớp nhưng ngày sinh mâu thuẫn (cả 2 bên đều có, khác nhau) →
không tái sử dụng, đánh dấu review. Case C (đã có Application cùng Job) là trục kiểm tra độc
lập, không thuộc 4 trường hợp trên. Tái khẳng định: không dùng fuzzy matching/Levenshtein/AI.

**Lý do:** ADR-017/026 trước đó gộp trường hợp "thiếu dữ liệu so sánh" (ví dụ 1 bên có ngày
sinh, bên kia không) vào chung case A (tái sử dụng) miễn là "ngày sinh khớp nếu cả 2 bên có" —
cách diễn đạt này thực chất coi thiếu dữ liệu là "không cần kiểm tra", dẫn tới nguy cơ tái sử
dụng nhầm khi dữ liệu không đủ để khẳng định chắc chắn cùng một người. Tách trường hợp 2 riêng
biệt (không tái sử dụng khi thiếu dữ liệu) an toàn hơn — im lặng (thiếu dữ liệu) không nên
được xử lý như một kết quả khớp dương tính. Trường hợp 4 (ngày sinh mâu thuẫn dù tên+phone
khớp) cũng được nêu rõ thành trường hợp riêng vì đây là tín hiệu mâu thuẫn trực tiếp, rủi ro
cao hơn thiếu dữ liệu đơn thuần — cả hai đều không tái sử dụng nhưng đáng được ghi nhận là 2
loại rủi ro khác nhau khi HR/Admin xem lại.

## ADR-041 — Submission Token Lifecycle chính thức: session đa-token, diễn đạt lại quy tắc dùng token

**Quyết định:** `applications.submission_token` giữ NOT NULL/UNIQUE (ADR-035 không đổi). Chốt
quy trình 10 bước đầy đủ (`docs/CORE-FLOWS.md` mục 3): server sinh token gắn với đúng `job_id`
khi mở form; **session lưu được nhiều token cùng lúc** (không phải 1 khóa duy nhất bị ghi đè);
server đối chiếu token với đúng `job_id` khi submit; token đã dùng thì request lặp nhận lại kết
quả cũ; unique conflict rollback rồi đọc lại theo token; không có cột `expires_at` riêng (vòng
đời gắn với session).

**Diễn đạt chính thức mới**: *"Một token chỉ được tạo tối đa một Application, nhưng request lặp
với cùng token được phép nhận lại kết quả Application đã tạo."*

**Lý do:** Đặc tả trước dùng cụm "token chỉ dùng một lần" mà không làm rõ hành vi khi có request
lặp lại (double-click) — dễ hiểu lầm thành "lần lặp bị từ chối lỗi", trong khi hành vi đúng là
"lần lặp nhận lại kết quả thành công cũ". Yêu cầu "session lưu nhiều token" là sửa một giả định
ngầm sai — nếu chỉ lưu 1 token theo 1 khóa cố định, người dùng mở 2 tab cho 2 Job khác nhau sẽ
làm token của tab đầu bị ghi đè, khiến tab đó submit thất bại dù vẫn hợp lệ.

## ADR-042 — Job Verification Scheduler Contract: chỉ cảnh báo ở Phase 1, không tự động pause

**Quyết định:** Settings `job_verification_warning_days=7`, `job_auto_pause_days=14`,
`job_auto_pause_enabled=false` (seed sẵn). Phase 1 chỉ hiển thị cảnh báo tính toán khi render
(không ghi DB); không gửi email; không tự động pause Job dưới bất kỳ điều kiện nào (code path
tắt mặc định, không cần build/test). Job `is_urgent` dùng cùng ngưỡng. **[CẦN CHỐT VỚI CÔNG
TY]**: có bật `job_auto_pause_enabled=true` ở giai đoạn sau — nếu bật, cần bổ sung
`actor_type`/`changed_by` nullable cho `job_status_histories` bằng migration riêng lúc đó.

**Lý do:** Đặc tả trước dùng cụm "có thể pause sau 14 ngày" mơ hồ — không rõ đây là hành vi mặc
định hay tùy chọn. Chốt theo hướng an toàn: mặc định không tự động thay đổi trạng thái vận
hành của Job (rủi ro thấp hơn nhiều so với auto-pause nhầm một Job đang thực sự cần tuyển gấp).
Không thêm `actor_type` cho `job_status_histories` ngay vì auto-pause không có code path nào
thực thi ở Phase 1 — thêm cột chờ sẵn sẽ vi phạm nguyên tắc "không tạo cột dự phòng".

## ADR-043 — Quy tắc hiển thị Job `closed`/`paused`: giữ URL, giữ CTA, không xây "liên hệ tư vấn chung"

**Quyết định:** Cả `closed` và `paused` đều: rời khỏi danh sách/tìm kiếm/sitemap công khai; URL
chi tiết vẫn `200` (không `404`); hiển thị trạng thái rõ ràng ("Đã ngừng tuyển"/"Tạm ngừng
tuyển"); ẩn nút "Ứng tuyển ngay"; **CTA Gọi/Zalo giữ nguyên hiển thị** dùng contact cơ sở như
Job `published`. Không xây tính năng "liên hệ tư vấn chung" mới cho trường hợp này.

**Lý do:** `paused` thường là tạm thời — trả `404` sẽ làm gãy liên kết cho một trạng thái không
vĩnh viễn, nên xử lý thống nhất với `closed` (vốn đã chốt giữ URL từ trước, ADR liên quan tới
SEO). Giữ CTA Gọi/Zalo vì đây chỉ là kênh liên lạc thủ công (`tel:`/`zalo.me`), không tạo bản
ghi trong hệ thống — không có rủi ro dữ liệu khi vẫn hiển thị. "Liên hệ tư vấn chung" nếu xây
mới sẽ tương đương một hình thức Lead, nằm ngoài phạm vi Phase 1 (ADR-021) — nên chủ động không
đi theo hướng đó dù đặc tả gốc để ngỏ khả năng này.

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
`.claude/rules/scope-standards.md`).

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

## ADR-047 — Job Publish Contract: thêm điều kiện xác minh còn tuyển và điều kiện địa điểm đủ rõ, kèm admin override có kiểm soát

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

## ADR-049 — Phân loại 3 nhóm blocker (Migration / Go-live / Phase 2 decision); tách khỏi điều kiện chuyển Giai đoạn 1

**Quyết định:** Áp dụng khung phân loại 3 nhóm cho mọi hạng mục còn mở, thay thế cách gộp chung
"Blockers" trước đây (`ROADMAP.md` mục "Phân loại blocker"):

1. **Migration blockers** (chặn viết migration): chỉ còn 5 enum đề xuất chờ công ty duyệt bằng
   văn bản (`docs/CORE-FLOWS.md` mục 8.2) — các mục schema khác từng bị treo (nullability
   `company_locations`, `jobs.owner_branch_id`, `last_checked_at`/`last_verified_at`) đã được
   chốt trong ADR-045..048, không còn là blocker.
2. **Go-live blockers** (chặn vận hành thật, không chặn migration/code): thời hạn lưu dữ liệu
   ứng viên (mục 7.4) và mức mask cụ thể cho `submission_snapshot` khi anonymize (mục 7.2) —
   ảnh hưởng nội dung **Action** anonymize và chính sách vận hành, không ảnh hưởng cấu trúc bảng
   (`candidates.anonymized_at`/`anonymized_by`, `applications.submission_snapshot` JSON đã đủ
   linh hoạt cho mọi phương án mask khi công ty chốt).
3. **Phase 2 decisions** (không thuộc Phase 1, không được thiết kế schema trước): có bật
   `job_auto_pause_enabled` hay không (mục 1.3) — mặc định `false`, không code path nào thực thi
   ở Phase 1, việc công ty chưa quyết định **không** ảnh hưởng gì tới khả năng tạo migration hay
   go-live Phase 1.

**Điều kiện chuyển sang Giai đoạn 1 (sửa lại, chỉ còn phụ thuộc nhóm 1):** 5 enum đề xuất
(`docs/CORE-FLOWS.md` mục 8.2) được xác nhận bằng văn bản, cộng với môi trường code đã cài đặt
xong. Nhóm 2 (go-live) và nhóm 3 (Phase 2) **không** còn là điều kiện chặn Giai đoạn 1 — có thể
xử lý song song hoặc muộn hơn (nhóm 2 chậm nhất phải xong trước khi go-live thật ở Giai đoạn 4;
nhóm 3 có thể để ngỏ vô thời hạn).

**Lý do:** Đặc tả trước gộp chung "3 mục CẦN CHỐT VỚI CÔNG TY" (data retention, mask
`submission_snapshot`, `job_auto_pause_enabled`) thành một khối chặn thẳng việc `composer
create-project`/viết migration — nhưng cả 3 mục này, xét đúng bản chất, không đổi bất kỳ cột hay
bảng nào trong schema Phase 1 hiện tại (dù công ty chọn phương án nào, `submission_snapshot` vẫn
là cột JSON không đổi, `job_auto_pause_enabled` vẫn là setting mặc định tắt). Giữ chúng làm
migration blocker là quá thận trọng, vi phạm chính nguyên tắc "không để Phase 2 decision chặn
migration Phase 1" mà yêu cầu nghiệp vụ mới nêu rõ. Việc tách nhóm giúp Giai đoạn 1 (khởi tạo
Laravel, viết migration) có thể bắt đầu ngay sau khi 5 enum được duyệt, thay vì chờ những quyết
định pháp lý/chính sách chưa chắc có thời hạn trả lời rõ ràng.

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

## ADR-056 — PII schema tối thiểu cho `applications`: nullability và cơ chế anonymize (tách khỏi retention)

**Quyết định:** Chốt cấu trúc schema (không đổi cột hiện có, chỉ khóa lại hành vi) cho 6 cột PII
trực tiếp trên `applications`:

| Cột | Nullable (không đổi) | Khi anonymize | Ảnh hưởng index/unique | Giữ lại để audit |
|---|---|---|---|---|
| `submitted_full_name` | NOT NULL | **Mask**: ghi đè bằng placeholder cố định (vd `"Ứng viên đã ẩn danh"`) | Không đánh index — không ảnh hưởng | Không giữ giá trị gốc |
| `submitted_phone` | NOT NULL | **Mask**: ghi đè bằng placeholder cố định (vd `"0000000000"`) | Không đánh index — không ảnh hưởng | Không giữ giá trị gốc |
| `submitted_phone_normalized` | NOT NULL | **Mask**: ghi đè cùng placeholder đã chuẩn hóa | Có index (không unique) — nhiều bản ghi trùng giá trị mask không vi phạm gì, chấp nhận được | Không giữ giá trị gốc |
| `submission_snapshot` | NOT NULL (json) | **Thay thế** (không set NULL) bằng JSON đã redact — cột luôn giữ JSON hợp lệ; **danh sách key cụ thể bị redact** vẫn là **[CẦN CHỐT VỚI CÔNG TY]** (mục 7.2, go-live blocker) | Không đánh index | Giữ các key nghiệp vụ không định danh (`education_level`, `experience_summary`, nguồn...) |
| `consent_ip` | nullable (không đổi) | **Set NULL** | Không đánh index | Không giữ |
| `consent_user_agent` | nullable (không đổi) | **Set NULL** | Không đánh index | Không giữ |

Không thêm cột `applications.anonymized_at` riêng — nguồn sự thật duy nhất về việc anonymize vẫn
là `candidates.anonymized_at`/`status=anonymized` (`docs/DATABASE-DICTIONARY.md` mục 9.2); Action
anonymize cascade ghi đè các cột trên của **toàn bộ** `applications` thuộc candidate đó trong
cùng transaction, không cần cột đánh dấu riêng ở `applications`.

**Lý do:** Yêu cầu nghiệp vụ mới tách rõ 2 lớp quyết định: (1) **cấu trúc** — nullable hay
không, set NULL hay mask hay thay thế, có ảnh hưởng index/unique không — đây là quyết định kiến
trúc, khóa được ngay; (2) **nội dung chính xác** của việc mask (che bao nhiêu ký tự số điện
thoại, giữ lại key nào trong JSON) — vẫn là quyết định công ty, không ảnh hưởng gì tới việc viết
migration (cột nào NOT NULL/nullable đã chốt xong dù công ty chọn nội dung mask nào). Giữ
`submitted_full_name`/`submitted_phone`/`submitted_phone_normalized` là NOT NULL (không đổi
sang nullable) để tránh phá vỡ giả định "Application luôn có đủ dữ liệu snapshot tại thời điểm
nộp" ở nơi khác trong code — dùng mask (ghi đè placeholder) thay vì NULL để giữ nguyên bất biến
NOT NULL trong khi vẫn xóa dữ liệu định danh, nhất quán với cách xử lý `candidates.full_name`
(cũng NOT NULL, cũng mask thay vì NULL — mục 7.3).

## ADR-057 — Phase 1 Plan Baseline v1.0 (freeze chính thức)

**Quyết định:** Đóng băng phạm vi Phase 1 tại phiên bản **Phase 1 Plan Baseline v1.0**. Từ thời
điểm này, không mở rộng thêm chức năng vào Phase 1 ngoài những gì đã liệt kê trong
`docs/PHASE-1-SCOPE.md` (mới), trừ khi phát hiện lỗi nghiệp vụ hoặc lỗ hổng bảo mật nghiêm
trọng cần vá trước khi tạo migration. Toàn bộ chức năng không được liệt kê rõ trong Phase 1 mặc
định thuộc `docs/PHASE-2-BACKLOG.md` (mới). Hai file này là điểm tổng hợp chính thức, không thay
thế `docs/CORE-FLOWS.md` (vẫn là nguồn sự thật chi tiết cho 6 luồng nghiệp vụ) —
`docs/PHASE-1-SCOPE.md` là bản khai phạm vi + tuyên bố đóng băng, liên kết tới các nguồn chi tiết
thay vì chép lại.

**Lý do:** Sau ADR-045..056, toàn bộ khoảng trống ảnh hưởng trực tiếp tới migration đã được lấp
(Quick Create, Job Draft/Publish/Verification, Branch Transfer, enum strategy, PII schema tối
thiểu, bootstrap/seeder). Cần một mốc rõ ràng đánh dấu "phạm vi Phase 1 đã chốt" để tránh việc
tiếp tục bổ sung yêu cầu mới không giới hạn ngay trước khi viết migration — mỗi lần bổ sung sau
mốc này phải được cân nhắc là ngoại lệ nghiêm trọng, không phải quy trình bình thường.
