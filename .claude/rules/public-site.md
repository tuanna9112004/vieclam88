---
paths:
  - "app/Http/Controllers/Public/**/*.php"
  - "app/Http/Requests/Public/**/*.php"
  - "resources/views/public/**/*.blade.php"
  - "resources/js/public/**/*"
  - "resources/css/public/**/*"
  - "routes/web.php"
  - "tests/Feature/Public/**/*.php"
---

# Public site

Trang chủ: header → search → urgent/new jobs → jobs theo KCN → companies → quy trình → lead form → FAQ → footer.

Job detail: thông tin nhanh → lương → mô tả → yêu cầu → phúc lợi → xe/chỗ ở → hồ sơ → company → related jobs. Mobile có sticky Gọi/Zalo/Ứng tuyển.

- Form ngắn, guest submit được, tuân thủ consent và danh sách dữ liệu cấm.
- Không copy thương hiệu/nội dung ảnh tham khảo.
- Chỉ mở đúng ảnh cần dùng trong `docs/ui-reference/phase-1/`.
- Route và acceptance criteria phải khớp `docs/ROUTE-MAP.md` và `docs/ACCEPTANCE-CRITERIA.md`.
