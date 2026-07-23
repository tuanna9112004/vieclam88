# Refactor Playbook — điểm vào tra cứu (không phải nguồn nghiệp vụ)

> Tài liệu này chỉ tóm tắt **cách vận hành** playbook tái cấu trúc theo
> `docs/VIECLAM88_15_KE_HOACH_SUA_SLASH_COMMANDS_V2.1_TOI_UU.pdf` (43 task, Phần 0–13). Không chép
> lại toàn bộ nội dung PDF — mỗi task cụ thể (KEY/GATE/DONE/NEXT) nằm ở
> `docs/VIECLAM88_TASK_REGISTRY_V2.3.md`, tra theo mã `TASK x.y` (đọc qua `/task-cycle TASK x.y`).
> `docs/refactor/TASK-INDEX.md`/`tasks/` chỉ còn hồ sơ lịch sử của TASK 0.1. Nghiệp vụ/schema hiện
> tại vẫn theo `docs/DATABASE-DICTIONARY.md`, `docs/ERD.md`, `docs/ROUTE-MAP.md`,
> `docs/CORE-FLOWS.md` (theo `docs/INDEX.md`) — PDF chỉ là kế hoạch, **chưa migrate**.
> `docs/PHASE-2-ARCHITECTURE-PROPOSAL.md` (ADR-080) mô tả cùng kiến trúc đích ở dạng 9 Batch —
> **thứ tự thi công chính thức là Task x.y ở registry**, không phải thứ tự Batch; đối chiếu hai bên
> và các chỗ lệch đã biết ở [`BATCH-TASK-MAP.md`](BATCH-TASK-MAP.md).

## 1. Nguyên tắc thực hiện chung

- Một task = một vertical slice = một evidence-based gate = một commit thủ công (không tự
  commit/push).
- Không viết lại workflow Job/Application/Candidate đang hoạt động nếu task không yêu cầu; các
  phần phải giữ nguyên: idempotency `submission_token`, duplicate review/merge/anonymize,
  merged-family contract, status history (Job/Application), branch policy 403, backup/restore.
- Mọi thay đổi schema phải có: migration, model, validation backend, policy khi liên quan,
  factory/seeder, test, docs cập nhật đúng nguồn sự thật, và rollback hợp lý.
- Không tuyên bố `DONE` khi chưa có command output làm bằng chứng; thiếu dependency/binary phải
  ghi `BLOCKED`/`INCOMPLETE` chính xác, không suy đoán.
- Không chuyển sang task tiếp theo khi tiêu chí `DONE` của task hiện tại chưa đạt.
- Cập nhật `docs/PROJECT-STATUS.md` (≤ 40 dòng) sau mỗi task; ADR chỉ tạo khi có quyết định kiến
  trúc lâu dài đã chốt.

## 2. Chuỗi slash command chuẩn

`/task-cycle TASK x.y` chạy trọn chuỗi dưới đây tự động (gate tự sửa tối đa 2 vòng, chỉ commit khi
PASS + APPROVE). Dùng chuỗi thủ công khi cần tách bước hoặc `--audit`/`--resume` chưa đủ:

```
/plan-next <task>
  -> /db-task hoặc /implement <task>       (chọn theo MODE ghi trong VIECLAM88_TASK_REGISTRY_V2.3.md)
  -> /test-task <contract/regression>       (khi cần tách riêng)
  -> /verify-task <task>
  -> /review-changes <task>
  -> /fix-review <finding cụ thể>           (nếu có)
  -> /verify-task lại
  -> /review-changes lại
  -> commit + push thủ công (người dùng xác nhận)
  -> /handoff <task>
Gate lớn (không dùng cho task tài liệu đơn lẻ): /release-gate baseline | staging | production
```

Không có bước nào ở trên tự động commit, push, migrate dữ liệu thật hoặc deploy — đó luôn là thao
tác thủ công sau khi có PASS/APPROVE.

## 3. Quy tắc migration an toàn

- Không sửa bất kỳ migration nào **đã tồn tại** tại thời điểm task bắt đầu — chỉ tạo migration
  additive (timestamp mới). Danh sách migration hiện có phải kiểm kê trực tiếp từ
  `database/migrations/` tại thời điểm chạy, **không hardcode một con số cố định** (số bảng/số
  migration có thể đổi giữa các phiên).
- Không chạy `migrate:fresh`, `db:wipe`, `migrate:reset`, rollback destructive hoặc xóa dữ liệu.
- Expand → Backfill → Switch → Contract: không batch nào được xóa bảng/cột cũ (Contract) trước khi
  Switch đã chạy ổn định và có theo dõi (khớp `docs/PHASE-2-ARCHITECTURE-PROPOSAL.md`).

## 4. Quy tắc verify / review / fix / commit / handoff

- `/verify-task`: đóng vai reviewer độc lập, chỉ đọc thay đổi trong phạm vi task, không sửa file;
  kết luận `PASS`/`FAIL`/`INCOMPLETE` kèm bằng chứng theo file.
- `/review-changes`: review diff, không sửa file, tối đa 10 finding có severity/file/kịch bản tái
  hiện/cách sửa nhỏ nhất/test cần bổ sung; kết luận `APPROVE` hoặc `CHANGES REQUIRED`.
- `/fix-review`: chỉ sửa finding còn tái hiện được, sửa root cause, thêm regression test; không tự
  commit/push.
- Commit: luôn thủ công, một vertical slice một commit, chỉ sau khi verify PASS + review APPROVE.
- `/handoff`: chốt completed/verification/blocker và đúng một `NEXT` vào `docs/PROJECT-STATUS.md`;
  không dùng `CLAUDE.md` để lưu trạng thái phiên.

## 5. Source thực tế ưu tiên hơn số liệu snapshot trong PDF

Các con số trong
`docs/VIECLAM88_15_KE_HOACH_SUA_SLASH_COMMANDS_V2.1_TOI_UU.pdf` (số migration, số test, số model,
số route...) là kiểm kê từ một **archive khác** (`vieclam88-srccode(1).zip`, thiếu `vendor`/`.git`)
tại một thời điểm nhất định — **không phải** số liệu của repository Git đang làm việc. Khi số liệu
PDF mâu thuẫn với source thật:

1. Luôn kiểm kê lại trực tiếp từ source thật tại thời điểm chạy task (migration:
   `database/migrations/`; route: `php artisan route:list`; test:
   `php artisan test`).
2. Ghi số liệu thật vào tài liệu output của task (vd. `docs/refactor/00-CURRENT-BASELINE.md`),
   không chép số liệu PDF.
3. Nếu chênh lệch ảnh hưởng phạm vi/quyết định của task, dừng và ghi blocker vào
   `docs/PROJECT-STATUS.md` thay vì tự chọn số liệu để tin theo (đúng nguyên tắc "khi nguồn mâu
   thuẫn" của `CLAUDE.md`).
