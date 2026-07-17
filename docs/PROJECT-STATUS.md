# Project Status

## Phase / slice hiện tại

Giai đoạn 0 (chuẩn hóa tài liệu) — chưa có mã nguồn Laravel.

## Đã hoàn thành

- Bộ tài liệu đầy đủ: `CLAUDE.md` (điều phối, 57 dòng), `.claude/rules/*.md` (9 file,
  path-scoped), `docs/` (CONTEXT-MAP, ERD, DATABASE-DICTIONARY, ROUTE-MAP,
  ACCEPTANCE-CRITERIA, DECISIONS, PROJECT-STATUS), `.claude/skills/{implement,db-task,
  review-changes,handoff}`, `.claude/agents/reviewer.md`.
- Audit toàn dự án: xóa `.claude/rules/workflow-session-handoff.md` (trùng nội dung với
  `/handoff` skill, thiếu frontmatter `paths:`); sửa toàn bộ tham chiếu "mục X.Y" lỗi thời
  còn sót lại sau khi rules được rút gọn bỏ số thứ tự (`ROADMAP.md`, `UI-REFERENCE.md`,
  `docs/ERD.md`, `docs/DECISIONS.md`, `docs/DATABASE-DICTIONARY.md`,
  `docs/ACCEPTANCE-CRITERIA.md`).
- 25 bảng Phase 1 đã chốt cấu trúc (ERD + dictionary), routing `/hr`, PHP 8.4/Laravel 13.

## Verification

Không chạy được `scripts/check-claude-config.py` (máy dev chưa cài Python). Đã tự đối chiếu
thủ công theo đúng logic script: required files ✓, `CLAUDE.md` 57 dòng ✓, không `@` import ✓,
9/9 rule file path-scoped ✓, `settings.json` không chặn nhầm `.env.example`/`storage` ✓,
`viec3mien_giaodienweb/` đã xóa ✓, `PROJECT-STATUS.md` ≤45 dòng ✓.

## Blockers

- 6 chỗ đánh dấu **[đề xuất]** trong `docs/DATABASE-DICTIONARY.md` (`jobs.employment_type`,
  `jobs.close_reason`, `pages.status`, `settings.type`, `company_contacts.status`) — cần xác
  nhận nghiệp vụ trước khi tạo migration thật.
- Chưa cài Python trên máy dev — không tự động chạy được `check-claude-config.py`.

## Bước tiếp theo

1. Cài Python, chạy `python scripts/check-claude-config.py` để xác nhận lại bằng máy.
2. Chốt các enum **[đề xuất]**.
3. `composer create-project` Laravel 13.x (cần xác nhận trước khi chạy).
