---
paths:
  - "app/Http/Controllers/Public/**/*.php"
  - "resources/views/public/**/*.blade.php"
  - "resources/views/jobs/**/*.blade.php"
  - "resources/views/companies/**/*.blade.php"
  - "routes/web.php"
  - "public/**/*"
  - "tests/**/*Seo*.php"
---

# SEO public

- Có title, meta description, canonical, OG, breadcrumb, sitemap, robots và structured data phù hợp.
- HR/login luôn noindex. Filter URL phải có canonical rõ, tránh index tổ hợp vô hạn.
- Sitemap chỉ chứa URL active theo public contract. Job paused/closed/expired giữ URL 200 nhưng rời active sitemap.
- Ảnh có alt; heading có thứ bậc; không nhồi từ khóa hoặc tạo nội dung trùng.
