# Core Flows — vieclam88 (Phase 1)

Nguồn sự thật duy nhất cho 6 luồng nghiệp vụ cốt lõi (CRITICAL BUSINESS FLOWS). Database
(`docs/ERD.md`, `docs/DATABASE-DICTIONARY.md`), route (`docs/ROUTE-MAP.md`), rule
(`.claude/rules/*`), acceptance criteria (`docs/ACCEPTANCE-CRITERIA.md`) và ADR
(`docs/DECISIONS.md`) phải khớp file này. Nếu phát hiện mâu thuẫn, dừng lại và cập nhật file
này trước, không tự chọn diễn giải khác ở nơi khác.

Một chức năng chỉ tính là hoàn thành khi luồng tương ứng chạy đúng từ đầu đến cuối, không tạo
trùng, không sai cơ sở, không mất lịch sử, và có test.

## 0. Phạm vi Phase 1

Phase 1 chỉ xử lý hồ sơ ứng viên gửi về qua **form ứng tuyển trên website**
(`applications.store`). Luồng tổng:

```
Nhà máy có nhu cầu tuyển
→ nhân viên tạo Job (draft) → publish
→ ứng viên tìm việc → xem chi tiết → gửi form ứng tuyển
→ hệ thống kiểm tra dữ liệu và chống trùng → tạo Application đúng cơ sở
→ nhân viên gọi điện/Zalo thủ công (ngoài hệ thống) → ghi kết quả liên hệ
→ cập nhật trạng thái → hẹn phỏng vấn → cập nhật kết quả phỏng vấn
→ chờ đi làm → xác nhận đã đi làm hoặc đóng hồ sơ.
```

**Phase 1 KHÔNG triển khai** (chuyển Phase 2 — xem `ROADMAP.md`):

- **Lead dưới mọi hình thức** — không có Lead từ cuộc gọi, không có Lead từ Zalo, và **không có
  form "yêu cầu tư vấn" tạo Lead trên website**. Bảng `lead_requests` không nằm trong database
  Phase 1 (xem ADR-021). Đây là thay đổi so với đặc tả trước — trước đó `lead_requests` được
  giữ lại Phase 1 chỉ để ghi nhận số điện thoại (ADR-018); nay bỏ hẳn, không chỉ bỏ phần
  chuyển đổi.
- Tích hợp Zalo API, tự động gọi/gửi tin nhắn.
- **Phân công/tự nhận hồ sơ dưới mọi hình thức** — không có "Nhận xử lý" (claim), không có gán
  nhân viên (assign), không tự động phân công, không round-robin, không có vai trò "trưởng cơ
  sở" phân công. Không có bảng `application_assignment_histories`, không có cột
  `applications.assigned_to` trong database Phase 1 (xem ADR-021). Đây là thay đổi so với đặc
  tả trước — trước đó staff được tự nhận (claim) hồ sơ chưa gán; nay bỏ hẳn cơ chế này khỏi
  Phase 1, kể cả ở mức database.
- CRM đa kênh, AI matching, cộng tác viên/hoa hồng.
- Candidate Account nâng cao (dashboard nâng cao, theo dõi trạng thái pipeline qua tài khoản).
- **Favorites** — không có bảng `favorites` trong database Phase 1 (xem ADR-021).

Nút Gọi/Zalo trên website chỉ mở kênh liên lạc (`tel:`/`https://zalo.me/...`); không tự tạo
bản ghi nào trong hệ thống.

## 1. Luồng 1 — Tạo và xuất bản việc làm

```
Công ty/nhà máy gửi nhu cầu (điện thoại/Zalo/Excel/gặp trực tiếp — ngoài hệ thống)
→ nhân viên đăng nhập HR → tạo Job (status=draft)
→ chọn company → chọn company_location(s) → chọn owner_branch_id (cơ sở nội bộ phụ trách)
→ chọn company_contact_id (nếu có, để liên hệ nội bộ) → nhập nội dung/lương/ca/quyền lợi
→ lưu nháp → kiểm tra điều kiện publish → người có quyền publish → Job status=published.
```

Điều kiện publish (Service kiểm tra, không chỉ ẩn nút ở view):

1. `companies.status = active`, chưa `deleted_at`.
2. `jobs.title`, `jobs.job_description` không rỗng.
3. Có ít nhất một `job_locations`, đúng một `is_primary = true`.
4. Mọi `job_locations.company_location_id` thuộc đúng `jobs.company_id`.
5. `jobs.owner_branch_id` khác null (bắt buộc trước publish, dù cột DB cho phép null ở
   draft — xem `docs/DATABASE-DICTIONARY.md` mục `jobs`).
6. `branches.status = active` và chưa `deleted_at` cho cơ sở ở bước 5.
7. Cơ sở ở bước 5 có `phone` hoặc `zalo` khác rỗng — xem "Quy tắc contact CTA" bên dưới.
8. `jobs.status` không phải `closed`/đã `deleted_at`.
9. Các trường bắt buộc theo nghiệp vụ (lương, ca...) đã đầy đủ theo Form Request.

**Job transition matrix chính thức** — không cho phép chuyển trạng thái ngoài bảng này:

| From | To |
|---|---|
| `draft` | `published` |
| `published` | `paused` |
| `paused` | `published` |
| `published` | `closed` |
| `paused` | `closed` |

`draft → closed` không nằm trong matrix — Job nháp bị bỏ dùng đóng bằng soft delete
(`hr.jobs.destroy`), một hành động khác, không phải transition trạng thái. `closed` là trạng
thái cuối trong Phase 1 (không có transition đi ra khỏi `closed`).

**Quy tắc contact CTA (Gọi/Zalo) trên Job:**

- CTA Gọi/Zalo trên trang Job công khai **luôn dùng `branches.phone`/`branches.zalo` của
  `jobs.owner_branch_id`** — cơ sở đã bắt buộc có contact hợp lệ trước khi publish (điều kiện
  6–7 ở trên), nên CTA luôn có dữ liệu để hiển thị.
- `company_contacts` (đầu mối công ty/nhà máy khách hàng) **không** phải nguồn CTA thay thế
  mặc định. Chỉ hiển thị công khai khi `company_contacts.is_public = true` **và** được nhân
  viên chọn rõ ràng làm `jobs.company_contact_id` cho Job đó — khi đó hiển thị **thêm** như
  một kênh liên hệ phụ, không thay thế CTA của cơ sở. Không tự động suy ra hoặc mặc định lộ số
  điện thoại nhà máy.
- Đây là thay đổi so với đặc tả trước (trước đó `company_contacts.is_public` được ưu tiên số 1,
  branch chỉ là fallback) — xem ADR-023.

## 2. Luồng 2 — Ứng viên tìm và chọn việc

Website chỉ hiển thị Job khi `status = published`, chưa `expires_at` (hoặc null), chưa
`deleted_at`. Job `draft`/`paused`/`closed` không xuất hiện ở danh sách/tìm kiếm công khai
(job đã `closed` vẫn giữ URL chi tiết — xem `.claude/rules/security-seo-testing.md`).

CTA trên trang chi tiết: **Ứng tuyển ngay** (luồng 3), **Gọi điện**, **Nhắn Zalo** (mở kênh
thủ công dùng contact cơ sở — mục 1, không tạo bản ghi). Chỉ "Ứng tuyển ngay" tạo dữ liệu
trong hệ thống.

## 3. Luồng 3 — Ứng viên gửi form ứng tuyển

```
Bấm "Ứng tuyển ngay" → điền form (kèm submission_token sinh sẵn khi render form)
→ đồng ý chính sách dữ liệu → gửi
→ server đọc lại Job từ DB (không tin dữ liệu client gửi kèm) → kiểm tra Job còn active
→ validate (Form Request) → chuẩn hóa họ tên/số điện thoại
→ kiểm tra trùng Candidate (mục 6) → kiểm tra Application cùng Job đã tồn tại (case C, mục 6)
→ tạo hoặc tái sử dụng Candidate → tạo Application, copy owner_branch_id từ Job,
  lưu submission_token
→ lưu submission_snapshot + job_snapshot → lưu consent
→ tạo application_status_histories (null → new) → tạo application_branch_histories
  (from_branch_id=null → to_branch_id=owner_branch_id, transferred_by=null = hệ thống)
→ commit transaction → thông báo thành công → hồ sơ xuất hiện ở đúng cơ sở phụ trách.
```

Toàn bộ nằm trong **1 transaction** (Action, không phải Controller) — nếu bất kỳ bước nào lỗi,
rollback toàn bộ, không để lại `candidates`/`applications` rác.

**Trường bắt buộc tối thiểu:** họ tên, số điện thoại, Job, đồng ý chính sách dữ liệu.
**Trường tùy chọn:** ngày sinh, giới tính, nơi ở hiện tại, số điện thoại khác, học vấn, kinh
nghiệm.

**Bắt buộc kỹ thuật:** server-side validation, CSRF, rate limit, honeypot/chống spam,
transaction, và **client không được tự gửi `stage`, `owner_branch_id` hoặc `assigned_to`** —
các trường này luôn do server tính/copy, không đọc từ input (mass-assignment allowlist ở Form
Request; `assigned_to` không tồn tại trong Phase 1, xem mục 0).

**Idempotency contract (chống double-click, refresh, và 2 request đồng thời):**

- `applications.submission_token` (string, unique khi có giá trị): sinh 1 lần khi server
  render form ứng tuyển (ẩn trong form, ví dụ hidden input gắn với session), gửi kèm khi
  submit. Đây là cơ chế **chống cùng một lần gửi form bị lặp** (double-click, F5 sau khi đã
  submit) — khác với unique `(candidate_id, job_id)` vốn chống **ứng tuyển lại** (submit mới,
  token khác, cùng candidate + cùng job).
- Request đầu tiên dùng token: tạo Application bình thường.
- Request thứ hai cùng token (double-click, resend): DB unique constraint chặn ở tầng
  `submission_token`; server bắt exception, trả về Application đã tạo ở request đầu (thông báo
  thành công, không lỗi 500, không tạo Candidate/Application thứ hai).
- 2 request đồng thời cùng token: unique constraint DB là chốt chặn cuối cùng — request thua
  xử lý như trên.
- 2 request đồng thời **khác token** nhưng cùng candidate + cùng job (vd 2 tab, 2 lần bấm sinh
  2 token khác nhau): chốt chặn cuối là unique `(candidate_id, job_id)` — xử lý như case C
  (mục 6.2), không lỗi 500.

## 4. Luồng 4 — Nhân viên cơ sở xử lý hồ sơ

Application luôn thuộc đúng một `owner_branch_id` (copy từ Job lúc tạo, không suy ra động qua
Job — Job có thể đổi cơ sở sau này mà không ảnh hưởng Application đã tạo).

- Staff chỉ thấy/xử lý Application có `owner_branch_id = users.branch_id` của mình.
- Admin thấy tất cả cơ sở.
- **Phase 1 không có khái niệm "phân công cho từng nhân viên"** — Application chỉ gán về cơ
  sở, không gán về người. Không có route/action "Nhận xử lý" (claim) hay "Gán nhân viên"
  (assign), không có bảng lịch sử phân công. Bất kỳ nhân viên nào cùng cơ sở đều xem và xử lý
  được mọi Application của cơ sở đó.
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

| From stage | To stage | Điều kiện bắt buộc | Ai được thực hiện |
|---|---|---|---|
| (mới) | `new` | Application vừa được tạo bởi Luồng 3 | Hệ thống (Action Apply) |
| `new` | `contacting` | Có ít nhất 1 `application_contact_attempts` cho application này | Staff/admin cùng cơ sở |
| `new` | `closed` | `close_reason` khác null | Staff/admin cùng cơ sở |
| `contacting` | `consulted` | Có `application_contact_attempts.result` ∈ {`consulted`, `interview_agreed`} | Staff/admin cùng cơ sở |
| `contacting` | `closed` | `close_reason` khác null | Staff/admin cùng cơ sở |
| `consulted` | `interview_scheduled` | Có `application_appointments(type=interview, status=scheduled)` | Staff/admin cùng cơ sở |
| `consulted` | `closed` | `close_reason` khác null | Staff/admin cùng cơ sở |
| `interview_scheduled` | `interviewed` | Appointment interview tương ứng có `status=completed` | Staff/admin cùng cơ sở |
| `interview_scheduled` | `closed` | `close_reason` khác null | Staff/admin cùng cơ sở |
| `interviewed` | `waiting_start` | `applications.expected_start_at` khác null | Staff/admin cùng cơ sở |
| `interviewed` | `closed` | `close_reason` khác null | Staff/admin cùng cơ sở |
| `waiting_start` | `started` | `applications.started_at` khác null | Staff/admin cùng cơ sở |
| `waiting_start` | `closed` | `close_reason` khác null | Staff/admin cùng cơ sở |
| `closed` | `new` | Có lý do mở lại (lưu ở `application_status_histories.note`, bắt buộc); **và** việc mở lại không được vi phạm unique `(candidate_id, job_id)` với một Application khác đang active của cùng candidate/job (trường hợp bản ghi này từng bị đóng do case C hoặc do merge — mục 6.2–6.3 — không được mở lại) | Staff/admin cùng cơ sở |
| `started` | (bất kỳ) | Trạng thái cuối trong Phase 1; không có transition tiếp | — |

Quy tắc chung:

- Mọi transition đi qua `ChangeApplicationStageAction` (không sửa cột `stage` trực tiếp từ
  controller). Action xử lý: authorization (đúng cơ sở), validate transition theo bảng trên,
  kiểm tra dữ liệu bắt buộc, khóa row (`lockForUpdate`) chống concurrent update, ghi
  `application_status_histories`, cập nhật `applications`, trong 1 transaction.
- Khi `closed → new`: xóa `applications.close_reason` và `applications.closed_at` (set null)
  vì Application không còn ở trạng thái đóng.
- Appointment bị `cancelled`/`no_show` **không** tự động lùi hoặc đổi stage. Stage giữ nguyên
  cho tới khi staff chủ động tạo appointment mới hoặc chuyển `closed`.
- Contact result và Application stage là 2 khái niệm khác nhau (ADR-009): ghi nhận
  `application_contact_attempts` không bao giờ tự động đổi `applications.stage`.

### 5.2. Contact Result — enum chính thức

`reached`, `no_answer`, `busy`, `wrong_number`, `consulted`, `callback_requested`,
`interview_agreed`, `candidate_refused`, `unsuitable`, `message_sent`, `other`
(cột `application_contact_attempts.result`; đồng nhất với `docs/DATABASE-DICTIONARY.md`, không
còn khác biệt giữa 2 file như bản trước).

Nhóm mở khóa `contacting → consulted` (đã chốt): `consulted`, `interview_agreed`. Các kết quả
khác (`no_answer`, `busy`, `wrong_number`, `callback_requested`) không mở khóa vì chưa tư vấn
được thật sự. `candidate_refused`/`unsuitable` không tự động đóng hồ sơ — staff phải chủ động
chuyển `closed` kèm lý do.

Ví dụ: contact result = `no_answer` → `applications.stage` vẫn là `contacting`.

### 5.3. Appointment (lịch gọi lại / phỏng vấn)

Bảng `application_appointments`:

- `type`: `callback`, `interview`.
- `status`: `scheduled`, `completed`, `cancelled`, `no_show`.
- Hẹn gọi lại bắt buộc có `scheduled_at`. Phỏng vấn hoàn thành phải cập nhật `outcome`/`note`
  và `status = completed`, `completed_by`, `completed_at` trước khi Application được phép
  chuyển `interview_scheduled → interviewed` (xem 5.1).
- **Đổi lịch không sửa đè bản ghi cũ.** Khi cần đổi giờ/hủy lịch đã có: chuyển bản ghi cũ sang
  `status = cancelled` (hoặc `no_show` nếu đã quá hạn không đến), sau đó tạo 1 bản ghi
  Appointment **mới** cho lịch mới. Giữ nguyên toàn bộ lịch sử các lần hẹn, không ghi đè
  `scheduled_at` của bản ghi đang tồn tại.

## 6. Luồng 6 — Chuyển cơ sở ngoại lệ và duplicate handling contract

### 6.1. Chuyển cơ sở (application_branch_histories)

Chỉ dùng cho ngoại lệ: Job gán sai cơ sở, cơ sở phụ trách đổi, bàn giao vận hành, quản lý điều
chuyển. Không phải luồng thường xuyên.

```
Admin mở Application → "Chuyển cơ sở" → chọn cơ sở nhận → nhập lý do
→ kiểm tra quyền (chỉ admin — Phase 1 chưa có vai trò "trưởng cơ sở")
→ cập nhật applications.owner_branch_id → thêm application_branch_histories
  (from_branch_id=cơ sở cũ, to_branch_id=cơ sở mới, transferred_by, reason bắt buộc)
→ giữ nguyên toàn bộ contact attempts/status histories/appointments/notes
→ commit transaction → cơ sở mới nhìn thấy hồ sơ.
```

Không tạo Application mới khi chuyển cơ sở. Chỉ admin thực hiện (staff không có quyền chuyển
cơ sở trong Phase 1).

### 6.2. Duplicate handling contract

**Case A — khớp mạnh** (cùng số điện thoại chuẩn hóa + họ tên **giống chính xác** sau chuẩn
hóa + ngày sinh khớp nếu cả 2 bên có): tái sử dụng `candidates` đã tồn tại, không tạo candidate
mới. Chuẩn hóa họ tên để so khớp: chuyển thường (lowercase), bỏ dấu tiếng Việt, gộp khoảng
trắng thừa, trim đầu/cuối — so khớp **bằng chuỗi tuyệt đối** sau chuẩn hóa, không dùng thuật
toán khoảng cách chuỗi/ngưỡng tương đồng (đã chốt, thay thế phần "[CẦN CHỐT] ngưỡng tương đồng"
ở bản đặc tả trước).

**Case B — chỉ trùng số điện thoại** (tên sau chuẩn hóa không khớp tuyệt đối, hoặc thiếu dữ
liệu so sánh): **không** tự động gộp. Tạo Candidate mới bình thường, đánh dấu
`applications.needs_duplicate_review = true` để HR kiểm tra thủ công sau
(`duplicate_reviewed_at`, `duplicate_reviewed_by` khi đã xử lý).

**Case C — đã có Application cùng Job** (unique `candidate_id + job_id` bị vi phạm): không
tạo Application mới, không trả lỗi 500. Cập nhật `applications.last_reapplied_at` trên bản ghi
đã tồn tại, hiển thị thông báo "Bạn đã ứng tuyển vị trí này, chúng tôi sẽ liên hệ sớm." Không
tạo bảng "activity" riêng cho sự kiện này trong Phase 1 (giữ tối giản — ADR-019).

### 6.3. Merge Candidate khi cả hai đã có Application cùng Job

Khi merge Candidate B (nguồn) vào Candidate A (đích) và cả hai đều có `applications` cho cùng
`job_id`:

1. **Application được giữ**: **admin chọn thủ công** — hệ thống chỉ đề xuất gợi ý (Application
   có `stage` tiến xa hơn trong thứ tự pipeline `new < contacting < consulted <
   interview_scheduled < interviewed < waiting_start < started`; `closed` không tính là "tiến
   xa"), admin xác nhận hoặc chọn bản ghi khác. Đã chốt thay thế phần "[CẦN CHỐT] tiêu chí tự
   động" ở bản đặc tả trước.
2. **Application còn lại**: chuyển `stage = closed`, `close_reason = duplicate` qua
   `ChangeApplicationStageAction` bình thường (vẫn ghi `application_status_histories`). Bản
   ghi này **không được mở lại** (`closed → new`) sau đó — xem điều kiện ở mục 5.1.
3. **Contact Log, Note, Appointment, Status History, Branch History**: giữ nguyên gắn với
   `application_id` gốc của từng bản ghi — không di chuyển, không xóa. Cả 2 Application (giữ
   và đóng) vẫn hiển thị được lịch sử đầy đủ của mình.
4. **Application khác** (không trùng job) của Candidate B: đổi `candidate_id` sang A.
5. **Candidate B**: `status = merged`, `merged_into_candidate_id = A.id`, `merged_at`,
   `merged_by`.
6. Toàn bộ 5 bước trong **1 transaction**, lock cả 2 candidate trước khi thao tác.
7. **Ai được merge**: chỉ `admin` (staff không có quyền merge candidate).

## 7. Chính sách dữ liệu cá nhân (khung tối thiểu)

Khung tối thiểu áp dụng ngay; các mục đánh dấu **[CẦN CHỐT]** chưa có quyết định từ công ty,
không tự đặt giá trị cụ thể.

- **Thời hạn lưu dữ liệu ứng viên** (candidate + application sau khi `closed`/`started` bao
  lâu thì được coi là đủ điều kiện anonymize): **[CẦN CHỐT]** — chưa có con số từ công ty,
  không tự đặt.
- **Trường hợp anonymize**: (a) theo yêu cầu chủ động của ứng viên (quyền được xóa dữ liệu) —
  luôn thực hiện được, không phụ thuộc thời hạn lưu; (b) tự động theo thời hạn lưu ở trên — chỉ
  triển khai sau khi thời hạn được xác nhận.
- **Ai có quyền anonymize**: đề xuất chỉ `admin` (nhất quán với quyền merge candidate, mục
  6.3), thực hiện qua 1 Action riêng, ghi lịch sử (`candidates.anonymized_at`).
- **Xử lý khi anonymize**: `candidates.status = anonymized`; các trường định danh cá nhân
  (`full_name`, ngày sinh, địa chỉ, toàn bộ `candidate_contacts`) được thay bằng giá trị che
  (mask) hoặc xóa giá trị; `candidates.id`/quan hệ với `applications` giữ nguyên để không phá
  vỡ lịch sử tuyển dụng và báo cáo.
- **Snapshot khi anonymize**: `applications.submission_snapshot`/`job_snapshot` đã lưu tên/số
  điện thoại tại thời điểm nộp — **[CẦN CHỐT]** có anonymize luôn nội dung JSON lịch sử này
  hay giữ nguyên vì mục đích chứng minh lịch sử tuyển dụng (2 mục tiêu — "xóa triệt để" và "giữ
  bằng chứng lịch sử" — mâu thuẫn nhau, cần công ty quyết định ưu tiên cái nào).
- **Contact Log/Note**: không tự động xóa hay che khi candidate được anonymize (đây là lịch sử
  thao tác nội bộ của nhân viên, không phải hồ sơ ứng viên); staff có trách nhiệm không ghi
  thông tin định danh nhạy cảm ngoài phạm vi cần thiết vào `note` (nhắc trong hướng dẫn sử
  dụng, không phải ràng buộc kỹ thuật).
- **Trường dữ liệu cấm thu thập từ form public**: xem `.claude/rules/roles-business-rules.md`
  (CCCD, ảnh CCCD, tài khoản ngân hàng, dân tộc, tôn giáo, tình trạng hôn nhân, hồ sơ sức khỏe
  chi tiết) — nguồn duy nhất, không lặp lại danh sách ở đây.
- **Consent version**: `applications.consent_version` lưu phiên bản chính sách tại thời điểm
  đồng ý; nội dung đầy đủ từng phiên bản hiển thị qua trang tĩnh (`pages`, slug cố định, ví dụ
  `chinh-sach-du-lieu-ca-nhan`) — không cần bảng version riêng trong Phase 1, mỗi lần sửa nội
  dung chính sách thì tăng `consent_version` thủ công.
- **Audit log cần giữ**: đáp ứng đầy đủ bởi các bảng lịch sử append-only đã có
  (`application_status_histories`, `application_contact_attempts`, `application_branch_histories`,
  `job_verifications`, `export_logs`) — không cần bảng `audit_logs` tổng quát (ADR-019).

## 8. Danh sách [CẦN CHỐT] tổng hợp

1. Thời hạn lưu dữ liệu ứng viên trước khi đủ điều kiện anonymize tự động (mục 7).
2. Có anonymize nội dung `submission_snapshot`/`job_snapshot` hay giữ nguyên vì mục đích lịch
   sử (mục 7) — 2 mục tiêu đối lập cần công ty chọn ưu tiên.
3. 5 enum **[đề xuất]** còn tồn đọng từ trước (`jobs.employment_type`, `jobs.close_reason`,
   `pages.status`, `settings.type`, `company_contacts.status`) — xem
   `docs/DATABASE-DICTIONARY.md`.

Đã chốt trong vòng cập nhật này (không còn nằm trong danh sách [CẦN CHỐT]): ngưỡng khớp tên
duplicate case A (mục 6.2 — nay là khớp chính xác sau chuẩn hóa, không dùng ngưỡng tương đồng),
nhóm contact result mở khóa `contacting → consulted` (mục 5.2), cho phép mở lại `closed → new`
có kiểm soát (mục 5.1), tiêu chí chọn Application khi merge (mục 6.3 — admin chọn thủ công),
staff xem Job cơ sở khác ở chế độ read-only (đã xác nhận là mặc định Phase 1), và scope cơ sở
cho lead (không còn áp dụng vì `lead_requests` đã bỏ khỏi Phase 1 — mục 0).

Không migration nào được viết cho tới khi các mục ở đây được công ty xác nhận hoặc chấp nhận
mặc định đề xuất bằng văn bản (cập nhật lại mục tương ứng, xóa khỏi danh sách [CẦN CHỐT]).
