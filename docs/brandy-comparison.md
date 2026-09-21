# Brandy vs Flexa — Đối chiếu chức năng

Brandy **2.0.0** (YayCommerce, thương mại) · Flexa **1.2.0** · Ngày rà soát: **2026-09-21**

> Đọc trực tiếp mã nguồn Brandy tại `wp-content/themes/brandy/`, không dựa vào tài liệu quảng cáo.

---

## 0. Bối cảnh — đừng so sánh nhầm hạng cân

Hai theme này **không cùng mục tiêu**, nên phần lớn khác biệt về quy mô là chủ đích chứ không phải thiếu sót:

| | Brandy | Flexa |
|---|---|---|
| Kênh phân phối | Bán riêng tại wpbrandy.com | WordPress.org Theme Directory |
| Định vị | Theme ecommerce trọn gói | Block starter theme tối giản |
| Quy mô PHP | **508 file / 59.209 dòng** | **23 file / 1.312 dòng** |
| Patterns | 67 | 12 |
| Templates / parts / styles | 19 / 2 / 4 | 11 / 3 / 5 |
| Kiến trúc | PSR-4 `Brandy\` → `inc/`, Composer autoload, class + SingletonTrait | Hàm thuần có prefix `flexa_`, require_once |
| Admin UI | React + Vite, build sẵn vào `assets/dist/` | PHP render + vanilla JS |

Flexa nhỏ hơn 45 lần là **đúng định vị**. Mục tiêu của tài liệu này không phải đuổi kịp quy mô, mà là nhặt những kỹ thuật cụ thể đáng mang về.

---

## 1. Brandy có những chức năng gì

### 1.1 Header / Footer Builder riêng

`inc/Customizer/` — hệ builder tự viết, không dùng FSE template parts cho header/footer khi bật. Có **51 element** trong `Customizer/Elements/`: Logo, Menu (4 biến thể), MegaMenu, Search, Cart, Wishlist, Account, Currency, LanguageSwitcher, Newsletter, Socials, PaymentMethod, Divider, HTML, Widget, Button…

Đây là phần đồ sộ nhất: `BaseSearch.php` **1911 dòng**, `BaseLanguageSwitcher.php` 1515 dòng, `BaseMenu.php` 1346 dòng.

Có cơ chế chuyển đổi: `brandy_uses_native_header_footer()` — nếu theme dùng FSE part thật thì bỏ qua toàn bộ builder (`ThemeInitialize.php`).

### 1.2 Block Settings — mở rộng editor

`inc/BlockSettings/`, **24 module**, mỗi module một tính năng thêm vào block editor:

`AutoHoverColor`, `ButtonSize`, `ContentResponsiveControls`, `CopyAnimation`, `CustomBoxShadow`, `DisplayAnimation`, `DisplaySettings`, `EditorBadge`, `FeaturedImagePlaceholder`, `GalleryMasonryDisplay`, `GridResponsiveLayout`, `HoverAnimation`, `HoverSettings`, `ImageObjectPosition`, `ImagePerformance`, `ProductDetailsDisplayTabs`, `ProductImageTooltip`, `ResponsiveTypography`, `SliderControlVariants`, `StringVariables`, `VariationHoverCompat`, `AvatarPostAuthorTooltip`, `BlockPresets`.

### 1.3 Dashboard riêng

`inc/Admin/Pages/Dashboard/` — trang React với 4 tab (Dashboard, Starter Sites, Extensions, Changelog), thêm shortcut vào admin bar, và:

- `ThemeDashboard.php` (536 dòng) — điều phối, 7 endpoint AJAX
- `PluginService.php` (480 dòng) — cài/kích hoạt plugin đề xuất
- `ImportService.php` (609 dòng) — import nội dung mẫu theo từng chunk, có theo dõi `position` để chạy tiếp

### 1.4 Niches — bộ template theo ngành

`inc/Niches/` + `AbstractNicheSetup.php` (1444 dòng). Chọn một "niche" là import trọn gói pattern, template, widget, ảnh mẫu.

### 1.5 WooCommerce sâu

`inc/WooCommerce/`: Cart, Checkout, SingleProduct, ProductLoop, BuyNow, StickyAddToCart. Kèm CSS tách riêng cho từng trang Woo (`wc-cart`, `wc-checkout`, `wc-my-account`, `wc-single`, `wc-order-completed`).

### 1.6 Tích hợp plugin cùng nhà

`inc/Integrations/`: YayCurrency, YayExtra, YayPricing, YaySwatches, và `CachePurgeIntegration`.

### 1.7 Hạ tầng

- `inc/DynamicCss/` — sinh CSS động từ tuỳ chọn của người dùng
- `inc/Core/Services/PerformanceService.php` — tối ưu tải
- `inc/Database/Migration.php` — **migration cho option**, chuyển `theme_mod` → `option`, dọn option cũ còn sót
- `inc/ThemeJson/CustomSelectors/` — mở rộng selector của theme.json
- `CustomizerVite.php` / `FrontendVite.php` / `PostEditorVite.php` — chuyển đổi dev server ↔ bản build
- AI Layout Builder (mới ở 2.0.0) — mô tả bằng chữ, theme dựng section

---

## 2. Flexa đang làm tốt hơn

### 2.1 🟢 Trang cài plugin: động thay vì hardcode

Đây là khác biệt lớn nhất và nghiêng hẳn về Flexa.

| | Brandy | Flexa |
|---|---|---|
| Nguồn danh sách | Hardcode ~12 plugin trong PHP | `plugins_api( 'query_plugins', [ 'author' => … ] )` |
| Link tải | Hardcode `downloads.wordpress.org/...zip` | Lấy từ `plugins_api( 'plugin_information' )` |
| Cache | Không | Transient 12h + nút làm mới |
| Phát hiện cập nhật | **Không có** | Có, so version từ header trên đĩa |
| Publish plugin mới | Phải sửa code, phát hành theme mới | Tự xuất hiện |

Brandy *có* gọi `plugins_api` trong `PluginService::get_plugins_information()`, nhưng chỉ để lấy thêm metadata rồi **lọc ngược lại theo danh sách hardcode**. Không giải quyết được vấn đề gốc.

### 2.2 🟢 Bảo mật endpoint AJAX

`ThemeDashboard::install_plugin()` (`inc/Admin/Pages/Dashboard/ThemeDashboard.php:301`):

- **Không có `current_user_can()`** — chỉ kiểm tra nonce
- `$_POST['plugin']` nhận nguyên mảng **không sanitize**, rồi `$plugin['download_link']` đưa thẳng vào `Plugin_Upgrader::install()` → URL ZIP do client quyết định

Phạm vi khai thác: nonce chỉ sinh ra trên trang dashboard (cap `switch_themes`, mặc định chỉ Administrator), mà nonce WordPress gắn theo user nên user quyền thấp không tự tạo được. **Chưa phải lỗ hổng leo thang đặc quyền khai thác được ngay**, nhưng là thiếu lớp phòng thủ: nonce chống CSRF chứ không phải phân quyền. Nếu nonce lọt ra thì thành cài ZIP tuỳ ý từ xa.

Flexa làm đủ cả ba lớp trên mọi endpoint: nonce riêng cho từng action, `current_user_can()`, allowlist slug theo danh sách tác giả, cộng `validate_file()`.

Công bằng mà nói: cùng codebase Brandy, `PostEditorSetup::ajax_install_ai_builder_plugin()` lại làm đúng đủ. Đây là thiếu sót cục bộ, không phải chuẩn chung của họ.

### 2.3 🟢 Vị trí menu đúng luật WordPress.org

Brandy dùng `add_menu_page()` với `POSITION = 2` — menu cấp cao nhất, chen lên gần đầu sidebar. Theme trên wp.org **không được phép** làm vậy; đây là một trong các lỗi bị soi khi review.

Flexa dùng `add_submenu_page( 'themes.php', … )` — đúng chỗ.

### 2.4 🟢 Kích thước file kiểm soát được

File lớn nhất của Flexa là `inc/plugin-installer/ajax.php` 232 dòng. Brandy có 12 file trên 800 dòng, đỉnh là 1911 dòng. Một file 1911 dòng không ai review nổi.

### 2.5 🟢 Escape và sanitize

Flexa escape đầy đủ ở mọi điểm xuất (`esc_html`, `esc_attr`, `esc_url`) và sanitize mọi input. Brandy có chỗ lấy `$_POST['plugin']` nguyên mảng không xử lý.

---

## 3. Flexa đang thiếu hoặc kém hơn

### 3.1 ✅ ĐÃ SỬA — Bỏ qua `unexpected_output` khi kích hoạt

`activate_plugin()` trả về `WP_Error` mã `unexpected_output` khi plugin **in ra output lúc load nhưng vẫn được bật thành công**. Brandy xử lý đúng:

```php
// PluginService::activate_plugin()
if ( is_wp_error( $activate_status ) && 'unexpected_output' !== $activate_status->get_error_code() ) {
    throw new \Error( __( 'Activate failed', 'brandy' ) );
}
```

Trước đây `inc/plugin-installer/ajax.php` coi mọi `WP_Error` là thất bại → báo lỗi đỏ dù plugin đã bật.

**Đã sửa 2026-09-21** trong `flexa_pi_ajax_activate()`, dùng đúng cách kiểm tra như Brandy.

> **Phát hiện thêm trong lúc test.** Nút Cập nhật để lại plugin ở trạng thái **tắt**. Nguyên nhân
> nằm ở chỗ khác hẳn: `Plugin_Upgrader::upgrade()` đăng ký `deactivate_plugin_before_upgrade()`
> để tắt plugin, còn `active_before()`/`active_after()` chỉ bật/tắt maintenance mode và chỉ chạy
> trong cron — **không có gì bật plugin lại**. Đã đổi sang `bulk_upgrade()`, đúng như
> `wp_ajax_update_plugin()` của core làm. Đây là lỗi của Flexa, Brandy không liên quan.

### 3.2 ✅ ĐÃ SỬA — Không dò bản Pro

Brandy kiểm tra cả `$slug` lẫn `$slug . '-pro'`; nếu bản Pro đã cài thì không mời cài bản free nữa.

**Nhưng đừng bê nguyên logic này** — nó chỉ đúng với mô hình Pro thay thế free. Plugin của Flexa lại theo mô hình add-on: `flexa-block-pro` khai báo `Requires Plugins: flexa-block` và từ WordPress 6.5 core **bắt buộc** header này, tức không có bản free thì không kích hoạt nổi bản Pro. Chặn cài bản free trong trường hợp đó là làm hỏng chính thứ người dùng cần.

Cách làm đúng là đọc header `RequiresPlugins` (có sẵn trong `get_plugins()`) để phân biệt hai mô hình, thay vì suy đoán từ hậu tố `-pro`:

- Pro **có** khai báo phụ thuộc → vẫn mời cài bản free, kèm nhãn giải thích tại sao
- Pro **không** khai báo → là bản độc lập, khoá nút cài

Site hiện tại có cả `flexa-block` lẫn `flexa-block-pro` nên không lộ lỗi; chỉ khi gỡ bản free mới thấy.

**Đã làm 2026-09-21** trong `flexa_pi_get_state()` + `flexa_pi_pro_requires()`. Kiểm thử 6 tổ hợp
(không có gì / chỉ free / chỉ Pro add-on / chỉ Pro độc lập / cả hai × 2 kiểu) đều đúng. Kết quả:
Flexa giờ **xử lý đúng hơn Brandy** ở mục này, vì Brandy chặn theo hậu tố `-pro` mà không đọc header.

### 3.3 🟠 Không có kiến trúc class khi codebase lớn lên

Flexa dùng hàm thuần prefix `flexa_pi_`. Ở mức 1.312 dòng thì **hoàn toàn hợp lý** — thêm namespace lúc này chỉ tạo indirection thừa. Nhưng nếu theme phình lên vài chục nghìn dòng thì mô hình PSR-4 + `SingletonTrait` của Brandy là hướng đi đúng, nên biết trước.

### 3.4 ✅ ĐÃ XỬ LÝ — Cơ chế migration

Brandy có `Database/Migration.php`, ghi nhận version đã migrate vào `brandy_successful_migrations` để không chạy lại. Chi tiết đáng học: họ dọn các option `brandy_unsaved_*` cũ vì chúng **autoload** và bị nạp vào cache `alloptions` ở mọi request frontend.

**Rà soát 2026-09-21:** Flexa không lưu một option nào (`get_option`/`update_option`/`add_option`/`set_theme_mod` đều bằng 0). Thứ duy nhất chạm database là transient cache. Nên migration theo nghĩa đen là **không có gì để migrate**, và bài học autoload thì Flexa đã đúng sẵn — `set_transient()` đặt `autoload = false` cho mọi transient có thời hạn.

Nhưng nguyên tắc đằng sau chỉ ra một lỗi thật sắp xảy ra: cấu trúc mảng được cache đã đổi ba lần trong một phiên làm việc, mà `card.php` đọc thẳng các khoá không kiểm tra. Site giữ cache 12 tiếng → cập nhật theme giữa chừng → code mới đọc mảng cũ → `Warning: Undefined array key`, vi phạm mục 4 của quy định wp.org.

**Đã làm:** gắn số phiên bản vào khoá transient (`flexa_pi_plugins_v1`), tăng số mỗi khi đổi cấu trúc. Đó là toàn bộ migration cần thiết — bản ghi cũ không được đọc nữa và tự hết hạn. Quy ước đầy đủ nằm ở [`storage-conventions.md`](./storage-conventions.md).

### 3.5 ⛔ BỎ QUA — Shortcut trên admin bar

Brandy thêm mục vào admin bar qua `add_dashboard_to_admin_bar()`, và **gỡ node `plugins` của core ra rồi gắn lại phía sau** để mục của mình đứng trên:

```php
$wp_admin_bar->remove_node( 'plugins' );
$wp_admin_bar->add_node( array(
    'parent' => 'site-name',
    'id'     => 'brandy-dashboard',
    'title'  => __( 'Brandy Dashboard', 'brandy' ),
    'href'   => admin_url( 'admin.php?page=' . self::PAGE_ID ),
) );
```

**Quyết định 2026-09-21: không làm.** Ba lý do, xếp theo sức nặng.

**1. Bản sao y nguyên vi phạm mục 13 ở hai chỗ.** Tra `wp_admin_bar_site_menu()` trong core thì node `plugins` nằm ở nhánh `elseif`, tức **chỉ tồn tại ngoài front end**:

```php
if ( is_admin() ) {
    // Visit Site / Manage Site
} elseif ( current_user_can( 'read' ) ) {
    // Dashboard, Themes, Widgets, Menus…
    if ( current_user_can( 'activate_plugins' ) ) {
        $wp_admin_bar->add_node( array( 'id' => 'plugins', … ) );
    }
}
```

Nên mục đó hiện trên **giao diện người dùng**, không phải trong admin. Đối chiếu mục 13:

> Themes must not display **upselling on the front**.
>
> Themes can include **one single front-facing credit link**, which is restricted to the Theme URI or Author URI defined in `style.css`.

Một link front-facing trỏ tới trang liệt kê 11 plugin của chính tác giả — vừa là upselling ngoài front end, vừa là link front-facing thứ hai. Brandy không phải lo vì bán riêng, không qua review.

**2. Bản giới hạn trong admin thì hết vi phạm, nhưng giá trị gần như bằng không.** Thêm `if ( ! is_admin() ) return;` là an toàn, song trang Plugin Flexa là màn hình người ta ghé vài lần sau khi cài theme rồi thôi, hiện đã cách 2 cú bấm. Đổi một vị trí vĩnh viễn trên mọi trang admin lấy 1 cú bấm là không xứng.

Khác biệt bản chất: dashboard của Brandy là nơi quay lại liên tục (Starter Sites, Extensions, Quick settings, Customizer shortcut). Trang của Flexa làm một việc, xong là thôi.

**3. Gỡ node của core để chen lên trước là thói quen xấu**, không nên mang về kể cả khi hợp lệ.

**Mở lại khi nào:** nếu trang đó phình thành dashboard thật — nhiều tab, người dùng quay lại thường xuyên.

### 3.6 🟠 Child theme không override được hàm nào của parent

> Phát hiện ngày 2026-09-21, không có trong bản rà soát đầu.

Theme định nghĩa **29 hàm toàn cục, 0 hàm có `function_exists()` guard**. Điều này quan trọng vì
thứ tự nạp file trong `wp_get_active_and_valid_themes()` (`wp-includes/load.php`):

```php
if ( is_child_theme() ) {
    $themes[] = $wp_stylesheet_path;   // child vào trước
}
$themes[] = $wp_template_path;         // parent vào sau
```

`wp-settings.php:746` include theo đúng thứ tự đó, nên **`functions.php` của child chạy trước parent**.
Hậu quả: child theme định nghĩa `flexa_setup()` để đổi theme support → tới lượt parent nạp, PHP gặp
hàm trùng tên → **Fatal error: Cannot redeclare**. Trắng màn hình.

Không phải tình huống giả định: thư mục `flexa-theme-child/` đã tồn tại, `readme.txt` có mục FAQ
*"Can I use Flexa as a parent theme?"*, và style.css tự định vị là starter theme để người khác xây tiếp.

Kèm theo: `inc/plugin-installer/screen.php:34` dùng closure làm callback cho `admin_enqueue_scripts`,
nên child theme hay plugin **không `remove_action()` được**.

**Cách sửa** — chỉ bọc 5 hàm cấp theme (`flexa_setup`, `flexa_enqueue_styles`,
`flexa_register_block_styles`, `flexa_register_patterns`, `flexa_enqueue_block_styles`), và đổi
closure thành hàm có tên. Không bọc 22 hàm nội bộ `flexa_pi_*` — chúng là chi tiết cài đặt, bọc hết
sẽ gửi tín hiệu sai rằng mọi thứ đều nên override.

### 3.7 🟡 Thiếu các mảng chức năng lớn

Header/footer builder, Starter Sites, WooCommerce, DynamicCss, Block Settings — Flexa không có. **Đây là chủ đích**, không phải thiếu sót: theme starter tối giản mà nhồi builder riêng thì hỏng định vị, và phần lớn những thứ này thuộc địa hạt plugin nên wp.org sẽ không duyệt.

---

## 4. Nên học gì — xếp theo mức đáng làm

| # | Việc | Mức | Ghi chú |
|---|---|---|---|
| 1 | Bỏ qua `unexpected_output` khi activate | ✅ Xong | 2026-09-21, §3.1 |
| 2 | Dò bản Pro qua header `RequiresPlugins` | ✅ Xong | 2026-09-21, §3.2 — làm tốt hơn Brandy |
| 3 | **Cho child theme override được** | 🟠 **Việc kế tiếp** | §3.6 — hiện đang gây fatal error |
| 4 | Quy ước lưu trữ + version hoá cache | ✅ Xong | 2026-09-21, §3.4 → [`storage-conventions.md`](./storage-conventions.md) |
| 5 | Shortcut admin bar cho trang Plugin Flexa | ⛔ Không làm | §3.5 — hiện ngoài front end, đụng mục 13 |
| 6 | Mô hình PSR-4 + SingletonTrait | ⛔ Không làm | §3.3 — 1.413 dòng, thêm vào chỉ tạo gián tiếp thừa |
| 7 | Vite dev/prod swap | 🟡 Để dành | Chỉ khi chuyển admin UI sang React |

Hai kỹ thuật nhỏ khác đáng ghi nhận:

- **Tách `brandy_uses_native_header_footer()` làm công tắc** — nạp hay không nạp cả một mảng tính năng dựa trên một điều kiện, thay vì rải `if` khắp nơi.
- **Giải thích "tại sao" trong comment.** Comment của Brandy ở chỗ xử lý WooCommerce 10.9 (pre-seed transient `wc_attribute_taxonomies` để tránh query khi bảng chưa tạo) ghi rõ nguyên nhân và hệ quả. Đây là kiểu comment đáng học.

---

## 5. Không nên học

| Việc | Lý do |
|---|---|
| Hardcode danh sách plugin + download link | Publish plugin mới là phải phát hành lại theme |
| Thiếu `current_user_can()` ở endpoint AJAX | Nonce là chống CSRF, không phải phân quyền |
| Nhận `download_link` từ `$_POST` | Client quyết định cài ZIP nào |
| `add_menu_page()` ở position 2 | wp.org cấm; Flexa phải giữ `add_submenu_page` |
| File 1900 dòng | Không review được |
| Comment bỏ phần kiểm tra nonce rồi để lại | `inc/Core/Ajax.php:150-154`, code chết |
| Bảng dịch JS viết tay | `I18n::get_translations()` liệt kê thủ công từng chuỗi tiếng Anh → `__()`. Dùng `wp_set_script_translations()` đúng hơn |

---

## Phụ lục — số liệu

```
Brandy 2.0.0                        Flexa 1.2.0
508 file PHP / 59.209 dòng          23 file PHP / 1.312 dòng
67 patterns                         12 patterns
19 templates / 2 parts / 4 styles   11 templates / 3 parts / 5 styles
51 Customizer elements              0
24 BlockSettings modules            0
7 AJAX endpoint (dashboard)         3 AJAX endpoint
File lớn nhất: 1.911 dòng           File lớn nhất: 232 dòng
register_post_type: 0               0
add_shortcode: 0                    0
dbDelta / CREATE TABLE: 0           0
```

Điểm chung tích cực: **cả hai đều không lấn địa hạt plugin** theo nghĩa custom post type, shortcode hay tạo bảng riêng.
