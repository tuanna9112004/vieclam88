# Phạm vi và sản phẩm

> Nguồn ADR theo chủ đề. Không đổi mã ADR; ADR mới phải được thêm vào file chủ đề phù hợp và cập nhật `docs/decisions/INDEX.md`.

<a id="adr-012"></a>

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
`docs/PHASE-1-SCOPE.md`).

<a id="adr-018"></a>

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

<a id="adr-021"></a>

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
phòng" (`.claude/rules/architecture.md`). Trách nhiệm xử lý hồ sơ vẫn được theo dõi đầy đủ qua
audit trail theo từng action đã có (ADR-019): người tạo Contact Log, người đổi trạng thái,
người tạo/hoàn thành Appointment, người thêm Note — không cần cột "người phụ trách" để biết ai
đã làm gì.

<a id="adr-028"></a>

## ADR-028 — Bỏ Candidate Account khỏi schema Phase 1 (`users.role=candidate`, `candidates.user_id`)

**Quyết định:** `users.role` Phase 1 chỉ nhận `staff`/`admin` — bỏ giá trị `candidate`.
`candidates` không có cột `user_id`. Bỏ `users.phone_normalized` (mục đích duy nhất trước đây
là đăng nhập cho candidate). `users.email` chuyển thành bắt buộc (NOT NULL) vì mọi user Phase 1
đều cần đăng nhập `/hr/dang-nhap`. Toàn bộ route Candidate Account (`/dang-ky`, `/dang-nhap` cho
candidate, `/quen-mat-khau`, `/tai-khoan`, `/tai-khoan/da-ung-tuyen`) bỏ khỏi Phase 1.

**Lý do:** Yêu cầu nghiệp vụ cập nhật xác nhận ứng viên Phase 1 luôn là guest, không có tính
năng đăng nhập nào cho ứng viên. Giữ `role=candidate`/`candidates.user_id` trong schema mà
không có route/action nào ghi dữ liệu vào đó tạo ra cột/giá trị enum dự phòng không ai dùng —
vi phạm nguyên tắc "không tạo cột/bảng dự phòng" (`.claude/rules/architecture.md`). Khi Phase 2
triển khai Candidate Account, thêm lại bằng migration mới, không giữ trước "phòng khi cần".

<a id="adr-029"></a>

## ADR-029 — Bỏ `applications.referral_code` và `actor_type=import` khỏi schema Phase 1

**Quyết định:** `applications` không có cột `referral_code` trong Phase 1.
`application_status_histories.actor_type` chỉ nhận `user`/`system` — bỏ giá trị `import`.

**Lý do:** Cả hai đều là cột/giá trị chuẩn bị trước cho tính năng chưa tồn tại ở Phase 1 (module
cộng tác viên — Phase 2; tính năng import dữ liệu hàng loạt — không nằm trong 6 luồng cốt lõi).
Rà soát schema Phase 1 theo nguyên tắc "không giữ cột dự phòng chỉ vì sau này có thể dùng" —
thêm lại bằng migration mới khi tính năng tương ứng thực sự được duyệt xây dựng.

<a id="adr-042"></a>

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

<a id="adr-043"></a>

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

<a id="adr-049"></a>

## ADR-049 — Phân loại 3 nhóm blocker (Migration / Go-live / Phase 2 decision); tách khỏi điều kiện chuyển Giai đoạn 1

**Status:** Điểm 1 dưới đây ("5 enum đề xuất... migration blocker duy nhất") bị thay thế bởi
ADR-055 — 5 enum không còn migration blocker sau khi chuyển sang `varchar` + PHP backed enum.
Khung phân loại 3 nhóm (migration/go-live/Phase 2) vẫn là quyết định hiện hành, chỉ nội dung mục
1 lỗi thời; danh sách blocker hiện hành đầy đủ nhất: `ROADMAP.md` mục "Phân loại blocker".

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

<a id="adr-057"></a>

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

<a id="adr-072"></a>

## ADR-072 — Job expired behavior chính thức (`effective_status = expired`)

**Quyết định:** Khi `jobs.status = published` và `jobs.expires_at < now()`, định nghĩa giá trị
tính toán ở tầng ứng dụng **`effective_status = expired`** (không phải giá trị cột DB — `jobs.status`
vẫn giữ nguyên `published` cho tới khi có hành động tường minh đổi sang trạng thái khác; Phase 1
**không** tự động chuyển `status` khi hết hạn, vì auto-close không thuộc Phase 1 — nhất quán với
quyết định không auto-pause, ADR-042/049). Áp dụng đúng quy tắc hiển thị đã có cho Job
`closed`/`paused` (mục 2.1, ADR-043) cho Job có `effective_status = expired`:

- Không xuất hiện trong danh sách/tìm kiếm/sitemap Job đang hoạt động (đã đúng — mục "Luồng 2"
  hiện có đã loại Job hết hạn khỏi listing).
- Không nhận Application — server từ chối submit trực tiếp (bổ sung tường minh vào điều kiện
  kiểm tra "Job còn active" ở Luồng 3, vốn trước đây chỉ kiểm tra `status = published` mà chưa
  nói rõ có kiểm tra `expires_at` hay không).
- URL chi tiết vẫn `200` (không `404`), hiển thị trạng thái "Đã hết hạn tuyển" (khác thông điệp
  với `closed`/`paused` để không gây hiểu nhầm là bị chủ động đóng/tạm dừng), ẩn nút "Ứng tuyển
  ngay", giữ CTA Gọi/Zalo.
- Dashboard/danh sách `/hr/viec-lam` hiển thị rõ các Job `published` đã hết hạn cần xử lý (tái sử
  dụng cột `expires_at` đã có để tính, không thêm cột).
- Không tự động đổi `jobs.status` — Staff/Admin chủ động `pause`/`close` qua action đã có nếu
  muốn phản ánh đúng trạng thái DB.

**Lý do:** Trước rà soát này, hành vi khi Job hết hạn (`published` + `expires_at` đã qua) chỉ
được suy ra gián tiếp từ điều kiện listing ("chưa `expires_at`"), chưa có quy tắc tường minh cho
trang chi tiết hay việc submit Application trực tiếp (bỏ qua UI) — một request POST thẳng vào
`applications.store` cho Job hết hạn nhưng `status` DB vẫn `published` có nguy cơ được chấp nhận
nếu Service chỉ kiểm tra `status = published` mà quên điều kiện `expires_at`. `effective_status`
tính ở tầng ứng dụng (không lưu DB) giữ đúng nguyên tắc không tự thêm auto-close ngoài phạm vi.
