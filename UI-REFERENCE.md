# UI Reference — Phân tích tham khảo từ Viec3mien.vn

Tài liệu này ghi lại các pattern UX/luồng chức năng rút ra từ ảnh chụp màn hình trong
`viec3mien_giaodienweb/`, dùng làm tham khảo khi build giao diện. Đây là **tài liệu tham
khảo thiết kế**, tách riêng khỏi CLAUDE.md (đặc tả chính thức của dự án).

**Nguyên tắc bắt buộc khi dùng file này:** chỉ học bố cục, luồng thao tác, cách tổ chức
thông tin. Không sao chép logo, nội dung, hình ảnh hoặc nhận diện thương hiệu của
Viec3mien.vn (đã quy định ở CLAUDE.md mục 9). Nếu một pattern ở đây mâu thuẫn với quy tắc
nghiệp vụ trong CLAUDE.md, CLAUDE.md luôn được ưu tiên áp dụng.

## Quy ước đặt tên file trong `viec3mien_giaodienweb/`

Tên file có tiền tố là **số mục CLAUDE.md** mà ảnh minh họa cho, để lọc theo tên thay vì mở
từng ảnh:

- `05.x-*.png` — thuộc mục 5 (Website công khai, xem `.claude/rules/public-site.md`). Số sau
  dấu chấm khớp đúng mục con (5.1 Header, 5.2 Trang chủ, 5.3 Danh sách việc làm, 5.4 Chi tiết
  việc làm, 5.5 Công ty, 5.6 Ứng tuyển, 5.7 Tài khoản ứng viên).
- `13-*.png` — thuộc mục 13 (Ngoài phạm vi MVP, xem `.claude/rules/scope-standards.md`),
  chủ yếu là luồng cộng tác viên/hoa hồng (`ctv-`). Chỉ để tham khảo, **không xây ở Phase 1**.
- `khac-*.png` — không khớp mục nào trong CLAUDE.md hiện tại (vd tin tức/blog — CLAUDE.md
  mục 5.1 không liệt kê "Tin tức" trong header). Cần quyết định có bổ sung vào spec hay bỏ
  qua trước khi dùng làm tham khảo xây dựng.

Ví dụ lọc nhanh: muốn xem tất cả tham khảo cho trang chi tiết việc làm (mục 5.4) → tìm file
bắt đầu bằng `05.4-`.

## 1. Đáng học theo

### Card việc làm (trang chủ, danh sách việc làm)
*(xem `05.2-trang-chu.png`, `05.3-bo-loc-viec-lam.png`)*
Logo công ty, tên vị trí, mức lương nổi bật (số to, tách dòng riêng), tỉnh/ca làm, nhãn
"Tuyển gấp" màu đỏ, icon trái tim lưu việc ở góc phải. Khớp với yêu cầu ở CLAUDE.md mục 5.3.

### Bộ lọc việc làm dạng modal + chip button
*(xem `05.3-bo-loc-viec-lam.png`, `05.5-bo-loc-cong-ty.png`)*
Thay vì dropdown truyền thống, dùng nút tròn (pill) để chọn nhanh tỉnh/mức lương/độ tuổi,
kèm 1 ô nhập tự do bên dưới cho trường hợp không có trong preset. Nút "Bỏ lọc" / "Áp dụng"
cố định ở đáy modal. Phù hợp mobile-first, nên áp dụng cho cả bộ lọc việc làm và bộ lọc
công ty.

### Form ứng tuyển chia 2 tầng
*(xem `05.6-form-ung-tuyen-co-ban.png`, `05.6-form-ung-tuyen-bo-sung.png`)*
Trường bắt buộc hiện ngay (Giới tính, Ngày sinh, Nơi ở, SĐT), phần còn lại gói trong
accordion "THÔNG TIN BỔ SUNG" ẩn/hiện. Giảm friction, tăng tỷ lệ nộp hồ sơ — đúng tinh thần
mục 5.6 CLAUDE.md (bắt buộc ngắn, bổ sung optional). SĐT tự động điền sẵn nếu ứng viên đã
đăng nhập.

### Bố cục trang chi tiết việc làm
*(xem `05.4-chi-tiet-viec-lam.png`)*
Thứ tự: mô tả ngắn → giới thiệu tuyển dụng → mô tả công việc → phúc lợi (tách rõ từng
khoản: lương cơ bản, phụ cấp, chuyên cần, thưởng) → thời gian làm việc → yêu cầu → việc
làm liên quan → công ty liên quan. Gần khớp 1-1 với mục 5.4 CLAUDE.md.

### Trang công ty
*(xem `05.5-danh-sach-cong-ty.png`, `05.5-chi-tiet-cong-ty.png`)*
Banner ảnh bìa + logo + tên + lĩnh vực + tỉnh, danh sách "Vị trí đang tuyển" của công ty,
"Công ty liên quan" gợi ý chéo ở cuối trang. Khớp mục 5.5 CLAUDE.md.

### Trang cá nhân
*(xem `05.7-trang-ca-nhan-ung-vien.png`)*
2 counter to, rõ ("Mục yêu thích" / "Việc làm ứng tuyển") thay vì liệt kê danh sách dài
ngay đầu trang. Cách hiển thị trực quan, dễ áp dụng cho mục 5.7 CLAUDE.md.

### Menu tài khoản dạng trượt
*(xem `05.1-menu-tai-khoan-truot.png`)*
Slide-in từ phải, avatar + tên ở đầu, danh sách mục dạng icon + label rõ ràng (dễ chạm, khớp
tinh thần nút tối thiểu 48px ở mục 9). Khi áp dụng: bỏ mục "0 xu" (điểm thưởng, ngoài MVP
theo mục 13) và "Cộng tác viên" khỏi menu.

## 2. Thấy nhưng KHÔNG được copy

### Form thu thập vượt quá danh sách trường cho phép ở CLAUDE.md mục 5.6
*(xem `05.6-form-ung-tuyen-bo-sung.png`)*
Phần "Thông tin bổ sung" của họ có: Số CMND/CCCD + ngày cấp + nơi cấp, Tình trạng hôn
nhân, Trình độ học vấn, Ngoại ngữ, Dân tộc. CLAUDE.md mục 5.6 chỉ cho phép các trường bổ
sung: Quận/huyện, địa chỉ, Số Zalo, Trình độ, kinh nghiệm, Ca làm mong muốn, Ngày có thể đi
làm, Ghi chú.

- **CCCD**: vi phạm trực tiếp câu *"Không thu CCCD, ảnh CCCD hoặc tài khoản ngân hàng ở
  form công khai."*
- **Dân tộc**: không nằm trong danh sách cho phép, và là loại dữ liệu cá nhân nhạy cảm
  theo pháp luật Việt Nam — mức độ rủi ro tương đương CCCD, không nên thu thập ở form công
  khai nếu chưa có yêu cầu nghiệp vụ rõ ràng.
- **Tình trạng hôn nhân, Ngoại ngữ**: không nằm trong danh sách cho phép — không thêm vào
  trừ khi cập nhật CLAUDE.md mục 5.6 trước.

Chỉ học cơ chế accordion ẩn/hiện của "THÔNG TIN BỔ SUNG"; danh sách trường bên trong phải
giới hạn đúng theo mục 5.6 CLAUDE.md, không copy nguyên khối trường của Viec3mien.

### Điểm thưởng, modal chọn ứng tuyển hay giới thiệu, luồng cộng tác viên
*(xem `13-modal-chon-ung-tuyen-hay-gioi-thieu.png`, `13-ctv-hoa-hong-tong-quan.png`,
`13-ctv-trang-gioi-thieu-chuong-trinh.png`, `13-ctv-danh-sach-ho-so-da-gioi-thieu.png`,
`13-ctv-danh-sach-ho-so-chua-gioi-thieu.png`, `05.1-menu-tai-khoan-truot.png` mục "0 xu")*
Toàn bộ nhóm này thuộc phạm vi **Ngoài MVP** theo CLAUDE.md mục 13 (cộng tác viên, hoa
hồng, điểm thưởng). Không xây các luồng UI này ở Phase 1. Ghi nhận: việc họ tách riêng
luồng "giới thiệu ứng viên" xác nhận thiết kế bảng `candidate_sources/referrers` trong
CLAUDE.md mục 8 là đúng hướng cho mở rộng tương lai — chỉ chưa cần UI.

### Rating sao trên card công ty (5/5, 4.8/5...)
*(xem `05.5-chi-tiet-cong-ty.png`)*
CLAUDE.md mục 5.5 đã quy định: *"Không hiển thị đánh giá sao nếu chưa có dữ liệu đánh giá
thật."* Không hiển thị số sao cho đến khi có cơ chế review thật.

## 3. Không khớp mục nào trong CLAUDE.md hiện tại

`khac-tin-tuc-blog-chua-co-trong-spec.png` — trang tin tức/blog. CLAUDE.md mục 5.1 (Header)
không liệt kê "Tin tức" trong menu chính, và không có mục nào khác nhắc đến blog/tin tức.
Đây không phải lỗi cần sửa — chỉ là điểm cần quyết định: bỏ qua hoàn toàn, hay bổ sung vào
CLAUDE.md nếu công ty thực sự muốn có mục tin tức. Chưa tự ý thêm vào spec khi chưa có yêu
cầu (theo mục 14 CLAUDE.md).

Phần "Chương trình Cộng tác viên" ở footer trang chủ và các trang liên quan đến app riêng
(`APP VIEC3MIEN`, QR code tải app) xuất hiện lặp lại ở hầu hết ảnh — thuộc phạm vi ngoài MVP
hoặc không áp dụng (dự án này chưa có app mobile theo CLAUDE.md mục 13), không cần phân tích
thêm mỗi lần gặp lại.
