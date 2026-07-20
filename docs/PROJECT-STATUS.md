# Project Status

## Phase / slice hiện tại

**Nhóm 1 (nền tảng) DONE, đã commit.** Laravel 13.20.0 đúng stack (Bootstrap 5.3 + Alpine.js, `Asia/Ho_Chi_Minh`, locale `vi`). 37/37 test pass. Gate 1.1–1.4 (`about` + `test` + kiến trúc) đã kiểm chứng PASS.

## Quyết định quan trọng đã đưa ra (và lý do)

- Xóa 3 migration mặc định Laravel (`users`/`cache`/`jobs`) — sai thứ tự/schema so với Dictionary, tạo bảng hạ tầng thừa (`CACHE_STORE=file`/`QUEUE_CONNECTION=sync` không cần DB).
- Tailwind (mặc định scaffold) → Bootstrap 5.3 + Alpine.js — đúng stack đã khóa, không tự đổi framework khi chưa có ADR.
- Xóa 4 Branch Action rỗng, chỉ giữ `CreateStaffAction` — 4 class kia không có logic/không có caller, đúng "không class rỗng hàng loạt".
- Chưa build Controller/Route/Blade cho `hr.branches.*`/`hr.staff.*` — chưa có auth foundation, dựng route không đăng nhập được thì không "chạy thử thủ công" được.
- `.env.testing` tách DB test khỏi dev — đúng contract "test không được chạm database development".
- Guard chặn nhầm DB đặt trong `createApplication()`, không phải sau `parent::setUp()` — `RefreshDatabase` chạy `migrate:fresh` bên trong `parent::setUp()`, đặt sau sẽ quá muộn (xác nhận qua đọc source Laravel).

## Đã hoàn thành

- Baseline Plan/Database/Claude Context v1.0 đóng băng (commit `10039ef`); môi trường PHP 8.4.23/Composer 2.10.2/MariaDB 11.4.3 verify PASS.
- Nhóm 1: `administrative_units`/`branches`/`users` (model/`BranchPolicy`/Action/console `app:create-admin`).
- Test isolation (`.env.testing`) + guard fix (9 test mới); `.env.example` driver đồng bộ Nhóm 1.

## Verification gần nhất

```bash
php artisan test                                                    # PASS 37/37
composer validate / check-claude-config.py / check-claude-skills.py / git diff --check   # tất cả PASS
```

## Blockers

Không có.

## Bước tiếp theo

1. **NEXT:** Auth foundation (login, `EnsureUserIsActive`, `EnsurePasswordChanged`).
2. Nhóm 2 (`ROADMAP.md`): `industrial_parks`, `work_shifts`, `recruitment_sources`, `settings`.
