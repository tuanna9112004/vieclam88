# Project Status

## Phase / slice hiện tại

**Nhóm 1 (nền tảng) DONE, đã commit.** Đăng nhập HR **DONE** — login/logout, `EnsureUserIsActive` (ADR-077), `EnsurePasswordChanged` + `hr.password.change/update` (ADR-067), `robots.txt` chặn `/hr`. `/verify-task` + `/review-changes` PASS, 54/54 test. Chưa commit.

## Quyết định quan trọng đã đưa ra (và lý do)

- Xóa 3 migration mặc định Laravel (`users`/`cache`/`jobs`) — sai thứ tự/schema so với Dictionary, tạo bảng hạ tầng thừa (`CACHE_STORE=file`/`QUEUE_CONNECTION=sync` không cần DB).
- Tailwind (mặc định scaffold) → Bootstrap 5.3 + Alpine.js — đúng stack đã khóa, không tự đổi framework khi chưa có ADR.
- Xóa 4 Branch Action rỗng, chỉ giữ `CreateStaffAction` — 4 class kia không có logic/không có caller, đúng "không class rỗng hàng loạt".
- Chưa build Controller/Route/Blade cho `hr.branches.*`/`hr.staff.*` — chưa có auth foundation, dựng route không đăng nhập được thì không "chạy thử thủ công" được.
- `.env.testing` tách DB test khỏi dev — đúng contract "test không được chạm database development".
- Guard chặn nhầm DB đặt trong `createApplication()`, không phải sau `parent::setUp()` — `RefreshDatabase` chạy `migrate:fresh` bên trong `parent::setUp()`, đặt sau sẽ quá muộn (xác nhận qua đọc source Laravel).

## Đã hoàn thành

- Baseline Plan/Database/Claude Context v1.0 đóng băng (commit `10039ef`); Nhóm 1 nền tảng (commit `2d2a3a9`, đã push).
- `hr.login`/`hr.login.store`/`hr.logout`/`hr.dashboard`/`hr.password.change`/`hr.password.update`: rate limit theo email+IP, regenerate session sau login, invalidate session khi logout, guest bị chặn `/hr/*`, `status=locked` không login được và mất quyền ngay giữa phiên (`EnsureUserIsActive`), `password_changed_at=null` ép về đổi mật khẩu (`EnsurePasswordChanged`), `robots.txt` chặn `/hr` — 17 test mới, 54/54 tổng.
- `User::$fillable` bổ sung `password_changed_at` (trước đó `CreateAdminCommand`/`CreateStaffAction` gán field này bị guard âm thầm bỏ qua, chỉ "đúng" nhờ trùng default DB).

## Verification gần nhất

```bash
php artisan test        # PASS 54/54
npm run build / check-claude-config.py / git diff --check   # tất cả PASS
```

## Blockers

Không có.

## Bước tiếp theo

1. **NEXT:** Commit slice Đăng nhập HR khi người dùng xác nhận.
2. Nhóm 2 (`ROADMAP.md`): `industrial_parks`, `work_shifts`, `recruitment_sources`, `settings`.
