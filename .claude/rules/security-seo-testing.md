---
paths:
  - "app/Http/**/*.php"
  - "app/Policies/**/*.php"
  - "app/Providers/**/*.php"
  - "config/**/*.php"
  - "routes/**/*.php"
  - "resources/views/**/*.blade.php"
  - "public/**/*"
  - "tests/**/*.php"
---

# Security, SEO, test

- CSRF, Form Request, escaped Blade output, mass-assignment allowlist.
- Rate limit login/application/lead; honeypot mặc định, CAPTCHA chỉ khi cần.
- Không log secret, token hoặc dữ liệu nhạy cảm; không đọc/ghi `.env` tùy tiện.
- Upload phải kiểm MIME/size, tên ngẫu nhiên, chặn executable.
- Authorization ở route và Policy; không chỉ ẩn nút trong view.
- SEO: title, meta, canonical, OG, breadcrumb, sitemap, robots, JobPosting, alt text.
- HR/login noindex. Filter URL kiểm canonical. Job closed giữ URL nhưng rời danh sách active.
- Mọi thay đổi có focused test; không tuyên bố xong khi test/build chưa chạy hoặc đang fail.
- Acceptance source: `docs/ACCEPTANCE-CRITERIA.md`.
