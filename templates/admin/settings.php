<?php
/**
 * Admin settings screen.
 *
 * @var array  $settings
 * @var array  $defaults
 * @var array  $products
 * @var string $shortcode
 * @var string $version
 * @var string $developer
 * @var string $product_post_type
 * @var array  $font_options
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$option = 'pishgamnoor_studio_settings';
$prefix = $option . '[';

$field = static function ( $key, $label, $type = 'text', $args = array() ) use ( $settings, $prefix ) {
    $value = $settings[ $key ] ?? '';
    $id    = 'pn-' . $key;
    $desc  = $args['desc'] ?? '';
    $attrs = '';

    if ( isset( $args['min'] ) ) {
        $attrs .= ' min="' . esc_attr( $args['min'] ) . '"';
    }
    if ( isset( $args['max'] ) ) {
        $attrs .= ' max="' . esc_attr( $args['max'] ) . '"';
    }
    if ( isset( $args['step'] ) ) {
        $attrs .= ' step="' . esc_attr( $args['step'] ) . '"';
    }
    if ( ! empty( $args['placeholder'] ) ) {
        $attrs .= ' placeholder="' . esc_attr( $args['placeholder'] ) . '"';
    }

    echo '<div class="pn-field' . ( 'checkbox' === $type ? ' pn-field-check' : '' ) . '">';
    echo '<label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label>';

    if ( 'color' === $type ) {
        echo '<input type="text" class="pn-color-field" id="' . esc_attr( $id ) . '" name="' . esc_attr( $prefix . $key ) . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $defaults[ $key ] ?? '' ) . '">';
    } elseif ( 'number' === $type ) {
        echo '<input type="number" id="' . esc_attr( $id ) . '" name="' . esc_attr( $prefix . $key ) . ']" value="' . esc_attr( $value ) . '"' . $attrs . '>';
    } elseif ( 'textarea' === $type ) {
        echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( $prefix . $key ) . ']" rows="' . esc_attr( $args['rows'] ?? 4 ) . '"' . $attrs . '>' . esc_textarea( $value ) . '</textarea>';
    } elseif ( 'select' === $type ) {
        echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( $prefix . $key ) . ']">';
        foreach ( (array) ( $args['options'] ?? array() ) as $opt_value => $opt_label ) {
            echo '<option value="' . esc_attr( $opt_value ) . '"' . selected( $value, $opt_value, false ) . '>' . esc_html( $opt_label ) . '</option>';
        }
        echo '</select>';
    } elseif ( 'checkbox' === $type ) {
        echo '<input type="hidden" name="' . esc_attr( $prefix . $key ) . ']" value="0">';
        echo '<label class="pn-switch"><input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( $prefix . $key ) . ']" value="1"' . checked( (int) $value, 1, false ) . '><span></span></label>';
    }

    if ( $desc ) {
        echo '<p class="pn-desc">' . wp_kses_post( $desc ) . '</p>';
    }
    echo '</div>';
};
?>

<div class="wrap pn-admin">
    <div class="pn-admin-header">
        <div class="pn-admin-brand">
            <span class="pn-admin-mark" aria-hidden="true">✦</span>
            <div>
                <h1>اختصاصی پیشگام نور</h1>
                <p>استودیو نور سه‌بعدی، مدیریت محصولات و تنظیمات ظاهری</p>
            </div>
        </div>
        <div class="pn-admin-meta">
            <span>نسخه <?php echo esc_html( $version ); ?></span>
            <span>توسعه‌دهنده: <?php echo esc_html( $developer ); ?></span>
            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $product_post_type ) ); ?>" class="button">مدیریت محصولات</a>
        </div>
    </div>

    <?php settings_errors(); ?>

    <div class="pn-admin-layout">
        <div class="pn-admin-main">
            <nav class="pn-tabs" aria-label="بخش‌های تنظیمات">
                <button type="button" class="pn-tab is-active" data-pn-tab="appearance">ظاهر و تایپوگرافی</button>
                <button type="button" class="pn-tab" data-pn-tab="studio">استودیو و نمایش</button>
                <button type="button" class="pn-tab" data-pn-tab="shortcode">شورت‌کد</button>
            </nav>

            <form method="post" action="options.php" class="pn-settings-form" data-pn-settings-form>
                <?php settings_fields( 'pishgamnoor_studio' ); ?>

                <section class="pn-panel is-active" data-pn-panel="appearance">
                    <div class="pn-panel-head">
                        <h2>رنگ‌ها و تایپوگرافی</h2>
                        <p>این مقادیر به‌صورت CSS Variables روی ریشه استودیو اعمال می‌شوند و فقط همان بخش را تغییر می‌دهند.</p>
                    </div>

                    <div class="pn-grid pn-grid-3">
                        <?php
                        $field( 'accent', 'رنگ زرد / اکسنت', 'color', array( 'desc' => 'رنگ دکمه‌ها، کلید پاور و حالت فعال.' ) );
                        $field( 'bg', 'پس‌زمینه استودیو', 'color' );
                        $field( 'panel', 'پس‌زمینه پنل', 'color' );
                        $field( 'panel_2', 'پس‌زمینه ریل', 'color' );
                        $field( 'text', 'رنگ متن اصلی', 'color' );
                        $field( 'muted', 'رنگ متن ثانویه', 'color' );
                        ?>
                    </div>

                    <div class="pn-grid pn-grid-2">
                        <?php
                        $field( 'font_family', 'فونت', 'select', array( 'options' => $font_options, 'desc' => 'فونت IRANSansX همراه پلاگین عرضه می‌شود.' ) );
                        $field( 'font_size', 'اندازه فونت پایه پنل (px)', 'number', array( 'min' => 12, 'max' => 22 ) );
                        $field( 'title_size', 'اندازه عنوان محصول (px)', 'number', array( 'min' => 18, 'max' => 40 ) );
                        $field( 'small_size', 'اندازه فونت ریز (px)', 'number', array( 'min' => 10, 'max' => 18 ) );
                        $field( 'radius', 'گردی گوشه‌ها (px)', 'number', array( 'min' => 0, 'max' => 24, 'desc' => 'مقدار پیشنهادی: ۱۰ تا ۱۲ پیکسل.' ) );
                        $field( 'shell_height', 'ارتفاع پوسته استودیو (px)', 'number', array( 'min' => 600, 'max' => 1000 ) );
                        $field( 'stage_height', 'حداقل ارتفاع استیج (px)', 'number', array( 'min' => 400, 'max' => 1000 ) );
                        $field( 'section_padding', 'فاصله بالا/پایین بخش (px)', 'number', array( 'min' => 0, 'max' => 120 ) );
                        ?>
                    </div>
                </section>

                <section class="pn-panel" data-pn-panel="studio">
                    <div class="pn-panel-head">
                        <h2>استودیو و کنترل‌ها</h2>
                        <p>مشخص کنید چه بخش‌هایی در فرانت‌اند دیده شوند و تجربه پیش‌فرض چه باشد.</p>
                    </div>

                    <div class="pn-grid pn-grid-2">
                        <?php
                        $field(
                            'default_product',
                            'محصول پیش‌فرض',
                            'select',
                            array(
                                'options' => $products,
                                'desc'    => empty( $products ) ? 'هنوز محصولی ساخته نشده است.' : 'با باز شدن استودیو، این محصول انتخاب می‌شود.',
                            )
                        );
                        $field( 'product_link_label', 'متن دکمه مشاهده محصول', 'text' );
                        $field( 'disclaimer', 'متن هشدار زیر استودیو', 'textarea', array( 'rows' => 3 ) );
                        ?>
                    </div>

                    <div class="pn-grid pn-grid-2">
                        <?php
                        $field( 'auto_rotate', 'چرخش خودکار اولیه', 'checkbox', array( 'desc' => 'کاربر می‌تواند با کشیدن، چرخش خودکار را متوقف کند.' ) );
                        $field( 'show_rail', 'نمایش ریل محصولات', 'checkbox' );
                        $field( 'show_finish', 'نمایش انتخاب رنگ بدنه', 'checkbox' );
                        $field( 'show_cct', 'نمایش دمای رنگ', 'checkbox' );
                        $field( 'show_dimmer', 'نمایش شدت نور', 'checkbox' );
                        $field( 'show_specs', 'نمایش مشخصات فنی', 'checkbox' );
                        $field( 'show_power', 'نمایش کلید روشن/خاموش', 'checkbox' );
                        $field( 'show_fullscreen', 'نمایش دکمه تمام‌صفحه', 'checkbox' );
                        $field( 'show_product_link', 'نمایش دکمه مشاهده محصول', 'checkbox' );
                        ?>
                    </div>

                    <div class="pn-grid pn-grid-1">
                        <?php
                        $field( 'custom_css', 'CSS سفارشی', 'textarea', array( 'rows' => 7, 'desc' => 'برای جلوگیری از تداخل، سلکتورها را با <code>.pn-studio</code> شروع کنید.' ) );
                        ?>
                    </div>

                    <details class="pn-details">
                        <summary>تنظیمات پیشرفته</summary>
                        <div class="pn-grid pn-grid-1">
                            <?php $field( 'delete_data_on_uninstall', 'حذف محصولات و تنظیمات هنگام حذف پلاگین', 'checkbox', array( 'desc' => 'به‌صورت پیش‌فرض داده‌ها حفظ می‌شوند.' ) ); ?>
                        </div>
                    </details>
                </section>

                <section class="pn-panel" data-pn-panel="shortcode">
                    <div class="pn-panel-head">
                        <h2>شورت‌کد</h2>
                        <p>استودیو را در هر برگه، نوشته یا ویجت المنتور با شورت‌کد زیر نمایش دهید.</p>
                    </div>

                    <div class="pn-shortcode-box">
                        <code>[pishgamnoor_studio]</code>
                        <button type="button" class="button pn-copy" data-pn-copy="[pishgamnoor_studio]">کپی شورت‌کد</button>
                    </div>

                    <p class="pn-note">در هر برگه فقط یک نمونه از استودیو نمایش داده می‌شود تا شناسه‌های DOM و رندر WebGL پایدار بمانند.</p>

                    <h3>نمونه با محصول مشخص</h3>
                    <div class="pn-shortcode-box">
                        <code>[pishgamnoor_studio product="gamma"]</code>
                        <button type="button" class="button pn-copy" data-pn-copy='[pishgamnoor_studio product="gamma"]'>کپی</button>
                    </div>

                    <h3>پارامترهای قابل استفاده</h3>
                    <div class="pn-table-wrap">
                        <table class="widefat striped pn-table">
                            <thead>
                                <tr><th>پارامتر</th><th>توضیح</th><th>نمونه</th></tr>
                            </thead>
                            <tbody>
                                <tr><td><code>product</code></td><td>شناسه محصول پیش‌فرض</td><td><code>gamma</code></td></tr>
                                <tr><td><code>accent</code>, <code>bg</code>, <code>panel</code>, <code>text</code></td><td>رنگ‌های همان نمونه</td><td><code>#f7ce26</code></td></tr>
                                <tr><td><code>radius</code></td><td>گردی گوشه‌ها (px)</td><td><code>12</code></td></tr>
                                <tr><td><code>font_size</code>, <code>title_size</code>, <code>small_size</code></td><td>اندازه فونت‌ها (px)</td><td><code>16</code></td></tr>
                                <tr><td><code>show_rail</code>, <code>show_specs</code>, <code>show_power</code></td><td>نمایش/مخفی کردن بخش‌ها</td><td><code>0</code> یا <code>1</code></td></tr>
                                <tr><td><code>class</code></td><td>کلاس اضافه برای ریشه</td><td><code>my-studio</code></td></tr>
                            </tbody>
                        </table>
                    </div>

                    <p class="pn-note">برای اتصال به المنتور از ویجت Shortcode استفاده کنید. فایل‌های سه‌بعدی به‌صورت محلی از خود پلاگین لود می‌شوند.</p>
                </section>

                <div class="pn-form-footer">
                    <?php submit_button( 'ذخیره تنظیمات', 'primary', 'submit', false ); ?>
                    <span class="pn-form-hint">تغییرات پس از ذخیره، در همان لحظه روی شورت‌کد اعمال می‌شوند.</span>
                </div>
            </form>
        </div>

        <aside class="pn-admin-side">
            <div class="pn-side-card pn-side-card-accent">
                <span class="pn-side-kicker">شورت‌کد آماده</span>
                <code>[pishgamnoor_studio]</code>
                <button type="button" class="button button-primary pn-copy" data-pn-copy="[pishgamnoor_studio]">کپی</button>
                <p>یک بار در هر برگه قرار دهید.</p>
            </div>

            <div class="pn-side-card">
                <h3>مدیریت محصولات</h3>
                <p>افزودن، ویرایش، حذف، رنگ‌ها، CCT و مشخصات فنی هر محصول.</p>
                <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . $product_post_type ) ); ?>">ورود به محصولات</a>
                <a class="pn-side-link" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . $product_post_type ) ); ?>">افزودن محصول جدید ←</a>
            </div>

            <div class="pn-side-card">
                <h3>درباره پلاگین</h3>
                <p>نسخه <strong><?php echo esc_html( $version ); ?></strong></p>
                <p>توسعه‌دهنده: <strong><?php echo esc_html( $developer ); ?></strong></p>
                <a class="pn-side-link" href="https://github.com/mosimum/pishgamnoor-plugin" target="_blank" rel="noopener">مخزن GitHub ←</a>
            </div>

            <div class="pn-side-card pn-side-card-muted">
                <h3>راهنمای سریع</h3>
                <ol>
                    <li>محصولات را از «شبیه‌ساز محصولات» بسازید.</li>
                    <li>رنگ بدنه و CCT را در همان صفحه تنظیم کنید.</li>
                    <li>شورت‌کد را در برگه قرار دهید.</li>
                    <li>ظاهر را از همین صفحه شخصی‌سازی کنید.</li>
                </ol>
            </div>
        </aside>
    </div>

    <p class="pn-admin-footer">
        ساخته‌شده برای صنایع روشنایی پیشگام نور — توسعه: <?php echo esc_html( $developer ); ?>
    </p>
</div>
