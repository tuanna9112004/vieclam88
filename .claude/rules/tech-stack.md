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

- PHP 8.4.x, Laravel 13.x, MariaDB; khóa major version.
- Blade + Bootstrap 5.3 + Alpine.js; Node LTS chỉ build Vite.
- Laravel monolith; HR dùng `/hr`; một session auth và một bảng `users`.
- Vai trò Phase 1: `candidate`, `staff`, `admin`; guest không phải role.
- Controller mỏng; input qua Form Request; authorization qua Policy; nghiệp vụ nhiều bước ở Action/Service.
- Dùng Eloquent, migration, factory, seeder, PHP enum và `DB::transaction()`.
- Tiền VND dùng `bigint unsigned`, không dùng float.
- Không hard-delete dữ liệu nghiệp vụ; không tạo bảng/cột dự phòng.
- Không tự thêm React/Vue/Next/Nuxt, Node backend, Redis, Elasticsearch, Docker, Livewire hoặc microservice. Chỉ thêm khi có nhu cầu đo được và ADR được chấp nhận.
- Không đổi schema nguồn chỉ để code thuận tiện; khác biệt phải được báo cáo.

Nguồn chi tiết: `docs/DECISIONS.md`, `docs/DATABASE-DICTIONARY.md`.
