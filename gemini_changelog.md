# Nhật ký thay đổi (Changelog) - Gemini

File này ghi lại các thay đổi mà AI (Gemini) đã thực hiện trên mã nguồn của dự án để bạn dễ dàng theo dõi.

## [2026-05-05]
- **File sửa đổi**: `database/seeders/DigitalSatMockSeeder.php`
- **Mô tả thay đổi**: Xóa bỏ thuộc tính `'question_number' => $i + 1` trong các hàm `Question::create()`.
- **Lý do**: Cột `question_number` đã bị xóa khỏi bảng `questions` trong database thông qua file migration `2026_04_28_093442_remove_question_number_from_questions_table.php`, dẫn đến lỗi khi chạy lệnh `php artisan db:seed`. Việc xóa thuộc tính này giúp seeder hoạt động bình thường trở lại.

---

- **File sửa đổi**: `vite.config.js`
- **Mô tả thay đổi**: Thêm `resources/css/test/test-main.css` và `resources/js/test.js` vào mảng `input` của Vite.
- **Lý do**: Blade template `components/layouts/test.blade.php` sử dụng `@vite()` để gọi các file CSS/JS của Test Engine, nhưng chúng chưa được đăng ký trong `vite.config.js`, dẫn đến lỗi `ViteException: Unable to locate file in Vite manifest`. Sau khi thêm và chạy `npm run build`, trang `/take-test` có thể load bình thường.

---

- **File sửa đổi**: `resources/views/tests/take/take-reading.blade.php`, `resources/views/tests/take/take-math.blade.php`
- **Mô tả thay đổi**: Thay toàn bộ `$q->question_number` bằng `$loop->iteration` (và `$loop->parent->iteration` trong vòng lặp con của answer choices).
- **Lý do**: Cột `question_number` đã bị xóa khỏi bảng `questions`, khiến `$q->question_number` luôn trả về `null`. Hậu quả: tất cả các element đều có cùng ID (trùng nhau), và tất cả radio button dùng chung `name="q"` nên chỉ chọn được 1 đáp án cho toàn bộ 6 câu. Sử dụng `$loop->iteration` để đánh số thứ tự 1, 2, 3... tự động theo vị trí trong vòng lặp.

---

- **File sửa đổi**: `resources/views/tests/take/take-math.blade.php` (dòng 111)
- **Mô tả thay đổi**: Sửa `$loop->parent->iteration` thành `$loop->iteration` cho input SPR (Student Produced Response).
- **Lý do**: Input SPR nằm trực tiếp trong vòng lặp `@foreach($questions ...)` chính (không nằm trong vòng lặp con `@foreach answerChoices`), nên `$loop->parent` là `null` gây lỗi "Attempt to read property iteration on null".

---

- **File sửa đổi**: `vite.config.js`
- **Mô tả thay đổi**: Thêm `resources/css/home.css` vào mảng `input` của Vite, sau đó chạy `npm run build`.
- **Lý do**: Trang `/home` (`home.blade.php`) gọi `@vite(['resources/css/home.css', ...])` nhưng file chưa được đăng ký trong Vite config, gây lỗi `ViteException`.

---

- **File sửa đổi**: `resources/views/components/layouts/test.blade.php` (dòng 11)
- **Mô tả thay đổi**: Thêm cấu hình `delimiters` cho KaTeX `auto-render`, bao gồm cả `$...$` (inline) và `$$...$$` (display).
- **Lý do**: KaTeX mặc định chỉ nhận dạng `\(...\)` và `\[...\]`. Dữ liệu câu hỏi Toán trong seeder sử dụng cú pháp `$x^2 - 5x + 6 = 0$` nên không được render thành công thức, hiển thị dưới dạng text thô.
