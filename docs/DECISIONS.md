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
