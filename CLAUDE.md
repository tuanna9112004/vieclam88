# vieclam88 — Claude Code

## Mục tiêu

Laravel monolith cho công ty cung ứng lao động miền Bắc:
- Public: tìm việc, xem công ty, ứng tuyển guest.
- HR tại `/hr`: quản lý công ty, việc làm, ứng viên và quy trình xử lý.

## Stack cố định

PHP 8.4.x · Laravel 13.x · MariaDB 11.4 LTS (ADR-039) · Blade · Bootstrap 5.3 · Alpine.js · Vite.
Không tự thêm framework/service mới. Mọi thay đổi kiến trúc phải ghi ADR trong `docs/DECISIONS.md`.

## Bất biến nghiệp vụ

6 luồng nghiệp vụ cốt lõi (nguồn sự thật, mọi thứ khác phải khớp): `docs/CORE-FLOWS.md`.

- `users` Phase 1 chỉ phục vụ staff/admin (không có role `candidate`); `candidates` là hồ sơ ứng viên, luôn ứng tuyển dạng guest.
- Không nhận diện người chỉ bằng một số điện thoại; liên hệ nằm ở `candidate_contacts`.
- Một `job` là một đợt tuyển; không tạo trùng `(candidate_id, job_id)`. `applications.submission_token` (NOT NULL, UNIQUE) chống double-submit cùng 1 lần gửi form.
- `applications` phải lưu snapshot và consent tại thời điểm gửi.
- `applications.owner_branch_id` copy từ `jobs.owner_branch_id` lúc tạo, không suy ra động; Staff thuộc đúng cơ sở hoặc Admin mới xem/xử lý được (Staff không được cơ sở khác, Admin không giới hạn). Chuyển cơ sở là ngoại lệ có kiểm soát (lý do + lịch sử), chỉ admin, không tạo Application mới.
- `jobs.owner_branch_id` NOT NULL ngay từ lúc tạo Job (kể cả draft): staff tự động gán = cơ sở mình, không tự chọn/đổi; chỉ admin đổi được, chỉ khi Job `draft`/`paused` (không `published`/`closed`, có lý do, ghi `job_branch_histories`). Company/Company Location dùng Quick Create (chỉ cần tên, còn lại `NULL` chứ không lưu chuỗi "Chưa xác định"); Job publish còn yêu cầu địa điểm đủ rõ và đã xác minh còn tuyển. Staff không xóa/khôi phục Company Location/Contact (chỉ admin).
- Application mở lại (`closed → new`) phải theo đúng chu kỳ xử lý mới (`workflow_cycle`) — dữ liệu Contact Log/Appointment của lần xử lý trước không được dùng để mở khóa trạng thái của lần xử lý sau.
- Pipeline, lần liên hệ, lịch hẹn và ghi chú là dữ liệu riêng; lịch sử chỉ thêm, không ghi đè.
- Không hard-delete dữ liệu tuyển dụng cốt lõi.
- Enum trạng thái cốt lõi (`jobs.status`, `applications.stage`) dùng DB `enum()`; enum phụ khác dùng `varchar` + PHP backed enum (ADR-055) — không tạo DB `enum()` mới cho cột chưa ổn định.
- **Phase 1 đã đóng băng (Baseline v1.0, `docs/PHASE-1-SCOPE.md`)**: không có Lead (mọi hình thức), không có phân công/claim hồ sơ cho nhân viên, không có Candidate Account, không có Favorites — thuộc Phase 2 (`docs/PHASE-2-BACKLOG.md`). Không làm chức năng ngoài Phase 1 nếu chưa có ADR được chấp nhận.

## Cách làm việc

1. Đọc `docs/PROJECT-STATUS.md` và `docs/CONTEXT-MAP.md`; nếu task đụng tới Job/Application/cơ sở, đọc thêm `docs/CORE-FLOWS.md`.
2. Chỉ đọc tài liệu đúng loại task; không quét toàn bộ `docs/` hoặc toàn bộ ảnh.
3. Mỗi session xử lý một vertical slice nhỏ, có tiêu chí nghiệm thu rõ.
4. Sửa ít file nhất; không refactor ngoài phạm vi.
5. Chạy kiểm tra nhỏ nhất liên quan trước, toàn bộ suite sau khi slice ổn định.
6. Không tuyên bố hoàn thành nếu chưa chạy lệnh kiểm tra và đọc kết quả.
7. Không commit/push, migrate fresh, xóa file hoặc đổi schema khi chưa được yêu cầu rõ.

## Lệnh chuẩn

Khi Laravel đã tồn tại:
```bash
php artisan test --filter=<TestName>
php artisan test
npm run build
```

Tài liệu hiện tại:
```bash
python scripts/check-claude-config.py
```

## Workflow nhanh

- `/implement <kết quả cần đạt>`: triển khai một slice có test.
- `/db-task <thay đổi dữ liệu>`: làm task database theo dictionary/ERD.
- `/review-changes`: review diff trong context riêng.
- `/handoff`: cập nhật trạng thái cuối session.

## Trạng thái phiên gần nhất — FINAL PLAN HARDENING (chi tiết đầy đủ: `docs/PROJECT-STATUS.md`, `docs/DECISIONS.md`)

**Đã hoàn thành:** rà soát và siết toàn bộ plan/spec trước migration — Company/Location Quick
Create + Job Draft/Publish/Verification Contract (ADR-045..048); Job Branch Transfer chỉ
`draft`/`paused` (ADR-054); validation tỉnh khớp KCN (ADR-052); quyền xóa/khôi phục Company
Location/Contact chỉ admin (ADR-053); Bootstrap Sequence 10 bước + `app:create-admin` (ADR-050)
+ seeder tách production-safe/demo-test (ADR-051); **Enum Strategy** — 5 cột enum phụ chuyển
`varchar`+PHP backed enum, xóa migration blocker cuối cùng (ADR-055); PII schema tối thiểu cho
`applications` (ADR-056); đóng băng phạm vi **Phase 1 Plan Baseline v1.0** với
`docs/PHASE-1-SCOPE.md`/`docs/PHASE-2-BACKLOG.md` mới (ADR-057).

**Trạng thái từng phần:**
- Đặc tả/schema (CORE-FLOWS, DATABASE-DICTIONARY, ERD, ROUTE-MAP, ACCEPTANCE-CRITERIA): **chốt
  xong**, đồng bộ chéo, `check-claude-config.py` chạy 0 warning.
- Migration blockers: **0**.
- Go-live blockers (không chặn migration): thời hạn lưu dữ liệu ứng viên, mức mask nội dung
  `submission_snapshot` — cả hai vẫn **[CẦN CHỐT VỚI CÔNG TY]**.
- Phase 2 decision còn mở (không chặn gì): `job_auto_pause_enabled`.
- Môi trường code: **chưa cài** (PHP 8.4/Composer/Node LTS/MariaDB 11.4).
- Mã nguồn Laravel: **chưa viết**, chưa có migration nào.

**Bước tiếp theo (session sau):**
1. Cài đặt môi trường: PHP 8.4.x, Composer, Node LTS, MariaDB 11.4 LTS.
2. `composer create-project laravel/laravel` (khóa `^13.0`) — xác nhận với người dùng trước khi
   chạy (thay đổi hard-to-reverse).
3. Giai đoạn 1 (`ROADMAP.md`): viết migration cho 27 bảng đúng `docs/DATABASE-DICTIONARY.md`,
   model/relationship khớp `docs/ERD.md`, factory, seeder (tách đúng 2 nhóm — ADR-051), PHP
   backed enum, rồi `php artisan migrate:fresh --seed && php artisan test`.
4. Song song, không chặn (3): xin công ty xác nhận 2 go-live blocker trên trước Giai đoạn 4.

**Quyết định quan trọng & lý do (đầy đủ ở `docs/DECISIONS.md` ADR-045..057):**
- *Enum Strategy (ADR-055)* — đổi 5 cột enum "đề xuất" sang `varchar`+backed enum vì DB `enum()`
  khóa cứng giá trị, đổi sau phải `ALTER TABLE`; `varchar` cho phép sửa giá trị bằng code, nên
  không cần chờ công ty duyệt trước khi migration — đây là quyết định gỡ bỏ blocker cuối cùng.
- *Job Branch Transfer cấm khi `closed` (ADR-054)* — bản trước chỉ cấm khi `published`, vô tình
  vẫn cho phép đổi cơ sở của một đợt tuyển đã đóng (không còn ý nghĩa nghiệp vụ, sai lịch sử
  báo cáo theo cơ sở).
- *Quyền xóa Company Location/Contact chỉ admin (ADR-053)* — Route Map cũ mâu thuẫn với quy tắc
  đã chốt ở CORE-FLOWS (chỉ Admin soft delete/restore); sửa Route Map theo đúng quy tắc.
- *Seeder tách 3 nhóm (ADR-051)* — bản trước gộp Branch mẫu (demo) chung câu với danh mục hệ
  thống thật, rủi ro chạy nhầm demo data (kèm tài khoản mật khẩu biết trước) lên production.
- *PII schema tối thiểu tách khỏi retention (ADR-056)* — nullable/cơ chế mask là quyết định kiến
  trúc khóa được ngay; chỉ nội dung mask cụ thể mới cần công ty duyệt, không ảnh hưởng cột nào.
- *Phase 1 freeze v1.0 (ADR-057)* — cần một mốc rõ ràng để ngừng mở rộng yêu cầu trước khi viết
  migration; mọi bổ sung sau mốc này phải là ngoại lệ (lỗi nghiệp vụ/bảo mật), không phải quy
  trình thường.

## Compact Instructions

Giữ lại khi compact: mục tiêu hiện tại, acceptance criteria, file đã đổi, lệnh đã chạy và kết quả, lỗi còn lại, bước tiếp theo. Bỏ output dài, kế hoạch cũ và mô tả file lặp lại.
