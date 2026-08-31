# Flexa — Danh sách vi phạm quy định WordPress.org Theme Directory

Đối chiếu với [`theme-required.md`](./theme-required.md).
Phiên bản kiểm tra: **1.2.0** · Ngày rà soát: **2026-08-31**

> Quy định ghi rõ: *"Themes that have 3 or more distinct issues may be closed as not-approved."*
>
> Rà soát ban đầu: **3 blocker + 4 nên sửa + 3 nhẹ**. Sau khi soi kỹ, mục #3 được hạ từ 🔴 xuống 🟠
> (xem phần đính chính trong mục đó) → còn **2 blocker + 5 nên sửa + 3 nhẹ**.
>
> **Đã xử lý: #1, #2, #3.** Còn lại 5 mục.

---

## Tổng quan

| # | Mức | Vi phạm | Mục | Trạng thái |
|---|-----|---------|-----|------------|
| 1 | 🔴 Blocker | Shortcode `[flexa_primary_menu]` trong template part (chưa đăng ký) | #5 | **[x] Đã sửa** |
| 2 | 🔴 Blocker | Line-ending lẫn lộn CRLF + LF (11 file CRLF) | #9 | **[x] Đã sửa** |
| 3 | 🟠 Nên sửa | Thiếu `:focus` cho ô form + điều khiển navigation | #3 | **[x] Đã sửa** |
| 4 | 🟠 Nên sửa | Copyright tác giả theme hiển thị ở frontend | #1 | [ ] |
| 5 | 🟠 Nên sửa | CSS rò rỉ sang wp-admin (enqueue sai hook) | #12 | [ ] |
| 6 | 🟠 Nên sửa | `remove_theme_support( 'core-block-patterns' )` | #5 | [ ] |
| 7 | 🟠 Nên sửa | Nguy cơ fatal error khi thiếu ext-dom | #4 | [ ] |
| 8 | 🟡 Nhẹ | File `.pot` khai GPLv3, theme khai GPLv2 | #1 | [ ] |
| 9 | 🟡 Nhẹ | Slug / text-domain / tên thư mục không khớp | #8 | [ ] |
| 10 | 🟡 Nhẹ | Trùng tên block style `outline` với core | #4 | [ ] |

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

**Cách sửa:** thay bằng copyright động của site — khối `wp:site-title` kèm năm, thay vì tên
thương hiệu theme. Chuỗi "demo block theme" cũng cần bỏ.

---

### 5. CSS rò rỉ sang wp-admin (enqueue sai hook)

**Vi phạm:** Liên quan mục #12 — *"they cannot leak/spill out to other WordPress admin pages"*
**Vị trí:** `inc/block-styles.php:25-32`

`wp_enqueue_style( 'flexa-block-styles', … )` được gọi trên hook `init`, không phải
`wp_enqueue_scripts`. Hàng đợi style là global nên file này sẽ nạp **cả trong wp-admin**.

**Cách sửa:** tách ra — giữ `wp_enqueue_block_style()` ở `init` (đúng), chuyển
`wp_enqueue_style()` sang hook `wp_enqueue_scripts`.

---

### 6. `remove_theme_support( 'core-block-patterns' )`

**Vi phạm:** Mục #5 — *"Do not: Remove non-presentational hooks."*
**Vị trí:** `inc/setup.php:25`

Gỡ toàn bộ pattern của core là gỡ một tính năng WordPress khỏi người dùng. Đây là điểm reviewer
thường xuyên yêu cầu bỏ.

**Cách sửa:** xoá dòng này.

---

### 7. Nguy cơ fatal error khi host thiếu ext-dom

**Vi phạm:** Mục #4 — *"There must not be any PHP or JavaScript errors, warnings, or notices."*
**Vị trí:** `inc/template-functions.php:21`

```php
$dom = new DOMDocument();
```

Không kiểm tra `class_exists( 'DOMDocument' )`. Trên host không bật extension `dom` sẽ
**fatal error mỗi lần render** block navigation / page-list.

**Cách sửa:** thêm guard đầu hàm:

```php
if ( ! class_exists( 'DOMDocument' ) ) {
    return $block_content;
}
```

---

## 🟡 Nhẹ

### 8. File `.pot` khai sai giấy phép

**Vi phạm:** Mục #1
**Vị trí:** `languages/flexa.pot:2`

```
# This file is distributed under the GNU General Public License v3 or later.
```

Trong khi `style.css` và `readme.txt` đều khai **GPLv2 or later**.

**Cách sửa:** sửa header `.pot` thành GPLv2 or later cho thống nhất (hoặc regenerate bằng
`wp i18n make-pot` sau khi cấu hình đúng).

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

**Vị trí:** `inc/block-styles.php:38-45`

Đăng ký block style tên `outline` cho `core/button`, nhưng core **đã có sẵn** `is-style-outline`
cho block này → ghi đè / trùng lặp.

**Cách sửa:** đổi tên style thành có prefix (`flexa-outline`) hoặc bỏ hẳn và chỉ style lại
`is-style-outline` của core qua CSS.

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
