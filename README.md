# TTVH-TC Admin

Ứng dụng quản trị nội bộ cho TTVH-TC, xây dựng bằng Laravel 12, Livewire 4 và Vite. Hệ thống tập trung vào vận hành, người dùng, phân quyền, lịch công tác, báo cáo ngày, tiến độ công việc, CRM và cấu hình email nội bộ.

## Module chính

- Dashboard tổng quan số liệu vận hành.
- Quản lý người dùng, khóa/mở khóa tài khoản, đặt lại mật khẩu.
- Vai trò và phân quyền theo Spatie Permission.
- Dự án, khách hàng, báo cáo vận hành, nhân sự, KPI, CRM và cảnh báo.
- Lịch công tác có phân quyền riêng tư và người tham gia.
- Báo cáo ngày và tiến độ công việc theo tuần.
- Quy định tài liệu, hộp thư nội bộ, hồ sơ cá nhân và cài đặt hệ thống.

## Yêu cầu môi trường

- PHP 8.3 trở lên.
- Composer.
- Node.js và npm.
- SQLite cho môi trường local mặc định, hoặc cấu hình lại database trong `.env`.

## Cài đặt local

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

Trên Windows PowerShell, có thể thay lệnh copy env bằng:

```powershell
Copy-Item .env.example .env
```

Chạy ứng dụng:

```bash
php artisan serve
npm run dev
```

Mặc định ứng dụng chạy tại `http://127.0.0.1:8000`.

## Tài khoản seed

Tất cả tài khoản mẫu dùng mật khẩu `password`.

| Tài khoản | Vai trò |
| --- | --- |
| `superadmin` | Super Admin |
| `giamdoc` | Giám đốc |
| `it` | IT |
| `sales` | Phòng Kinh doanh |
| `ketoan` | Kế toán |
| `tuvan` | Tư vấn |

Seeder cũng tạo thêm dữ liệu mẫu cho vận hành, CRM, lịch công tác và báo cáo ngày để có thể kiểm thử giao diện ngay sau khi migrate.

## Kiểm tra chất lượng

```bash
vendor/bin/pint --test
php artisan test
npm run build
```

Nếu cần tự động chuẩn hóa format PHP:

```bash
vendor/bin/pint
```

## Ghi chú triển khai

- Cập nhật `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL`, database, cache, queue và mail trong `.env`.
- Chạy `composer install --no-dev --optimize-autoloader`.
- Chạy `npm run build` và deploy thư mục `public/build`.
- Chạy migration bằng `php artisan migrate --force`.
- Tối ưu cache bằng:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

- Nếu dùng queue database, cấu hình worker cho `php artisan queue:work`.
- Đảm bảo web server trỏ document root vào thư mục `public`.

## Lệnh hữu ích

```bash
php artisan route:list
php artisan optimize:clear
php artisan db:seed
php artisan app:reset-password username password
```

Lệnh reset password nhận username và mật khẩu mới, dùng khi cần khôi phục tài khoản quản trị.
