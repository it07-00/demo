# Nguyên tắc Phát triển Frontend (Frontend Development Guidelines)

Để đảm bảo hiệu năng tối đa, khả năng chạy offline, tính bảo mật và sự gọn gàng cho mã nguồn dự án Greeco, tất cả lập trình viên cần tuân thủ tuyệt đối các nguyên tắc dưới đây khi làm việc với giao diện:

## 1. Không sử dụng CDN cho các thư viện ngoài (No External CDNs)
- **Quy định**: Tất cả các tệp CSS và JavaScript từ các bên thứ ba (FullCalendar, Flatpickr, SweetAlert2, v.v.) **bắt buộc** phải được tải về máy cục bộ, lưu trữ trong thư mục `public/` và tham chiếu qua hàm trợ giúp `asset()`.
- **Lý do**:
  - Tăng tốc độ tải trang khi trình duyệt lưu cache các file tĩnh nội bộ.
  - Đảm bảo hệ thống hoạt động ổn định kể cả khi mất kết nối Internet hoặc các dịch vụ CDN bên thứ ba gặp sự cố.
  - Kiểm soát chính xác phiên bản của thư viện được sử dụng trong toàn bộ vòng đời ứng dụng.
- **Cách thực hiện**:
  - Tải tệp tin về thư mục `public/js/` hoặc `public/css/` tương ứng.
  - Sử dụng cú pháp Blade:
    ```html
    <script src="{{ asset('js/lib-name.min.js') }}"></script>
    <link rel="stylesheet" href="{{ asset('css/lib-name.min.css') }}">
    ```

## 2. Không nhúng trực tiếp CSS vào file Blade (No CSS inside Blade)
- **Quy định**: Hạn chế tối đa việc sử dụng thẻ `<style>` hoặc viết CSS ghi đè (override) trực tiếp bên trong các tệp tin Blade templates. Thay vào đó, tất cả các tùy chỉnh CSS phải được chuyển sang stylesheet toàn cục `public/css/styles.css` hoặc các tệp CSS chuyên biệt được cấu hình tải toàn hệ thống.
- **Lý do**:
  - Giữ cho mã nguồn các Blade views rõ ràng, chỉ tập trung vào HTML cấu trúc và logic biểu diễn.
  - Dễ dàng quản lý, bảo trì và tái sử dụng mã nguồn CSS tại một nơi duy nhất.
  - Tối ưu hóa việc caching của trình duyệt đối với các tệp tin `.css` tĩnh.
- **Cách thực hiện**:
  - Định nghĩa các class tùy chỉnh hoặc CSS viết đè tại cuối tệp `public/css/styles.css`.
  - Nếu tệp tùy chỉnh CSS quá lớn, tạo tệp CSS riêng trong `public/css/` và link trong `<head>` của layout chính `layouts/app.blade.php`.
