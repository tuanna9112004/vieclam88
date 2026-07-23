# TASK 0.1 — Lập baseline kỹ thuật trước khi sửa

Nguồn: `docs/VIECLAM88_15_KE_HOACH_SUA_SLASH_COMMANDS_V2.1_TOI_UU.pdf`, Phần 0 — "Khóa baseline và
tạo đường lui" (trang 11), điều chỉnh theo `docs/refactor/PLAYBOOK.md` mục 5 (source thực tế ưu
tiên hơn số liệu PDF) và không hardcode số bảng/migration cố định.

- **Command chính:** `/implement`
- **Dependency:** không (task đầu tiên của playbook)
- **Trạng thái hiện tại:** `DONE` — xem mục "Kết quả đã tạo" bên dưới.

## Mục tiêu

Chốt một snapshot kỹ thuật đúng hiện trạng repository (schema, route, workflow, test) và một kế
hoạch backup/rollback, làm điểm tham chiếu trước khi bắt đầu bất kỳ batch migration nào của lộ
trình Phase 2 (ADR-080, `docs/PHASE-2-ARCHITECTURE-PROPOSAL.md`). Chưa thay đổi nghiệp vụ.

## Phạm vi

- Kiểm kê **toàn bộ** migration, model, route, policy, Form Request, Action, seeder, view và test
  đang tồn tại trong source **tại thời điểm chạy task** — lấy số liệu trực tiếp từ
  `database/migrations/`, `php artisan route:list`, `php artisan test`, không dùng số liệu cố định
  từ bất kỳ tài liệu PDF nào.
- Tạo `docs/refactor/00-CURRENT-BASELINE.md` ghi: version PHP/Laravel/MariaDB mục tiêu; danh sách
  bảng hiện tại (đúng số lượng kiểm kê được, không hardcode); route public và `/hr`; các workflow
  đang được bảo vệ; số test hiện có (từ lần chạy `php artisan test` thật); các xung đột đã biết
  với Baseline 1.1/PDF.
- Tạo `docs/refactor/01-ROLLBACK-PLAN.md` mô tả backup source (Git), DB (`db:backup`/
  `db:restore-test`), storage/uploads và cách phục hồi.
- Cập nhật `docs/INDEX.md` và `docs/PROJECT-STATUS.md` để dẫn tới bộ tài liệu refactor.

## Ngoài phạm vi

- Không tạo, sửa hoặc xóa bất kỳ migration nào.
- Không sửa model, controller, policy, Form Request, Action, route, Blade hoặc bất kỳ code nghiệp
  vụ nào.
- Không chạy `migrate:fresh`, `db:wipe`, `migrate:reset`, rollback destructive hoặc backfill dữ
  liệu thật.
- Không quyết định phương án cho các điểm CRITICAL còn xung đột giữa PDF và source (role 3 cấp,
  `jobs.company_id` nullable, `work_ward_id`, bỏ `company_locations`...) — các batch đó thuộc
  `docs/PHASE-2-ARCHITECTURE-PROPOSAL.md`, không thuộc TASK 0.1.

## File được phép sửa

Chỉ các file thuần tài liệu:

- `docs/refactor/00-CURRENT-BASELINE.md` (tạo mới)
- `docs/refactor/01-ROLLBACK-PLAN.md` (tạo mới)
- `docs/INDEX.md` (thêm dòng trỏ tới bộ tài liệu refactor)
- `docs/PROJECT-STATUS.md` (ghi nhận hoàn thành, ≤ 40 dòng)

Không sửa bất kỳ file `.php`, migration, route, Blade hoặc config nào.

## Checklist nghiệm thu

- [ ] Có danh sách đầy đủ bảng, route và module hiện tại — số liệu lấy từ kiểm kê thật, không phải
      số liệu PDF.
- [ ] Nêu rõ các thành phần phải giữ: idempotency, duplicate/merge, workflow history, branch
      policy.
- [ ] Có kế hoạch backup source, DB và storage/app.
- [ ] Có tiêu chí rollback cụ thể cho từng loại (source/DB/storage), không chỉ viết chung chung
      "khôi phục backup".
- [ ] Không có file production code hoặc migration nào bị tạo/sửa.
- [ ] `check-claude-config.py` và `check-claude-skills.py` vẫn chạy được (PASS hoặc chỉ có warning
      đã biết, không có ERROR mới do task này gây ra).

## Lệnh kiểm tra

```bash
python scripts/check-claude-config.py
python scripts/check-claude-skills.py
php artisan route:list
php artisan test
git status --short
git diff --check -- docs/refactor/ docs/INDEX.md docs/PROJECT-STATUS.md
```

## Điều kiện PASS / FAIL / BLOCKED / DONE

- **PASS:** toàn bộ checklist nghiệm thu đạt; số liệu trong `00-CURRENT-BASELINE.md` khớp kiểm kê
  thật tại thời điểm verify; không có migration/code nghiệp vụ nào bị sửa; `check-claude-config.py`
  không phát sinh ERROR mới.
- **FAIL:** số liệu baseline sai lệch so với source thật, thiếu một trong các mục checklist, hoặc
  phát hiện migration/code nghiệp vụ bị sửa ngoài phạm vi.
- **BLOCKED:** không chạy được `php artisan test`/`route:list` (thiếu môi trường/dependency), hoặc
  nguồn tài liệu mâu thuẫn không thể tự chọn phương án (ghi rõ blocker vào
  `docs/PROJECT-STATUS.md`, không tự suy đoán).
- **DONE:** `/verify-task` kết luận `PASS` và `/review-changes` kết luận `APPROVE`, không có thay
  đổi nghiệp vụ, `docs/PROJECT-STATUS.md` đã cập nhật.

## Prompt verify

```
/verify-task TASK 0.1 - Lập baseline kỹ thuật trước khi sửa
```

Đóng vai reviewer độc lập. Chỉ đọc thay đổi của TASK 0.1. Kiểm tra tài liệu baseline có khớp
source thật không (đếm lại migration/route/test thật, không tin số liệu ghi sẵn); tìm bảng, route,
workflow hoặc rủi ro bị bỏ sót. Xác nhận không có migration/source nghiệp vụ nào bị sửa. Trả về
PASS/FAIL/INCOMPLETE, bằng chứng theo file và danh sách sửa bắt buộc. Không tự sửa code.

## Prompt review

```
/review-changes TASK 0.1 - Lập baseline kỹ thuật trước khi sửa
```

Chỉ review phạm vi của task này, không sửa file.

- Đối chiếu thay đổi với mục tiêu/phạm vi/checklist ở trên và các business invariant liên quan
  (idempotency, duplicate/merge, workflow history, branch policy).
- Ưu tiên phát hiện: số liệu baseline sai/lỗi thời so với source thật, migration/code nghiệp vụ bị
  đụng ngoài phạm vi, tài liệu chép nguyên số liệu PDF thay vì kiểm kê thật.
- Mỗi finding phải ghi severity, file/symbol, cách tái hiện, hướng sửa và test/kiểm chứng cần bổ
  sung.
- Kết luận duy nhất: `APPROVE` hoặc `CHANGES REQUIRED`.

## Kết quả đã tạo (tham chiếu, không phải phần của checklist)

- [`../00-CURRENT-BASELINE.md`](../00-CURRENT-BASELINE.md)
- [`../01-ROLLBACK-PLAN.md`](../01-ROLLBACK-PLAN.md)
