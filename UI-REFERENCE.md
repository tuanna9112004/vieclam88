# UI Reference — Phân tích tham khảo từ Viec3mien.vn

Ghi lại pattern UX/luồng chức năng rút ra từ ảnh chụp màn hình trong
`docs/ui-reference/phase-1/`, `docs/ui-reference/phase-2/` và `docs/ui-reference/out-of-scope/`.
Đây là **tài liệu tham khảo thiết kế**, tách riêng khỏi `CLAUDE.md` và `.claude/rules/*.md`
(đặc tả chính thức).

**Nguyên tắc bắt buộc khi dùng file này:** chỉ học bố cục, luồng thao tác, cách tổ chức
thông tin. Không sao chép logo, nội dung, hình ảnh hoặc nhận diện thương hiệu của
Viec3mien.vn (`.claude/rules/ui-guidelines.md`). Nếu một pattern ở đây mâu thuẫn với quy tắc
nghiệp vụ trong `.claude/rules/*.md`, quy tắc nghiệp vụ luôn được ưu tiên áp dụng.

## Vị trí ảnh và quy ước đặt tên

- `docs/ui-reference/phase-1/` — ảnh thuộc phạm vi Phase 1: 5.2 trang chủ, 5.3 danh sách/lọc
  việc làm, 5.4 chi tiết việc làm, 5.5 công ty, 5.6 ứng tuyển. Tên file tiền tố `05.x-` (số cũ,
  giữ nguyên để không phải đổi tên hàng loạt). Đối chiếu nghiệp vụ hiện hành:
  `.claude/rules/scope-standards.md`, `.claude/rules/public-site.md`.
- `docs/ui-reference/phase-2/` — ảnh thuộc tính năng Candidate Account (Phase 2, ADR-028):
  5.1 menu tài khoản trượt, 5.7 trang cá nhân ứng viên. **Không dùng cho Phase 1** dưới bất kỳ
  hình thức nào — chuyển ra khỏi `phase-1/` để tránh nhầm là tài liệu tham khảo đang áp dụng.
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
Giảm friction, tăng tỷ lệ nộp hồ sơ. **Phase 1 không có đăng nhập ứng viên; người dùng tự nhập
số điện thoại trong form** (khác với ảnh tham khảo, vốn tự điền SĐT cho tài khoản đã đăng
nhập — không áp dụng vì Candidate Account là Phase 2, ADR-028). Danh sách trường cụ thể được
phép thu thập: `.claude/rules/roles-business-rules.md` — xem cảnh báo ở mục 3 bên dưới,
**không copy nguyên khối trường** của ảnh tham khảo.

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

## 2. Phase 2 — không dùng cho Phase 1

Hai ảnh dưới đây đã chuyển từ `docs/ui-reference/phase-1/` sang `docs/ui-reference/phase-2/`
vì gắn liền với Candidate Account (đăng nhập ứng viên), không thuộc Phase 1 dưới bất kỳ hình
thức nào, kể cả database (ADR-028). Chỉ ghi nhận layout tham khảo cho Phase 2, **không triển
khai ở Phase 1**.

### Trang cá nhân
*(xem `docs/ui-reference/phase-2/05.7-trang-ca-nhan-ung-vien.png`)*
Layout tham khảo: 1 counter to, rõ thay vì liệt kê danh sách dài ngay đầu trang. **Không copy**
counter "Mục yêu thích" khi triển khai Phase 2 — Favorites vẫn cần được duyệt riêng, không mặc
định đi kèm Candidate Account (ADR-021).

### Menu tài khoản dạng trượt
*(xem `docs/ui-reference/phase-2/05.1-menu-tai-khoan-truot.png`)*
Slide-in từ phải, avatar + tên ở đầu, danh sách mục dạng icon + label rõ ràng (dễ chạm, khớp
tinh thần nút tối thiểu 48px ở `.claude/rules/ui-guidelines.md`). Khi áp dụng ở Phase 2: bỏ
mục "0 xu" (điểm thưởng) và "Cộng tác viên" khỏi menu — vẫn ngoài phạm vi kể cả ở Phase 2 trừ
khi có ADR riêng duyệt.

## 3. Thấy nhưng KHÔNG được copy

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
này. `applications.referral_code` đã bị loại khỏi schema Phase 1 (ADR-029) — không giữ trước
"phòng khi cần"; khi module cộng tác viên được duyệt xây dựng ở Phase 2, thêm lại cột này
(và `referrer_id`/bảng `referrers` nếu cần — ADR-012) bằng migration mới.

### Rating sao trên card công ty (5/5, 4.8/5...)
*(xem `docs/ui-reference/phase-1/05.5-chi-tiet-cong-ty.png`)*
Không có bảng đánh giá/review trong schema Phase 1 (`docs/DATABASE-DICTIONARY.md`). Không
hiển thị số sao cho đến khi có cơ chế review thật và bảng dữ liệu tương ứng.

## 4. Không khớp phạm vi Phase 1 hiện tại

`docs/ui-reference/out-of-scope/khac-tin-tuc-blog-chua-co-trong-spec.png` — trang tin
tức/blog. `.claude/rules/scope-standards.md` không liệt kê "Tin tức" trong danh sách chức
năng website công khai, và `docs/ROUTE-MAP.md` không có route tương ứng. Không tự thêm
route/bảng cho tin tức khi chưa có yêu cầu chính thức (`.claude/rules/scope-standards.md`).

Phần "Chương trình Cộng tác viên" ở footer và các trang liên quan đến app riêng (QR code tải
app) xuất hiện lặp lại ở hầu hết ảnh tham khảo — thuộc phạm vi ngoài Phase 1 hoặc không áp
dụng (dự án này chưa có app mobile — `.claude/rules/scope-standards.md`, mục "Ngoài phạm
vi"), không cần phân tích thêm mỗi lần gặp lại.
