# Acceptance Criteria — vieclam88 (Phase 1)

Tiêu chí nghiệm thu tối thiểu bằng Feature Test (PHPUnit). Mỗi mục dưới đây phải có ít nhất
1 test tương ứng trước khi tính là hoàn thành. Không tự thêm tiêu chí ngoài phạm vi Phase 1
(`.claude/rules/scope-standards.md`), không bỏ bớt tiêu chí đã liệt kê. 6 luồng nghiệp vụ mà
các tiêu chí này xác nhận: `docs/CORE-FLOWS.md`. Phase 1 không có Lead, không có
assignment/claim, không có Favorites (ADR-021) — không viết test cho các phần này.

## 1. Company và job

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
      công (unique constraint trên cột generated, `docs/DATABASE-DICTIONARY.md` mục
      `job_locations`).
- [ ] Không publish được Job chưa có `owner_branch_id` (thiếu cơ sở phụ trách).
- [ ] Không publish được Job nếu cơ sở phụ trách không `active` (đã `inactive` hoặc
      `deleted_at`) — **Branch của Job phải active khi publish**.
- [ ] Không publish được Job nếu cơ sở phụ trách không có `phone` và không có `zalo`.
- [ ] Không hard delete được job đã có `applications` liên kết (phải chặn ở Service, có test
      xác nhận exception/lỗi trả về).
- [ ] Tạo `job_verifications` cập nhật đúng `jobs.last_verified_at` trong cùng transaction.
- [ ] `jobs.salary_min > salary_max` hoặc `min_age > max_age` (khi cả hai có giá trị) bị từ
      chối ở validation (và ở constraint DB nếu MariaDB hỗ trợ CHECK).

## 2. Candidate

- [ ] Guest ứng tuyển tạo được `candidates` mới khi không tìm thấy hồ sơ phù hợp.
- [ ] Guest ứng tuyển tái sử dụng được `candidates` đã tồn tại khi số điện thoại chuẩn hóa
      trùng, họ tên sau chuẩn hóa khớp **chính xác**, và ngày sinh khớp nếu cả 2 bên có — case A
      duplicate contract (`docs/CORE-FLOWS.md` mục 6.2).
- [ ] Hai người dùng chung số điện thoại nhưng họ tên sau chuẩn hóa không khớp tuyệt đối
      **không** bị tự động gộp thành 1 candidate; `applications.needs_duplicate_review = true`
      được đặt để HR kiểm tra sau — case B duplicate contract.
- [ ] Gộp (`merge`) 2 candidate không làm mất `applications` của candidate bị gộp — toàn bộ
      application (không trùng job) được chuyển sang candidate còn lại.
- [ ] Merge 2 candidate cùng có `applications` cho cùng 1 job: admin chọn Application được giữ
      (hệ thống chỉ đề xuất gợi ý theo stage tiến xa hơn); application còn lại chuyển
      `closed`/`duplicate`; **không vi phạm unique `(candidate_id, job_id)`**; Contact Log,
      Note, Appointment, Status History của cả 2 application được giữ nguyên, không di chuyển
      (`docs/CORE-FLOWS.md` mục 6.3).
- [ ] Chỉ `admin` thực hiện được merge candidate (staff bị từ chối).
- [ ] Candidate có `status = merged` không tạo được `applications` mới (chặn ở Service).

## 3. Application — nộp hồ sơ, trạng thái, chống trùng

- [ ] Guest ứng tuyển thành công không cần tài khoản.
- [ ] Ứng tuyển vào Job không `published` (draft/paused/closed) hoặc đã hết `expires_at` bị từ
      chối ở server dù request được gửi trực tiếp (không chỉ ẩn nút ở view).
- [ ] Submit form ứng tuyển 2 lần liên tiếp với cùng `submission_token` (double-click/refresh)
      chỉ tạo đúng 1 `applications`; request thứ hai nhận lại thông báo thành công của
      Application đã tạo, không lỗi 500.
- [ ] 2 request đồng thời cùng `submission_token` chỉ tạo đúng 1 `applications` (unique
      constraint DB là chốt chặn cuối).
- [ ] 2 request đồng thời cùng candidate + cùng job nhưng khác `submission_token` (2 tab, 2 lần
      submit riêng biệt) chỉ tạo đúng 1 `applications` (unique `(candidate_id, job_id)` là chốt
      chặn cuối; request thua nhận thông báo thân thiện, không lỗi 500).
- [ ] Không tạo được `applications` trùng cho cùng `candidate_id + job_id` — case C duplicate
      contract; `applications.last_reapplied_at` được cập nhật trên bản ghi đã tồn tại.
- [ ] `submission_snapshot` và `job_snapshot` được lưu đúng tại thời điểm nộp đơn.
- [ ] Dữ liệu consent (`consent_version`, `consent_text_hash`, `consented_at`, `consent_ip`)
      được lưu đầy đủ khi nộp đơn.
- [ ] Nộp đơn tạo đúng 1 bản ghi `application_status_histories` đầu tiên (`from_stage = null`,
      `to_stage = new`) và đúng 1 bản ghi `application_branch_histories` đầu tiên
      (`from_branch_id = null`, `to_branch_id = job.owner_branch_id`), cùng trong 1
      transaction với việc tạo `applications`.
- [ ] Tạo Candidate/Application lỗi giữa chừng (vd lỗi ở bước tạo status history) rollback toàn
      bộ, không để lại `candidates`/`applications` rác.
- [ ] `application_contact_attempts.result` chỉ nhận đúng 11 giá trị enum chính thức
      (`docs/CORE-FLOWS.md` mục 5.2), đồng nhất với `docs/DATABASE-DICTIONARY.md` — không có
      giá trị nào khác biệt giữa 2 tài liệu.
- [ ] Mọi transition `stage` không nằm trong transition matrix (`docs/CORE-FLOWS.md` mục 5.1)
      bị `ChangeApplicationStageAction` từ chối.
- [ ] `new → contacting` bị từ chối nếu chưa có `application_contact_attempts`.
- [ ] `contacting → consulted` bị từ chối nếu chưa có contact result thuộc {`consulted`,
      `interview_agreed`}.
- [ ] `consulted → interview_scheduled` bị từ chối nếu chưa có `application_appointments`
      (`type=interview`, `status=scheduled`).
- [ ] `interview_scheduled → interviewed` bị từ chối nếu appointment interview chưa
      `status=completed`.
- [ ] `waiting_start` bị từ chối nếu chưa có `expected_start_at`; `started` bị từ chối nếu
      chưa có `started_at`.
- [ ] Đổi `stage` sang `closed` bắt buộc phải có `close_reason` (validate ở Form Request,
      test cả trường hợp thiếu `close_reason` bị từ chối).
- [ ] `closed → new` (mở lại) bắt buộc có lý do mở lại (`application_status_histories.note`);
      thiếu lý do bị từ chối; `applications.close_reason`/`closed_at` được reset về null.
- [ ] `closed → new` bị từ chối nếu việc mở lại vi phạm unique `(candidate_id, job_id)` với
      một Application khác đang active của cùng candidate/job (trường hợp bị đóng do case C
      hoặc do merge duplicate, `docs/CORE-FLOWS.md` mục 6.3).
- [ ] Đổi `stage` tạo bản ghi `application_status_histories` mới, giữ nguyên bản ghi cũ.
- [ ] Ghi nhận `application_contact_attempts` (vd kết quả `no_answer`) **không** tự động làm
      thay đổi `applications.stage`. Appointment `no_show`/`cancelled` cũng không tự động đổi
      `applications.stage`.
- [ ] 2 nhân viên đổi `stage` đồng thời trên cùng 1 Application không ghi đè sai dữ liệu
      (`lockForUpdate` trong `ChangeApplicationStageAction`, request thua nhận lỗi rõ ràng hoặc
      dữ liệu mới nhất, không mất history).
- [ ] `application_notes` không xuất hiện ở bất kỳ response/view công khai nào.

## 4. Cơ sở (branch) và appointment

- [ ] `applications.owner_branch_id` luôn bằng `jobs.owner_branch_id` tại thời điểm tạo, kể cả
      khi Job đổi `owner_branch_id` sau đó (Application không tự đổi theo Job).
- [ ] Staff cơ sở A truy cập trực tiếp URL Application thuộc cơ sở B → 403 (không redirect lộ
      dữ liệu trước khi chặn).
- [ ] Danh sách `/hr/ho-so` của staff chỉ trả về Application thuộc cơ sở của staff đó; admin
      thấy tất cả cơ sở.
- [ ] Bất kỳ staff nào cùng cơ sở đều ghi Contact Log/Appointment/đổi stage được cho mọi
      Application của cơ sở đó — không có kiểm tra "phải là người được phân công" (Phase 1
      không có assignment, ADR-021).
- [ ] Chuyển cơ sở (`hr.applications.transfer-branch`) cập nhật đúng `owner_branch_id`, tạo
      `application_branch_histories`, và giữ nguyên toàn bộ `application_contact_attempts`,
      `application_status_histories`, `application_appointments`, `application_notes` hiện có
      (không xóa, không tạo Application mới).
- [ ] Chỉ `admin` thực hiện được chuyển cơ sở (staff bị từ chối).
- [ ] Hẹn gọi lại (`type=callback`) bắt buộc có `scheduled_at`.
- [ ] Hoàn thành lịch phỏng vấn bắt buộc cập nhật `outcome` trước khi coi là `completed`.
- [ ] Đổi lịch (callback hoặc interview) chuyển bản ghi Appointment cũ sang `status=cancelled`
      và tạo 1 bản ghi Appointment mới; không sửa `scheduled_at` của bản ghi cũ.
- [ ] CTA "Gọi"/"Nhắn Zalo" trên trang Job public luôn dùng `phone`/`zalo` của
      `owner_branch_id`; `company_contacts` dù `is_public=true` cũng không thay thế CTA của cơ
      sở, chỉ hiển thị thêm khi được gán làm `jobs.company_contact_id`.
- [ ] Mọi thao tác trên Application (Contact Log, đổi stage, Appointment, Note, chuyển cơ sở)
      ghi đúng người thực hiện trên bảng lịch sử tương ứng
      (`contacted_by`/`changed_by`/`created_by`/`completed_by`/`user_id`/`transferred_by`).

## 5. Authorization

- [ ] Tài khoản `role = candidate` không truy cập được bất kỳ route `/hr/*` nào (redirect
      hoặc 403, không phải để lộ dữ liệu rồi mới chặn ở view).
- [ ] Tài khoản `role = staff` không quản lý được tài khoản `admin` (không tạo/sửa/xóa được
      user có role admin).
- [ ] `staff` không xem được các trường/API dành riêng cho `admin` (vd export CSV — chỉ
      admin, xem `docs/ROUTE-MAP.md` phần "HR hồ sơ").
- [ ] Guest (chưa đăng nhập) không truy cập được bất kỳ dữ liệu nội bộ nào (`application_notes`,
      danh sách `/hr/*`).
- [ ] Xuất CSV chỉ chứa các cột được phép xuất (không có `application_notes.content` hay dữ
      liệu nhạy cảm khác nếu chưa được duyệt).
- [ ] Mỗi lần xuất CSV tạo đúng 1 bản ghi `export_logs`.

## 6. Database

- [ ] Toàn bộ foreign key khai báo đúng theo `docs/DATABASE-DICTIONARY.md` (test bằng cách cố
      tình insert dữ liệu vi phạm FK, kỳ vọng exception).
- [ ] Unique constraint hoạt động đúng: `applications(candidate_id, job_id)`,
      `applications(submission_token)` khi có giá trị, `job_locations(job_id,
      company_location_id)`, `job_locations` cột generated chống 2 primary location,
      `job_work_shifts(job_id, work_shift_id)`.
- [ ] Transaction rollback đúng khi có lỗi giữa chừng (vd lỗi ở bước tạo
      `application_status_histories` phải rollback luôn `applications` vừa tạo — test bằng
      cách giả lập lỗi và kiểm tra không còn bản ghi rác).
- [ ] Soft delete không làm hỏng dữ liệu lịch sử liên quan (vd soft delete `jobs` không xóa
      `application_status_histories` của các `applications` thuộc job đó).
- [ ] Không xảy ra cascade xóa nhầm dữ liệu nghiệp vụ ở các quan hệ bị cấm cascade (ADR trong
      `docs/DECISIONS.md`, chi tiết ở `docs/DATABASE-DICTIONARY.md` mục "Chính sách xóa").
- [ ] Không tồn tại bảng `lead_requests`, `favorites`, `application_assignment_histories`
      hoặc cột `applications.assigned_to` trong schema Phase 1 (ADR-021).

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
- [ ] Không mass-assign được `role`, `stage`, `owner_branch_id`, `created_by` từ public request
      (form ứng tuyển chỉ định Job qua slug, server tự tính/copy các trường này, không đọc từ
      input). **Không có `assigned_to`/`assigned_user_id`** trong Phase 1 (ADR-021) nên không
      cần kiểm tra mass-assignment cho trường này.
- [ ] Contact/location gắn vào job phải thuộc đúng `company_id` của job.
- [ ] CSV trung hòa giá trị bắt đầu bằng `=`, `+`, `-`, `@` để tránh formula injection.
- [ ] Secret, note nội bộ và dữ liệu private không xuất hiện trong log/response public.

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
