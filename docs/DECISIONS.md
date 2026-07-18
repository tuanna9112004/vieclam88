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

**Quyết định:** Yêu cầu "mọi thao tác phải ghi rõ người thực hiện/thời gian/nội dung" được đáp
ứng bằng các bảng lịch sử append-only chuyên biệt đã có
(`application_status_histories`, `application_assignment_histories`,
`application_contact_attempts`, `application_branch_histories`, `job_verifications`,
`export_logs`) — mỗi hành động nghiệp vụ tự ghi lại lịch sử của chính nó. Phase 1 **không**
tạo bảng `audit_logs`/`activities` tổng quát ghi mọi thay đổi trên mọi model.

**Lý do:** `.claude/rules/scope-standards.md` đã liệt kê "full audit log" là ngoài phạm vi
Phase 1. Yêu cầu "Action phải ghi Audit log" trong đặc tả luồng nghiệp vụ mới không mâu thuẫn
với giới hạn này — nó được thỏa mãn bởi audit trail per-action đã tồn tại, không cần thêm hạ
tầng audit tổng quát (dual-write, listener toàn cục) mà Phase 1 chưa cần.

## ADR-020 — Staff chỉ xem Application thuộc cơ sở phụ trách (thay vì toàn bộ)

**Quyết định:** Staff chỉ truy cập được `applications` có `owner_branch_id` trùng
`users.branch_id` của mình; truy cập URL của Application thuộc cơ sở khác trả về 403. Admin
không bị giới hạn cơ sở. Đây là thay đổi so với giả định trước đó ("Staff Phase 1 xem toàn bộ
application") trong `.claude/rules/roles-business-rules.md`.

**Lý do:** Khi hệ thống có khái niệm cơ sở nội bộ (ADR-015), để staff xem toàn bộ hồ sơ của mọi
cơ sở sẽ vô hiệu hóa mục đích phân vùng dữ liệu theo cơ sở và có nguy cơ lộ dữ liệu ứng viên
giữa các cơ sở không liên quan. Việc "tự nhận hồ sơ" (claim) trong phạm vi cơ sở của mình vẫn
giữ nguyên, không cần phân công cứng.

> Cập nhật (ADR-021): phần "tự nhận hồ sơ (claim)" ở ADR này bị thay thế — Phase 1 bỏ hẳn khái
> niệm claim/assign, không chỉ bỏ phân công cứng.

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
