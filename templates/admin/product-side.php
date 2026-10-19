<?php
/**
 * Product side meta box.
 *
 * @var WP_Post $post
 * @var string  $version
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$slug      = $post->post_name ? $post->post_name : 'your-product';
$shortcode = '[pishgamnoor_studio product="' . $slug . '"]';
$url       = (string) get_post_meta( $post->ID, '_pn_url', true );
?>

<div class="pn-side-box">
    <h3>شورت‌کد همین محصول</h3>
    <code class="pn-code-block"><?php echo esc_html( $shortcode ); ?></code>
    <button type="button" class="button button-primary pn-copy" data-pn-copy="<?php echo esc_attr( $shortcode ); ?>">کپی شورت‌کد</button>
    <p class="pn-side-note">برای نمایش استودیو با این محصول به‌عنوان انتخاب اول.</p>
</div>

<div class="pn-side-box">
    <h3>تصویر شاخص</h3>
    <?php if ( has_post_thumbnail( $post ) ) : ?>
        <div class="pn-side-thumb"><?php echo get_the_post_thumbnail( $post, array( 180, 120 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
        <p class="pn-side-note">تصویر شاخص در اولویت نمایش قرار دارد.</p>
    <?php else : ?>
        <p class="pn-side-note">تصویر شاخص تنظیم نشده؛ از آدرس تصویر در فیلد «تصویر محصول» استفاده می‌شود.</p>
    <?php endif; ?>
</div>

<div class="pn-side-box">
    <h3>تنظیمات کلی</h3>
    <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=pishgamnoor' ) ); ?>">تنظیمات استودیو</a>
    <?php if ( $url ) : ?>
        <a class="pn-side-link" href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener">مشاهده صفحه محصول ←</a>
    <?php endif; ?>
    <p class="pn-side-note">در هر برگه فقط یک نمونه از استودیو نمایش داده می‌شود.</p>
</div>

<p class="pn-side-version">Pishgamnoor Studio <?php echo esc_html( $version ); ?> — Mostafa Moosavi</p>
