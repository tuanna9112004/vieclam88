---
paths:
  - "app/**/*.php"
  - "bootstrap/**/*.php"
  - "config/**/*.php"
  - "database/**/*.php"
  - "routes/**/*.php"
  - "resources/**/*.{php,blade.php,js,css}"
  - "tests/**/*.php"
  - "composer.json"
  - "package.json"
  - "vite.config.*"
---

# Stack và kiến trúc

- PHP 8.4.x, Laravel 13.x, **MariaDB 11.4 LTS** (ADR-039, khóa đúng phiên bản cho local/test/
  staging/production — không ghi chung chung "MariaDB").
- Blade + Bootstrap 5.3 + Alpine.js; Node LTS chỉ build Vite.
- Laravel monolith; HR dùng `/hr`; một session auth và một bảng `users`.
- Vai trò Phase 1: `staff`, `admin` (`users` chỉ phục vụ nhân viên nội bộ). Không có role `candidate` — Candidate Account là Phase 2 (ADR-028); guest không phải role.
- Controller mỏng; input qua Form Request; authorization qua Policy; nghiệp vụ nhiều bước ở Action/Service.
- Dùng Eloquent, migration, factory, seeder, PHP enum và `DB::transaction()`. Enum trạng thái cốt lõi (`jobs.status`, `applications.stage`) dùng DB `enum()`; enum phụ khác dùng `varchar` + PHP backed enum (ADR-055, `docs/DATABASE-DICTIONARY.md`).
- Seeder tách 2 nhóm: production-safe (chạy mọi môi trường: `settings`, `work_shifts`, `recruitment_sources`, `administrative_units`) và demo/test (chỉ `local`/`testing`, không đăng ký trong seeder mặc định production) — ADR-051. Admin đầu tiên tạo bằng `php artisan app:create-admin` (ADR-050), không phải seeder.
- Tiền VND dùng `bigint unsigned`, không dùng float.
- Không hard-delete dữ liệu nghiệp vụ; không tạo bảng/cột dự phòng.
- Không tự thêm React/Vue/Next/Nuxt, Node backend, Redis, Elasticsearch, Docker, Livewire hoặc microservice. Chỉ thêm khi có nhu cầu đo được và ADR được chấp nhận.
- Không đổi schema nguồn chỉ để code thuận tiện; khác biệt phải được báo cáo.

Nguồn chi tiết: `docs/DECISIONS.md`, `docs/DATABASE-DICTIONARY.md`.
