---
paths:
  - "app/**/*.php"
  - "bootstrap/**/*.php"
  - "config/**/*.php"
  - "database/**/*.php"
  - "routes/**/*.php"
  - "resources/**/*"
  - "tests/**/*.php"
  - "composer.json"
  - "package.json"
  - "vite.config.*"
---

# Kiến trúc

- PHP 8.4.x, Laravel 13.x, MariaDB 11.4 LTS; Blade + Bootstrap 5.3 + Alpine.js; Laravel monolith, HR tại `/hr`.
- Không tự thêm React/Vue/Livewire, Redis, queue worker, Docker, microservice hoặc service ngoài stack. Thay đổi phải có ADR.
- Controller mỏng; input qua Form Request; quyền qua Policy; nghiệp vụ nhiều bước qua Action/Service và transaction.
- Phase 1 dùng `SESSION_DRIVER=file`, `CACHE_STORE=file`, `QUEUE_CONNECTION=sync`; không tạo bảng hạ tầng chưa dùng.
- `users` chỉ `staff`/`admin`; không có Candidate Account. Không tạo bảng/cột dự phòng Phase 2.
- Tiền VND dùng unsigned bigint; không dùng float. Không hard-delete dữ liệu tuyển dụng cốt lõi.
- Nguồn: `docs/PHASE-1-SCOPE.md`, `docs/decisions/INDEX.md`, `docs/DATABASE-DICTIONARY.md`.
- Kiến trúc mục tiêu Phase 2 đã duyệt (ADR-080, `docs/PHASE-2-ARCHITECTURE-PROPOSAL.md`) nhưng CHƯA migrate — thứ tự/nội dung thi công thật theo `TASK x.y` ở `docs/refactor/TASK-INDEX.md` (đối chiếu Batch↔Task: `docs/refactor/BATCH-TASK-MAP.md`), không tự suy diễn theo bảng Batch.
