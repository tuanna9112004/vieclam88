# CLAUDE.md — Tuyển Dụng Miền Bắc

## 1. Vai trò và mục tiêu

Bạn là Senior PHP Laravel Developer, Software Architect và UI/UX Designer.

Xây dựng website tuyển dụng cho công ty cung ứng nhân sự tại các khu công nghiệp miền Bắc. Hệ thống gồm:

- Website công khai để tìm việc và tiếp nhận ứng viên.
- Trang HR nội bộ để nhân viên quản lý, xử lý hồ sơ.
- Một codebase và một database dùng chung.

Nguyên tắc: ứng tuyển nhanh, dữ liệu sạch, dễ dùng trên điện thoại, HR xử lý được toàn bộ quy trình.

## 1.1. Giai đoạn triển khai

Dự án chia làm 2 giai đoạn. Không xây Phase 2 khi chưa có yêu cầu.

**Phase 1 (hiện tại — ưu tiên thực hiện trước):**

- Website công khai đầy đủ theo mục 5 (tìm việc, xem chi tiết, ứng tuyển, giới thiệu công ty).
- Trang quản lý nội bộ **rút gọn**, dùng cho nhân viên đăng tin và xử lý hồ sơ thủ công:
  - Đăng nhập nhân viên (role `staff`, `admin`; chưa cần `manager` riêng).
  - CRUD công ty và việc làm (mục 6.2).
  - Danh sách và chi tiết hồ sơ ứng tuyển, lọc theo việc làm/công ty/ngày.
  - Trạng thái đơn giản: `new` (Mới), `contacted` (Đã liên hệ), `done` (Xong) — thay cho 10 trạng thái pipeline đầy đủ ở mục 6.3.
  - Ghi chú nội bộ tự do cho mỗi hồ sơ.
  - Nhân viên tự nhận xử lý hồ sơ qua cột `assigned_to` (nullable). Mọi nhân viên vẫn xem được toàn bộ danh sách ứng viên — **chưa** giới hạn quyền xem theo người phụ trách, chưa cần Policy phân quyền theo `assigned_to`.
  - Xuất Excel danh sách ứng viên.
- Không xây ở Phase 1: dashboard thống kê (6.1), lịch sử đổi trạng thái chi tiết theo từng bước (6.3), phân công ứng viên bắt buộc và giới hạn quyền xem theo nhân viên (6.4), audit log, giao diện quản lý nguồn/referrer (6.5 phần nguồn ứng viên).

**Phase 2 (sau này, khi cần "chuyển đổi số" quy trình HR):**

- Triển khai đầy đủ mục 6 như mô tả gốc: dashboard, pipeline 10 trạng thái có lịch sử, phân công và giới hạn quyền xem theo `assigned_to`, audit log, quản lý nguồn ứng viên.

**Ràng buộc kỹ thuật xuyên suốt cả 2 giai đoạn:** database (mục 8) phải được thiết kế đầy đủ ngay từ Phase 1, kể cả các bảng/cột mà Phase 1 chưa dùng hết (`application_status_histories`, `candidate_sources`, cột `assigned_to` trong `applications`...). Việc này để tránh phải migrate và mất dữ liệu lịch sử khi chuyển sang Phase 2.

## Cấu trúc tài liệu

Đặc tả chi tiết được tách theo chủ đề trong `rules/` để CLAUDE.md gốc gọn, dễ scan. Số mục
(2, 3, 4...) giữ nguyên xuyên suốt các file — chỉ đổi vị trí vật lý, không đổi nội dung hay
số thứ tự, nên mọi tham chiếu "mục X" trong dự án (kể cả từ `UI-REFERENCE.md`) vẫn đúng.

- @.claude/rules/tech-stack.md — mục 2 (Công nghệ), mục 3 (Kiến trúc)
- @.claude/rules/roles-business-rules.md — mục 4 (Vai trò), mục 7 (Quy tắc nghiệp vụ)
- @.claude/rules/public-site.md — mục 5 (Website công khai, 5.1–5.7)
- @.claude/rules/hr-admin.md — mục 6 (Trang quản trị HR, 6.1–6.5)
- @.claude/rules/data-model.md — mục 8 (Dữ liệu cốt lõi)
- @.claude/rules/ui-guidelines.md — mục 9 (Giao diện)
- @.claude/rules/security-seo-testing.md — mục 10 (Bảo mật), 11 (SEO), 12 (Kiểm thử)
- @.claude/rules/scope-standards.md — mục 13 (Ngoài phạm vi MVP), 14 (Quy chuẩn thực thi)

Tài liệu tham khảo thiết kế riêng (không phải quy tắc bắt buộc): `UI-REFERENCE.md` ở thư mục gốc.

## 15. Quy trình làm việc giữa các session

CLAUDE.md đóng vai trò bộ nhớ dài hạn của dự án, không chỉ là đặc tả tĩnh. Mục 16 (Trạng
thái dự án) là nhật ký tiến độ được cập nhật liên tục — đọc và ghi đúng quy trình sau để
không mất ngữ cảnh giữa các session:

1. **Kết thúc session đúng cách:** trước khi đóng session, cập nhật mục 16 với: việc đã
   hoàn thành, trạng thái hiện tại của từng phần đang làm dở, bước tiếp theo cần làm, và
   các quyết định quan trọng kèm lý do (đặc biệt nếu lý do không nằm sẵn trong code).
2. **Bắt đầu session mới đúng cách:** đọc mục 16 trước khi bắt tay vào việc mới, để biết
   dự án đang ở đâu, bước tiếp theo là gì, và có gì cần chú ý.
3. **Session phức tạp:** dùng Plan Mode để lên kế hoạch dựa trên CLAUDE.md và mục 16 trước
   khi thực hiện, đặc biệt khi việc sắp làm ảnh hưởng nhiều file hoặc thay đổi kiến trúc.

## 16. Trạng thái dự án

*(Cập nhật ở cuối mỗi session — xem quy trình ở mục 15)*

**Đã hoàn thành:**

- Chốt phạm vi triển khai theo 2 giai đoạn (mục 1.1): Phase 1 = web công khai đầy đủ + trang
  quản lý nội bộ rút gọn; Phase 2 = HR pipeline đầy đủ.
- Chốt công nghệ (mục 2): PHP 8.2/8.3, Laravel bản mới nhất (thay vì khóa cứng PHP 8.0/Laravel 9
  đã EOL), do production chạy trên VPS riêng không bị giới hạn phiên bản.
- Chốt cơ chế gán phụ trách hồ sơ: cột `applications.assigned_to` (nullable) có sẵn trong schema
  từ đầu, nhưng Phase 1 chưa enforce quyền xem theo người phụ trách (mục 1.1, 6.3, 8).
- Phân tích giao diện tham khảo từ Viec3mien.vn, tách riêng thành `UI-REFERENCE.md` — ghi rõ
  pattern nên học và pattern không được copy (đặc biệt: không thu CCCD/Dân tộc ở form công khai).
- Đánh giá đề xuất dựng sẵn cấu trúc `.claude/` đầy đủ (rules/, agents/, skills/...) — quyết định
  ban đầu là **chưa dựng** vì chưa có git repo/code thật; đã `git init` sau đó.
- Tách CLAUDE.md thành file chỉ mục gọn + `.claude/rules/*.md` theo chủ đề (xem "Cấu trúc tài
  liệu" ở trên), sớm hơn ngưỡng ~500-600 dòng từng đề ra — làm theo yêu cầu trực tiếp của người
  dùng, không phải vì file đã quá dài. Ban đầu đặt nhầm `rules/` ở root, đã sửa lại vào đúng
  `.claude/rules/` theo đúng convention. Số mục giữ nguyên, chỉ đổi vị trí vật lý. `.claude/`
  giờ đã tồn tại nhưng mới chỉ có `rules/` — chưa có `settings.json`, `agents/`, `skills/`.
- Đổi tên toàn bộ 18 file trong `viec3mien_giaodienweb/` theo quy ước tiền tố số mục CLAUDE.md
  (`05.x-*` = mục 5 theo mục con, `13-*` = mục 13/ngoài phạm vi, `khac-*` = không khớp mục nào),
  để lọc theo tên thay vì mở từng ảnh. Cập nhật `UI-REFERENCE.md` khớp tên file mới, thêm mục
  "Quy ước đặt tên file" giải thích quy tắc. Phát hiện thêm: `khac-tin-tuc-blog-chua-co-trong-spec.png`
  (trang tin tức) không khớp mục nào trong CLAUDE.md — chưa quyết định có bổ sung vào spec không.
- Commit git lần đầu (`4ddddaf`) — toàn bộ CLAUDE.md, `.claude/rules/`, UI-REFERENCE.md và
  ảnh tham khảo giờ đã có version history.
- Lên `ROADMAP.md` (file riêng ở root) — lộ trình 8 giai đoạn hoàn thiện Phase 1, có checklist
  từng việc. Đây là bản lưu bền của kế hoạch, không phụ thuộc plan file tạm của Plan Mode (plan
  file ở `C:\Users\MSI\.claude\plans\` bị ghi đè mỗi lần vào Plan Mode cho việc khác).

**Trạng thái hiện tại:** Đã có git repo với 1 commit. Chưa viết code. Đang ở giai đoạn hoàn
thiện đặc tả (CLAUDE.md + `.claude/rules/`), tài liệu tham khảo thiết kế (UI-REFERENCE.md) và
lộ trình (ROADMAP.md) trước khi bắt đầu Giai đoạn 0 còn lại (setup Laravel).

**Bước tiếp theo (chưa làm):** Xem `ROADMAP.md` — đang ở Giai đoạn 0, việc kế tiếp là
`composer create-project` (cần xác nhận người dùng trước khi chạy) và chốt cơ chế routing HR.

**Quyết định quan trọng cần nhớ:**

- Không dùng Livewire hay bất kỳ SPA framework nào trừ khi có yêu cầu mới — giữ Blade +
  Bootstrap 5 + Alpine.js thuần theo mục 2.
- Routing HR theo domain (`hr.tencongty.vn`) vs path (`/hr` trên XAMPP) — **chưa chốt cơ chế
  chuyển đổi cụ thể**, cần quyết định trước khi viết route HR để tránh viết lại.
- Cấu trúc `.claude/` (settings.json, agents/, skills/) — vẫn chưa dựng, chỉ thêm khi có nhu
  cầu thật cụ thể (chạy lệnh thật cần allowlist, có code thật để agent review...). Riêng phần
  `rules/` đã tách sớm theo yêu cầu người dùng (xem "Đã hoàn thành" ở trên).
