# Baseline kỹ thuật hiện tại (TASK 0.1)

> **Mục đích:** chốt snapshot kỹ thuật của repository **trước khi** bắt đầu áp dụng lộ trình
> migration Phase 2 (ADR-080, `docs/PHASE-2-ARCHITECTURE-PROPOSAL.md`). Tài liệu này **không thay
> đổi nghiệp vụ, không tạo migration** — chỉ ghi lại hiện trạng để đối chiếu/rollback sau này.
> Nguồn sự thật chi tiết vẫn là `docs/DATABASE-DICTIONARY.md`/`docs/ERD.md`/`docs/ROUTE-MAP.md`/
> `docs/CORE-FLOWS.md` (theo `docs/INDEX.md`) — mục dưới đây chỉ tóm tắt, không chép lại contract.

Ngày lập baseline: 2026-07-23. Commit HEAD tại thời điểm lập: `97e97ae6b42d03f1bd901f9ce641ba7008c0a783`
(branch `main`, **chưa push** — xem `docs/PROJECT-STATUS.md`). Working tree có thay đổi
chưa commit (ADR-079 + `PHASE-2-ARCHITECTURE-PROPOSAL.md`, xem `git status` mục "Rollback").

## 1. Version môi trường mục tiêu

Theo `CLAUDE.md`/`.claude/rules/architecture.md`:

| Thành phần | Mục tiêu | Xác nhận thực tế trong môi trường lập baseline |
|---|---|---|
| PHP | 8.4.x | `PHP 8.4.23 (cli)` — đạt |
| Laravel | 13.x | theo `composer.json` (không đổi ở task này) |
| MariaDB | 11.4 LTS | không xác nhận được version server thật ở đây (không đọc `.env`); xem mục 4 — client binary `mariadb-dump`/`mariadb` **không có** trên PATH của máy lập baseline này |
| Blade/Bootstrap/Alpine/Vite | cố định, không đổi | không đổi |

## 2. Snapshot bảng dữ liệu hiện tại (source of truth: `docs/DATABASE-DICTIONARY.md` mục 9.1–9.28)

**28 bảng nghiệp vụ** đang chạy thật (khớp 28 file migration trong `database/migrations/`,
đã `migrate` xong, không phải target Phase 2). Liệt kê theo nhóm, không chép lại cột/constraint:

- **Nền tảng:** `administrative_units`, `branches`, `users`.
- **Danh mục:** `industrial_parks`, `work_shifts`, `recruitment_sources`, `settings`.
- **Company:** `companies`, `company_locations`, `company_contacts`.
- **Job:** `jobs`, `job_locations`, `job_work_shifts`, `job_verifications`, `job_status_histories`,
  `job_branch_histories`.
- **Candidate/Application:** `candidates`, `candidate_contacts`, `applications`,
  `candidate_duplicate_reviews`.
- **Xử lý hồ sơ:** `application_status_histories`, `application_contact_attempts`,
  `application_appointments`, `application_branch_histories`, `application_notes`.
- **Admin tools:** `export_logs`, `pages`, `faqs`.

Các bảng `provinces`/`wards`/`industrial_park_wards`/`industries`/`employment_types`/`job_images`/
`candidate_documents`/`activity_logs` là **target Phase 2** (`DATABASE-DICTIONARY.md` mục
9.29–9.36) — **chưa tồn tại**, không thuộc snapshot này.

## 3. Route hiện tại (source of truth: `docs/ROUTE-MAP.md`)

`php artisan route:list` xác nhận **106 route** đã đăng ký, gồm:

- **Public** (không đăng nhập): `home`, `jobs.index`/`jobs.show`, `applications.store`,
  `companies.index`/`companies.show`, `industrial-parks.show`, `faqs.index`,
  `pages.about`, `contact.show`, `sitemap`.
- **`/hr`** (staff/admin, qua middleware auth): `hr.dashboard`, `hr.jobs.*` (CRUD + publish/pause/
  close/duplicate/restore/transfer-branch/verify), `hr.applications.*` (index/show/export/stage/
  notes/appointments/contacts/transfer-branch), `hr.candidates.show/merge/anonymize`,
  `hr.duplicate-reviews.*`, `hr.companies.*`, `hr.company-locations.*`, `hr.company-contacts.*`,
  `hr.branches.*`, `hr.staff.*`, `hr.administrative-units.*`, `hr.industrial-parks.*`,
  `hr.pages.*`, `hr.faqs.*`, `hr.settings.*`, `hr.password.*`, `hr.login`/`hr.logout`.
- Route framework (`storage.local`, `up`) không thuộc route nghiệp vụ.

## 4. Workflow đang được bảo vệ (source of truth: `docs/CORE-FLOWS.md`)

6 luồng cốt lõi đã chốt và có test bảo vệ transition matrix:

1. Luồng 1 — Tạo và xuất bản việc làm (Job Draft/Publish Predicate, verification, branch transfer).
2. Luồng 2 — Ứng viên tìm và chọn việc (hiển thị `closed`/`paused`, CTA gọi/Zalo theo branch).
3. Luồng 3 — Ứng viên gửi form ứng tuyển (Submission Token Lifecycle, Duplicate Candidate Contract).
4. Luồng 4 — Nhân viên cơ sở xử lý hồ sơ (branch scoping, không có claim/assign).
5. Luồng 5 — Cập nhật trạng thái/kết quả (transition matrix, workflow cycle, reopen contract).
6. Luồng 6 — Chuyển cơ sở ngoại lệ + duplicate/merge contract (merged-family, chống vòng lặp).

Cộng thêm: Bootstrap Sequence + Initial Admin (ADR-050), Password-first-change (ADR-067),
Dashboard 11 KPI, CSV Export (`export_logs`), Administrative Unit import (ADR-079).

## 5. Test hiện có

Chạy `php artisan test` tại thời điểm lập baseline (2026-07-23):

```
tests: 817, passed: 811, failed: 6 (2 failures + 4 errors), assertions: 2372
```

6 fail đều thuộc `Tests\Feature\Console\DatabaseBackupContentTest` và
`DatabaseBackupRestoreCommandTest` — nguyên nhân: môi trường lập baseline này **không có** binary
`mariadb-dump`/`mariadb` trên PATH (đã xác nhận bằng `which`), không phải regression nghiệp vụ.
Chi tiết cơ chế và điều kiện cần trước khi chạy migration thật: xem
[`01-ROLLBACK-PLAN.md`](01-ROLLBACK-PLAN.md) mục 2.

## 6. Xung đột với Baseline 1.1 (PDF `bao_cao_cau_truc_lai_du_an_vieclam88_v1.1.pdf`)

Toàn bộ ma trận đối chiếu chi tiết đã có ở
`docs/PHASE-2-ARCHITECTURE-PROPOSAL.md` mục "Ma trận đối chiếu PDF ↔ hiện trạng" — không chép lại
ở đây. Tóm tắt các điểm mức **CRITICAL** (đổi ràng buộc cốt lõi, không phải chỉ thêm bảng):

- `users.role` 2 cấp (`admin`/`staff`) vs PDF đề xuất 3 cấp (`super_admin`/`branch_admin`/`staff`).
- `jobs.company_id` NOT NULL bắt buộc vs PDF cho phép nullable (`job_type=direct`).
- Địa điểm Job qua `JobLocation → CompanyLocation.administrative_unit_id` vs PDF dùng
  `jobs.work_ward_id` FK trực tiếp.
- `company_locations` và `administrative_units` là bảng lõi đang chạy (FK từ nhiều bảng) vs PDF
  liệt cả hai vào danh sách "không nên tạo" (mục 17.2 PDF) — xung đột trực tiếp ADR-010/CLAUDE.md.

Không tự chọn phương án cho các mục CRITICAL này ở task hiện tại — chờ đúng batch trong lộ trình
9-batch (`PHASE-2-ARCHITECTURE-PROPOSAL.md` mục "Kế hoạch migration").
