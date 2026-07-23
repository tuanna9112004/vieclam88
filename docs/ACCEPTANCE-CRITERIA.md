# Acceptance Criteria — vieclam88 (Phase 1)

Tiêu chí nghiệm thu tối thiểu bằng Feature Test (PHPUnit). Mỗi mục dưới đây phải có ít nhất
1 test tương ứng trước khi tính là hoàn thành. Không tự thêm tiêu chí ngoài phạm vi Phase 1
(`docs/PHASE-1-SCOPE.md`), không bỏ bớt tiêu chí đã liệt kê. 6 luồng nghiệp vụ mà
các tiêu chí này xác nhận: `docs/CORE-FLOWS.md`. Phase 1 không có Lead, không có
assignment/claim, không có Candidate Account, không có Favorites (ADR-021, ADR-028) — không
viết test cho các phần này.

## 1. Company và job

### 1.1. Company/Location/Job Quick Create (ADR-045, ADR-046)

- [ ] Chỉ nhập `name` vẫn tạo được `companies` (không cần mã số thuế, trụ sở, website, logo,
      mô tả doanh nghiệp chi tiết).
- [ ] Trường chưa xác định của `companies`/`company_locations` lưu `NULL` — không lưu chuỗi
      `"Chưa xác định"` hay placeholder văn bản nào khác vào cột dữ liệu.
- [ ] Chỉ nhập `name` vẫn tạo được `company_locations` (`administrative_unit_id` và
      `address_detail` chấp nhận `NULL`).
- [ ] Tạo được Job draft khi Company chỉ có tên và Location chưa có tỉnh/thành lẫn địa chỉ chi
      tiết (`docs/CORE-FLOWS.md` mục 1.0).
- [ ] Tạo/lưu được Job draft khi `job_description = NULL` (cột nullable — ADR-060).
- [ ] Tạo Job (kể cả `draft`) thiếu `owner_branch_id` bị từ chối ở tầng Service/Form Request —
      Staff luôn được tự động gán, Admin bắt buộc chọn tường minh, không có trạng thái "Job
      chưa có cơ sở phụ trách" (ADR-046).
- [ ] Tạo/sửa `company_locations` với `industrial_park_id` khác null nhưng
      `administrative_unit_id` khác tỉnh của KCN đó bị từ chối (ADR-052).
- [ ] Tạo/sửa `company_locations` với `industrial_park_id` khác null và
      `administrative_unit_id` khớp đúng tỉnh của KCN → thành công.
- [ ] `industrial_park_id = null` không bị áp dụng validation khớp tỉnh (Quick Create vẫn hoạt
      động bình thường, ADR-045 không đổi).

### 1.2. Job publish/transition

- [ ] Admin tạo, sửa, soft delete, khôi phục được `companies`.
- [ ] Admin tạo, sửa, publish, pause, close được `jobs`, đúng theo Job transition matrix
      (`docs/CORE-FLOWS.md` mục 1: `draft→published`, `published→paused`, `paused→published`,
      `published→closed`, `paused→closed`).
- [ ] Chuyển trạng thái Job ngoài transition matrix (vd `draft → closed`, `closed → published`)
      bị từ chối.
- [ ] Job `status = published` hiển thị ở trang công khai.
- [ ] Job `draft`, `paused`, `closed`, hết `expires_at`, hoặc đã soft delete **không** hiển
      thị ở trang công khai.
- [ ] Job có thể gắn nhiều `job_locations`.
- [ ] Một job chỉ có đúng 1 `job_locations.is_primary = true`; 2 request đồng thời cùng đặt
      `is_primary=true` cho 2 location khác nhau của cùng 1 job chỉ có đúng 1 request thành
      công (unique constraint trên cột generated).
- [ ] Không publish được Job nếu cơ sở phụ trách không `active` (đã `inactive` hoặc
      `deleted_at`).
- [ ] Không publish được Job nếu cơ sở phụ trách không có `phone` và không có `zalo`.
- [ ] Không publish được Job nếu location `is_primary=true` chưa có `administrative_unit_id`
      lẫn `address_detail` (địa điểm chưa đủ rõ — `docs/CORE-FLOWS.md` mục 1.2 `PUB-LOCATION-CLEAR`).
- [ ] Không publish được Job (`draft→published`/`paused→published`) nếu **bản ghi
      `job_verifications` mới nhất** không phải `still_open` — Staff bị từ chối; Admin publish
      được nhưng thiếu `job_status_histories.reason` thì bị từ chối (`docs/CORE-FLOWS.md` mục
      1.2 điều kiện 21, mục 1.3, ADR-058, ADR-060).
- [ ] Job có bản ghi `still_open` **cũ** (không phải mới nhất), sau đó có bản ghi
      `needs_review`/`paused`/`closed` mới hơn → **không** publish được — bản ghi `still_open` cũ
      không được dùng làm bằng chứng vượt qua điều kiện xác minh (ADR-058).
- [ ] `still_open` là bản ghi mới nhất của Job → vượt qua điều kiện verification, publish thành
      công.
- [ ] Job `draft` verify với `result=paused` hoặc `result=closed` bị từ chối (Job chưa từng
      publish — ADR-059).
- [ ] Job `closed` không nhận `job_verifications` mới qua route Staff/Admin thông thường
      (`403`/`422` — ADR-059) và không tự hoạt động lại dưới bất kỳ hình thức nào.
- [ ] Job `paused` verify lại với `result=paused` chỉ ghi `job_verifications`, **không** tạo thêm
      `job_status_histories` (status không đổi — ADR-059).
- [ ] Không publish được Job nếu `jobs.job_description`, `jobs.requirements`, hoặc
      `jobs.benefits` rỗng (NULL hoặc toàn khoảng trắng) — điều kiện 11–13, ADR-060.
- [ ] `PUB-SALARY`: `salary_period=negotiable` chỉ hợp lệ khi `salary_min`/`salary_max`/
      `salary_base` đều `NULL`; không lưu negotiable cùng lương số.
- [ ] `PUB-SALARY`: `salary_period!=negotiable` chỉ hợp lệ khi có ít nhất một số lương dương hoặc
      `salary_description` thực; nếu cùng có min/max thì `salary_min <= salary_max`.
- [ ] Admin chỉ vượt qua `PUB-VERIFY` khi nhập override reason không rỗng; reason được ghi vào
      `job_status_histories`; Staff không override được.
- [ ] Job Company A gắn `company_contact_id` của Company B, contact inactive hoặc soft-deleted →
      store/update/publish bị từ chối; contact chỉ xuất public khi thêm `is_public=true`.
- [ ] Shift Predicate: publish thất bại nếu Job chưa có bản ghi `job_work_shifts` nào; thành công
      khi có ít nhất 1 (ADR-060).
- [ ] Không hard delete được job đã có `applications` liên kết (phải chặn ở Service).
- [ ] Tạo `job_verifications` với `result=still_open` cập nhật đúng cả `jobs.last_checked_at`
      **và** `jobs.last_verified_at` trong cùng transaction.
- [ ] `jobs.salary_min > salary_max` hoặc `min_age > max_age` (khi cả hai có giá trị) bị từ
      chối ở validation.

### 1.3. Job Status History

- [ ] Publish Job (`draft→published` hoặc `paused→published`) tạo đúng 1 bản ghi
      `job_status_histories`.
- [ ] Pause Job tạo đúng 1 bản ghi `job_status_histories`.
- [ ] Mở lại Job (`paused→published`) tạo đúng 1 bản ghi `job_status_histories`, **và** phải
      kiểm tra lại toàn bộ điều kiện publish (mục 1) — nếu company/branch không còn hợp lệ tại
      thời điểm mở lại, transition bị từ chối dù trước đó đã từng publish được.
- [ ] Close Job tạo đúng 1 bản ghi `job_status_histories`, bắt buộc có `reason`.
- [ ] Sửa `jobs.status` trực tiếp từ code ngoài `ChangeJobStatusAction` không được chấp nhận
      trong review (kiểm tra bằng test gọi thẳng Eloquent update và xác nhận không có history
      tương ứng — không phải test runtime, nhưng vẫn liệt kê để nhắc khi implement).
- [ ] `job_verifications` và `job_status_histories` là 2 bảng độc lập — tạo verification không
      tự tạo status history và ngược lại (trừ khi verification dẫn tới đổi status, khi đó cả
      hai đều được ghi trong cùng transaction).

### 1.4. Job Branch Contract

- [ ] Staff tạo Job: `owner_branch_id` luôn = `users.branch_id` của staff, kể cả khi request cố
      gửi giá trị khác (server bỏ qua input, tự gán).
- [ ] Staff không có route/quyền đổi `owner_branch_id` của Job đã tạo.
- [ ] Staff gửi `PUT`/`POST` cho Job thuộc cơ sở khác (publish/pause/close/sửa nội dung) → `403`.
- [ ] Admin tạo Job cho bất kỳ cơ sở nào — chọn `owner_branch_id` tường minh.
- [ ] Admin đổi cơ sở Job (`hr.jobs.transfer-branch`) khi Job đang `published` → bị từ chối,
      phải pause trước.
- [ ] Admin đổi cơ sở Job khi Job đang `closed` → bị từ chối tuyệt đối, không có cách "pause
      trước" để vượt qua (ADR-054).
- [ ] Admin đổi cơ sở Job khi Job đã `deleted_at` (soft-deleted) → bị từ chối (ADR-054).
- [ ] Đổi cơ sở Job thành công (chỉ khi `draft`/`paused`) tạo đúng 1 bản ghi
      `job_branch_histories`; thiếu `reason` bị từ chối; cơ sở đích phải `active`, chưa
      `deleted_at`.
- [ ] `hr.jobs.update` (sửa nội dung khác của Job) không làm thay đổi `owner_branch_id` dù
      payload có gửi kèm giá trị khác.
- [ ] Application đã tạo trước khi Job đổi cơ sở giữ nguyên `owner_branch_id` cũ; Application
      tạo sau thời điểm đổi thuộc cơ sở mới.
- [ ] Sau khi đổi cơ sở, publish lại Job phải re-check toàn bộ điều kiện publish
      (`docs/CORE-FLOWS.md` mục 1.2).

### 1.4a. Branch seed contract (TASK 2.2)

- [ ] `DatabaseSeeder` chạy lặp vẫn chỉ có một branch cho mỗi code `VP`, `PT`, `HB`, `BGBN`.
- [ ] Bốn branch có đúng tên chuẩn; record mới không được gán `ward_id` giả hoặc hardcode ID.
- [ ] Re-seed không kích hoạt lại branch inactive và không làm mất `phone`, `phone_normalized`,
      `zalo`, email, ward hoặc địa chỉ đã có.
- [ ] Branch legacy không bị xóa/merge; report JSON/CSV phân loại legacy và bản ghi gần giống
      canonical để người vận hành merge thủ công.

### 1.5. Hiển thị Job `closed`/`paused`

- [ ] Job `closed`/`paused` không xuất hiện trong danh sách/tìm kiếm/sitemap công khai.
- [ ] URL chi tiết Job `closed` trả `200` (không `404`), hiển thị "Đã ngừng tuyển", ẩn nút
      "Ứng tuyển ngay".
- [ ] URL chi tiết Job `paused` trả `200` (không `404`), hiển thị "Tạm ngừng tuyển", ẩn nút
      "Ứng tuyển ngay".
- [ ] Submit form ứng tuyển trực tiếp (bỏ qua UI) cho Job `closed`/`paused` bị server từ chối.
- [ ] CTA Gọi/Zalo vẫn hiển thị trên trang Job `closed`/`paused`, dùng contact cơ sở như Job
      `published`.

### 1.6. Job Verification Scheduler

- [ ] Sau `job_verification_warning_days` (mặc định 7) ngày kể từ `jobs.last_verified_at` (hoặc
      `published_at` nếu chưa từng verify) mà Job vẫn `published`: hiển thị cảnh báo mức thường
      trên `/hr/viec-lam` cho Staff thuộc đúng cơ sở và Admin.
- [ ] Sau `job_auto_pause_days` (mặc định 14) ngày: cảnh báo chuyển mức cao hơn.
- [ ] Với `job_auto_pause_enabled = false` (mặc định), Job **không** tự động chuyển `paused` dù
      quá hạn bao lâu.
- [ ] Xác nhận lại Job với `result=still_open` (`hr.jobs.verify`) reset mốc đếm cảnh báo về
      `jobs.last_verified_at` mới.
- [ ] Xác nhận lại Job với `result=needs_review` cập nhật `jobs.last_checked_at` nhưng **không**
      thay đổi `jobs.last_verified_at` — cảnh báo (tính từ `last_verified_at`) không bị reset
      (ADR-048).
- [ ] `result=paused`/`result=closed` cập nhật `jobs.last_checked_at`, không đổi
      `last_verified_at`, và chuyển `jobs.status` tương ứng qua `ChangeJobStatusAction`.
- [ ] Job `is_urgent = true` áp dụng cùng ngưỡng cảnh báo, không có ngoại lệ rút ngắn.
- [ ] Cảnh báo (7 ngày/14 ngày) là giá trị tính toán khi hiển thị, tính từ `last_verified_at`
      — không tạo bản ghi `job_verifications`/`job_status_histories` chỉ vì hiển thị cảnh báo.

### 1.7. Company Location/Contact — quyền xóa/khôi phục (ADR-053)

- [ ] Staff gọi `DELETE` `hr.company-locations.destroy`/`hr.company-contacts.destroy` → `403`.
- [ ] Staff gọi `POST` `hr.company-locations.restore`/`hr.company-contacts.restore` → `403`.
- [ ] Admin soft delete/khôi phục được `company_locations`/`company_contacts` bình thường.
- [ ] Staff vẫn tạo/sửa (`store`/`update`) được `company_locations`/`company_contacts` bình
      thường — chỉ xóa/khôi phục mới bị chặn.

### 1.8. Enum Strategy (ADR-055)

- [ ] `company_contacts.status`, `jobs.employment_type`, `jobs.close_reason`, `pages.status`,
      `settings.type` là cột `varchar` ở DB — insert giá trị ngoài danh sách backed enum bị
      Form Request/Domain validation từ chối (không dựa vào ràng buộc DB `enum()`).
- [ ] `jobs.status`, `applications.stage` vẫn là DB `enum()` — insert giá trị ngoài danh sách
      bị DB từ chối ở tầng thấp nhất (không chỉ validation).

### 1.9. Job hết hạn (ADR-072)

- [ ] Job `published` với `expires_at < now()` không nhận Application — submit trực tiếp (bỏ qua
      UI) bị server từ chối.
- [ ] Job `published` với `expires_at < now()` không xuất hiện trong danh sách/tìm kiếm/sitemap
      công khai.
- [ ] URL chi tiết Job hết hạn trả `200` (không `404`), hiển thị "Đã hết hạn tuyển", ẩn nút "Ứng
      tuyển ngay", giữ CTA Gọi/Zalo.
- [ ] `jobs.status` **không** tự động đổi khi hết hạn — vẫn `published` cho tới khi Staff/Admin
      chủ động `pause`/`close`.

## 2. Candidate và Merge

- [ ] **Trường hợp 1 (khớp chắc chắn)**: `phone_normalized` trùng + họ tên sau chuẩn hóa khớp
      chính xác + (cả 2 bên đều có `date_of_birth` và khớp, HOẶC cả 2 đều không có) → tái sử
      dụng `candidates` đã tồn tại, không tạo mới.
- [ ] **Trường hợp 2 (thiếu dữ liệu ngày sinh)**: phone trùng + tên khớp, nhưng chỉ 1 bên có
      `date_of_birth` → **không** tái sử dụng; tạo Candidate mới,
      `needs_duplicate_review = true`.
- [ ] **Trường hợp 3 (trùng phone, tên khác)**: phone trùng, tên sau chuẩn hóa không khớp →
      **không** tái sử dụng; tạo Candidate mới, `needs_duplicate_review = true`.
- [ ] **Trường hợp 4 (ngày sinh mâu thuẫn)**: phone trùng + tên khớp, cả 2 bên đều có
      `date_of_birth` nhưng khác nhau → **không** tái sử dụng; tạo Candidate mới,
      `needs_duplicate_review = true`.
- [ ] Không có thuật toán fuzzy matching/Levenshtein/AI nào được dùng để tự động gộp Candidate
      trong bất kỳ trường hợp nào ở trên.
- [ ] Gộp (`merge`) 2 candidate không làm mất `applications` của candidate bị gộp — toàn bộ
      application không trùng job được chuyển `candidate_id` sang candidate đích.
- [ ] Merge 2 candidate cùng có `applications` cho cùng 1 job: admin chọn Application được giữ
      (hệ thống chỉ đề xuất gợi ý theo stage tiến xa hơn); application còn lại chuyển
      `closed`/`duplicate`; **`candidate_id` của cả 2 Application trùng job không bị đổi**;
      không vi phạm unique `(candidate_id, job_id)`.
- [ ] Chỉ `admin` thực hiện được merge candidate (staff bị từ chối).
- [ ] Candidate có `status = merged` không tạo được `applications` mới, không sửa được thông
      tin, không làm nguồn/đích của một merge khác.
- [ ] Candidate đích hiển thị được đầy đủ lịch sử Application từ candidate nguồn (truy vấn
      "merged family" — `docs/CORE-FLOWS.md` mục 6.3), với Staff vẫn lọc theo cơ sở của mình,
      Admin thấy toàn bộ.
- [ ] Merge nhiều tầng (A merge vào B, sau đó B merge vào C): candidate đích cuối (C) hiển thị
      được đầy đủ Application của cả A và B qua truy vấn family đệ quy.
- [ ] Không tạo được vòng lặp merge (X merge vào Y khi Y đã nằm trong chuỗi dẫn tới X bị từ
      chối).
- [ ] Không merge được Candidate vào chính nó.
- [ ] Không merge được candidate có `status = anonymized` hoặc đã `deleted_at`, dù làm nguồn
      hay đích.
- [ ] Candidate nguồn (`status = merged`) bị khóa chỉnh sửa; truy cập trực tiếp URL chi tiết
      của candidate nguồn redirect về candidate đích (root), không 404.
- [ ] Application `close_reason = duplicate` từ merge conflict không bao giờ được mở lại
      (`closed → new` bị từ chối tuyệt đối).

### 2.1. Anonymization

**2.1.1 — sẵn sàng nghiệm thu (kiến trúc đã chốt, ADR-056, không phụ thuộc công ty):**

- [ ] Chỉ `admin` có quyền anonymize Candidate (staff bị từ chối).
- [ ] Candidate `anonymized` không được chỉnh sửa (`full_name`, `date_of_birth`,
      `candidate_contacts`...).
- [ ] Candidate `anonymized` không được làm nguồn hoặc đích của một merge mới.
- [ ] Application của Candidate `anonymized` không được `reopen` (`closed → new`).
- [ ] Candidate `anonymized` không tạo được `applications` mới.
- [ ] Candidate `anonymized` không xuất hiện trong kết quả tìm kiếm/danh sách candidate mặc
      định.
- [ ] Họ tên và số điện thoại trên `candidates`/`candidate_contacts` được mask/xóa đúng khi
      anonymize.
- [ ] Anonymize cascade ghi đè đúng `applications.submitted_full_name`/`submitted_phone`/
      `submitted_phone_normalized` bằng placeholder cố định (mask, không set NULL — cột vẫn
      NOT NULL) cho **toàn bộ** Application của candidate đó, trong cùng transaction (mục
      7.2.1, ADR-056).
- [ ] Anonymize set `applications.consent_ip`/`consent_user_agent` = `NULL` cho toàn bộ
      Application của candidate đó (mục 7.2.1, ADR-056).
- [ ] Anonymize ghi đè `applications.submission_snapshot` bằng một JSON hợp lệ khác (không set
      NULL — cột giữ NOT NULL); test chỉ xác nhận cột **không rỗng và không còn giá trị PII cũ
      nguyên văn**, chưa xác nhận nội dung redact chi tiết (xem 2.1.2).
- [ ] Contact Log và Note **không** bị tự động xóa/che khi candidate anonymize.
- [ ] `job_snapshot` giữ nguyên không đổi khi candidate anonymize (không chứa PII candidate).
- [ ] Status History, Branch History và Job History liên quan không bị mất sau khi anonymize.
- [ ] `candidates.anonymized_at`/`anonymized_by` ghi đúng người thực hiện và thời gian.
- [ ] Thao tác anonymize không thể hoàn tác (không có route/action "undo").

**2.1.2 — chờ chính sách công ty, KHÔNG tính là đã hoàn thành** (chỉ nội dung redact cụ thể bên
trong `submission_snapshot`, `docs/CORE-FLOWS.md` mục 7.2 — go-live blocker, không ảnh hưởng
schema):

- [ ] `submission_snapshot` sau anonymize giữ đúng key nghiệp vụ nào, xóa/mask đúng key định
      danh nào theo mức mask công ty xác nhận — **chưa viết test cụ thể cho tới khi có quyết
      định**.

### 2.2. Duplicate Review và merged-root resolution (ADR-062, ADR-063)

- [ ] Trường hợp 2/3/4 của Duplicate Candidate Contract tạo đúng 1 bản ghi
      `candidate_duplicate_reviews` (`candidate_id`, `suspected_candidate_id`, `reason_code`
      đúng theo trường hợp), cùng transaction với việc tạo Candidate/Application.
- [ ] `hr.duplicate-reviews.resolve` cập nhật `status`/`review_note` **không** tự động gọi merge
      candidate — `confirmed_same` chỉ là kết luận, Admin phải chủ động thực hiện
      `hr.candidates.merge` riêng.
- [ ] Tạo 2 review trùng cặp `(candidate_id, suspected_candidate_id, reason_code)` khi bản ghi
      trước còn `status=pending` bị chặn ở DB (`pending_pair_key` UNIQUE).
- [ ] Candidate A `status=merged` vào B, sau đó số điện thoại cũ của A được dùng ứng tuyển Job
      mới → hệ thống resolve về B (root) → Application mới thuộc `candidate_id=B` → không tạo
      Candidate C.
- [ ] Chuỗi merge có vòng lặp hoặc vượt quá độ sâu resolve (20 bước) khi matching bị từ chối an
      toàn (log lỗi kỹ thuật, coi như không tìm thấy trùng, **không** chặn ứng viên nộp đơn).
- [ ] `candidates.full_name_normalized` được sinh tự động, nhất quán (cùng `full_name` luôn cho
      cùng giá trị chuẩn hóa, giữ dấu tiếng Việt) — dùng đúng trong so khớp trường hợp 1.
- [ ] Một số điện thoại khớp nhiều Candidate/root: query toàn bộ, resolve/dedupe root, không
      dùng `first()`; đúng một exact root thì reuse đúng root đó.
- [ ] Nhiều exact root cùng phone/name/DOB → không chọn ngẫu nhiên; tạo Candidate/Application mới
      và một review `multiple_exact_matches` cho từng exact root.
- [ ] Resolve một review khi Application còn review `pending` khác → `needs_duplicate_review`
      vẫn true và `duplicate_reviewed_at/by` vẫn null; chỉ pending cuối cùng mới hoàn tất summary.
- [ ] Candidate A đã có Application Job X rồi merge vào B; ứng tuyển lại Job X bằng contact của A
      → không tạo Application cho B, trả Application canonical trong merged family và cập nhật
      `last_reapplied_at`.

## 3. Application — nộp hồ sơ, trạng thái, chống trùng

- [ ] Guest ứng tuyển thành công không cần tài khoản.
- [ ] Ứng tuyển vào Job không `published` (draft/paused/closed) hoặc đã hết `expires_at` bị từ
      chối ở server dù request được gửi trực tiếp (không chỉ ẩn nút ở view).
- [ ] `submission_token` bắt buộc trên mọi request tạo Application — thiếu token bị từ chối ở
      Form Request (không tạo được Application nếu không có token hợp lệ do server sinh).
- [ ] Submit form ứng tuyển 2 lần liên tiếp với cùng `submission_token` (double-click/refresh)
      chỉ tạo đúng 1 `applications`; request thứ hai nhận lại thông báo thành công của
      Application đã tạo, không lỗi 500, không tạo Candidate rác.
- [ ] 2 request đồng thời cùng `submission_token` chỉ tạo đúng 1 `applications` (unique
      constraint DB là chốt chặn cuối); request thua rollback toàn bộ Candidate/Application vừa
      tạo trong transaction của nó.
- [ ] Cùng 1 `submission_token` không tái sử dụng được cho Job khác (token gắn với đúng 1 lần
      mở form của đúng 1 Job) — submit token của Job A cho Job B bị từ chối.
- [ ] Một session mở form của nhiều Job ở nhiều tab cùng lúc vẫn giữ được nhiều
      `submission_token` riêng biệt, không ghi đè lẫn nhau.
- [ ] Request lặp lại với token đã dùng để tạo Application nhận lại đúng kết quả của Application
      đó (cùng `public_id`), không tạo bản ghi mới, không lỗi 500.
- [ ] 2 request đồng thời cùng candidate + cùng job nhưng khác `submission_token` chỉ tạo đúng 1
      `applications` (unique `(candidate_id, job_id)` là chốt chặn cuối; request thua nhận
      thông báo thân thiện, không lỗi 500).
- [ ] 2 request đồng thời khác `submission_token`, cùng `phone_normalized` + họ tên chuẩn hóa +
      `date_of_birth` + cùng Job → chỉ **một** Candidate hợp lệ được tạo hoặc tái sử dụng (khóa
      `GET_LOCK` theo `phone_normalized` serialize việc đánh giá Duplicate Candidate Contract —
      ADR-061), không tạo 2 `candidates` khác nhau cho cùng người.
- [ ] Cùng tình huống trên → chỉ **một** `applications` được tạo; request còn lại nhận lại kết
      quả phù hợp (không lỗi 500).
- [ ] Khi khóa `GET_LOCK` hết thời gian chờ (timeout), request nhận lỗi thân thiện (không `500`),
      không để lại `candidates`/`applications` rác (rollback đầy đủ).
- [ ] Không tạo được `applications` trùng cho cùng `candidate_id + job_id` — case C duplicate
      contract; `applications.last_reapplied_at` được cập nhật trên bản ghi đã tồn tại; **không
      tự động mở lại** dù bản ghi đang `closed`.
- [ ] `submission_snapshot` và `job_snapshot` được lưu đúng tại thời điểm nộp đơn.
- [ ] Dữ liệu consent được lưu đầy đủ khi nộp đơn.
- [ ] Nộp đơn tạo đúng 1 bản ghi `application_status_histories` đầu tiên (`from_stage = null`,
      `to_stage = new`, `workflow_cycle = 1`) và đúng 1 bản ghi `application_branch_histories`
      đầu tiên, cùng trong 1 transaction với việc tạo `applications`.
- [ ] Tạo Candidate/Application lỗi giữa chừng rollback toàn bộ, không để lại bản ghi rác.
- [ ] `application_contact_attempts.result` chỉ nhận đúng 11 giá trị enum chính thức, đồng
      nhất giữa `docs/CORE-FLOWS.md` và `docs/DATABASE-DICTIONARY.md`.
- [ ] Mọi transition `stage` không nằm trong transition matrix bị `ChangeApplicationStageAction`
      từ chối.
- [ ] `new → contacting`, `contacting → consulted`, `consulted → interview_scheduled`,
      `interview_scheduled → interviewed` bị từ chối nếu chưa có bằng chứng tương ứng (Contact
      Log/Appointment) trong **chu kỳ hiện tại**.
- [ ] `waiting_start` bị từ chối nếu chưa có `expected_start_at`; `started` bị từ chối nếu
      chưa có `started_at`.
- [ ] Đổi `stage` sang `closed` bắt buộc phải có `close_reason`.
- [ ] Đổi `stage` tạo bản ghi `application_status_histories` mới, giữ nguyên bản ghi cũ.
- [ ] Ghi nhận Contact Log/Appointment không tự động làm thay đổi `applications.stage`.
- [ ] 2 nhân viên đổi `stage` đồng thời trên cùng 1 Application không ghi đè sai dữ liệu
      (`lockForUpdate`).
- [ ] `application_notes` không xuất hiện ở bất kỳ response/view công khai nào.

### 3.1. Workflow cycle

- [ ] Contact Log thuộc chu kỳ trước (trước lần mở lại gần nhất) **không** được dùng làm bằng
      chứng cho `new → contacting` ở chu kỳ hiện tại.
- [ ] Appointment `completed` thuộc chu kỳ trước **không** được dùng làm bằng chứng cho
      `interview_scheduled → interviewed` ở chu kỳ hiện tại.
- [ ] Mở lại Application (`closed → new`) tăng `workflow_cycle` thêm 1 và đặt lại
      `workflow_cycle_started_at`.
- [ ] Lịch sử của chu kỳ cũ (Contact Log, Appointment, Status History) vẫn hiển thị đầy đủ
      trong timeline của Application sau khi mở lại — không bị xóa hay ẩn.

### 3.2. Reopen Application (`closed → new`)

- [ ] Application đóng với `close_reason = duplicate` không thể mở lại (Staff lẫn Admin).
- [ ] Mở lại bắt buộc có lý do (`application_status_histories.note`) — thiếu lý do bị từ chối.
- [ ] Mở lại Application của Job đã `deleted_at` bị từ chối.
- [ ] Mở lại Application của Job không còn `published` (paused/closed/hết hạn): Staff bị từ
      chối, chỉ Admin thực hiện được.
- [ ] Mở lại Application của Candidate đã `anonymized` hoặc `merged` bị từ chối.
- [ ] Mở lại reset đúng dữ liệu dẫn xuất: `close_reason = null`, `closed_at = null`,
      `expected_start_at = null`, `stage = new`, `reopened_at`/`reopened_by` được ghi.
- [ ] Ứng viên gửi lại form (reapply) cho Job đã có Application `closed` **không** tự động mở
      lại — chỉ cập nhật `last_reapplied_at`; HR phải chủ động mở lại qua action riêng.

## 4. Cơ sở (branch) và appointment

- [ ] `applications.owner_branch_id` luôn bằng `jobs.owner_branch_id` tại thời điểm tạo, kể cả
      khi Job đổi `owner_branch_id` sau đó.
- [ ] Staff thuộc cơ sở A truy cập trực tiếp URL Application thuộc cơ sở B → 403 (không
      redirect lộ dữ liệu trước khi chặn).
- [ ] Danh sách `/hr/ho-so` của Staff chỉ trả về Application thuộc cơ sở của Staff đó; Admin
      thấy tất cả cơ sở.
- [ ] Bất kỳ Staff nào thuộc đúng cơ sở đều ghi Contact Log/Appointment/đổi stage được cho mọi
      Application của cơ sở đó — không có kiểm tra "phải là người được phân công".
- [ ] Hẹn gọi lại (`type=callback`) bắt buộc có `scheduled_at`.
- [ ] Hoàn thành lịch phỏng vấn bắt buộc cập nhật `outcome` trước khi coi là `completed`.
- [ ] Đổi lịch (callback hoặc interview) chuyển bản ghi Appointment cũ sang `status=cancelled`
      và tạo 1 bản ghi Appointment mới; không sửa `scheduled_at` của bản ghi cũ.
- [ ] CTA "Gọi"/"Nhắn Zalo" trên trang Job public luôn dùng `phone`/`zalo` của
      `owner_branch_id`; `company_contacts` dù `is_public=true` cũng không thay thế CTA của cơ
      sở.
- [ ] Mọi thao tác trên Application ghi đúng người thực hiện trên bảng lịch sử tương ứng.
- [ ] Staff truy cập `GET /hr/ung-vien/{candidate}` khi merged family của Candidate đó không có
      Application nào thuộc cơ sở của Staff → 403 (`docs/CORE-FLOWS.md` mục 6.4).
- [ ] Staff truy cập Candidate mà merged family có ít nhất 1 Application thuộc cơ sở mình → xem
      được trang, nhưng chỉ thấy Application thuộc cơ sở mình trong family đó; Admin thấy toàn
      bộ family không giới hạn cơ sở.

### 4.1. Transfer Branch

- [ ] Chuyển Application sang chính cơ sở hiện tại bị từ chối.
- [ ] Chuyển sang cơ sở `status = inactive` bị từ chối.
- [ ] Chuyển sang cơ sở đã `deleted_at` bị từ chối.
- [ ] Chuyển sang cơ sở không tồn tại (ID sai) bị từ chối với lỗi rõ ràng, không lỗi 500.
- [ ] Thiếu `reason` bị từ chối.
- [ ] Chỉ `admin` thực hiện được (staff bị từ chối, kể cả staff của cơ sở hiện tại hoặc cơ sở
      đích).
- [ ] 2 request chuyển cơ sở đồng thời cho cùng 1 Application không ghi đè sai —
      `lockForUpdate` đảm bảo request thứ hai đọc lại trạng thái mới nhất trước khi áp dụng
      điều kiện "khác cơ sở hiện tại".
- [ ] Chuyển thành công cập nhật đúng `owner_branch_id`, tạo `application_branch_histories`,
      giữ nguyên toàn bộ Contact Log/Status History/Appointment/Note/snapshot — không mất lịch
      sử, không tạo Application mới.
- [ ] Staff cơ sở cũ mất quyền xem/sửa Application ngay sau khi chuyển (test truy cập lại →
      403).
- [ ] Staff cơ sở mới nhìn thấy và xử lý được Application ngay sau khi chuyển.

### 4.2. Primary field và Administrative Unit root (ADR-064, ADR-065, ADR-068)

- [ ] 2 request đồng thời đặt `candidate_contacts.is_primary=true` cho 2 contact khác nhau cùng
      `(candidate_id, type)` chỉ có đúng 1 request thành công (unique trên cột generated
      `primary_flag_key`).
- [ ] Company có tối đa 1 `company_contacts.is_primary=true` đang `status=active` — đặt primary
      mới tự động bỏ primary cũ trong cùng transaction.
- [ ] Không tạo được 2 `administrative_units` cấp root (`parent_id=null`) cùng `slug` — insert
      bản ghi thứ hai bị DB từ chối (unique trên cột generated `root_slug_key`).
- [ ] Xóa nhầm `branches` khôi phục được qua `hr.branches.restore` (admin) — dữ liệu
      `users`/`jobs`/`applications` liên kết không bị ảnh hưởng.

## 5. Authorization

- [ ] Bất kỳ route `/hr/*` nào cũng yêu cầu `role ∈ {staff, branch_admin, super_admin}` — không có route `/hr/*`
      cho khách vãng lai hoặc bất kỳ vai trò nào khác (Phase 1 không có `role = candidate`,
      ADR-028).
- [ ] `staff` không quản lý user/branch; `branch_admin` chỉ quản lý Staff và Branch đúng cơ sở;
      `super_admin` giữ quyền toàn hệ thống.
- [ ] Guest (chưa đăng nhập) không truy cập được bất kỳ dữ liệu nội bộ nào (`application_notes`,
      danh sách `/hr/*`).
- [ ] Xuất CSV chỉ chứa các cột được phép xuất; mỗi lần xuất tạo đúng 1 bản ghi `export_logs`.
- [ ] `hr.candidates.anonymize` chỉ `admin` thực hiện được — Staff bị từ chối (`403`).
- [ ] Staff không truy cập được `hr.duplicate-reviews.*` (`403`) — chỉ Admin.
- [ ] Mọi role HR có `password_changed_at = null` chỉ truy cập được `hr.password.change/update`
      và `hr.logout` — mọi route HR khác redirect về `hr.password.change`.
- [ ] `hr.staff.reset-password` (Admin reset mật khẩu Staff) đặt lại `password_changed_at = null`
      — Staff đó bắt buộc đổi mật khẩu lại ở lần đăng nhập kế tiếp.
- [ ] Staff đang có session hợp lệ, sau đó bị Admin khóa (`status=locked`) → request HR kế
      tiếp bị `EnsureUserIsActive` logout/invalidate session; không tiếp tục thao tác bằng session cũ.
- [ ] `company_contacts` không `is_public=true` (hoặc `is_public=true` nhưng chưa được chọn làm
      `jobs.company_contact_id`) không xuất hiện ở bất kỳ response/view public nào (trang chi
      tiết Job, JSON-LD JobPosting, sitemap).

## 6. Database

- [ ] Toàn bộ foreign key khai báo đúng theo `docs/DATABASE-DICTIONARY.md` (test bằng cách cố
      tình insert dữ liệu vi phạm FK, kỳ vọng exception).
- [ ] Unique constraint hoạt động đúng: `applications(candidate_id, job_id)`,
      `applications(submission_token)` (NOT NULL), `job_locations` cột generated chống 2
      primary location, `job_work_shifts(job_id, work_shift_id)`.
- [ ] Transaction rollback đúng khi có lỗi giữa chừng.
- [ ] Soft delete không làm hỏng dữ liệu lịch sử liên quan.
- [ ] Không xảy ra cascade xóa nhầm dữ liệu nghiệp vụ ở các quan hệ bị cấm cascade.
- [ ] Không tồn tại bảng `lead_requests`, `favorites`, `application_assignment_histories` hoặc
      cột `applications.assigned_to`, `applications.referral_code`, `candidates.user_id` trong
      schema Phase 1; `users.role` chỉ nhận `staff`/`branch_admin`/`super_admin`.
- [ ] `application_status_histories.actor_type` chỉ nhận `user`/`system` (không có `import`).
- [ ] `php artisan migrate:fresh` chạy đúng thứ tự 28 bảng business theo Migration order
      (`docs/DATABASE-DICTIONARY.md`) không vi phạm foreign key (không cần tắt kiểm tra FK tạm
      thời để chạy migration).
- [ ] `created_at`/`updated_at` của mọi bảng do Eloquent ghi (không có `DEFAULT CURRENT_TIMESTAMP`
      ở tầng schema DB, trừ trường hợp có ADR riêng — ADR-066).
- [ ] Nội dung PII trong `application_notes.content`, `application_contact_attempts.note`,
      `application_appointments.note`/`outcome` **không** bị tự động redact/xóa khi Candidate
      liên quan anonymize (đúng theo chính sách ADR-071 — chỉ `submission_snapshot`/
      `candidates`/`candidate_contacts` bị mask).
- [ ] Danh sách 28 bảng business (mục `## 9.x`) trong `docs/DATABASE-DICTIONARY.md` khớp danh
      sách entity trong `docs/ERD.md` (không thiếu/thừa bảng nào ở tài liệu nào).

## Điều kiện chạy test

```bash
php artisan migrate:fresh --seed
php artisan test
```

Cả 2 lệnh phải chạy thành công (exit code 0) trước khi coi một giai đoạn trong `ROADMAP.md`
là hoàn thành.

## 7. Search và filter

- [ ] Chỉ trả job `published`, chưa hết hạn và chưa soft delete.
- [ ] Lọc đúng theo KCN, đơn vị hành chính, công ty, khoảng lương, ca, xe đưa đón và chỗ ở.
- [ ] Kết hợp nhiều filter không tạo bản ghi trùng khi job có nhiều location/shift.
- [ ] Query giữ filter khi phân trang; input không hợp lệ không gây lỗi 500.

## 8. Security và dữ liệu xuất

- [ ] Login và form ứng tuyển có rate limit; honeypot/CSRF hoạt động.
- [ ] Không mass-assign được `role`, `stage`, `owner_branch_id`, `created_by` từ public request.
- [ ] Contact/location gắn vào job phải thuộc đúng `company_id` của job.
- [ ] CSV trung hòa giá trị bắt đầu bằng `=`, `+`, `-`, `@` để tránh formula injection.
- [ ] Secret, note nội bộ và dữ liệu private không xuất hiện trong log/response public.
- [ ] `php artisan app:create-admin` từ chối khi `email` đã tồn tại trong `users`; mật khẩu được
      hash, không bao giờ log/echo plaintext (ADR-050).
- [ ] `php artisan app:create-admin` chạy lần 2 khi đã có ≥1 `role=super_admin` bị từ chối trừ khi
      truyền `--force` (ADR-050).
- [ ] Seeder demo (Branch/Staff/Company/Job/Candidate mẫu) không chạy khi
      `app()->environment('production')` (ADR-051).

## 9. SEO

- [ ] Job public có canonical, meta và JobPosting schema hợp lệ.
- [ ] Sitemap chỉ chứa URL public đang hợp lệ; HR/login không xuất hiện.
- [ ] Trang HR/login có `noindex`; `robots.txt` chặn `/hr`.
- [ ] Job đã đóng giữ URL và hiển thị trạng thái/việc liên quan; không còn trong danh sách active.

## 10. Mobile checklist

- [ ] Viewport 360px không tràn ngang.
- [ ] Nút chính tối thiểu 48px; form dùng được bằng bàn phím mobile.
- [ ] Sticky actions không che nội dung hoặc thông báo lỗi.
- [ ] Filter mobile mở/đóng, áp dụng và xóa lọc đúng.

## 11. Scope Phase 1 (không được tồn tại)

- [ ] Không tồn tại bất kỳ route Candidate Account nào (`/dang-ky`, `/dang-nhap` cho candidate,
      `/tai-khoan`, `/tai-khoan/da-ung-tuyen`) trong `routes/web.php` (ADR-028).
- [ ] Không tồn tại route/action nào liên quan Lead, Favorites, hoặc Assignment (claim/assign)
      trong Phase 1 (ADR-021).
- [ ] Schema Phase 1 không chứa cột chỉ phục vụ referral (`applications.referral_code`) hoặc
      import (`actor_type = import`) khi chưa có luồng nghiệp vụ tương ứng (ADR-029).

## 12. Dashboard và bộ lọc hồ sơ (`docs/CORE-FLOWS.md` mục 9)

- [ ] Dashboard Staff chỉ tính trên Application thuộc cơ sở của Staff đó; đúng 8 số liệu tối
      thiểu (mới hôm nay, chưa liên hệ, đang xử lý, lịch gọi lại hôm nay, lịch phỏng vấn hôm
      nay, chờ đi làm, đã đi làm, đã đóng).
- [ ] Dashboard Admin không giới hạn cơ sở, lọc được theo một hoặc nhiều cơ sở.
- [ ] `hr.applications.index` lọc đúng theo từng tiêu chí ở mục 9.2 (tên/phone candidate, job,
      company, cơ sở, stage, khoảng ngày, hồ sơ mới/chưa liên hệ/có lịch/chờ đi làm/đã đi
      làm/nghi ngờ trùng); Staff không đổi được filter cơ sở qua query string sang cơ sở khác.
- [ ] Kết hợp filter "có lịch gọi lại"/"có lịch phỏng vấn" với filter khác không tạo dòng trùng
      khi Application có nhiều Appointment.
