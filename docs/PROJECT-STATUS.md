# Project Status

## Phase / slice hiện tại

**Giai đoạn 0 hoàn thành — Phase 1 Plan, Database và Claude Context Baseline v1.0 đã đóng băng.** Commit `10039ef` (push `origin/main`); `/release-gate baseline` xác nhận READY, 0 warning. Chưa có source Laravel hoặc migration.

## Đã hoàn thành

- Khóa Phase 1/Phase 2, 6 Core Flows, schema 28 business tables, migration order và acceptance criteria.
- Chốt Job draft/publish/verification, branch ownership, submission concurrency, duplicate review, merged family, PII schema và authorization.
- Tách ADR theo chủ đề tại `docs/decisions/`; thêm `docs/INDEX.md` và Context Map tối giản.
- Rút gọn `CLAUDE.md`; chia rule theo architecture/schema/job/application/auth/public/security/test/SEO; bổ sung bộ skill vibe coding thông minh và semantic checker.
- Commit + push baseline (`10039ef`), release gate baseline READY — Plan/Database/Claude Context Baseline v1.0 chính thức đóng băng.

## Verification bắt buộc trước baseline

```bash
python scripts/check-claude-config.py
git diff --check
```

## Blockers

- **Migration:** không còn.
- **Trước coding:** môi trường PHP 8.4, Composer, Node LTS, MariaDB 11.4 chưa được xác nhận/cài đặt.
- **Go-live, không chặn migration:** retention, mask snapshot, verification validity days, nguồn dataset hành chính, redact free-text nâng cao.
- **Phase 2 decision:** auto-pause Job mặc định tắt.

## Bước tiếp theo

1. Gắn git tag chính thức cho baseline (`git tag`/`git push` cần xác nhận riêng theo `.claude/settings.json`).
2. Kiểm tra/cài môi trường và khởi tạo Laravel 13.x sau khi được phép.
3. Triển khai Nhóm 1 trong `ROADMAP.md`: administrative units, branches, users và auth nền tảng, kèm test.
