# Documentation Index

Điểm vào duy nhất cho tài liệu dự án. Không đọc toàn bộ `docs/`; dùng `docs/CONTEXT-MAP.md` để lấy đúng context cho task.

## Thứ tự ưu tiên nguồn

1. `docs/PHASE-1-SCOPE.md` — ranh giới Phase 1/Phase 2.
2. `docs/CORE-FLOWS.md` — nghiệp vụ và state transitions.
3. `docs/DATABASE-DICTIONARY.md` — cột, FK, index, constraint và migration order.
4. `docs/ERD.md` — quan hệ tổng quan.
5. `docs/ROUTE-MAP.md` — route, role và action dự kiến.
6. `docs/ACCEPTANCE-CRITERIA.md` — điều kiện nghiệm thu/test.
7. `docs/decisions/INDEX.md` — quyết định kiến trúc theo chủ đề.
8. `ROADMAP.md` — lịch sử triển khai Phase 1 (đã DONE, đóng băng); kế hoạch tiếp theo ở `docs/refactor/`.
9. `docs/PROJECT-STATUS.md` — trạng thái phiên hiện tại, không phải nguồn nghiệp vụ.

Khi hai nguồn cùng cấp mâu thuẫn, dừng task liên quan và ghi blocker vào `docs/PROJECT-STATUS.md`.

## Tài liệu theo nhóm

| Nhóm | Tài liệu | Vai trò |
|---|---|---|
| Phạm vi | `docs/PHASE-1-SCOPE.md`, `docs/PHASE-2-BACKLOG.md` | In scope/out of scope |
| Đề xuất kiến trúc | `docs/PHASE-2-ARCHITECTURE-PROPOSAL.md` | Kiến trúc/gap-analysis đích (PDF ngoài luồng) — **chưa áp dụng**; KHÔNG còn quyết định thứ tự thi công, xem `docs/refactor/BATCH-TASK-MAP.md` |
| Refactor playbook | `docs/refactor/PLAYBOOK.md` (cách vận hành), `docs/VIECLAM88_TASK_REGISTRY_V2.3.md` (KEY/GATE/DONE/NEXT từng `TASK x.y`, chạy qua `/task-cycle`), `docs/refactor/BATCH-TASK-MAP.md` | Nguồn: `docs/VIECLAM88_15_KE_HOACH_SUA_SLASH_COMMANDS_V2.1_TOI_UU.pdf`; `BATCH-TASK-MAP.md` đối chiếu với Batch ADR-080, **thứ tự Task x.y là chính thức**. `docs/refactor/TASK-INDEX.md`/`tasks/` chỉ còn hồ sơ lịch sử TASK 0.1 |
| Refactor baseline | `docs/refactor/00-CURRENT-BASELINE.md`, `docs/refactor/01-ROLLBACK-PLAN.md` | Snapshot kỹ thuật + kế hoạch backup/rollback trước khi chạy batch migration Phase 2 (kết quả TASK 0.1) |
| Nghiệp vụ | `docs/CORE-FLOWS.md` | 6 luồng cốt lõi, pipeline, verification, duplicate/merge |
| Database | `docs/DATABASE-DICTIONARY.md`, `docs/ERD.md` | Schema chi tiết và quan hệ |
| Delivery | `ROADMAP.md` (lịch sử, đóng băng), `docs/PROJECT-STATUS.md` (trạng thái thật) | Giai đoạn Phase 1 đã qua và trạng thái hiện tại |
| Claude workflow | `docs/CLAUDE-SKILLS.md` | Chọn skill, luồng vibe coding và ví dụ lệnh |
| Contract | `docs/ROUTE-MAP.md`, `docs/ACCEPTANCE-CRITERIA.md` | Route/policy và testable outcomes |
| Quyết định | `docs/decisions/INDEX.md` | ADR chia theo chủ đề |
| UI | `UI-REFERENCE.md`, `docs/ui-reference/phase-1/` | Quy tắc và ảnh tham khảo Phase 1 |

## Claude Code

- Global: `CLAUDE.md`.
- Rule tự nạp theo path: `.claude/rules/`.
- Workflow: `.claude/skills/`; danh mục: `docs/CLAUDE-SKILLS.md`.
- Reviewer read-only: `.claude/agents/reviewer.md`.
- Kiểm tra cấu hình/tài liệu: `python scripts/check-claude-config.py`.

## Quy tắc đọc context

- Bắt đầu bằng `docs/PROJECT-STATUS.md` và một dòng tương ứng trong `docs/CONTEXT-MAP.md`.
- Chỉ mở section liên quan trong tài liệu dài; không tải toàn bộ Dictionary/Core Flows nếu task nhỏ.
- ADR chỉ đọc file chủ đề chứa mã ADR được tham chiếu.
- Ảnh `out-of-scope/` không phải yêu cầu Phase 1.
