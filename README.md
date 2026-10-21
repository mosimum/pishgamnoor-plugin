# Pishgamnoor Studio

پلاگین اختصاصی وردپرس برای **شبیه‌ساز سه‌بعدی محصولات پیشگام نور**.

- **نسخه:** 1.4
- **توسعه‌دهنده:** Mostafa Moosavi
- **مخزن:** https://github.com/mosimum/pishgamnoor-plugin
- **Release 1.4:** `https://github.com/mosimum/pishgamnoor-plugin/releases/tag/v1.4`
- **شورت‌کد:** `[pishgamnoor_studio]`
- **حداقل وردپرس:** 6.0
- **حداقل PHP:** 7.4
- **مجوز:** GPL-2.0-or-later

---

## قابلیت‌ها

- استودیوی سه‌بعدی ۱۰ محصول با Three.js
- چرخش ۳۶۰ درجه، زوم، پریست‌های دوربین
- تغییر رنگ بدنه، دمای رنگ (CCT)، شدت نور و روشن/خاموش
- تمام‌صفحه با Fullscreen API و fallback برای iframe
- شورت‌کد قابل استفاده در برگه، نوشته یا ویجت Shortcode المنتور
- مدیریت محصولات با پست تایپ اختصاصی `pn_product`
- افزودن، ویرایش و حذف محصول از پیشخوان
- فیلدهای کامل: مدل سه‌بعدی، خانواده، نام کوتاه، حرف، توضیح، لینک، تصویر، رنگ‌ها، CCT، مشخصات، دوربین
- تنظیمات ظاهری: رنگ‌ها، فونت، اندازه فونت، گردی گوشه، ارتفاع استیج/پوسته و فاصله بخش
- نمایش یا مخفی کردن ریل، رنگ، CCT، دیمر، مشخصات، پاور، فول‌اسکرین و دکمه محصول
- تب‌بندی «محصولات» و «انتخاب ویژگی» در موبایل و تبلت
- ساختار کلاس‌محور و قابل توسعه

---

## نصب

1. فایل ZIP نسخه 1.4 را از GitHub Releases دانلود کنید.
2. در وردپرس به **افزونه‌ها ← افزودن ← بارگذاری افزونه** بروید.
3. ZIP را بارگذاری و پلاگین را فعال کنید.
4. از منوی **اختصاصی پیشگام نور** تنظیمات و محصولات را مدیریت کنید.
5. شورت‌کد را در برگه قرار دهید.

پس از فعال‌سازی، ۱۰ محصول پیش‌فرض به‌صورت خودکار ساخته می‌شوند تا شورت‌کد بلافاصله قابل استفاده باشد.

---

## شورت‌کد

```text
[pishgamnoor_studio]
```

نمونه با محصول مشخص:

```text
[pishgamnoor_studio product="gamma"]
```

### پارامترها

| پارامتر | توضیح |
|---|---|
| `product` | شناسه محصول پیش‌فرض (slug) |
| `accent`, `bg`, `panel`, `panel_2`, `text`, `muted` | رنگ‌های همان نمونه |
| `radius` | گردی گوشه‌ها (px) |
| `font_size`, `title_size`, `small_size` | اندازه فونت‌ها |
| `shell_height`, `stage_height`, `section_padding` | ابعاد و فاصله |
| `show_rail` | نمایش ریل محصولات |
| `show_finish` | نمایش انتخاب رنگ |
| `show_cct` | نمایش دمای رنگ |
| `show_dimmer` | نمایش شدت نور |
| `show_specs` | نمایش مشخصات |
| `show_power` | نمایش کلید پاور |
| `show_fullscreen` | نمایش تمام‌صفحه |
| `show_product_link` | نمایش دکمه محصول |
| `product_link_label` | متن دکمه محصول |
| `class` | کلاس اضافه برای ریشه |

> در نسخه ۱.۱ در هر برگه یک نمونه استودیو نمایش داده می‌شود تا شناسه‌های DOM و رندر WebGL پایدار بمانند.

---

## مدیریت محصولات

منوی **اختصاصی پیشگام نور** شامل:

- **تنظیمات شبیه‌ساز** — رنگ، فونت، اندازه، گردی گوشه، نمایش کنترل‌ها
- **شبیه‌ساز محصولات** — لیست، ویرایش و حذف محصولات موجود

> **افزودن محصول جدید موقتاً از پیشخوان وردپرس غیرفعال است.** محصولات موجود قابل ویرایش‌اند. برای فعال‌کردن دوباره در آینده:
>
> ```php
> add_filter( 'pishgamnoor_studio_enable_product_creation', '__return_true' );
> ```

### فیلدهای محصول

- عنوان (عنوان پست)
- مدل سه‌بعدی (`gamma`, `theta`, `omega`, `zeous`, `magnet`, `spot2r`, `omegalinear`, `inout`, `spotre`, `beta2`)
- خانواده، نام کوتاه، حرف یونانی
- توضیح کوتاه
- لینک صفحه محصول
- نوع صحنه: داخلی / محوطه
- ترتیب نمایش
- فعال/غیرفعال
- تصویر شاخص یا آدرس تصویر
- رنگ‌های بدنه (نام، کد رنگ، متالیک، زبری)
- دمای رنگ‌های موجود
- مشخصات فنی (عنوان/مقدار)
- دوربین و پریست‌ها (پیشرفته)

---

## ساختار پلاگین

```text
Pishgamnoor-plugin/
├─ pishgamnoor-plugin.php
├─ uninstall.php
├─ readme.txt
├─ README.md
├─ CHANGELOG.md
├─ includes/
│  ├─ class-plugin.php
│  ├─ class-activator.php
│  ├─ Admin/
│  │  ├─ class-admin.php
│  │  ├─ class-settings-page.php
│  │  ├─ class-product-meta.php
│  │  └─ class-product-columns.php
│  ├─ Frontend/
│  │  ├─ class-assets.php
│  │  └─ class-shortcode.php
│  ├─ PostTypes/
│  │  └─ class-product.php
│  ├─ Repositories/
│  │  └─ class-product-repository.php
│  └─ Support/
│     ├─ class-helper.php
│     └─ class-settings.php
├─ templates/
│  ├─ studio.php
│  └─ admin/
│     ├─ settings.php
│     ├─ product-meta.php
│     └─ product-side.php
├─ assets/
│  ├─ css/admin.css
│  ├─ css/public.css
│  ├─ js/admin.js
│  ├─ js/studio.js
│  ├─ fonts/
│  ├─ img/products/
│  └─ vendor/three/
├─ data/default-products.json
└─ .github/workflows/
```

---

## معماری و توسعه

### نقاط توسعه

- `pishgamnoor_studio_loaded` — بعد از راه‌اندازی پلاگین
- `pishgamnoor_studio_products` — فیلتر لیست محصولات فرانت
- `pishgamnoor_studio_product` — فیلتر داده یک محصول
- `pishgamnoor_studio_shortcode_atts` — فیلتر پارامترهای شورت‌کد
- `pishgamnoor_studio_settings` — فیلتر تنظیمات
- `pishgamnoor_studio_models` — فیلتر مدل‌های سه‌بعدی
- قالب‌ها با قرار دادن فایل در `wp-content/themes/{theme}/pishgamnoor-studio/` قابل بازنویسی هستند.

### افزودن مدل سه‌بعدی جدید

1. یک builder در `assets/js/studio.js` اضافه کنید.
2. کلید آن را به `MODEL_DEFS`/`PRODUCTS` در JS و `Product::models()` در PHP اضافه کنید.
3. محصول جدید را با همان کلید مدل بسازید.

### ذخیره‌سازی

- محصولات: پست تایپ `pn_product`
- متا: `_pn_model`, `_pn_family`, `_pn_short`, `_pn_letter`, `_pn_description`, `_pn_url`, `_pn_stage`, `_pn_image_url`, `_pn_finishes`, `_pn_ccts`, `_pn_specs`, `_pn_camera`, `_pn_presets`, `_pn_enabled`, `_pn_order`
- تنظیمات: آپشن `pishgamnoor_studio_settings`

---

## انتشار نسخه جدید

1. نسخه را در `pishgamnoor-plugin.php`، `readme.txt` و `CHANGELOG.md` به‌روزرسانی کنید.
2. تغییرات را commit و push کنید.
3. یک تگ بسازید:

```bash
git tag v1.2
git push origin v1.2
```

4. GitHub Actions فایل ZIP پلاگین را می‌سازد و Release جدید را منتشر می‌کند.

---

## محدودیت‌های نسخه ۱.۱

- در هر صفحه یک نمونه استودیو.
- مدل‌های سه‌بعدی Procedural هستند؛ برای دقت مهندسی کامل باید GLB/CAD جایگزین شود.
- داده‌های محصولات پیش‌فرض از صفحات رسمی محصولات گرفته شده‌اند و باید با دفتر فنی تأیید شوند.
- فرم/لید در این پلاگین نیست؛ این پلاگین فقط استودیو را نمایش می‌دهد.

---

## اعتبار

- توسعه: **Mostafa Moosavi**
- سه‌بعدی: Three.js (MIT)
- فونت: IRANSansX / Vazirmatn
- تصاویر و داده محصولات: پیشگام نور
