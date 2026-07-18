# Acceptance Criteria — vieclam88 (Phase 1)

Tiêu chí nghiệm thu tối thiểu bằng Feature Test (PHPUnit). Mỗi mục dưới đây phải có ít nhất
1 test tương ứng trước khi tính là hoàn thành. Không tự thêm tiêu chí ngoài phạm vi Phase 1
(`.claude/rules/scope-standards.md`), không bỏ bớt tiêu chí đã liệt kê. 6 luồng nghiệp vụ mà
các tiêu chí này xác nhận: `docs/CORE-FLOWS.md`.

## 1. Company và job

- [ ] Admin tạo, sửa, soft delete, khôi phục được `companies`.
- [ ] Admin tạo, sửa, publish, pause, close được `jobs`.
- [ ] Job `status = published` hiển thị ở trang công khai.
- [ ] Job `draft`, `paused`, `closed`, hết `expires_at`, hoặc đã soft delete **không** hiển
      thị ở trang công khai.
- [ ] Job có thể gắn nhiều `job_locations`.
- [ ] Một job chỉ có đúng 1 `job_locations.is_primary = true`.
- [ ] Không publish được Job chưa có `owner_branch_id` (thiếu cơ sở phụ trách).
- [ ] Không publish được Job chưa có contact công khai hợp lệ (company_contact `is_public` hoặc
      fallback branch phone/zalo — `docs/CORE-FLOWS.md` mục 1).
- [ ] Không hard delete được job đã có `applications` liên kết (phải chặn ở Service, có test
      xác nhận exception/lỗi trả về).
- [ ] Tạo `job_verifications` cập nhật đúng `jobs.last_verified_at` trong cùng transaction.

## 2. Candidate

- [ ] Guest ứng tuyển tạo được `candidates` mới khi không tìm thấy hồ sơ phù hợp.
- [ ] Guest ứng tuyển tái sử dụng được `candidates` đã tồn tại khi thông tin khớp (không tạo
      trùng không cần thiết) — case A duplicate contract.
- [ ] Hai người dùng chung số điện thoại nhưng họ tên/ngày sinh khác biệt đáng kể **không**
      bị tự động gộp thành 1 candidate; `applications.needs_duplicate_review = true` được đặt
      để HR kiểm tra sau — case B duplicate contract (`docs/CORE-FLOWS.md` mục 6.2).
- [ ] Gộp (`merge`) 2 candidate không làm mất `applications` của candidate bị gộp — toàn bộ
      application (không trùng job) được chuyển sang candidate còn lại.
- [ ] Merge 2 candidate cùng có `applications` cho cùng 1 job: đúng 1 application được giữ
      (stage tiến xa hơn, hoặc tạo trước nếu bằng stage), application còn lại chuyển
      `closed`/`duplicate`; không vi phạm unique `(candidate_id, job_id)`; Contact Log, Note,
      Appointment, Status History của cả 2 application được giữ nguyên, không di chuyển
      (`docs/CORE-FLOWS.md` mục 6.3).
- [ ] Chỉ `admin` thực hiện được merge candidate (staff bị từ chối).
- [ ] Candidate có `status = merged` không tạo được `applications` mới (chặn ở Service).

## 3. Application — nộp hồ sơ, trạng thái, chống trùng

- [ ] Guest ứng tuyển thành công không cần tài khoản.
- [ ] Ứng tuyển vào Job không `published` (draft/paused/closed) hoặc đã hết `expires_at` bị từ
      chối ở server dù request được gửi trực tiếp (không chỉ ẩn nút ở view).
- [ ] Submit form ứng tuyển 2 lần liên tiếp (double-click/refresh) chỉ tạo đúng 1
      `applications`.
- [ ] 2 request đồng thời cùng candidate + cùng job chỉ tạo đúng 1 `applications` (unique
      constraint DB là chốt chặn cuối; request thua nhận thông báo thân thiện, không lỗi 500).
- [ ] Không tạo được `applications` trùng cho cùng `candidate_id + job_id` (unique
      constraint + thông báo lỗi rõ ràng cho người dùng, không phải lỗi 500) — case C duplicate
      contract; `applications.last_reapplied_at` được cập nhật trên bản ghi đã tồn tại.
- [ ] `submission_snapshot` và `job_snapshot` được lưu đúng tại thời điểm nộp đơn.
- [ ] Dữ liệu consent (`consent_version`, `consent_text_hash`, `consented_at`, `consent_ip`)
      được lưu đầy đủ khi nộp đơn.
- [ ] Nộp đơn tạo đúng 1 bản ghi `application_status_histories` đầu tiên (`from_stage = null`,
      `to_stage = new`) và đúng 1 bản ghi `application_branch_histories` đầu tiên
      (`from_branch_id = null`, `to_branch_id = job.owner_branch_id`).
- [ ] Mọi transition `stage` không nằm trong transition matrix (`docs/CORE-FLOWS.md` mục 5.1)
      bị `ChangeApplicationStageAction` từ chối.
- [ ] `new → contacting` bị từ chối nếu chưa có `application_contact_attempts`.
- [ ] `consulted → interview_scheduled` bị từ chối nếu chưa có `application_appointments`
      (`type=interview`, `status=scheduled`).
- [ ] `interview_scheduled → interviewed` bị từ chối nếu appointment interview chưa
      `status=completed`.
- [ ] `waiting_start` bị từ chối nếu chưa có `expected_start_at`; `started` bị từ chối nếu
      chưa có `started_at`.
- [ ] Đổi `stage` sang `closed` bắt buộc phải có `close_reason` (validate ở Form Request,
      test cả trường hợp thiếu `close_reason` bị từ chối).
- [ ] Đổi `stage` tạo bản ghi `application_status_histories` mới, giữ nguyên bản ghi cũ.
- [ ] Ghi nhận `application_contact_attempts` (vd kết quả "không nghe máy") **không** tự động
      làm thay đổi `applications.stage`. Appointment `no_show`/`cancelled` cũng không tự động
      đổi `applications.stage`.
- [ ] 2 nhân viên đổi `stage` đồng thời trên cùng 1 Application không ghi đè sai dữ liệu
      (`lockForUpdate` trong `ChangeApplicationStageAction`, request thua nhận lỗi rõ ràng hoặc
      dữ liệu mới nhất, không mất history).
- [ ] Gán/tự nhận nhân viên phụ trách tạo bản ghi `application_assignment_histories`; chỉ gán
      được cho `staff` cùng `owner_branch_id` với Application.
- [ ] `application_notes` không xuất hiện ở bất kỳ response/view công khai nào.

## 4. Cơ sở (branch) và appointment

- [ ] `applications.owner_branch_id` luôn bằng `jobs.owner_branch_id` tại thời điểm tạo, kể cả
      khi Job đổi `owner_branch_id` sau đó (Application không tự đổi theo Job).
- [ ] Staff cơ sở A truy cập trực tiếp URL Application thuộc cơ sở B → 403 (không redirect lộ
      dữ liệu trước khi chặn).
- [ ] Danh sách `/hr/ho-so` của staff chỉ trả về Application thuộc cơ sở của staff đó; admin
      thấy tất cả cơ sở.
- [ ] Chuyển cơ sở (`hr.applications.transfer-branch`) cập nhật đúng `owner_branch_id`, tạo
      `application_branch_histories`, và giữ nguyên toàn bộ `application_contact_attempts`,
      `application_status_histories`, `application_appointments`, `application_notes` hiện có
      (không xóa, không tạo Application mới).
- [ ] Chỉ `admin` thực hiện được chuyển cơ sở (staff bị từ chối).
- [ ] Hẹn gọi lại (`type=callback`) bắt buộc có `scheduled_at`.
- [ ] Hoàn thành lịch phỏng vấn bắt buộc cập nhật `outcome` trước khi coi là `completed`.
- [ ] CTA "Gọi"/"Nhắn Zalo" trên trang Job public hiển thị đúng contact ưu tiên: company_contact
      `is_public=true` nếu có, ngược lại số điện thoại/Zalo của `owner_branch_id` — không bao
      giờ lộ contact công ty khách hàng chưa được đánh dấu `is_public`.

## 5. Lead (yêu cầu tư vấn) — chỉ capture, không chuyển đổi trong Phase 1

- [ ] Guest gửi form "để lại số điện thoại cần tư vấn" tạo được `lead_requests` thành công.
- [ ] Không tồn tại route/action nào chuyển `lead_requests` thành `candidates`/`applications`
      trong Phase 1 (`lead_requests.status` chỉ nhận `new`/`contacting`/`closed`, không có
      `converted`) — xem ADR-018, đã dời sang Phase 2.

## 6. Authorization

- [ ] Tài khoản `role = candidate` không truy cập được bất kỳ route `/hr/*` nào (redirect
      hoặc 403, không phải để lộ dữ liệu rồi mới chặn ở view).
- [ ] Tài khoản `role = staff` không quản lý được tài khoản `admin` (không tạo/sửa/xóa được
      user có role admin).
- [ ] `staff` không xem được các trường/API dành riêng cho `admin` (vd export CSV nếu giới
      hạn chỉ admin — xem `docs/ROUTE-MAP.md` phần "HR hồ sơ và lead").
- [ ] Guest (chưa đăng nhập) không truy cập được bất kỳ dữ liệu nội bộ nào (`application_notes`,
      danh sách `/hr/*`).
- [ ] Xuất CSV chỉ chứa các cột được phép xuất (không có `application_notes.content` hay dữ
      liệu nhạy cảm khác nếu chưa được duyệt).
- [ ] Mỗi lần xuất CSV tạo đúng 1 bản ghi `export_logs`.

## 7. Database

- [ ] Toàn bộ foreign key khai báo đúng theo `docs/DATABASE-DICTIONARY.md` (test bằng cách cố
      tình insert dữ liệu vi phạm FK, kỳ vọng exception).
- [ ] Unique constraint hoạt động đúng: `applications(candidate_id, job_id)`,
      `favorites(user_id, job_id)`, `job_locations(job_id, company_location_id)`,
      `job_work_shifts(job_id, work_shift_id)`.
- [ ] Transaction rollback đúng khi có lỗi giữa chừng (vd lỗi ở bước tạo
      `application_status_histories` phải rollback luôn `applications` vừa tạo — test bằng
      cách giả lập lỗi và kiểm tra không còn bản ghi rác).
- [ ] Soft delete không làm hỏng dữ liệu lịch sử liên quan (vd soft delete `jobs` không xóa
      `application_status_histories` của các `applications` thuộc job đó).
- [ ] Không xảy ra cascade xóa nhầm dữ liệu nghiệp vụ ở các quan hệ bị cấm cascade (ADR trong
      `docs/DECISIONS.md`, chi tiết ở `docs/DATABASE-DICTIONARY.md` mục "Chính sách xóa").

## Điều kiện chạy test

```bash
php artisan migrate:fresh --seed
php artisan test
```

Cả 2 lệnh phải chạy thành công (exit code 0) trước khi coi một giai đoạn trong `ROADMAP.md`
là hoàn thành.

## 8. Search và filter

- [ ] Chỉ trả job `published`, chưa hết hạn và chưa soft delete.
- [ ] Lọc đúng theo KCN, đơn vị hành chính, công ty, khoảng lương, ca, xe đưa đón và chỗ ở.
- [ ] Kết hợp nhiều filter không tạo bản ghi trùng khi job có nhiều location/shift.
- [ ] Query giữ filter khi phân trang; input không hợp lệ không gây lỗi 500.

## 9. Security và dữ liệu xuất

- [ ] Login, application và lead form có rate limit; honeypot/CSRF hoạt động.
- [ ] Không mass-assign được `role`, `stage`, `owner_branch_id`, `assigned_to`, `created_by`
      từ public request (form ứng tuyển chỉ định Job qua slug, server tự tính/copy 3 trường
      này, không đọc từ input).
- [ ] Contact/location gắn vào job phải thuộc đúng `company_id` của job.
- [ ] CSV trung hòa giá trị bắt đầu bằng `=`, `+`, `-`, `@` để tránh formula injection.
- [ ] Secret, note nội bộ và dữ liệu private không xuất hiện trong log/response public.

## 10. SEO

- [ ] Job public có canonical, meta và JobPosting schema hợp lệ.
- [ ] Sitemap chỉ chứa URL public đang hợp lệ; HR/login không xuất hiện.
- [ ] Trang HR/login có `noindex`; `robots.txt` chặn `/hr`.
- [ ] Job đã đóng giữ URL và hiển thị trạng thái/việc liên quan; không còn trong danh sách active.

## 11. Mobile checklist

- [ ] Viewport 360px không tràn ngang.
- [ ] Nút chính tối thiểu 48px; form dùng được bằng bàn phím mobile.
- [ ] Sticky actions không che nội dung hoặc thông báo lỗi.
- [ ] Filter mobile mở/đóng, áp dụng và xóa lọc đúng.
