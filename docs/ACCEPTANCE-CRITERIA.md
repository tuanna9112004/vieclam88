# Acceptance Criteria — vieclam88 (Phase 1)

Tiêu chí nghiệm thu tối thiểu bằng Feature Test (PHPUnit). Mỗi mục dưới đây phải có ít nhất
1 test tương ứng trước khi tính là hoàn thành. Không tự thêm tiêu chí ngoài phạm vi Phase 1
(`.claude/rules/scope-standards.md`), không bỏ bớt tiêu chí đã liệt kê.

## 1. Company và job

- [ ] Admin tạo, sửa, soft delete, khôi phục được `companies`.
- [ ] Admin tạo, sửa, publish, pause, close được `jobs`.
- [ ] Job `status = published` hiển thị ở trang công khai.
- [ ] Job `draft`, `paused`, `closed`, hết `expires_at`, hoặc đã soft delete **không** hiển
      thị ở trang công khai.
- [ ] Job có thể gắn nhiều `job_locations`.
- [ ] Một job chỉ có đúng 1 `job_locations.is_primary = true`.
- [ ] Không hard delete được job đã có `applications` liên kết (phải chặn ở Service, có test
      xác nhận exception/lỗi trả về).
- [ ] Tạo `job_verifications` cập nhật đúng `jobs.last_verified_at` trong cùng transaction.

## 2. Candidate

- [ ] Guest ứng tuyển tạo được `candidates` mới khi không tìm thấy hồ sơ phù hợp.
- [ ] Guest ứng tuyển tái sử dụng được `candidates` đã tồn tại khi thông tin khớp (không tạo
      trùng không cần thiết).
- [ ] Hai người dùng chung số điện thoại nhưng họ tên/ngày sinh khác biệt đáng kể **không**
      bị tự động gộp thành 1 candidate.
- [ ] Gộp (`merge`) 2 candidate không làm mất `applications` của candidate bị gộp — toàn bộ
      application được chuyển sang candidate còn lại.
- [ ] Candidate có `status = merged` không tạo được `applications` mới (chặn ở Service).

## 3. Application

- [ ] Guest ứng tuyển thành công không cần tài khoản.
- [ ] Không tạo được `applications` trùng cho cùng `candidate_id + job_id` (unique
      constraint + thông báo lỗi rõ ràng cho người dùng, không phải lỗi 500).
- [ ] `submission_snapshot` và `job_snapshot` được lưu đúng tại thời điểm nộp đơn.
- [ ] Dữ liệu consent (`consent_version`, `consent_text_hash`, `consented_at`, `consent_ip`)
      được lưu đầy đủ khi nộp đơn.
- [ ] Nộp đơn tạo đúng 1 bản ghi `application_status_histories` đầu tiên (`from_stage = null`,
      `to_stage = new`).
- [ ] Đổi `stage` tạo bản ghi `application_status_histories` mới, giữ nguyên bản ghi cũ.
- [ ] Đổi `stage` sang `closed` bắt buộc phải có `close_reason` (validate ở Form Request,
      test cả trường hợp thiếu `close_reason` bị từ chối).
- [ ] Ghi nhận `application_contact_attempts` (vd kết quả "không nghe máy") **không** tự động
      làm thay đổi `applications.stage`.
- [ ] Gán/tự nhận nhân viên phụ trách tạo bản ghi `application_assignment_histories`.
- [ ] `application_notes` không xuất hiện ở bất kỳ response/view công khai nào.

## 4. Lead (yêu cầu tư vấn)

- [ ] Guest gửi form "để lại số điện thoại cần tư vấn" tạo được `lead_requests` thành công.
- [ ] Chuyển đổi lead thành `candidate` + `applications` hoạt động đúng theo transaction mục
      11.6 (tìm hoặc tạo candidate, tạo application, tạo history, cập nhật lead).
- [ ] Không chuyển đổi được 1 lead 2 lần (`converted_application_id` đã có giá trị thì từ
      chối chuyển lần nữa).

## 5. Authorization

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

## 6. Database

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
- [ ] Không xảy ra cascade xóa nhầm dữ liệu nghiệp vụ ở 4 quan hệ bị cấm cascade (ADR trong
      `docs/DECISIONS.md`, chi tiết ở `docs/DATABASE-DICTIONARY.md` mục "Chính sách xóa").

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

- [ ] Login, application và lead form có rate limit; honeypot/CSRF hoạt động.
- [ ] Không mass-assign được `role`, `stage`, `assigned_to`, `created_by` từ public request.
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
