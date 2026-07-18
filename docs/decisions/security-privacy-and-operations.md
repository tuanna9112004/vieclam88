# Bảo mật, quyền riêng tư và vận hành

> Nguồn ADR theo chủ đề. Không đổi mã ADR; ADR mới phải được thêm vào file chủ đề phù hợp và cập nhật `docs/decisions/INDEX.md`.

<a id="adr-013"></a>

## ADR-013 — CSV cho xuất dữ liệu, ghi log mỗi lần xuất

**Quyết định:** Xuất danh sách ứng viên dùng CSV (không dùng Excel binary). Mỗi lần xuất ghi
1 bản ghi vào `export_logs` (người xuất, thời gian, số dòng, điều kiện lọc). Không lưu file
CSV đã xuất lâu dài trên server.

**Lý do:** CSV đơn giản, không cần thư viện nặng như PhpSpreadsheet cho MVP. Ghi log xuất dữ
liệu là yêu cầu bảo mật tối thiểu khi dữ liệu chứa thông tin cá nhân của ứng viên.

<a id="adr-019"></a>

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

**Lý do:** `docs/PHASE-1-SCOPE.md` đã liệt kê "full audit log" là ngoài phạm vi
Phase 1. Yêu cầu "Action phải ghi Audit log" trong đặc tả luồng nghiệp vụ mới không mâu thuẫn
với giới hạn này — nó được thỏa mãn bởi audit trail per-action đã tồn tại, không cần thêm hạ
tầng audit tổng quát (dual-write, listener toàn cục) mà Phase 1 chưa cần.

<a id="adr-020"></a>

## ADR-020 — Staff chỉ xem Application thuộc cơ sở phụ trách (thay vì toàn bộ)

**Status:** Partially superseded by ADR-021 — phần "tự nhận hồ sơ (claim)" ở quyết định này bị
thay thế hoàn toàn (Phase 1 bỏ hẳn khái niệm claim/assign, không chỉ bỏ phân công cứng). Phần
"staff chỉ xem Application thuộc cơ sở mình, admin không giới hạn" vẫn là quyết định hiện hành
— diễn đạt chuẩn: "Staff thuộc đúng cơ sở hoặc Admin" (không dùng "staff/admin cùng cơ sở").

**Quyết định:** Staff chỉ truy cập được `applications` có `owner_branch_id` trùng
`users.branch_id` của mình; truy cập URL của Application thuộc cơ sở khác trả về 403. Admin
không bị giới hạn cơ sở. Đây là thay đổi so với giả định trước đó ("Staff Phase 1 xem toàn bộ
application") trong `docs/CORE-FLOWS.md`.

**Lý do:** Khi hệ thống có khái niệm cơ sở nội bộ (ADR-015), để staff xem toàn bộ hồ sơ của mọi
cơ sở sẽ vô hiệu hóa mục đích phân vùng dữ liệu theo cơ sở và có nguy cơ lộ dữ liệu ứng viên
giữa các cơ sở không liên quan. Việc "tự nhận hồ sơ" (claim) trong phạm vi cơ sở của mình vẫn
giữ nguyên, không cần phân công cứng.

<a id="adr-027"></a>

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

<a id="adr-036"></a>

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

<a id="adr-037"></a>

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

<a id="adr-056"></a>

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

<a id="adr-067"></a>

## ADR-067 — Password-first-change flow đầy đủ và bổ sung route HR admin còn thiếu

**Quyết định:** Chốt luồng đầy đủ dựa trên `users.password_changed_at` đã có (ADR-050):

1. Admin tạo Staff (`hr.staff.store`) → mật khẩu tạm do Admin đặt (không sinh ngẫu nhiên gửi
   email tự động — Phase 1 không có hạ tầng gửi mail, Admin truyền đạt thủ công) →
   `password_changed_at = null`.
2. Middleware `EnsurePasswordChanged` (đăng ký cho mọi route trong `role:staff,admin` ngoại trừ
   chính route đổi mật khẩu và `hr.logout`): nếu `auth()->user()->password_changed_at === null`,
   redirect toàn bộ request tới `hr.password.change` — Staff/Admin **không** vào được
   Dashboard/bất kỳ module HR nào trước khi đổi mật khẩu.
3. `GET /hr/doi-mat-khau` (`hr.password.change`) hiển thị form; `PUT /hr/doi-mat-khau`
   (`hr.password.update`) nhận mật khẩu mới, validate qua Form Request (`confirmed`, độ dài tối
   thiểu theo `config/validation` mặc định Laravel), cập nhật `password` (hash),
   `password_changed_at = now()`, và **regenerate session** (`session()->regenerate()`, chống
   session fixation) trong cùng request.
4. Không log giá trị mật khẩu (cũ lẫn mới) ở bất kỳ tầng nào (nhất quán ADR-050).
5. **Admin reset mật khẩu Staff** (`POST /hr/nhan-vien/{staff}/dat-lai-mat-khau`,
   `hr.staff.reset-password`, admin-only): đặt mật khẩu tạm mới (Admin nhập, không tự sinh) →
   `password_changed_at = null` (bắt buộc đổi lại ở lần đăng nhập kế tiếp) → không hard-invalidate
   session hiện tại của Staff đó ở Phase 1 (Laravel không có cơ chế "kill session của user khác"
   tối giản sẵn có mà không cần thêm hạ tầng session tra cứu theo user — nếu công ty cần bắt buộc
   đăng xuất ngay lập tức, đó là bổ sung Phase 2; Phase 1 chấp nhận độ trễ tới khi session Staff
   đó tự hết hạn hoặc họ tự logout).
6. **Staff lock/unlock** (`POST /hr/nhan-vien/{staff}/khoa` → `hr.staff.lock`,
   `POST /hr/nhan-vien/{staff}/mo-khoa` → `hr.staff.unlock`, admin-only): đổi `users.status`
   giữa `active`/`locked` (đã có ở dictionary, chưa có route) — không hard-delete.

Bổ sung các route HR admin còn thiếu (thay thế câu "resource routes tương tự" ở `docs/ROUTE-MAP.md`
bằng bảng route cụ thể): `hr.staff.*` (index/create/store/edit/update/lock/unlock/reset-password),
`hr.pages.*`, `hr.faqs.*`, `hr.settings.*` (index/update), `hr.password.change/update`,
`hr.branches.restore` (còn thiếu — xem ADR-068), `hr.candidates.anonymize`,
`hr.duplicate-reviews.*` (ADR-062).

**Lý do:** `password_changed_at` đã tồn tại từ ADR-050 nhưng chưa có route/middleware/action cụ
thể nào tiêu thụ nó — cột tồn tại mà không có luồng ghi/đọc đầy đủ là dạng "cột dự phòng nửa
vời". `docs/ROUTE-MAP.md` mục "HR admin" bản cũ chỉ có 1 câu mô tả chung "resource routes tương
tự" — đúng loại câu mơ hồ mà vòng rà soát này yêu cầu loại bỏ, không đủ cụ thể để tạo route thật.

<a id="adr-071"></a>

## ADR-071 — Chính sách PII trong nội dung free-text (rà soát, go-live blocker)

**Quyết định:** Mở rộng chính sách dữ liệu cá nhân (mục 7) sang các trường free-text ngoài
`applications.submission_snapshot` — trước đây mục 7 chỉ xét snapshot, chưa xét ghi chú nội bộ:

1. **Danh sách free-text có thể chứa PII**: `application_notes.content`,
   `application_contact_attempts.note`, `application_appointments.note`/`outcome`,
   `candidates.experience_summary`, `applications.submission_snapshot` (đã có chính sách riêng,
   mục 7.2), các JSON snapshot khác (`job_snapshot` — không chứa PII candidate, mục 7.1).
2. **Quy tắc phòng ngừa** (không phải ràng buộc kỹ thuật, đã có từ mục 7.3): nhân viên không ghi
   CCCD/thông tin định danh nhạy cảm ngoài phạm vi cần thiết vào các trường trên — nhắc trong
   hướng dẫn sử dụng nội bộ.
3. **Không tự động redact bất kỳ trường nào ở trên** khi Candidate anonymize (giữ nguyên quyết
   định đã có ở mục 7.3 — dò tìm PII trong văn bản tự do bằng regex/NLP không đủ tin cậy để tự
   động hóa an toàn).
4. **Review thủ công**: nếu Admin phát hiện một ghi chú cụ thể chứa PII không cần thiết khi xử lý
   yêu cầu anonymize của một Candidate, Admin **có thể** sửa/xóa nội dung note đó qua route đã có
   (`hr.applications.notes.update`/`destroy`, quyền owner/admin) — đây là hành động thủ công theo
   từng trường hợp, không phải quy trình tự động, không thuộc phạm vi thêm mới.
5. **Dữ liệu giữ lại vì audit**: `application_contact_attempts`/`application_appointments` là
   bảng lịch sử append-only (hoặc gần append-only) — không sửa/xóa nội dung sau khi tạo (khác
   `application_notes`, vốn cho phép owner/admin sửa) — giữ nguyên nhất quán với "lịch sử chỉ
   thêm, không ghi đè".
6. **Người xem hồ sơ đã anonymize**: không đổi quyền xem hiện có — Staff thuộc đúng cơ sở hoặc
   Admin vẫn xem được Application/Note/Contact Log như bình thường (anonymize chỉ mask dữ liệu
   định danh của Candidate, không tạo một mức quyền xem mới).
7. **Lịch sử chỉnh sửa Note**: Phase 1 **không** lưu lịch sử đầy đủ các lần sửa `application_notes.content`
   — chỉ giữ `edited_at` (mốc sửa gần nhất, đã có ở dictionary). Quyết định có chủ đích: thêm bảng
   lịch sử sửa Note là một module mới ngoài 6 luồng cốt lõi, trong khi Note vốn là ghi chú làm
   việc (working note), không phải hồ sơ pháp lý cần audit trail đầy đủ như trạng thái
   Application/Job.

Toàn bộ mục 1–2 (danh sách + quy tắc phòng ngừa) là **quyết định kiến trúc, khóa được ngay**.
Việc có xây thêm cơ chế redact/kiểm duyệt nội dung free-text mạnh hơn (tự động hoặc bán tự động)
là **go-live/chính sách vận hành, [CẦN CHỐT VỚI CÔNG TY]** nếu công ty yêu cầu mức kiểm soát cao
hơn — không ảnh hưởng schema hiện tại (các cột free-text này đã tồn tại đúng kiểu dữ liệu cần
thiết, không cần đổi), nên **không phải migration blocker**.

**Lý do:** Mục 7 (chính sách dữ liệu cá nhân) trước đây chỉ xét `submission_snapshot`/`job_snapshot`
— giả định "chỉ mask Candidate và Application là đủ" chưa được kiểm chứng khi Contact Log/Note/
Appointment cũng là free-text nhân viên tự gõ, có khả năng vô tình chứa PII. Rà soát này không
thêm ràng buộc kỹ thuật mới (đã có quyết định "không tự động xử lý" từ ADR-027/mục 7.3) mà chỉ
liệt kê tường minh phạm vi rủi ro và xác nhận lại các quyết định liên quan đang nhất quán, tránh
để trống một khoảng "chưa xét tới" trong chính sách.

<a id="adr-077"></a>

## ADR-077 — Tài khoản bị khóa mất quyền ở request kế tiếp

**Quyết định:** Mọi route HR sau auth chạy `EnsureUserIsActive` trước middleware đổi mật khẩu.
Nếu `users.status != active`, invalidate session, logout và từ chối/redirect phù hợp.
