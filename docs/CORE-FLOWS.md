# Core Flows — vieclam88 (Phase 1)

Nguồn sự thật duy nhất cho 6 luồng nghiệp vụ cốt lõi (CRITICAL BUSINESS FLOWS). Database
(`docs/ERD.md`, `docs/DATABASE-DICTIONARY.md`), route (`docs/ROUTE-MAP.md`), rule
(`.claude/rules/*`), acceptance criteria (`docs/ACCEPTANCE-CRITERIA.md`) và ADR
(`docs/DECISIONS.md`) phải khớp file này. Nếu phát hiện mâu thuẫn, dừng lại và cập nhật file
này trước, không tự chọn diễn giải khác ở nơi khác.

Một chức năng chỉ tính là hoàn thành khi luồng tương ứng chạy đúng từ đầu đến cuối, có test.

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

- Tạo Lead trực tiếp từ cuộc gọi hoặc từ tin nhắn Zalo.
- Chuyển đổi Lead (`lead_requests`) thành Application — bất kể lead đến từ kênh nào, kể cả
  form "yêu cầu tư vấn" trên website. `lead_requests` Phase 1 chỉ là nơi ghi nhận số điện
  thoại cần tư vấn để nhân viên gọi lại thủ công; không có action chuyển đổi tự động thành
  candidate/application. Đây là thay đổi so với tài liệu trước (xem ADR-018).
- Tích hợp Zalo API, tự động gọi/gửi tin nhắn.
- Tự động phân công hồ sơ, round-robin.
- CRM đa kênh, AI matching, cộng tác viên/hoa hồng.
- Candidate Account phức tạp, workflow phê duyệt nhiều tầng.

Nút Gọi/Zalo trên website chỉ mở kênh liên lạc (`tel:`/`https://zalo.me/...`); không tự tạo
bản ghi nào trong hệ thống.

## 1. Luồng 1 — Tạo và xuất bản việc làm

```
Công ty/nhà máy gửi nhu cầu (điện thoại/Zalo/Excel/gặp trực tiếp — ngoài hệ thống)
→ nhân viên đăng nhập HR → tạo Job (status=draft)
→ chọn company → chọn company_location(s) → chọn owner_branch_id (cơ sở nội bộ phụ trách)
→ chọn company_contact_id (nếu có, để liên hệ nội bộ) → nhập nội dung/lương/ca/quyền lợi
→ kiểm tra điều kiện publish → người có quyền publish → Job status=published.
```

Điều kiện publish (Service kiểm tra, không chỉ ẩn nút ở view):

1. `companies.status = active`, chưa `deleted_at`.
2. `jobs.title`, `jobs.job_description` không rỗng.
3. Có ít nhất một `job_locations`, đúng một `is_primary = true`.
4. Mọi `job_locations.company_location_id` thuộc đúng `jobs.company_id`.
5. `jobs.owner_branch_id` khác null (bắt buộc trước publish, dù cột DB cho phép null ở
   draft — xem `docs/DATABASE-DICTIONARY.md` mục `jobs`).
6. Có contact công khai hợp lệ — xem "Quy tắc contact CTA" bên dưới.
7. `jobs.status` không phải `closed`/đã `deleted_at`.
8. Các trường bắt buộc theo nghiệp vụ (lương, ca...) đã đầy đủ theo Form Request.

Trạng thái: `draft → published → paused → closed` (đã chốt, xem ADR-008 về không tái sử dụng
job cũ). Không thêm workflow phê duyệt nhiều bước trừ khi có ADR mới.

**Quy tắc contact CTA (Gọi/Zalo) trên Job:**

- Ưu tiên 1: `company_contacts` có `is_public = true` gắn với `jobs.company_contact_id`.
- Ưu tiên 2 (fallback, và điều kiện publish tối thiểu): `branches.phone` / `branches.zalo`
  của `jobs.owner_branch_id`.
- Không hiển thị `company_contacts` chưa đánh dấu `is_public`. Vì mọi Job đều có
  `owner_branch_id` trước khi publish, điều kiện "có contact công khai hợp lệ" luôn thỏa mãn
  tối thiểu qua branch, không phụ thuộc công ty khách hàng có cung cấp contact công khai hay
  không.

## 2. Luồng 2 — Ứng viên tìm và chọn việc

Website chỉ hiển thị Job khi `status = published`, chưa `expires_at` (hoặc null), chưa
`deleted_at`. Job `draft`/`paused`/`closed` không xuất hiện ở danh sách/tìm kiếm công khai
(job đã `closed` vẫn giữ URL chi tiết — xem `.claude/rules/security-seo-testing.md`).

CTA trên trang chi tiết: **Ứng tuyển ngay** (luồng 3), **Gọi điện**, **Nhắn Zalo** (mở kênh
thủ công, không tạo bản ghi). Chỉ "Ứng tuyển ngay" tạo dữ liệu trong hệ thống.

## 3. Luồng 3 — Ứng viên gửi form ứng tuyển

```
Bấm "Ứng tuyển ngay" → điền form → đồng ý chính sách dữ liệu → gửi
→ server đọc lại Job từ DB (không tin dữ liệu client gửi kèm) → kiểm tra Job còn active
→ validate (Form Request) → chuẩn hóa họ tên/số điện thoại
→ kiểm tra trùng Candidate (mục 6) → kiểm tra Application cùng Job đã tồn tại (case C, mục 6)
→ tạo hoặc tái sử dụng Candidate → tạo Application, copy owner_branch_id từ Job
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

**Bắt buộc kỹ thuật:** server-side validation, CSRF, rate limit, honeypot/chống spam, chống
double-click và chống refresh gửi lại (idempotent theo phía UI), transaction, và **client
không được tự gửi `stage`, `owner_branch_id` hoặc `assigned_to`** — 3 trường này luôn do
server tính/copy, không đọc từ request (mass-assignment allowlist ở Form Request).

**Chống 2 request đồng thời tạo 2 Application:** cơ chế đảm bảo cuối cùng là unique constraint
DB `applications(candidate_id, job_id)` — request thua sẽ bắt exception unique violation và xử
lý như case C (mục 6), không trả lỗi 500. Không cần lock đặc biệt ở tầng Candidate cho trường
hợp 2 candidate mới trùng số điện thoại được tạo gần như đồng thời (chấp nhận được — bắt bởi
duplicate review case B, xem mục 6).

## 4. Luồng 4 — Nhân viên cơ sở xử lý hồ sơ

Application luôn thuộc đúng một `owner_branch_id` (copy từ Job lúc tạo, không suy ra động qua
Job — Job có thể đổi cơ sở sau này mà không ảnh hưởng Application đã tạo).

- Staff chỉ thấy Application có `owner_branch_id = users.branch_id` của mình.
- Admin thấy tất cả cơ sở.
- Phase 1 **không tự động gán** Application cho nhân viên cụ thể trong cùng cơ sở. Staff có
  thể tự nhận (`assigned_to`) hồ sơ chưa gán; không bắt buộc phải nhận mới được xử lý. Admin
  có thể gán/gán lại. Mọi thay đổi `assigned_to` tạo 1 `application_assignment_histories`.
- `assigned_to` chỉ được gán cho `users.role = staff` có `branch_id` trùng
  `applications.owner_branch_id` (không gán chéo cơ sở qua `assigned_to`; chuyển cơ sở dùng
  luồng 6).

Danh sách hồ sơ theo cơ sở phải hiển thị tối thiểu: trạng thái hiện tại, người liên hệ gần
nhất, thời gian liên hệ gần nhất, kết quả liên hệ gần nhất, lịch gọi lại/phỏng vấn gần nhất,
thời gian cập nhật gần nhất — mục đích giảm 2 nhân viên gọi trùng dù chưa có phân công cứng.

Mỗi thao tác (contact attempt, đổi stage, appointment, note, assignment, transfer) ghi rõ
người thực hiện, thời gian, nội dung, trạng thái trước/sau nếu có — được đáp ứng bởi các bảng
lịch sử append-only tương ứng (không cần bảng "audit log" tổng quát riêng — xem ADR-019 về
phân biệt "audit trail theo từng action" và "audit log tổng quát" ngoài phạm vi Phase 1).

## 5. Luồng 5 — Cập nhật trạng thái và kết quả

### 5.1. Transition matrix chính thức

| From stage | To stage | Điều kiện bắt buộc | Ai được thực hiện |
|---|---|---|---|
| (mới) | `new` | Application vừa được tạo bởi Luồng 3 | Hệ thống (Action Apply) |
| `new` | `contacting` | Có ít nhất 1 `application_contact_attempts` cho application này | Staff/admin cùng cơ sở |
| `contacting` | `consulted` | Có `application_contact_attempts.result` thuộc nhóm "đã liên hệ được" — xem 5.2 [CẦN CHỐT] | Staff/admin cùng cơ sở |
| `consulted` | `interview_scheduled` | Có `application_appointments(type=interview, status=scheduled)` | Staff/admin cùng cơ sở |
| `interview_scheduled` | `interviewed` | Appointment interview tương ứng có `status=completed` | Staff/admin cùng cơ sở |
| `interviewed` | `waiting_start` | `applications.expected_start_at` khác null | Staff/admin cùng cơ sở |
| `waiting_start` | `started` | `applications.started_at` khác null | Staff/admin cùng cơ sở |
| bất kỳ trạng thái nào **trừ** `closed`/`started` | `closed` | `applications.close_reason` khác null (Form Request bắt buộc) | Staff/admin cùng cơ sở, hoặc admin |
| `closed` | (bất kỳ) | **Không cho phép** mở lại trong Phase 1 — [CẦN CHỐT] nếu công ty cần "mở lại hồ sơ đã đóng" | — |
| `started` | (bất kỳ) | Trạng thái cuối (thành công); không có transition tiếp trong Phase 1 | — |

Quy tắc chung:

- Mọi transition đi qua `ChangeApplicationStageAction` (không sửa cột `stage` trực tiếp từ
  controller). Action xử lý: authorization (đúng cơ sở), validate transition theo bảng trên,
  kiểm tra dữ liệu bắt buộc, khóa row (`lockForUpdate`) chống concurrent update, ghi
  `application_status_histories`, cập nhật `applications`, trong 1 transaction.
- Appointment bị `cancelled`/`no_show` **không** tự động lùi hoặc đổi stage. Stage giữ nguyên
  cho tới khi staff chủ động tạo appointment mới hoặc chuyển `closed` — nhất quán với nguyên
  tắc "contact result không tự đổi stage" áp dụng tương tự cho appointment.
- Contact result và Application stage là 2 khái niệm khác nhau (ADR-009): ghi nhận
  `application_contact_attempts` không bao giờ tự động đổi `applications.stage`.

### 5.2. Contact result tối thiểu

`không_nghe_máy`, `máy_bận`, `sai_số`, `đã_liên_hệ`, `đã_tư_vấn`, `hẹn_gọi_lại`,
`đồng_ý_phỏng_vấn`, `từ_chối`, `không_phù_hợp` (map vào
`application_contact_attempts.result` — tên cột enum kỹ thuật xem
`docs/DATABASE-DICTIONARY.md`).

**[CẦN CHỐT]** Nhóm kết quả nào được coi là "đã liên hệ được" để mở khóa
`contacting → consulted`? Đề xuất mặc định (cần công ty xác nhận): `đã_liên_hệ`,
`đã_tư_vấn`, `đồng_ý_phỏng_vấn` mở khóa; `không_nghe_máy`, `máy_bận`, `sai_số`,
`hẹn_gọi_lại` không mở khóa (chưa nói chuyện được thật sự). Kết quả `từ_chối`/
`không_phù_hợp` không tự động đóng hồ sơ — staff phải chủ động chuyển `closed`.

### 5.3. Appointment (lịch gọi lại / phỏng vấn)

Bảng `application_appointments` (mới — xem `docs/DATABASE-DICTIONARY.md`):

- `type`: `callback`, `interview`.
- `status`: `scheduled`, `completed`, `cancelled`, `no_show`.
- Hẹn gọi lại bắt buộc có `scheduled_at`. Phỏng vấn hoàn thành phải cập nhật `outcome`/`note`
  và `status = completed`, `completed_by`, `completed_at` trước khi Application được phép
  chuyển `interview_scheduled → interviewed` (xem 5.1).

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

**Case A — khớp mạnh** (cùng số điện thoại chuẩn hóa + tên tương đồng mạnh + ngày sinh khớp
nếu cả 2 bên có): tái sử dụng `candidates` đã tồn tại, không tạo candidate mới.

> **[CẦN CHỐT]** Ngưỡng "tên tương đồng mạnh" (so khớp chính xác sau chuẩn hóa bỏ dấu/hoa
> thường, hay dùng thuật toán khoảng cách chuỗi và ngưỡng cụ thể) chưa được đặc tả — cần xác
> nhận trước khi code service phát hiện trùng.

**Case B — chỉ trùng số điện thoại** (tên/ngày sinh không đủ khớp hoặc thiếu dữ liệu so
sánh): **không** tự động gộp. Tạo Candidate mới bình thường, đánh dấu
`applications.needs_duplicate_review = true` để HR kiểm tra thủ công sau
(`duplicate_reviewed_at`, `duplicate_reviewed_by` khi đã xử lý).

**Case C — đã có Application cùng Job** (unique `candidate_id + job_id` bị vi phạm): không
tạo Application mới, không trả lỗi 500. Cập nhật `applications.last_reapplied_at` trên bản ghi
đã tồn tại, hiển thị thông báo "Bạn đã ứng tuyển vị trí này, chúng tôi sẽ liên hệ sớm." Không
tạo bảng "activity" riêng cho sự kiện này trong Phase 1 (giữ tối giản — xem ADR-019 về audit
log tổng quát nằm ngoài phạm vi).

### 6.3. Merge Candidate khi cả hai đã có Application cùng Job

Khi merge Candidate B (nguồn) vào Candidate A (đích) và cả hai đều có `applications` cho cùng
`job_id`:

1. **Application được giữ**: bản ghi có `stage` tiến xa hơn trong thứ tự pipeline
   (`new < contacting < consulted < interview_scheduled < interviewed < waiting_start <
   started`; `closed` không tính là "tiến xa"). Nếu bằng `stage`, giữ bản ghi có `created_at`
   nhỏ hơn (tạo trước).
   > **[CẦN CHỐT]** nếu công ty muốn tiêu chí khác (VD: để staff tự chọn thủ công khi merge
   > thay vì quy tắc tự động).
2. **Application còn lại**: chuyển `stage = closed`, `close_reason = duplicate` qua
   `ChangeApplicationStageAction` bình thường (vẫn ghi `application_status_histories`).
3. **Contact Log, Note, Appointment, Status History, Branch History**: giữ nguyên gắn với
   `application_id` gốc của từng bản ghi — không di chuyển, không xóa. Cả 2 Application (giữ
   và đóng) vẫn hiển thị được lịch sử đầy đủ của mình.
4. **Application khác** (không trùng job) của Candidate B: đổi `candidate_id` sang A.
5. **Candidate B**: `status = merged`, `merged_into_candidate_id = A.id`, `merged_at`,
   `merged_by`.
6. Toàn bộ 5 bước trong **1 transaction**, lock cả 2 candidate trước khi thao tác.
7. **Ai được merge**: chỉ `admin` (staff không có quyền merge candidate).

## 7. Danh sách [CẦN CHỐT] tổng hợp

1. Ngưỡng "tên tương đồng mạnh" cho duplicate case A (mục 6.2).
2. Nhóm contact result nào mở khóa `contacting → consulted` (mục 5.2).
3. Có cho phép mở lại Application đã `closed` không (mục 5.1)?
4. Tiêu chí chọn Application giữ lại khi merge nếu công ty muốn khác quy tắc mặc định
   (mục 6.3).
5. Staff có được xem (read-only) Job của cơ sở khác hay chỉ xem Job cơ sở mình? Mặc định đề
   xuất: staff xem tất cả Job (để nắm tổng quan thị trường), chỉ sửa/publish Job cơ sở mình.
6. `lead_requests` ("yêu cầu tư vấn") có cần gắn cơ sở phụ trách để scope hiển thị cho staff
   như Application không? Phase 1 hiện chưa có model cơ sở cho lead vì lead có thể chưa gắn
   Job. Chưa quyết — giữ nguyên hành vi cũ (staff Phase 1 vẫn xem toàn bộ `lead_requests`,
   khác với `applications` đã scope theo cơ sở) cho tới khi có xác nhận.
7. 5 enum **[đề xuất]** còn tồn đọng từ trước (`jobs.employment_type`, `jobs.close_reason`,
   `pages.status`, `settings.type`, `company_contacts.status`) — xem
   `docs/DATABASE-DICTIONARY.md`.

Không migration nào được viết cho tới khi các mục ở đây được công ty xác nhận hoặc chấp nhận
mặc định đề xuất bằng văn bản (cập nhật lại mục tương ứng, xóa khỏi danh sách [CẦN CHỐT]).
