# Đối chiếu Batch (ADR-080) ↔ Task x.y (Playbook 43-task)

> Giải quyết xung đột: `docs/PHASE-2-ARCHITECTURE-PROPOSAL.md` (ADR-080) chia migration thành 9
> Batch; `docs/refactor/PLAYBOOK.md` + `docs/VIECLAM88_15_KE_HOACH_SUA_SLASH_COMMANDS_V2.1_TOI_UU.pdf`
> chia thành 43 Task (Phần 0–13). Hai tài liệu mô tả cùng một schema đích nhưng khác thứ tự và
> khác độ chi tiết. File này là nguồn duy nhất đối chiếu hai bên — không lặp lại nội dung chi tiết
> của từng bên (xem `PHASE-2-ARCHITECTURE-PROPOSAL.md` cho lý do kiến trúc, `TASK-INDEX.md`/
> `tasks/` cho nội dung thi công).

## Quyết định về thứ tự thực thi

**Thứ tự thi công chính thức = theo Task x.y** (`docs/refactor/TASK-INDEX.md`), vì đó là hệ đang
được vận hành thật qua slash command (`CLAUDE.md`/`AGENTS.md` mục "Command chứa `TASK x.y`").
Bảng Batch trong `PHASE-2-ARCHITECTURE-PROPOSAL.md` **giữ nguyên vai trò tài liệu kiến trúc/gap
analysis** (lý do đổi, ma trận đối chiếu PDF ↔ hiện trạng, mức độ rủi ro CRITICAL/HIGH) — **không
còn dùng để quyết định thứ tự thi công**. Khi hai bên gợi ý thứ tự khác nhau (xem mục "Chỗ lệch"
bên dưới), thứ tự Task x.y thắng.

## Bảng đối chiếu

| Batch (ADR-080) | Nội dung Batch | Task x.y tương ứng (Phần) | Ghi chú lệch |
|---|---|---|---|
| 1 | `provinces`, `wards` + backfill | 1.1–1.3 (Phần 1) | Khớp thứ tự |
| 2 | `industrial_park_wards`, `industrial_parks.branch_id` | 3.1–3.3 (Phần 3) | **Lệch thứ tự** — Batch xếp trước Batch 3 (role), Task xếp sau Task 2.x (role) |
| 3 | `users.role` + `branch_admin` | 2.1–2.3 (Phần 2) | **Lệch thứ tự** — xem trên; theo quyết định ở mục trước, làm role (2.x) trước KCN (3.x) |
| 4 | `industries`, `employment_types` | 4.1–4.2, 5.1–5.3 (Phần 4–5) | Khớp nhóm, không lệch thứ tự tương đối |
| 5 | `jobs.work_ward_id`/`industry_id`/`employment_type_id` | 6.1–6.5 (Phần 6, một phần) | Không tách 1-1 được — xem mục dưới |
| 6 | `job_images`, `candidate_documents`, `activity_logs`, cột `candidates.*` | 7.1–8.2 (job_images) + 9.1–9.5 (candidate/CV) + 10.1–10.2 (activity_logs) | Batch 6 gộp 3 mảng mà Task tách thành 3 Phần riêng — dùng Task làm chuẩn chia nhỏ |
| 7 | `jobs.company_id` nullable + `job_type` | 6.1–6.5 (Phần 6, một phần) | Không tách 1-1 được — cùng nằm trong Phần 6 với Batch 5 |
| 8 (Switch) | Đổi read/write sang schema mới | 12.1–12.3 (Phần 12) | Khớp nhóm |
| 9 (Contract) | Xóa bảng/cột cũ | 12.4 | Khớp nhóm |

## Chỗ lệch cần biết khi thi công

- **Batch 5 và Batch 7 không tách được 1-1 với Task**: cả hai cùng rơi vào Phần 6 (Task 6.1–6.5).
  Khi làm Task 6.x, phải tự xác định slice nào thuộc Batch 5 (cột địa điểm/ngành/loại hình trên
  `jobs`) và slice nào thuộc Batch 7 (`company_id` nullable) để áp đúng ghi chú rủi ro của từng
  Batch (Batch 7 đặc biệt: ADR-080 ghi rõ "chưa có AC đủ để tự code" — phải xác nhận nghiệp vụ đã
  chốt AC "tuyển trực tiếp" trước khi làm phần này của Task 6.x, dù Task 6.x có prompt riêng).
- **Batch 2 và Batch 3 đảo thứ tự**: theo quyết định trên, làm Task 2.x (role) trước Task 3.x (KCN)
  — khác thứ tự Batch 2 trước Batch 3 trong `PHASE-2-ARCHITECTURE-PROPOSAL.md`. Không có phụ thuộc
  kỹ thuật bắt buộc giữa hai việc (branch đã tồn tại từ Phase 1), nên đảo thứ tự an toàn.

## Khi phát hiện thêm lệch

Nếu khi thi công một Task x.y phát hiện nội dung khác với Batch tương ứng (không chỉ thứ tự mà cả
nội dung kỹ thuật), dừng phần đó và ghi blocker vào `docs/PROJECT-STATUS.md` — không tự chọn theo
bên nào ngoài quy tắc thứ tự đã chốt ở trên.
