## 2. Công nghệ

- PHP 8.2 hoặc 8.3 (dev local dùng bản XAMPP có kèm PHP 8.2/8.3 để đồng bộ với production; production chạy trên VPS riêng nên không bị giới hạn phiên bản PHP như shared hosting).
- Laravel bản ổn định mới nhất tương thích với PHP đã chọn (không khóa cứng theo PHP 8.0 như trước — PHP 8.0 và Laravel 9 đều đã EOL, không còn nhận vá bảo mật).
- MySQL hoặc MariaDB.
- Blade, Bootstrap 5, JavaScript thuần hoặc Alpine.js.
- Vite để build asset CSS/JS (Bootstrap/Alpine). Vite cần Node.js chỉ ở bước build lúc dev/deploy, không phải Node chạy như backend server lúc runtime — không vi phạm nguyên tắc "không dùng Node.js backend" bên dưới.
- Intervention Image (hoặc thư viện tương đương) để xử lý/convert ảnh upload sang WebP theo yêu cầu SEO ở mục 11.
- Eloquent, Form Request, Service, Middleware, Policy.
- Authentication bằng session.
- PHPUnit/Feature Test.

Không dùng React, Vue, Next.js, Node.js backend, Docker, Redis hoặc Elasticsearch trong MVP.

## 3. Kiến trúc

- Website công khai: `tencongty.vn`.
- Trang HR: `hr.tencongty.vn` hoặc route `/hr` khi chạy XAMPP.
- Hai giao diện dùng chung backend và database.
- Tách route, controller, view, middleware và layout giữa Public và HR.
- Không để route HR truy cập được nếu chưa đăng nhập và không đủ quyền.
