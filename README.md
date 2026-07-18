# vieclam88

Hệ thống tuyển dụng cho công ty cung ứng lao động tại các khu công nghiệp miền Bắc: website public và HR `/hr`, dùng chung Laravel monolith và MariaDB.

## Trạng thái

Phase 1 Plan/Database Baseline đã khóa về nội dung; chưa khởi tạo source Laravel. Xem `docs/PROJECT-STATUS.md`.

## Làm việc với Claude Code

1. Mở Claude Code tại root; global rules nằm ở `CLAUDE.md`.
2. Bắt đầu từ `docs/INDEX.md` và `docs/CONTEXT-MAP.md`; rule trong `.claude/rules/` tự nạp theo path.
3. Điểm vào mặc định: `/vibe-task <mục nhỏ>`; xem toàn bộ tại `docs/CLAUDE-SKILLS.md`.
4. Kiểm tra cấu hình/tài liệu:

```bash
python scripts/check-claude-skills.py
python scripts/check-claude-config.py
```

## Nguồn chính

- Scope: `docs/PHASE-1-SCOPE.md`; backlog: `docs/PHASE-2-BACKLOG.md`.
- Flow: `docs/CORE-FLOWS.md`; schema: `docs/DATABASE-DICTIONARY.md`, `docs/ERD.md`.
- Route/test contract: `docs/ROUTE-MAP.md`, `docs/ACCEPTANCE-CRITERIA.md`.
- ADR: `docs/decisions/INDEX.md`; roadmap: `ROADMAP.md`.
- UI: `UI-REFERENCE.md`, `docs/ui-reference/phase-1/`.
