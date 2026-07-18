# Candidate và Application domain

> Nguồn ADR theo chủ đề. Không đổi mã ADR; ADR mới phải được thêm vào file chủ đề phù hợp và cập nhật `docs/decisions/INDEX.md`.

<a id="adr-005"></a>

## ADR-005 — Không dùng phone number làm định danh duy nhất của candidate

**Quyết định:** Tách `candidates` (hồ sơ nghiệp vụ) khỏi `candidate_contacts` (số điện
thoại/email/Zalo, nhiều bản ghi per candidate). Không unique cứng theo số điện thoại ở cấp
candidate.

**Lý do:** Một người có thể đổi số điện thoại, dùng nhiều số, hoặc nộp hồ sơ bằng số của
người thân. Coi 1 số điện thoại = 1 danh tính duy nhất sẽ gộp nhầm hoặc tách nhầm candidate.
Cần cơ chế phát hiện trùng dựa trên nhiều tín hiệu (số điện thoại + họ tên + ngày sinh), có
kiểm tra thủ công trước khi gộp.

<a id="adr-006"></a>

## ADR-006 — `users` là tài khoản đăng nhập, `candidates` là hồ sơ nghiệp vụ

**Status:** Partially superseded by ADR-028 — phần "candidate tự tạo tài khoản,
`candidates.user_id` trỏ `users.id`" không còn trong Phase 1 (dời Phase 2). Phần "guest không
cần `users`; staff/admin có `users` nhưng không có `candidates`" vẫn là quyết định hiện hành.

**Quyết định:** Guest ứng tuyển tạo `candidates` không cần `users`. Staff/admin có `users`
nhưng không có `candidates`. Khi candidate tự tạo tài khoản, `candidates.user_id` trỏ tới
`users.id`.

**Lý do:** Guest chiếm phần lớn lượng ứng tuyển (`docs/CORE-FLOWS.md`:
không bắt buộc đăng nhập để ứng tuyển).
Bắt buộc tạo `users` cho mọi candidate sẽ phát sinh tài khoản rác không ai đăng nhập, và làm
authentication logic phức tạp không cần thiết cho Phase 1.

<a id="adr-007"></a>

## ADR-007 — Application lưu snapshot tại thời điểm ứng tuyển

**Quyết định:** `applications` lưu `submission_snapshot` (JSON) và `job_snapshot` (JSON) tại
thời điểm nộp. Sửa `candidates`/`jobs`/`companies` sau này không làm thay đổi dữ liệu lịch sử
của application đã tồn tại. JSON snapshot chỉ dùng để tra cứu lịch sử, không dùng làm nguồn
lọc/báo cáo chính (lọc/báo cáo dùng cột quan hệ thật: `candidate_id`, `job_id`, `stage`...).

**Lý do:** HR cần biết chính xác thông tin ứng viên đã khai báo và điều kiện công việc tại
thời điểm ứng tuyển, kể cả khi job đã đổi lương hoặc candidate đã sửa hồ sơ sau đó. Dùng JSON
làm nguồn lọc sẽ chậm và không index được — chỉ dùng cho hiển thị lịch sử.

<a id="adr-009"></a>

## ADR-009 — Tách pipeline xử lý khỏi lịch sử liên hệ (contact attempts)

**Quyết định:** `applications.stage` chỉ dùng 8 giá trý pipeline chính (`new`, `contacting`,
`consulted`, `interview_scheduled`, `interviewed`, `waiting_start`, `started`, `closed`).
Kết quả từng cuộc gọi (nghe máy, không nghe máy, sai số...) lưu riêng trong
`application_contact_attempts`, không trộn vào `stage`.

**Lý do:** "Không nghe máy" là kết quả 1 lần gọi, có thể gọi lại nhiều lần trong cùng 1 stage
`contacting`. Trộn 2 khái niệm này vào 1 cột trạng thái làm pipeline không phản ánh đúng tiến
trình xử lý và khó báo cáo (không biết đã gọi bao nhiêu lần).

<a id="adr-016"></a>

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

<a id="adr-017"></a>

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

<a id="adr-022"></a>

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

<a id="adr-024"></a>

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

<a id="adr-026"></a>

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

<a id="adr-030"></a>

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

<a id="adr-031"></a>

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

<a id="adr-032"></a>

## ADR-032 — Hoàn thiện validation khi chuyển cơ sở (Transfer Branch)

**Quyết định:** Chuyển cơ sở yêu cầu đủ 7 điều kiện: cơ sở đích tồn tại, `active`, chưa xóa,
khác cơ sở hiện tại, người thực hiện là admin, có lý do, và `lockForUpdate` chống 2 request
đồng thời — chi tiết: `docs/CORE-FLOWS.md` mục 6.1.

**Lý do:** Đặc tả trước chỉ nói "kiểm tra quyền, cập nhật, ghi lịch sử" mà chưa liệt kê điều
kiện đầu vào cụ thể — có thể dẫn tới chuyển sang cơ sở không hợp lệ (inactive/đã xóa) hoặc
"chuyển" sang chính cơ sở hiện tại (tạo lịch sử vô nghĩa). Yêu cầu `lockForUpdate` chống trường
hợp 2 admin cùng chuyển 1 Application gần như đồng thời tới 2 cơ sở khác nhau.

<a id="adr-034"></a>

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

<a id="adr-035"></a>

## ADR-035 — Chốt `submission_token` NOT NULL, lưu trực tiếp trên `applications`

**Quyết định:** `applications.submission_token` là `NOT NULL, UNIQUE` (bỏ trạng thái nullable
tạm thời ở vòng đặc tả trước). Không tạo bảng `application_submissions` riêng — token sinh và
tiêu thụ trong đúng 1 request tạo Application, không có kịch bản cần bảng trung gian.

**Lý do:** Phase 1 chỉ có duy nhất 1 luồng tạo Application (form guest, 1 bước, đủ dữ liệu ngay
trong request) — không có trường hợp hợp lệ nào Application được tạo mà thiếu token, nên
nullable chỉ tạo khoảng trống cho phép bỏ sót kiểm tra. Bảng riêng chỉ hợp lý nếu có luồng
"giữ chỗ trước, hoàn tất dữ liệu sau" (vd nhập liệu nhiều bước, import) — chưa tồn tại ở Phase 1
(ADR-029 đã loại bỏ `actor_type=import`), nên không tạo trước.

<a id="adr-040"></a>

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

<a id="adr-041"></a>

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

<a id="adr-061"></a>

## ADR-061 — Submission concurrency khác `submission_token`: khóa named lock theo `phone_normalized`

**Quyết định:** Chọn **Named/advisory lock của MariaDB** (`GET_LOCK()`/`RELEASE_LOCK()`) làm cơ
chế serialize việc tạo Candidate/Application khi 2 request đồng thời dùng 2 `submission_token`
khác nhau nhưng cùng `phone_normalized` (mục 3, ADR-041 không đổi — đây là lớp bảo vệ **bổ
sung**, độc lập với unique `(candidate_id, job_id)` đã có). Không chọn phương án bảng khóa kỹ
thuật riêng (phương án 2) vì thêm 1 bảng + vòng đời dọn dẹp bản ghi khóa chỉ để làm việc mà
`GET_LOCK` đã làm sẵn ở tầng DB — không tối giản bằng.

**Hợp đồng xử lý (`SubmitApplicationAction`, thực thi khi bắt đầu xử lý request `applications.store`):**

1. Chuẩn hóa `phone_normalized` từ input **trước** khi chạm DB.
2. Tính khóa: `lock_key = 'app_submit_phone:' . hash('sha256', $phoneNormalized)` — **không bao
   giờ dùng số điện thoại thô làm khóa hay ghi log** (chỉ log hash/`lock_key`, không log
   `phoneNormalized`).
3. Gọi `SELECT GET_LOCK(?, ?)` với timeout mặc định **5 giây** (hằng số cấu hình ở Action, không
   cần thêm `settings` — đây là tham số kỹ thuật chống deadlock, không phải chính sách nghiệp
   vụ). `GET_LOCK` trả `0` (hết thời gian chờ) hoặc `NULL` (lỗi) → dừng ngay, rollback mọi thay
   đổi đã làm trong request đó (nếu có), trả về lỗi **thân thiện** ("Hệ thống đang xử lý một yêu
   cầu khác cho số điện thoại này, vui lòng thử lại sau vài giây") — **không** trả `500`.
4. Bắt đầu transaction DB (`DB::transaction`).
5. Trong transaction: query lại Candidate theo `phone_normalized` (đọc dữ liệu **mới nhất**, vì
   request đã giữ được lock nên không còn race) → áp dụng Duplicate Candidate Contract 4 trường
   hợp (mục 6.2, không đổi) → resolve candidate trùng thành merged root nếu match trúng một
   candidate `status=merged` (ADR-063) → tạo hoặc tái sử dụng Candidate.
6. Kiểm tra lại Application theo `(candidate_id, job_id)` (case C, mục 6.2) — vì Candidate vừa
   xác định có thể đã có Application cho đúng Job này (tạo bởi chính request đang chờ lock trước
   đó, hoặc một request khác đã hoàn tất).
7. Tạo Application nếu chưa có (theo Luồng 3 bình thường, không đổi).
8. Commit transaction.
9. `SELECT RELEASE_LOCK(?)` trong khối `finally` (giải phóng dù bước 4–8 thành công hay ném
   exception) — không giữ lock treo nếu request lỗi giữa chừng. Nếu tiến trình PHP crash trước
   khi gọi `RELEASE_LOCK` (không kịp `finally`), MariaDB tự giải phóng lock khi kết nối đóng
   (hành vi mặc định của `GET_LOCK`, không cần xử lý thêm).
10. Không có bước nào ở trên tự động gộp (merge) 2 Candidate có cùng `phone_normalized` — lock
    chỉ đảm bảo Duplicate Candidate Contract được áp dụng **tuần tự** (không còn race), **không**
    thay đổi 4 trường hợp đã chốt (case 2/3/4 vẫn tạo Candidate mới + đánh dấu review, không tự
    merge).

**Lý do:** Unique `(candidate_id, job_id)` chỉ chặn được trùng lặp **sau khi** đã xác định xong
`candidate_id` — không chặn được việc 2 request đồng thời, do race điều kiện đọc-rồi-ghi (read
then write) khi kiểm tra "candidate đã tồn tại chưa" ở tầng Service, cùng kết luận "chưa có" và
cùng tạo 2 Candidate mới (2 `candidate_id` khác nhau) cho cùng 1 người trước khi transaction nào
kịp commit — mỗi Candidate mới lại tạo 1 Application riêng cho cùng Job, không vi phạm unique
`(candidate_id, job_id)` vì `candidate_id` khác nhau, dẫn tới 2 hồ sơ trùng thật sự cho cùng một
người. `GET_LOCK` là cơ chế named lock chuẩn của MySQL/MariaDB, hoạt động xuyên suốt mọi kết nối
(không chỉ trong 1 transaction), đơn giản hơn tự xây bảng khóa, và MariaDB 11.4 (ADR-039) hỗ trợ
đầy đủ. Hash hóa số điện thoại trước khi dùng làm khóa/log tránh lộ PII trong log hệ thống hoặc
công cụ giám sát MariaDB (`SHOW PROCESSLIST`, log chậm).

<a id="adr-062"></a>

## ADR-062 — Duplicate Review data model: thêm bảng `candidate_duplicate_reviews`

**Quyết định:** Thêm bảng Phase 1 mới **`candidate_duplicate_reviews`** (bảng thứ 28) — dữ liệu
đủ để Admin thực sự xử lý nghi ngờ trùng, thay vì chỉ có cờ `applications.needs_duplicate_review`
(không biết trùng với candidate nào, không có lịch sử xử lý riêng).

| Cột | Kiểu | Nullable | Ghi chú |
|---|---|---|---|
| `id` | bigint unsigned | không | PK |
| `application_id` | bigint unsigned | **không** | FK `applications.id`, RESTRICT — Phase 1 chỉ tạo review từ đúng 1 nguồn duy nhất (Luồng 3, trường hợp 2/3/4 của Duplicate Candidate Contract), luôn có Application đi kèm ngay lúc tạo — không để nullable "phòng khi sau này" |
| `candidate_id` | bigint unsigned | không | FK `candidates.id`, RESTRICT — Candidate **mới** vừa tạo ở trường hợp 2/3/4 |
| `suspected_candidate_id` | bigint unsigned | không | FK `candidates.id`, RESTRICT — Candidate **đã tồn tại** trùng `phone_normalized` |
| `reason_code` | varchar(30) **[varchar+enum, ADR-055 pattern]** | không | `same_phone_missing_dob` (trường hợp 2), `same_phone_different_name` (trường hợp 3), `same_identity_conflicting_dob` (trường hợp 4), `other` |
| `status` | varchar(20) **[varchar+enum]** | không, default `pending` | `pending`, `confirmed_same`, `confirmed_distinct`, `dismissed` |
| `pending_pair_key` | varchar(80), generated | có (null khi không `pending`) | `IF(status='pending', CONCAT(candidate_id,'-',suspected_candidate_id,'-',reason_code), NULL)` STORED, **UNIQUE** — chặn tạo 2 review `pending` trùng cặp candidate + lý do (cùng pattern với `job_locations.primary_flag_job_id`) |
| `reviewed_by` | bigint unsigned | có | FK `users.id`, SET NULL |
| `reviewed_at` | timestamp | có | |
| `review_note` | string(255) | có | |
| `created_at` | timestamp | có | |
| `updated_at` | timestamp | có | Không phải bảng append-only thuần — `status`/`reviewed_by`/`reviewed_at`/`review_note` cập nhật sau khi Admin xử lý, giống `application_appointments` |

**Quy tắc:**

- Tạo đúng 1 bản ghi mỗi khi Luồng 3 rơi vào trường hợp 2/3/4 (cùng transaction với việc tạo
  Candidate/Application mới, mục 6.2) — `reason_code` map trực tiếp theo trường hợp.
- **Không tự động merge** — `confirmed_same` chỉ đánh dấu kết luận của Admin, **không** tự gọi
  `CandidateMergeController@store`; Admin phải chủ động thực hiện merge như một hành động riêng
  (`hr.candidates.merge`, đã có, ADR-034) sau khi xem xét, đúng yêu cầu "merge luôn do admin chọn
  thủ công" (ADR-026) — 2 hành động độc lập, không gộp.
- Trang chi tiết (`hr.duplicate-reviews.show`) hiển thị Candidate và suspected Candidate cạnh
  nhau (2 bản ghi `candidates` + `candidate_contacts` liên quan) để Admin so sánh.
- `hr.duplicate-reviews.resolve` cập nhật `status`/`reviewed_by`/`reviewed_at`/`review_note` của
  bản ghi này **và** đồng thời cập nhật `applications.duplicate_reviewed_at`/`duplicate_reviewed_by`
  của `application_id` liên quan trong cùng transaction (giữ 2 cột cờ nhanh trên `applications`
  hoạt động đúng cho bộ lọc "nghi ngờ trùng" ở mục 9.2, không bỏ 2 cột đó).
- Chỉ **admin** truy cập route Duplicate Review (Staff không có quyền — nhất quán với việc chỉ
  admin được merge candidate).

**Lý do:** `applications.needs_duplicate_review = true` chỉ là một cờ boolean — không lưu
candidate nào đang bị nghi ngờ trùng với candidate nào, không lưu lý do phân loại (thiếu ngày
sinh/tên khác/ngày sinh mâu thuẫn), khiến Admin phải tự tra cứu thủ công (tìm candidate cùng
`phone_normalized`) mỗi lần xử lý — không đủ dữ liệu để "thực sự xử lý" như yêu cầu nghiệp vụ.
Thêm bảng nhỏ, dùng lại pattern generated-column-unique đã có (`job_locations`) để chặn review
trùng ở tầng DB thay vì chỉ dựa vào kiểm tra Service.

<a id="adr-063"></a>

## ADR-063 — Duplicate matching hardening: resolve candidate merged về root, thêm `candidates.full_name_normalized`

**Quyết định (2 phần liên quan, cùng ảnh hưởng Luồng 3/mục 6.2):**

**Phần 1 — Merged-root resolution khi matching:** Khi Duplicate Candidate Contract (mục 6.2) tìm
thấy một `candidates` có `phone_normalized` khớp nhưng `status = merged`, **bắt buộc** resolve
sang candidate root (chưa merged) trước khi áp dụng 4 trường hợp so khớp — không dùng trực tiếp
candidate nguồn đã merged làm đối tượng so khớp/tạo Application. Thuật toán (tái dùng đúng cách
"tìm root" đã mô tả ở mục 6.3, áp dụng thêm cho luồng nộp đơn):

1. Bắt đầu từ candidate tìm được theo `phone_normalized`.
2. Nếu `status != merged`: đây là root, dùng trực tiếp — dừng.
3. Nếu `status = merged`: đi theo `merged_into_candidate_id` sang candidate tiếp theo, lặp lại
   bước 2, tối đa **20 bước** (giới hạn độ sâu phòng dữ liệu lỗi — trong vận hành đúng, chuỗi
   merge không bao giờ dài vì mỗi candidate chỉ làm nguồn đúng 1 lần, 20 là biên an toàn rộng).
4. Nếu tại bất kỳ bước nào gặp lại chính candidate id đã đi qua trước đó (chu trình) → dữ liệu
   lỗi kỹ thuật — ghi log lỗi (không phải log nghiệp vụ), dừng resolve, coi như **không tìm thấy
   candidate trùng** (rơi về nhánh "không có candidate nào khớp `phone_normalized`" — tạo
   Candidate mới bình thường theo luồng hiện có, **không** chặn ứng viên nộp đơn vì lỗi dữ liệu
   nội bộ).
5. Nếu vượt quá 20 bước mà chưa tới root → coi như bước 4 (log lỗi kỹ thuật, không chặn nộp đơn).
6. Dùng candidate root tìm được (nếu có) làm đối tượng so khớp cho 4 trường hợp ở mục 6.2 — nếu
   trường hợp 1 (khớp chắc chắn) đúng, **tái sử dụng root**, không tạo Candidate mới chỉ vì
   contact nằm trên candidate nguồn đã merged.

**Phần 2 — `candidates.full_name_normalized`:** Thêm cột chính thức (trước đây chuẩn hóa chỉ mô
tả bằng lời, không có cột lưu) — `string(150)`, **NOT NULL**, có index (không unique), sinh tự
động từ `full_name` ở tầng Model (Eloquent observer/mutator khi tạo/sửa `full_name`), **không**
nhận giá trị trực tiếp từ client. **Thuật toán chuẩn hóa chính thức (thay thế mô tả cũ ở mục
6.2, vốn nói "bỏ dấu tiếng Việt" — nay đổi thành giữ dấu):**

1. Trim khoảng trắng đầu/cuối.
2. Gộp nhiều khoảng trắng liên tiếp thành đúng 1 khoảng trắng.
3. Chuẩn hóa Unicode dạng NFC.
4. Lowercase theo Unicode (vd `mb_strtolower(..., 'UTF-8')`), **giữ nguyên dấu tiếng Việt** (khác
   quyết định cũ — không bỏ dấu).
5. Loại bỏ mọi ký tự không phải chữ cái Unicode, chữ số, hoặc khoảng trắng (xóa dấu câu: `.`,
   `,`, `'`, `-`, `"`...); sau bước này gộp khoảng trắng liên tiếp lần nữa (phòng trường hợp xóa
   dấu câu giữa 2 từ để lại khoảng trắng kép).
6. Không dùng fuzzy matching/Levenshtein/AI ở bất kỳ bước nào (không đổi so với ADR-026/040).

**Lý do:** Phần 1 — mục 6.2 (Duplicate Candidate Contract) trước đây không xử lý trường hợp kết
quả tra cứu theo `phone_normalized` trúng một candidate đã `merged` — nếu dùng thẳng candidate đó
để so khớp/tạo Application sẽ vi phạm chính quy tắc đã chốt ở mục 9.2 dictionary ("Candidate
`status = merged` không được dùng để tạo application mới"). Phần 2 — chuẩn hóa họ tên trước đây
chỉ là mô tả thuật toán, không có cột lưu trữ thật, khiến việc so khớp trường hợp 1 phải tính lại
mỗi lần truy vấn (chậm, không index được hiệu quả); thêm cột giải quyết cả hiệu năng lẫn tính
nhất quán. Đổi "bỏ dấu" thành "giữ dấu" vì bỏ dấu tiếng Việt làm tăng nguy cơ khớp nhầm 2 tên
khác nghĩa nhưng cùng dạng không dấu (vd "Ba" và "Bà" đều thành "ba") — giữ dấu chính xác hơn cho
mục đích so khớp chính xác tuyệt đối (không fuzzy) đã chốt.

<a id="adr-075"></a>

## ADR-075 — Candidate matching phải xét toàn bộ phone roots; Duplicate Review là nguồn sự thật

**Quyết định (mở rộng/supersede giả định “một suspected Candidate” của ADR-062/063):** Dưới named lock, query toàn bộ Candidate cùng normalized phone, resolve từng
Candidate về root, dedupe root rồi so khớp tất cả; cấm dùng `first()`. Đúng một exact root thì
reuse. Không exact thì tạo Candidate/Application mới và review cho mỗi suspected root. Nhiều
exact root dùng reason `multiple_exact_matches`. `candidate_duplicate_reviews` là nguồn sự thật;
summary trên Application chỉ hoàn tất khi không còn review pending.

<a id="adr-076"></a>

## ADR-076 — Cùng Job phải được kiểm tra trên toàn merged family

**Quyết định:** Trước insert Application, query cùng Job trên toàn merged family. Nếu có, không
tạo mới; chọn canonical deterministic (không duplicate trước, stage xa hơn, id nhỏ hơn), cập nhật
`last_reapplied_at`. Unique `(candidate_id, job_id)` chỉ bảo vệ cấp một Candidate.
