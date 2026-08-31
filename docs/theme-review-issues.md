# Flexa — Danh sách vi phạm quy định WordPress.org Theme Directory

Đối chiếu với [`theme-required.md`](./theme-required.md).
Phiên bản kiểm tra: **1.2.0** · Ngày rà soát: **2026-08-31**

> Quy định ghi rõ: *"Themes that have 3 or more distinct issues may be closed as not-approved."*
>
> Rà soát ban đầu: **3 blocker + 4 nên sửa + 3 nhẹ**. Sau khi soi kỹ: #3 hạ 🔴→🟠, #6 hạ 🟠→🟡, và
> **#11 được phát hiện thêm** trong lúc sửa #4 → tổng **2 blocker + 5 nên sửa + 4 nhẹ**.
>
> **Đã xử lý: #1, #2, #3, #4, #5, #6, #7, #8.** Còn lại 3 mục: #9, #10, #11.
>
> ⚠️ Ba mục từng bị trích sai điều khoản, đã đính chính tại chỗ: **#3** (bỏ sót `theme.json`),
> **#5** (mục #12 không áp), **#6** (theme-support flag không phải hook).

---

## Tổng quan

| # | Mức | Vi phạm | Mục | Trạng thái |
|---|-----|---------|-----|------------|
| 1 | 🔴 Blocker | Shortcode `[flexa_primary_menu]` trong template part (chưa đăng ký) | #5 | **[x] Đã sửa** |
| 2 | 🔴 Blocker | Line-ending lẫn lộn CRLF + LF (11 file CRLF) | #9 | **[x] Đã sửa** |
| 3 | 🟠 Nên sửa | Thiếu `:focus` cho ô form + điều khiển navigation | #3 | **[x] Đã sửa** |
| 4 | 🟠 Nên sửa | Copyright tác giả theme hiển thị ở frontend | #1 | **[x] Đã sửa** |
| 5 | 🟠 Nên sửa | CSS rò rỉ sang wp-admin (enqueue sai hook) | — | **[x] Đã sửa** |
| 6 | 🟡 Nhẹ | `remove_theme_support( 'core-block-patterns' )` | — | **[x] Đã sửa** |
| 7 | 🟠 Nên sửa | Nguy cơ fatal error khi thiếu ext-dom (code chết) | #4 | **[x] Đã sửa** |
| 8 | 🟠 Nên sửa | File `.pot` khai GPLv3 **và thiếu 14 chuỗi** | #1, #8 | **[x] Đã sửa** |
| 9 | 🟡 Nhẹ | Slug / text-domain / tên thư mục không khớp | #8 | [ ] |
| 10 | 🟡 Nhẹ | Trùng tên block style `outline` với core | — | **[x] Đã sửa** |
| 11 | 🟠 Nên sửa | Preset `muted` fail contrast trên `nordic.json` (3.19) | #3 | [ ] |

---

## 🔴 Blocker

### 1. Shortcode `[flexa_primary_menu]` trong `parts/header.html`

**Vi phạm:** Mục #5 — *"Do not include: Shortcodes"*
**Vị trí:** `parts/header.html:6-8`

```html
<!-- wp:shortcode -->
[flexa_primary_menu]
<!-- /wp:shortcode -->
```

**Vấn đề:**

- Block theme không được thêm shortcode.
- Nghiêm trọng hơn: shortcode này **không được đăng ký ở đâu cả** — grep toàn bộ theme không có
  `add_shortcode()` nào. Ngoài frontend nó in ra nguyên chuỗi `[flexa_primary_menu]`,
  **menu chính bị hỏng hoàn toàn**.

**✅ ĐÃ SỬA** — thay khối `wp:shortcode` bằng khối `wp:navigation`, dùng lại nguyên bộ attribute
của `parts/header-with-topbar.html` để hai header trong cùng theme không lệch nhau:

```html
<!-- wp:navigation {"overlayMenu":"mobile","overlayBackgroundColor":"contrast","overlayTextColor":"base","layout":{"type":"flex","justifyContent":"right","flexWrap":"nowrap"}} /-->
```

> **⚠️ Đính chính:** bản đầu của tài liệu này ghi "bỏ luôn `register_nav_menus()`". **Sai — đã giữ lại.**
>
> Xoá nó sẽ làm `has_nav_menu( 'primary' )` trả `false`, kéo theo `Renderer::render_primary_menu()`
> của plugin flexa-starter trả rỗng trên mọi site đã build, và mất luôn ô chọn location ở
> Appearance → Menus.
>
> Nó cũng **không vi phạm gì**: mục #4 miễn trừ rõ *"Exceptions: Menu locations and sidebar IDs"*.
>
> Quan trọng hơn, sau khi đổi sang `core/navigation` thì core mới là bên **đọc** location này:
> `WP_Navigation_Fallback::get_nav_menu_at_primary_location()` đọc `get_nav_menu_locations()['primary']`
> và tự convert menu classic thành `wp_navigation`. Chỉ có comment ở `inc/setup.php` được viết lại
> cho đúng lý do mới.

**Vì sao sửa file này không ảnh hưởng site đã build:** plugin flexa-starter ghi header vào post
`wp_template_part` kèm term `wp_theme` + `wp_template_part_area` (`TemplateParts::apply()`), và core
luôn resolve post đó **thay cho** file theme. `parts/header.html` chỉ là fallback — nó chạy đúng vào
lúc chưa build hoặc sau `TemplateParts::reset()`, tức là đúng lúc không chắc có plugin, nên nó bắt
buộc phải tự đứng được.

**Cách kiểm chứng:**

1. `grep -rn "wp:shortcode\|flexa_primary_menu" .` → phải rỗng
2. Xoá post `wp_template_part` slug `header` → load trang → menu vẫn hiện, không có chuỗi thô
3. Chạy lại build → header của template đè lên như cũ

---

### 2. Line-ending lẫn lộn CRLF + LF

**Vi phạm:** Mục #9 — *"Make sure that only one type of line ending is used. If both DOS and UNIX
line endings are used, this can cause problems with SVN, and your theme or theme update will not
be uploaded to the directory."*

**Cách kiểm tra đúng:** phải soi ở **Git index**, không phải working tree.
Repo đặt `core.autocrlf = true` nên Git tự đổi hết thành CRLF khi checkout xuống Windows —
kiểm tra trên file đã checkout sẽ cho kết quả sai lệch.

```bash
git ls-files --eol
```

**11 file đang là CRLF trong index:**

- `assets/css/editor-style.css`
- `patterns/query-loop-card.php`
- `patterns/query-loop.php`
- `patterns/query-no-results.php`
- `patterns/query-pagination.php`
- `patterns/search-content.php`
- `patterns/single-author-bio.php`
- `patterns/single-comments.php`
- `patterns/single-post-content.php`
- `patterns/single-post-header.php`
- `patterns/single-post-navigation.php`

**36 file còn lại là LF** — kể cả 2 file cùng thư mục `patterns/` là `404-content.php`
và `page-comments.php`, nên ngay trong một thư mục đã lẫn 2 kiểu.

**✅ ĐÃ SỬA** — thêm `.gitattributes` + renormalize:

```bash
git config core.autocrlf false          # config máy, không đi theo repo
# .gitattributes: * text=auto eol=lf, + danh sách binary, + *.sh pin riêng
git add --renormalize .
git checkout-index -f -a                # KHÔNG đủ, xem ghi chú bên dưới
```

`.gitattributes` là phần bắt buộc, không phải `core.autocrlf`. `autocrlf` là config **per-máy**,
không clone theo được, nên nó khiến zip đúng hay sai phụ thuộc vào máy của người chạy `release.sh`.
`eol=lf` trong `.gitattributes` ghi đè `autocrlf` và đi theo repo → kết quả giống nhau trên
Windows / macOS / Linux / CI.

Dùng `eol=lf` chứ không chỉ `text=auto`, vì `release.sh` rsync **working tree** vào zip — nên
working tree mới là thứ thật sự được ship, không phải index.

> **⚠️ Bẫy khi refresh working tree:** sau `git add --renormalize .` thì **index** đã LF nhưng
> **working tree vẫn CRLF**, và `git checkout-index -f -a` *không* ghi đè (Git coi file đã
> up-to-date qua stat cache). Phải ép:
>
> ```bash
> git rm --cached -r -q .
> git reset --hard
> ```
>
> Chạy khi working tree đã sạch. Không có bước này thì repo đúng nhưng zip build ra vẫn CRLF.

**Kết quả kiểm chứng:**

```
git ls-files --eol | awk '{print $1,$2}' | sort | uniq -c
      2 i/-text w/-text        (screenshot.png, .DS_Store)
     48 i/lf    w/lf           (toàn bộ file text, index VÀ working tree)

git diff --cached --ignore-cr-at-eol   → rỗng (nội dung không đổi 1 ký tự)
```

Quét byte-level toàn thư mục (kể cả file chưa track): 48/48 file text đều `crlf=0`, không file nào lẫn.

> **Chưa kiểm chứng được:** máy này thiếu `rsync` và `zip` nên không chạy được `release.sh` để soi
> eol trong zip thật. Cần chạy lại trên máy có 2 tool đó trước khi nộp.

---

### 3. Thiếu `:focus` cho ô form + điều khiển navigation

**Vi phạm:** Mục #3 — *"Theme authors must provide visual keyboard focus highlighting in navigation
menus and for form fields, submit buttons, and text links."*

> **⚠️ Đính chính:** bản đầu của tài liệu này ghi *"không có `:focus` cho link / menu / form / nút
> submit"* và xếp 🔴 Blocker. **Nói quá.** Kết luận đó dựa trên việc chỉ grep `style.css` +
> `assets/css/*.css` — **bỏ sót `theme.json`**, nơi đã khai sẵn:
>
> ```jsonc
> "elements": {
>   "link":   { ":focus": { "outline": { "color": "…accent", "style": "solid", "width": "2px" } } },
>   "button": { ":focus": { "outline": { "color": "…accent", "style": "solid", "width": "2px" } } }
> }
> ```
>
> Và core hỗ trợ thật, không phải khai chết: `class-wp-theme-json.php:299-302` map
> `outline-color/offset/style/width`, còn `VALID_ELEMENT_PSEUDO_SELECTORS` cho phép `:focus` và
> `:focus-visible` trên cả `link` lẫn `button`.
>
> Phần reset trong `style.css` cũng **không** có `outline: none` ở đâu, nên focus ring mặc định của
> trình duyệt còn nguyên. Vì vậy hạ xuống 🟠.

**Thực trạng đúng trước khi sửa:**

| Phần tử | Trạng thái |
|---|---|
| Link văn bản (`a`) | ✅ theme.json |
| Nút block (`.wp-element-button`) | ✅ theme.json |
| Nút submit search / comment form | ✅ core gắn `wp-element-button` → ăn theo |
| Nút style outline | ✅ `assets/css/block-styles.css:11` |
| Skip link | ✅ core tự lo |
| **Ô form** (`input`, `select`, `textarea`) | ❌ chỉ còn ring mặc định của trình duyệt |
| **Nút mở/đóng overlay menu, submenu toggle** | ❌ là `<button>` nhưng không mang `.wp-element-button` |
| `outline-offset` | ❌ không set → viền dính sát chữ |

**✅ ĐÃ SỬA** — thêm một khối vào `style.css`, **không đụng `theme.json`** (phần đang chạy đúng thì
để yên, và `:focus` ở đó còn làm lớp fallback):

1. `input / select / textarea:focus-visible` → outline 2px accent + offset 2px
2. `.wp-block-navigation__responsive-container-open / -close`,
   `.wp-block-navigation-submenu__toggle` → outline 2px **`currentColor`**, không dùng accent, vì
   overlay đảo màu nền (`overlayBackgroundColor: contrast`) sẽ làm viền xanh chìm vào nền tối
3. `a` + `.wp-element-button` → chỉ thêm `outline-offset`; màu và độ dày vẫn ở `theme.json` để style
   variation đổi accent thì viền đổi theo
4. `style.css:207` — thêm `:focus-visible` vào cạnh `.wp-block-post-terms a:hover` (hover mà không
   kèm focus chính là pattern reviewer soi)

Dùng `:focus-visible` để viền chỉ hiện khi đi bằng bàn phím, không hiện khi bấm chuột. Trình duyệt
cũ không hiểu selector thì bỏ qua rule và vẫn còn `:focus` của `theme.json` đỡ bên dưới — không có
kịch bản nào mất hoàn toàn focus indicator.

**Contrast viền so với nền (WCAG 1.4.11 yêu cầu ≥ 3:1)** — đã tính trên cả 6 bảng màu:

| Bảng màu | accent | base | Tỉ lệ | |
|---|---|---|---|---|
| theme.json | `#5f7e64` | `#ffffff` | 4.51 | ✅ |
| dark | `#7aa87f` | `#111111` | 6.96 | ✅ |
| dusty-rose | `#a16161` | `#fdf8f7` | 4.54 | ✅ |
| nordic | `#5b8fa8` | `#f8f9fb` | 3.36 | ✅ (sát nhất) |
| primary | `#c0392b` | `#ffffff` | 5.44 | ✅ |
| sapphire-sun | `#c0392b` | `#ffffff` | 5.44 | ✅ |

**Còn phải test tay:** Tab qua toàn trang (site title → menu → hamburger ở viewport nhỏ → submenu →
search field → search button → nội dung → comment form → submit) phải thấy viền ở mọi điểm dừng; bấm
chuột vào cùng những chỗ đó thì **không** được hiện viền.

---

## 🟠 Nên sửa

### 4. Copyright tác giả theme hiển thị ở frontend

**Vi phạm:** Mục #1 — *"Copyright statements on the front end must only display the user's
copyright, not the theme author's copyright."*
**Vị trí:** `parts/footer.html:4`

```html
<p class="...">© Flexa Theme — demo block theme.</p>
```

### Ràng buộc: block theme không in được năm hiện tại

`parts/footer.html` là **file HTML tĩnh**, không chạy PHP. Không core block nào xuất ra năm hiện
tại, và thêm shortcode thì tái phạm đúng mục #5 vừa sửa ở lỗi #1. Nên "© {năm} {tên site}" không
phải thứ lấy sẵn được.

Tham chiếu: **Twenty Twenty-Five không có dòng © nào cả** — footer nó chỉ có site title, tagline,
nav và một dòng *"Designed with WordPress"* (credit link mục #13 cho phép).

**✅ ĐÃ SỬA** — bỏ năm, giữ tên site động:

```html
<!-- wp:group {"layout":{"type":"flex","justifyContent":"center","flexWrap":"wrap"},"style":{"spacing":{"blockGap":"0.4em"}}} -->
<div class="wp-block-group">
    <!-- wp:paragraph {"fontSize":"small"} -->
    <p class="has-small-font-size">&copy;</p>
    <!-- /wp:paragraph -->

    <!-- wp:site-title {"level":0,"isLink":false,"fontSize":"small"} /-->
</div>
<!-- /wp:group -->
```

- Phải bọc group flex vì `wp:site-title` là block cấp khối, không nhét vào trong `wp:paragraph` được.
- `isLink: false` để nó là text thuần — nếu để link nó ăn style link (gạch chân + accent), lạc lõng
  trong footer. Người dùng bật lại được trong Site Editor.
- Không có năm: mục #1 chỉ yêu cầu *"must only display the user's copyright"*, **không** bắt có năm.
  Bỏ năm thì không bao giờ lỗi thời, không cần PHP, footer vẫn là HTML thuần như mọi part khác.

**Phương án đã cân nhắc và loại:** đổi `parts/footer.html` thành `<!-- wp:pattern {"slug":"flexa/footer"} /-->`
rồi viết `patterns/footer.php` (đúng cơ chế TT5) để có PHP dùng `date_i18n( 'Y' )`. Loại vì ngay khi
người dùng sửa footer trong Site Editor, Gutenberg bung `wp:pattern` thành markup thật và **năm bị
đóng băng** tại thời điểm đó.

### Sửa kèm: bỏ mã màu cứng `#6b7280`

Dòng cũ có `style="color:#6b7280"` — hex cứng, style variation không đổi được, và **đang fail
contrast thật**:

| Bảng màu | Tỉ lệ | |
|---|---|---|
| dark (`#6b7280` trên `#111111`) | **3.91** | ❌ dưới 4.5 |
| các bảng còn lại | 4.59 – 4.83 | ✅ |

Đã **bỏ hẳn thuộc tính màu** để chữ kế thừa màu body (`contrast`, an toàn theo định nghĩa ở mọi
variation) — đúng cách TT5 làm, không override màu ở footer.

Không dùng preset `muted` thay thế, vì nó còn tệ hơn ở chỗ khác — xem mục #11.

---

### 5. CSS rò rỉ sang wp-admin (enqueue sai hook)

**Vị trí:** `inc/block-styles.php:25-32` (cũ)

> **⚠️ Đính chính:** bản đầu ghi *"Liên quan mục #12 — they cannot leak/spill out to other WordPress
> admin pages"*. **Trích sai.** Mục #12 thuộc phần *"Theme settings pages and onboarding"* — nó cấm
> CSS của **trang admin do theme tạo ra** tràn sang trang admin khác. Theme này không có trang admin
> nào, nên câu đó không áp trực tiếp.
>
> Đây là **dùng sai API**, không phải vi phạm một điều khoản cụ thể. Reviewer vẫn bắt, nhưng dưới
> dạng code quality. Cột "Mục" đổi thành `—`.

**Cơ chế** — truy trong core:

```
wp-admin/admin-header.php:137        do_action( 'admin_print_styles' )
wp-admin/includes/admin-filters.php:64  add_action( 'admin_print_styles', 'print_admin_styles', 20 )
wp-includes/script-loader.php        print_admin_styles() → $wp_styles->do_items( false )
```

`do_items( false )` duyệt **toàn bộ** queue, không lọc. Mà `init` chạy trên cả request admin. Nên
`wp_enqueue_style()` đặt ở `init` khiến `block-styles.css` tải trên **mọi trang wp-admin** — mỗi
trang một request thừa, kèm `border: none !important` ([block-styles.css:19](../assets/css/block-styles.css))
rơi vào những trang không hề cần.

**Và nó không đổi lại được gì.** Editor từ WP 6.x render trong **iframe**; `print_admin_styles()` ghi
ra document cha nên các rule này **không** vào tới canvas. Sai cả hai đầu: bẩn admin chrome, mà
editor vẫn thiếu style.

**✅ ĐÃ SỬA** — hai thay đổi, cả hai đều cần:

1. **`inc/block-styles.php`** — tách hàm. `wp_enqueue_block_style()` **giữ ở `init`** (đúng chỗ: nó
   không enqueue gì cả, chỉ đăng ký callback để core chạy lúc block render). `wp_enqueue_style()`
   chuyển sang `wp_enqueue_scripts`.
2. **`inc/setup.php`** — thêm `assets/css/block-styles.css` vào `add_editor_style()`. Đây là cơ chế
   nạp CSS vào **trong iframe** của editor. Không có bước này thì bản sửa là một regression trá
   hình: nút Outline và separator Wavy sẽ không hiển thị đúng trong editor.

**Kiểm chứng thực tế** (không chỉ đọc code): `wp-login.php` dùng **chính** `print_admin_styles()` như
wp-admin (`default-filters.php:395`), nên nó là proxy kiểm tra được mà không cần đăng nhập.

| | `flexa-block-styles-css` trên `wp-login.php` | trên front-end |
|---|---|---|
| Code cũ (`git stash`) | **có** ❌ | có |
| Code mới | **không** ✅ | có ✅ |

**Cải tiến để cân nhắc sau (không làm bây giờ):** tách `block-styles.css` thành `block-button.css` +
`block-separator.css` rồi dùng `wp_enqueue_block_style()` cho từng block — được conditional loading,
trang không có separator thì không tải CSS separator. Vẫn phải giữ bước `add_editor_style()` vì
`wp_enqueue_block_style()` chỉ hook vào front-end:

```php
// script-loader.php, wp_enqueue_block_style()
$hook = did_action( 'wp_enqueue_scripts' ) ? 'wp_footer' : 'wp_enqueue_scripts';
```

Đây là tối ưu hiệu năng, không phải sửa lỗi.

---

### 6. `remove_theme_support( 'core-block-patterns' )`

**Vị trí:** `inc/setup.php:25` (cũ)

> **⚠️ Đính chính:** bản đầu ghi *"Vi phạm mục #5 — Do not: Remove non-presentational hooks"*.
> **Trích sai.** `core-block-patterns` là một **theme-support flag**, không phải hook — không có
> `remove_action`/`remove_filter` nào ở đây. Danh sách cấm của mục #5 (paywall, ẩn admin bar,
> redirect khi kích hoạt, gỡ hook) không phủ được việc này.
>
> **Không phải vi phạm rõ ràng nào cả.** Reviewer có thể hỏi, nhưng không có điều khoản để chỉ vào.
> Hạ từ 🟠 xuống 🟡, cột "Mục" đổi thành `—`.
>
> Đây là lỗi trích dẫn thứ hai trong tài liệu này, sau mục #5.

**Dòng này làm nhiều hơn mô tả ban đầu.** Nó tắt **hai** thứ, không phải một:

```php
// block-patterns.php:72  — pattern đóng gói sẵn trong core
$should_register_core_patterns = get_theme_support( 'core-block-patterns' );

// block-patterns.php:290 và :329  — Pattern Directory từ xa
$supports_core_patterns = get_theme_support( 'core-block-patterns' );
$should_load_remote     = apply_filters( 'should_load_remote_block_patterns', true );
if ( ! $should_load_remote || ! $supports_core_patterns ) { return; }
```

Nên nó **cũng chặn kết nối tới Pattern Directory của WordPress.org** — một lý do chính đáng có thật
(riêng tư, hiệu năng). Có thể người viết cố ý.

**Kiểm tra phụ thuộc trước khi gỡ:**

| Kiểm tra | Kết quả |
|---|---|
| 12 pattern của theme | Tất cả `Categories: flexa`, không dùng category core |
| 20 chỗ `wp:pattern` trong `templates/` + `patterns/` | Tất cả là slug `flexa/*` |
| `_register_theme_block_patterns()` | **Độc lập**, không bị gate |
| Core pattern **categories** | Đăng ký ở `block-patterns.php:96`+, **ngoài** khối `if` |
| `theme.json` khai `patterns` | Không có |

**✅ ĐÃ SỬA** — xoá hẳn dòng đó.

Lý do, dù không phải vi phạm điều khoản: quyết định *"site này có dùng pattern của WordPress hay
không"* là của **chủ site**, không phải của theme. Người dùng mất hàng chục pattern core cộng toàn
bộ Pattern Directory mà **không có UI nào để bật lại** — muốn lấy lại phải sửa code theme.

Mô tả trong `style.css` nói theme là nền sạch *"so you can build without clearing away what someone
else put there first"*. "Someone else" ở đó là tác giả theme; pattern của core là của WordPress,
không phải thứ theme này có quyền dọn hộ.

**Phương án đã cân nhắc và loại:** nếu chỉ muốn chặn kết nối từ xa mà giữ pattern đóng gói sẵn, công
cụ hẹp hơn là:

```php
add_filter( 'should_load_remote_block_patterns', '__return_false' );
```

Không ship cái này — vẫn là theme tự quyết thay người dùng, chỉ nhỏ hơn. Nếu ưu tiên riêng tư thì để
plugin làm, hoặc ghi vào `readme.txt` như đoạn code gợi ý cho child theme.

---

### 7. Nguy cơ fatal error khi host thiếu ext-dom

**Vi phạm:** Mục #4 — *"There must not be any PHP or JavaScript errors, warnings, or notices."*
**Vị trí:** `inc/template-functions.php` (đã xoá)

`new DOMDocument()` không có guard `class_exists()`. WordPress tự xếp `dom` là **tuỳ chọn**:

```php
// wp-admin/includes/class-wp-site-health.php:946-949
'dom' => array( 'class' => 'DOMNode', 'required' => false ),
```

Trên Debian/Ubuntu nó nằm ở gói `php-xml` tách rời. Không có → **fatal error mỗi lần render**
navigation/page-list. Reviewer chạy môi trường chuẩn có `dom` nên gần như chắc chắn không gặp — đây
là rủi ro cho người dùng thật, không phải thứ làm rớt review.

**✅ ĐÃ SỬA — xoá hẳn, không phải thêm guard.** Test hàm đó độc lập (nó không gọi hàm WordPress nào)
cho thấy nó là code chết:

**1. Không đổi gì với markup mà WP 6.5+ thực sự xuất ra**

| Trường hợp | Kết quả |
|---|---|
| Nav thuần `navigation-link` | không đổi |
| Nav có `core/search` | không đổi |
| Nav có `site-title` (core tự bọc `<li>`) | không đổi |
| `core/page-list` | không đổi |

**2. Bất lực với chính trường hợp nó sinh ra để xử lý.** Cho `<ul>` chứa `<form>` trực tiếp:

```
CÂY DOM SAU loadHTML():
  <nav>
    <ul>
      <li> → <a>
    <form> → <input>      ← libxml đã đẩy RA NGOÀI <ul>

Con trực tiếp của <ul>: li
```

`libxml` tự sửa cấu trúc **ngay lúc phân tích** — đóng `<ul>` trước `<form>`. Khi hàm bắt đầu duyệt
thì không còn gì để tìm, `$changed` giữ `false`, trả về nguyên bản.

**3. Chỉ kích hoạt với thứ core không bao giờ tạo ra**

| Chèn vào `<ul>` | Filter chạy? |
|---|---|
| `form`, `script` | không |
| `div`, `span`, `p`, `nav`, `a`, `button`, `ul` lồng | có |

Nhưng từ WP 6.5 `WP_Navigation_Block_Renderer` **đóng `<ul>`** khi gặp block không phải list item
(`navigation.php:281-296`), kể cả `core/spacer` vốn render `<div>`. Mà `style.css` ghi
`Requires at least: 6.5`. Nên chỉ plugin/block tuỳ biến nhét thẳng `<div>` vào `<ul>` mới chạm tới.

**4. Cái giá:** `0.093 ms` mỗi lần render navigation **và** page-list (đo 2000 lần, menu 8 mục), để
không làm gì. Cộng `libxml_use_internal_errors( true )` **không khôi phục** → tắt libxml error
reporting cho toàn bộ request còn lại, ảnh hưởng cả plugin khác.

**Đã xoá:** `inc/template-functions.php`, dòng include trong `functions.php`, và rule CSS chết
`.flexa-nav-item-wrap` ở `assets/css/block-style.css`.

**Nếu sau này thật sự cần bọc `<li>`:** core có sẵn extension point từ 6.5, một dòng, không cần DOM:

```php
add_filter( 'block_core_navigation_listable_blocks', 'flexa_listable_blocks' );
```

---

## 🟡 Nhẹ

### 8. File `.pot` khai sai giấy phép

**Vi phạm:** Mục #1 (giấy phép) **và** mục #8 (*"All text strings must be translatable"*)
**Vị trí:** `languages/flexa.pot`

> **⚠️ Nâng mức:** ban đầu xếp 🟡 vì tưởng chỉ sai 1 dòng giấy phép. Kiểm tra kỹ thì file `.pot`
> **stale toàn diện** — nó được sinh ngày 2026-06-27 cho version **1.0.0** và chưa regenerate lần
> nào kể từ đó, trong khi theme đã lên 1.2.0 qua 3 lần release. Nâng lên 🟠.

**Bốn thứ sai cùng lúc:**

| | Cũ | Đúng |
|---|---|---|
| Giấy phép | GNU GPL **v3** or later | GPL **v2** or later |
| `Project-Id-Version` | Flexa **1.0.0** | Flexa **1.2.0** |
| Số chuỗi | 60 | **74** |
| Line refs | `inc/block-styles.php:42, :50` | `:64, :72` |

Dòng GPLv3 là tàn dư: changelog 1.1.1 ghi *"Unified the theme license to GPLv2 or later across
style.css and readme.txt"* — sửa 2 file đó nhưng quên `.pot`.

**14 chuỗi bị thiếu hoàn toàn** (dịch giả không bao giờ thấy để dịch):

```
"Primary Menu"  "Footer Menu"          ← register_nav_menus, thêm ở 1.2.0
"Dark"                                 ← style variation, thêm ở 1.1.0
"Inter"  "Neutral"  "Secondary"        ← palette + font, thêm ở 1.2.0
"Page (With Title)"                    ← custom template, thêm ở 1.2.0
"1" "2" "3" "4" "5" "6"                ← thang spacing, thêm ở 1.2.0
```

`styles/dark.json` không được tham chiếu một lần nào (giờ: 7 lần).

**✅ ĐÃ SỬA** — regenerate bằng chính công cụ đã sinh ra nó (`X-Generator: WP-CLI 2.12.0`):

```bash
wp i18n make-pot . languages/flexa.pot --domain=flexa --exclude=docs,dist \
   --headers='{"Report-Msgid-Bugs-To":"https://wordpress.org/support/theme/flexa"}'
```

Giấy phép và version giờ đọc thẳng từ `style.css` nên tự khớp. 74 chuỗi, **không mất chuỗi nào**
(diff chỉ có dòng `>`, không có `<`).

> **Vì sao phải truyền `--headers` thủ công:** `make-pot` suy ra slug từ **tên thư mục**, nên nó tự
> ghi `.../theme/flexa-theme`. Đó chính là triệu chứng của mục **#9** rò vào file này. Ép về `flexa`
> cho khớp với Text Domain và `SLUG` trong `release.sh`. Sau khi #9 xong (đổi tên thư mục thành
> `flexa`) thì cờ này không còn cần nữa.

**Ghi chú môi trường:** máy này không có `wp` trên PATH. Đã tải `wp-cli.phar` về scratchpad và chạy
bằng PHP của Local, cần bật thêm 3 extension mà PHP CLI mặc định không nạp:

```
-d extension=php_mbstring.dll   # make-pot bắt buộc
-d extension=php_openssl.dll    # \
-d extension=php_curl.dll       # / để tải theme-i18n.json cho việc trích chuỗi từ theme.json
```

**Cần làm lại `make-pot` mỗi khi:** thêm/sửa chuỗi dịch, đổi palette hoặc font trong `theme.json`,
thêm style variation, thêm custom template, hoặc bump version.

---

### 9. Slug / text-domain / tên thư mục không khớp

**Vi phạm:** Mục #8 — *"Use the theme slug as the text-domain… It is also the folder name for the theme."*

| Thành phần | Giá trị |
|---|---|
| Tên thư mục hiện tại | `flexa-theme` |
| Text Domain (`style.css`) | `flexa` |
| `SLUG` trong `release.sh` | `flexa` |

Zip build ra đúng (`flexa/`), nhưng thư mục làm việc thì lệch — dễ gây nhầm khi test hoặc khi
ai đó zip thủ công.

**Cách sửa:** đổi tên thư mục local thành `flexa`.

---

### 10. Trùng tên block style `outline` với core

**Vị trí:** `inc/block-styles.php:60-66` (cũ)

Core đã khai `outline` cho `core/button` ngay trong block.json:

```jsonc
// wp-includes/blocks/button/block.json:143-144
"styles": [
  { "name": "fill", "label": "Fill", "isDefault": true },
  { "name": "outline", "label": "Outline" }
]
```

kèm CSS ở `blocks/button/style.css:78-88`. Theme đăng ký lại **cùng tên** → thêm một entry thứ hai
vào `WP_Block_Styles_Registry` bên cạnh cái của core.

`WP_Block_Styles_Registry::register()` **không kiểm tra trùng** — nó ghi thẳng vào
`registered_block_styles[$block][$name]`, không `_doing_it_wrong`, không `WP_Error`. Nên va chạm này
diễn ra hoàn toàn im lặng, không gì báo.

**Đối chiếu `core/separator`:** core chỉ có `default`, `wide`, `dots` — **không** có `wavy`. Nên
đăng ký `wavy` là bổ sung thật, đúng chỗ, **giữ nguyên**.

**✅ ĐÃ SỬA** — bỏ đăng ký `outline`, giữ `wavy`.

**Không mất gì.** `is-style-outline` vẫn do core cung cấp, và đó chính là class mà
`assets/css/block-styles.css:4-13` nhắm tới để đổi giao diện. Theme vẫn restyle được nút Outline như
cũ — nó chỉ thôi khai lại một style vốn đã tồn tại.

> Chuỗi `__( 'Outline', 'flexa' )` biến mất theo, nên `.pot` đã được regenerate lại trong cùng thay
> đổi này. Xem lệnh ở mục #8.

---

### 11. Preset `muted` fail contrast trên `nordic.json`

**Vi phạm:** Mục #3 (Accessibility)
**Vị trí:** `styles/nordic.json` — palette `muted`

Phát hiện khi tính contrast cho mục #4. Không phải màu trang trí — `muted` đang dùng cho **chữ thật**
ở `style.css:185` (`.post-meta`) và `patterns/single-post-header.php:10` (ngày đăng, tác giả).

| Bảng màu | muted | base | Tỉ lệ | (cần 4.5 cho chữ thường) |
|---|---|---|---|---|
| theme.json | `#707070` | `#ffffff` | 4.95 | ✅ |
| dark | `#9a9a9a` | `#111111` | 6.71 | ✅ |
| dusty-rose | `#7c6464` | `#fdf8f7` | 5.16 | ✅ |
| **nordic** | **`#7a8f9e`** | **`#f8f9fb`** | **3.19** | ❌ |
| primary | `#6c6c80` | `#ffffff` | 5.13 | ✅ |
| sapphire-sun | `#323232` | `#ffffff` | 12.82 | ✅ |

**Cách sửa:** làm tối `muted` của nordic lại, giữ nguyên sắc xanh xám của bảng màu. Đã tính sẵn vài
ứng viên trên nền `#f8f9fb`:

| Màu | Tỉ lệ | |
|---|---|---|
| `#7a8f9e` (hiện tại) | 3.19 | ❌ |
| `#647889` | 4.34 | ❌ vẫn thiếu |
| `#5f7181` | 4.79 | ✅ |
| `#5c6e7d` | 5.01 | ✅ có biên an toàn hơn |

Đề xuất `#5f7181`. Cần xem mắt thường trên `nordic` trước khi chốt — con số đạt không có nghĩa là
nhìn vẫn còn ra chất "muted".

---

## Ghi chú thêm: file dev trong thư mục theme

Không phải vi phạm nếu đóng gói bằng `release.sh` (script đã `--exclude` chúng), nhưng cần lưu ý
nếu zip thủ công. Các file/thư mục sau **không được có** trong zip nộp lên (mục #9):

- `.DS_Store` ← *`.gitignore` hiện chưa ignore file này*
- `.gitignore`
- `release.sh`
- `docs/` ← *bao gồm chính file này*

---

## Các mục đã ĐẠT

Ghi lại để không mất công kiểm tra lại:

- ✅ Screenshot `1200×900`, đúng tỉ lệ 4:3, không vượt giới hạn (mục #9)
- ✅ Đủ file bắt buộc của block theme: `style.css`, `readme.txt`, `theme.json`,
  `templates/index.html` (mục #11)
- ✅ Tất cả 11 template đều có `"tagName":"main"` → skip link được core tự sinh (mục #3)
- ✅ Không có remote resource / CDN / Google Fonts — toàn bộ dùng system font stack (mục #9)
- ✅ Prefix `flexa` (5 ký tự ≥ 4), nhất quán cho function, style handle, pattern category (mục #4)
- ✅ `ABSPATH` guard trên mọi file PHP
- ✅ Không có CPT, custom block, custom role, custom mime type, contact method (mục #5)
- ✅ Không có admin page, không có demo import, không có external call (mục #12)
- ✅ Không có upsell / affiliate link ở frontend (mục #13)
- ✅ Tags trong `style.css` và `readme.txt` khớp nhau và đều hợp lệ
- ✅ Chỉ dùng một text domain duy nhất (`flexa`) (mục #8)
