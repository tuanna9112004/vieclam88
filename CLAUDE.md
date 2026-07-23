# vieclam88 — Claude Code

ƯU TIÊN TRẢ LỜI BẰNG TIẾNG VIỆT sau khi hoàn thành prompt
## Mục tiêu và stack

Laravel monolith cho công ty cung ứng lao động miền Bắc: website public và HR tại `/hr`.
Stack cố định: PHP 8.4.x, Laravel 13.x, MariaDB 11.4 LTS, Blade, Bootstrap 5.3, Alpine.js, Vite. Không tự thêm framework/service; thay đổi kiến trúc phải có ADR.

## Phạm vi đã khóa

Phase 1 theo `docs/PHASE-1-SCOPE.md`; nội dung không được nêu mặc định chuyển `docs/PHASE-2-BACKLOG.md`. Không xây Candidate Account, Lead, Favorites, assignment/claim, Zalo API, CTV/hoa hồng, import hàng loạt, AI matching hoặc BI nâng cao.

## Bất biến toàn cục

- `users` chỉ có `staff`/`branch_admin`/`super_admin`; ứng viên là `candidates` và luôn ứng tuyển guest.
- `branches` là cơ sở nội bộ; `company_locations` là địa điểm của khách hàng.
- Job/Application sở hữu cơ sở bằng `owner_branch_id`; Staff/Branch Admin chỉ truy cập dữ liệu
  đúng cơ sở, Super Admin không giới hạn.
- Job/Application đổi trạng thái qua domain Action, có transaction/history; không cập nhật trực tiếp từ Controller.
- Application dùng `submission_token` idempotent, lưu snapshot/consent; duplicate/merge phải tuân thủ merged-family contract.
- Không hard-delete dữ liệu tuyển dụng cốt lõi; constraint quan trọng phải có DB constraint và test.
- Không tạo schema, route hoặc UI dự phòng cho Phase 2.

## Nguồn sự thật

- Điều hướng tài liệu: `docs/INDEX.md`, `docs/CONTEXT-MAP.md`.
- Tiến độ: `docs/PROJECT-STATUS.md`.
- Nghiệp vụ: `docs/CORE-FLOWS.md`; schema: `docs/DATABASE-DICTIONARY.md`; quan hệ: `docs/ERD.md`.
- Route: `docs/ROUTE-MAP.md`; nghiệm thu: `docs/ACCEPTANCE-CRITERIA.md`; ADR: `docs/decisions/INDEX.md`.
- Khi nguồn mâu thuẫn: dừng phần liên quan, ghi blocker; không tự chọn một phương án.
- Kiến trúc mục tiêu Phase 2 (đã duyệt, ADR-080, CHƯA migrate code): `docs/PHASE-2-ARCHITECTURE-PROPOSAL.md`. Bất biến bên dưới vẫn là hiện trạng thật — chỉ code theo bảng/cột mới khi đang thực thi đúng batch migration ở file đó.

## Quy trình mỗi task

1. Đọc `docs/PROJECT-STATUS.md`, sau đó dùng `docs/CONTEXT-MAP.md` để chọn context tối thiểu.
2. Xác định một vertical slice nhỏ và acceptance criteria cụ thể.
3. Sửa ít file nhất; không refactor hoặc mở rộng phạm vi ngoài task.
4. Viết/cập nhật test cùng thay đổi; chạy focused test trước, suite/build sau.
5. Báo cáo file đổi, lệnh đã chạy, kết quả và phần còn lại. Không tuyên bố hoàn thành khi chưa kiểm chứng.

## Hiệu quả thực thi

- Trước khi làm, xác định đúng một câu "kết quả cần đạt"; bỏ qua mọi việc không phục vụ trực tiếp câu đó.
- Ưu tiên đọc theo `docs/CONTEXT-MAP.md`/dòng task tương ứng; không quét toàn bộ `docs/` hoặc source ngoài phạm vi để "chắc ăn".
- Verify/regression chỉ chạy đúng phần bị ảnh hưởng bởi thay đổi; chỉ chạy lại toàn bộ suite/full re-audit khi có lý do cụ thể nghi ngờ ảnh hưởng chéo (đổi migration, shared Action/Policy, config toàn cục).
- Trong một phiên, tái dùng kết quả đã xác minh trước đó thay vì đọc/chạy lại; chỉ re-check phần thực sự mới thay đổi.
- Báo cáo ngắn: kết luận + bằng chứng cô đọng (tên lệnh, exit/kết quả); không dán log/JSON thô dài vào câu trả lời.

## An toàn thao tác

Không commit/push, `migrate:fresh`, rollback, xóa file hoặc đổi schema ngoài phạm vi khi chưa được yêu cầu rõ. Không đọc `.env` hoặc dữ liệu private.

## Lệnh chuẩn

```bash
python scripts/check-claude-config.py
php artisan test --filter=<TestName>
php artisan test
npm run build
```

## Skills

`/vibe-task` là điểm vào mặc định cho task ad-hoc; `/task-cycle TASK x.y` chạy trọn cycle cho task
trong `docs/VIECLAM88_TASK_REGISTRY_V2.3.md`. Danh mục đầy đủ tại `docs/CLAUDE-SKILLS.md`. Skills
nằm trong `.claude/skills/`.

## Command chứa `TASK x.y`

Khi nhận command có `TASK x.y`: đọc `docs/VIECLAM88_TASK_REGISTRY_V2.3.md` (nguồn KEY/GATE/DONE/NEXT
chính thức của mọi task), `docs/refactor/PLAYBOOK.md` (nguyên tắc vận hành/migration an toàn) và
`docs/PROJECT-STATUS.md`; đối chiếu với source thực tế, không tin số liệu snapshot. Chỉ làm đúng
task được gọi và dependency `NEXT` liền trước theo registry. `docs/refactor/TASK-INDEX.md`/`tasks/`
chỉ còn là hồ sơ lịch sử của TASK 0.1, không dùng để tra task mới.
