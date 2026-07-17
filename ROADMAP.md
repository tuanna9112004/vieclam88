# Lộ trình hoàn thiện dự án (Phase 1)

Phạm vi: **Phase 1** theo CLAUDE.md mục 1.1 — web công khai đầy đủ + trang quản lý nội bộ rút
gọn. Không đưa Phase 2 (mục 6 đầy đủ) vào lộ trình này. Cập nhật trạng thái từng giai đoạn ở
đây; tiến độ tổng quát vẫn ghi ở CLAUDE.md mục 16 theo đúng quy trình mục 15.

## Giai đoạn 0 — Nền tảng kỹ thuật

- [x] Commit git lần đầu.
- [ ] `composer create-project` Laravel bản mới nhất, PHP 8.2/8.3 (`.claude/rules/tech-stack.md`
      mục 2). Cài Vite + Bootstrap 5 + Alpine.js.
- [ ] Cấu hình `.env` kết nối MySQL/MariaDB (XAMPP local).
- [ ] Chốt cơ chế routing HR: domain (`hr.tencongty.vn`) vs path (`/hr`) — quyết định còn treo
      trong CLAUDE.md mục 16.

## Giai đoạn 1 — Dữ liệu nền (mục 8, `data-model.md`)

- [ ] Migration cho toàn bộ bảng cốt lõi: users, roles/permissions, provinces, industrial_parks,
      companies, company_locations, jobs, candidates, applications (kèm `assigned_to` nullable),
      application_status_histories, application_notes, candidate_sources/referrers, favorites,
      pages, faqs, settings, audit_logs. Thiết kế đủ cho cả Phase 1 và Phase 2 (mục 1.1).
- [ ] Model + relationship + index/FK/unique constraint đúng theo mục 8.
- [ ] Seeder + factory để có dữ liệu mẫu chạy thử ngay (mục 14).

## Giai đoạn 2 — Auth & phân quyền cơ bản (mục 4)

- [ ] Đăng ký/đăng nhập `candidate` (không bắt buộc để ứng tuyển — mục 5.6).
- [ ] Đăng nhập nhân viên `staff`/`admin`, tách biệt khỏi candidate.
- [ ] Middleware/Policy chặn route HR khi chưa đăng nhập hoặc không đủ quyền (mục 3).

## Giai đoạn 3 — Website công khai (mục 5, `public-site.md`)

Theo đúng thứ tự phụ thuộc:

- [ ] Header (5.1)
- [ ] Trang chủ (5.2)
- [ ] Danh sách + lọc việc làm (5.3, tham khảo `05.3-bo-loc-viec-lam.png`)
- [ ] Chi tiết việc làm (5.4, tham khảo `05.4-*.png`)
- [ ] Công ty danh sách + chi tiết (5.5)
- [ ] Form ứng tuyển (5.6, tham khảo `05.6-*.png`, **loại bỏ** các trường CCCD/Dân tộc/Tình
      trạng hôn nhân/Ngoại ngữ theo cảnh báo trong UI-REFERENCE.md)
- [ ] Tài khoản ứng viên (5.7)

Áp quy tắc chống trùng SĐT và chống ứng tuyển lặp (mục 7) ngay từ form ứng tuyển.

## Giai đoạn 4 — Trang quản lý nội bộ rút gọn (mục 1.1, KHÔNG phải mục 6 đầy đủ)

- [ ] CRUD công ty + việc làm (6.2), dùng chung logic soft delete với mục 7.
- [ ] Danh sách + chi tiết hồ sơ ứng tuyển, lọc theo việc làm/công ty/ngày.
- [ ] Trạng thái đơn giản `new`/`contacted`/`done` + ghi chú nội bộ tự do.
- [ ] Nhân viên tự nhận xử lý qua `assigned_to` (chưa enforce quyền xem theo mục 1.1).
- [ ] Xuất Excel danh sách ứng viên.

## Giai đoạn 5 — Hoàn thiện chất lượng (mục 9, 10, 11)

- [ ] UI polish: mobile-first, màu sắc, form ngắn theo `ui-guidelines.md` và pattern đã học ở
      UI-REFERENCE.md.
- [ ] Bảo mật: CSRF, rate limit, CAPTCHA form công khai, kiểm soát upload, chống IDOR/mass
      assignment (`security-seo-testing.md` mục 10).
- [ ] SEO: slug thân thiện, meta/canonical/OG, structured data JobPosting, sitemap, ảnh WebP,
      tránh N+1 (mục 11).

## Giai đoạn 6 — Kiểm thử (mục 12)

- [ ] Feature test theo đúng checklist mục 12 (guest ứng tuyển, chống trùng, soft delete, staff
      giới hạn quyền theo Phase 1...).
- [ ] README hướng dẫn cài XAMPP + deploy VPS (mục 14, cần bổ sung phần VPS vì trước đây README
      chỉ nhắc XAMPP trong khi production đã xác nhận chạy VPS riêng).

## Giai đoạn 7 — Deploy VPS

- [ ] Cài PHP 8.2/8.3 trên VPS, cấu hình domain/subdomain theo quyết định ở Giai đoạn 0.
- [ ] Deploy, migrate, seed dữ liệu production ban đầu (tỉnh, KCN, danh mục).

## Ghi chú áp dụng lộ trình

- Có phụ thuộc thứ tự: Giai đoạn 1 (schema) phải xong trước 2–4; Giai đoạn 2 (auth) nên xong
  trước Giai đoạn 4 (HR cần đăng nhập).
- Mỗi giai đoạn nên là 1 hoặc vài session riêng — không cố làm hết trong 1 lần.
- Cập nhật CLAUDE.md mục 16 (Trạng thái dự án) sau khi hoàn thành mỗi giai đoạn, theo đúng
  quy trình đã thống nhất ở mục 15.
- Không mở rộng sang Phase 2 hay các hạng mục mục 13 (Ngoài phạm vi MVP) trong lộ trình này.
