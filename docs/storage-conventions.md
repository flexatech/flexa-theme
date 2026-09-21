# Quy ước lưu trữ dữ liệu

Áp dụng cho mọi thứ Flexa ghi xuống database. Ngắn, vì theme cố tình lưu rất ít.

Ngày lập: **2026-09-21**

---

## Hiện trạng

Tính tới hôm nay Flexa **không lưu một option nào**:

```
get_option      0        set_theme_mod    0
update_option   0        get_theme_mod    0
add_option      0        update_user_meta 0
delete_option   0        set_transient    2
```

Thứ duy nhất chạm database là transient cache danh sách plugin của trang
**Giao diện → Plugin Flexa**.

Giữ nguyên tình trạng này càng lâu càng tốt. Không lưu gì là cách bảo trì rẻ nhất.

---

## Quy tắc 1 — Cache dùng transient, khoá phải có số phiên bản

```php
const FLEXA_PI_TRANSIENT = 'flexa_pi_plugins_v1';
```

**Đổi cấu trúc mảng được cache thì tăng số này.** Thêm khoá, đổi tên khoá, đổi kiểu dữ liệu — đều tính.

### Vì sao bắt buộc

Cache sống 12 tiếng trên site người dùng. Nếu họ cập nhật theme giữa chừng, code mới sẽ đọc mảng
cũ. Đọc một khoá chưa tồn tại trong bản cũ sinh ra **`Warning: Undefined array key`**, kéo dài tới
khi cache hết hạn.

Quy định WordPress.org mục 4 ghi thẳng:

> There must not be any PHP or JavaScript errors, warnings, or notices.

Lỗi này gần như không thể bắt được lúc phát triển, vì máy dev hiếm khi giữ cache từ phiên bản trước.
Nó chỉ nổ trên site thật, sau khi cập nhật.

Trong riêng phiên làm việc ngày 21/09, cấu trúc mảng cache đã đổi ba lần. Đây là chuyện xảy ra đều
đặn, không phải nguy cơ lý thuyết.

### Tăng số là toàn bộ việc migration

Không cần code dọn dẹp, không cần bảng theo dõi phiên bản kiểu `brandy_successful_migrations`.
Bản ghi cũ đơn giản là không bao giờ được đọc nữa và tự hết hạn.

Nó cũng không tốn gì trong lúc chờ: `set_transient()` đặt `autoload = false` cho mọi transient có
thời hạn, nên hàng cũ không bị nạp vào `alloptions` ở các request frontend.

```php
$autoload = true;
if ( $expiration ) {
    $autoload = false;
    add_option( $transient_timeout, time() + $expiration, '', false );
}
```

---

## Quy tắc 2 — Setting thật thì chỉ được một option duy nhất

Khi nào Flexa cần lưu lựa chọn của người dùng, **ngay từ cái đầu tiên** đã phải theo cấu trúc này:

```php
update_option(
    'flexa_settings',
    array(
        'schema'  => 1,
        'example' => 'value',
    )
);
```

- Tên option: `flexa_settings`, prefix bằng slug của theme
- Kiểu: **mảng**, mọi setting nằm chung trong đó
- Giữ khoá `schema` ngay từ đầu, để sau này đổi cấu trúc còn biết đường xử lý
- Mọi giá trị phải sanitize trước khi ghi, đúng kiểu dữ liệu của nó

### Vì sao

Quy định mục 12:

> Themes must use the Settings and Options APIs when storing custom settings in the database.
>
> Themes must also only add a **single database option**, which should be an array when storing
> multiple settings. This option must also be prefixed with the theme slug.
>
> All data passed to `add_option()`, `update_option()`, or other functions for saving to the
> database must be validated and/or sanitized with the correct function or method for the data type.

Tạo ba option riêng lẻ rồi mới sửa thì **lúc đó mới thật sự cần viết migration** — đúng vết xe
Brandy đã đi (xem [`brandy-comparison.md`](./brandy-comparison.md) §3.4: họ bắt đầu bằng
`theme_mod`, chuyển sang option, rồi phải viết `Database/Migration.php` để dọn phần còn sót).

Làm đúng từ đầu thì không bao giờ phải viết file đó.

---

## Quy tắc 3 — Không dùng `theme_mod` cho dữ liệu ngoài Customizer

`set_theme_mod()` lưu theo từng theme và **mất khi đổi theme**. Chỉ dùng cho thứ đúng nghĩa là tuỳ
biến giao diện của riêng theme này. Mọi thứ khác dùng `flexa_settings`.

---

## Khi nào mới cần migration thật

Chỉ khi cả hai điều sau cùng đúng:

1. Flexa đã phát hành một phiên bản có ghi dữ liệu xuống `flexa_settings`
2. Phiên bản sau đổi ý nghĩa hoặc cấu trúc của dữ liệu đã ghi

Lúc đó đọc `schema`, chuyển đổi, ghi lại với số mới. Vẫn không cần bảng theo dõi riêng — bản thân
khoá `schema` đã là bộ đếm.

Cache **không bao giờ** rơi vào trường hợp này. Cache thì tăng số trong tên khoá và quên nó đi.
