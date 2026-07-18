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
