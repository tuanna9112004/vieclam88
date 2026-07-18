# UI Reference — Phân tích tham khảo từ Viec3mien.vn

Ghi lại pattern UX/luồng chức năng rút ra từ ảnh chụp màn hình trong
`docs/ui-reference/phase-1/` và `docs/ui-reference/out-of-scope/`. Đây là **tài liệu tham
khảo thiết kế**, tách riêng khỏi `CLAUDE.md` và `.claude/rules/*.md` (đặc tả chính thức).

**Nguyên tắc bắt buộc khi dùng file này:** chỉ học bố cục, luồng thao tác, cách tổ chức
thông tin. Không sao chép logo, nội dung, hình ảnh hoặc nhận diện thương hiệu của
Viec3mien.vn (`.claude/rules/ui-guidelines.md`). Nếu một pattern ở đây mâu thuẫn với quy tắc
nghiệp vụ trong `.claude/rules/*.md`, quy tắc nghiệp vụ luôn được ưu tiên áp dụng.

## Vị trí ảnh và quy ước đặt tên

- `docs/ui-reference/phase-1/` — ảnh thuộc phạm vi Phase 1. Tên file tiền tố `05.x-` (số cũ,
  giữ nguyên để không phải đổi tên hàng loạt) tương ứng nhóm chức năng công khai: 5.1 menu
  tài khoản, 5.2 trang chủ, 5.3 danh sách/lọc việc làm, 5.4 chi tiết việc làm, 5.5 công ty,
  5.6 ứng tuyển, 5.7 tài khoản ứng viên. Đối chiếu nghiệp vụ hiện hành:
  `.claude/rules/scope-standards.md`, `.claude/rules/public-site.md`.
- `docs/ui-reference/out-of-scope/` — ảnh thuộc luồng cộng tác viên/hoa hồng (`13-ctv-*`,
  `13-modal-*`) và tin tức/blog chưa có trong spec (`khac-tin-tuc-*`). Đối chiếu:
  `.claude/rules/scope-standards.md` (mục "Ngoài phạm vi").

## 1. Đáng học theo

### Card việc làm (trang chủ, danh sách việc làm)
*(xem `docs/ui-reference/phase-1/05.2-trang-chu.png`, `05.3-bo-loc-viec-lam.png`)*
Logo công ty, tên vị trí, mức lương nổi bật (số to, tách dòng riêng), tỉnh/ca làm, nhãn
"Tuyển gấp" màu đỏ. Khớp yêu cầu lọc/hiển thị ở `.claude/rules/scope-standards.md` (mục
"Public"). **Không copy** icon trái tim lưu việc (Favorites) — không thuộc Phase 1, kể cả
database (ADR-021).

### Bộ lọc việc làm dạng modal + chip button
*(xem `docs/ui-reference/phase-1/05.3-bo-loc-viec-lam.png`, `05.5-bo-loc-cong-ty.png`)*
Thay vì dropdown truyền thống, dùng nút tròn (pill) để chọn nhanh tỉnh/mức lương/độ tuổi, kèm
1 ô nhập tự do bên dưới cho trường hợp không có trong preset. Nút "Bỏ lọc" / "Áp dụng" cố
định ở đáy modal. Phù hợp mobile-first (`.claude/rules/ui-guidelines.md`), nên áp dụng cho cả
bộ lọc việc làm và bộ lọc công ty. Lưu ý: filter theo tỉnh/xã phải dùng
`administrative_units` phân cấp (`.claude/rules/data-model.md`), không lưu chuỗi tự do như
tham khảo.

### Form ứng tuyển chia 2 tầng
*(xem `docs/ui-reference/phase-1/05.6-form-ung-tuyen-co-ban.png`,
`05.6-form-ung-tuyen-bo-sung.png`)*
Trường bắt buộc hiện ngay, phần còn lại gói trong accordion "THÔNG TIN BỔ SUNG" ẩn/hiện.
Giảm friction, tăng tỷ lệ nộp hồ sơ. SĐT tự động điền sẵn nếu ứng viên đã đăng nhập. Danh
sách trường cụ thể được phép thu thập: `.claude/rules/roles-business-rules.md` —
xem cảnh báo ở mục 2 bên dưới, **không copy nguyên khối trường** của ảnh tham khảo.

### Bố cục trang chi tiết việc làm
*(xem `docs/ui-reference/phase-1/05.4-chi-tiet-viec-lam.png`)*
Thứ tự: mô tả ngắn → giới thiệu tuyển dụng → mô tả công việc → phúc lợi (tách rõ từng khoản:
lương cơ bản, phụ cấp, chuyên cần, thưởng) → thời gian làm việc → yêu cầu → việc làm liên
quan → công ty liên quan. Khớp `.claude/rules/public-site.md` (phần "Job detail").

### Trang công ty
*(xem `docs/ui-reference/phase-1/05.5-danh-sach-cong-ty.png`, `05.5-chi-tiet-cong-ty.png`)*
Banner ảnh bìa + logo + tên + lĩnh vực + tỉnh, danh sách "Vị trí đang tuyển" của công ty,
"Công ty liên quan" gợi ý chéo ở cuối trang. Khớp `.claude/rules/scope-standards.md` (mục
"Public").

### Trang cá nhân
*(xem `docs/ui-reference/phase-1/05.7-trang-ca-nhan-ung-vien.png`)*
Chỉ học cách trình bày 1 counter to, rõ ("Việc làm ứng tuyển") thay vì liệt kê danh sách dài
ngay đầu trang. Áp dụng cho `.claude/rules/scope-standards.md` (mục "Candidate account" — làm
sau khi luồng guest + HR ổn định). **Không copy** counter "Mục yêu thích" — Favorites không
thuộc Phase 1, kể cả database (ADR-021).

### Menu tài khoản dạng trượt
*(xem `docs/ui-reference/phase-1/05.1-menu-tai-khoan-truot.png`)*
Slide-in từ phải, avatar + tên ở đầu, danh sách mục dạng icon + label rõ ràng (dễ chạm, khớp
tinh thần nút tối thiểu 48px ở `.claude/rules/ui-guidelines.md`). Khi áp dụng: bỏ mục "0 xu"
(điểm thưởng), "Cộng tác viên" và "Việc đã lưu" (Favorites) khỏi menu — cả ba đều ngoài phạm vi
Phase 1 (`.claude/rules/scope-standards.md`, mục "Ngoài phạm vi"; ADR-021).

## 2. Thấy nhưng KHÔNG được copy

### Form thu thập vượt quá danh sách trường cho phép
*(xem `docs/ui-reference/phase-1/05.6-form-ung-tuyen-bo-sung.png`)*
Phần "Thông tin bổ sung" của tham khảo có: Số CMND/CCCD + ngày cấp + nơi cấp, Tình trạng hôn
nhân, Trình độ học vấn, Ngoại ngữ, Dân tộc. `.claude/rules/roles-business-rules.md` cấm rõ:
CCCD, ảnh CCCD, tài khoản ngân hàng, **Dân tộc**, Tôn giáo, hồ sơ sức khỏe chi tiết,
**Tình trạng hôn nhân**.

- **CCCD, Dân tộc, Tôn giáo, tình trạng hôn nhân, hồ sơ sức khỏe:** cấm tuyệt đối trên form
  công khai.
- **Ngoại ngữ, trình độ học vấn:** không nằm trong quy tắc cấm cứng, nhưng cũng không thuộc
  danh sách trường Phase 1 hiện có trong `docs/DATABASE-DICTIONARY.md` (`candidates` chỉ có
  `education_level` dạng free text) — không tự thêm trường cấu trúc mới khi chưa có yêu cầu.

Chỉ học cơ chế accordion ẩn/hiện; danh sách trường bên trong phải giới hạn đúng theo
`.claude/rules/roles-business-rules.md` và schema thật ở `docs/DATABASE-DICTIONARY.md`.

### Điểm thưởng, modal chọn ứng tuyển hay giới thiệu, luồng cộng tác viên
*(xem `docs/ui-reference/out-of-scope/13-modal-chon-ung-tuyen-hay-gioi-thieu.png`,
`13-ctv-hoa-hong-tong-quan.png`, `13-ctv-trang-gioi-thieu-chuong-trinh.png`,
`13-ctv-danh-sach-ho-so-da-gioi-thieu.png`, `13-ctv-danh-sach-ho-so-chua-gioi-thieu.png`)*
Toàn bộ nhóm này thuộc phạm vi **Ngoài Phase 1** (`.claude/rules/scope-standards.md`, mục
"Ngoài phạm vi" — cộng tác viên, hoa hồng, điểm thưởng, referral). Không xây các luồng UI
này. Ghi nhận: việc tham khảo tách riêng luồng "giới thiệu ứng viên" xác nhận
`applications.source_id` + `referral_code` (không có bảng referrer riêng ở Phase 1 — ADR-012
trong `docs/DECISIONS.md`) là đủ chỗ để mở rộng module cộng tác viên sau này mà không cần sửa
schema đã có.

### Rating sao trên card công ty (5/5, 4.8/5...)
*(xem `docs/ui-reference/phase-1/05.5-chi-tiet-cong-ty.png`)*
Không có bảng đánh giá/review trong schema Phase 1 (`docs/DATABASE-DICTIONARY.md`). Không
hiển thị số sao cho đến khi có cơ chế review thật và bảng dữ liệu tương ứng.

## 3. Không khớp phạm vi Phase 1 hiện tại

`docs/ui-reference/out-of-scope/khac-tin-tuc-blog-chua-co-trong-spec.png` — trang tin
tức/blog. `.claude/rules/scope-standards.md` không liệt kê "Tin tức" trong danh sách chức
năng website công khai, và `docs/ROUTE-MAP.md` không có route tương ứng. Không tự thêm
route/bảng cho tin tức khi chưa có yêu cầu chính thức (`.claude/rules/scope-standards.md`).

Phần "Chương trình Cộng tác viên" ở footer và các trang liên quan đến app riêng (QR code tải
app) xuất hiện lặp lại ở hầu hết ảnh tham khảo — thuộc phạm vi ngoài Phase 1 hoặc không áp
dụng (dự án này chưa có app mobile — `.claude/rules/scope-standards.md`, mục "Ngoài phạm
vi"), không cần phân tích thêm mỗi lần gặp lại.
