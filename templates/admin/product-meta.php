<?php
/**
 * Product meta box.
 *
 * @var WP_Post $post
 * @var string  $model
 * @var string  $family
 * @var string  $short
 * @var string  $letter
 * @var string  $description
 * @var string  $url
 * @var string  $stage
 * @var string  $image_url
 * @var bool    $enabled
 * @var int     $order
 * @var array   $finishes
 * @var array   $ccts
 * @var array   $specs
 * @var array   $camera
 * @var array   $presets
 */

use Pishgamnoor\Studio\PostTypes\Product;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$models = Product::models();
$stages = Product::stages();

if ( empty( $finishes ) ) {
    $finishes = array(
        array( 'id' => 'black', 'label' => 'مشکی مات', 'color' => '#1b1c22', 'metalness' => 0.34, 'roughness' => 0.56 ),
    );
}

if ( empty( $specs ) ) {
    $specs = array( array( '', '' ) );
}

$ccts = array_map( 'intval', (array) $ccts );
$camera_val = static function ( $group, $key = null, $default = '' ) use ( $camera ) {
    if ( null === $key ) {
        return isset( $camera[ $group ] ) && ! is_array( $camera[ $group ] ) ? $camera[ $group ] : $default;
    }
    if ( isset( $camera[ $group ][ $key ] ) ) {
        return $camera[ $group ][ $key ];
    }
    if ( isset( $camera[ $group ] ) && ! is_array( $camera[ $group ] ) ) {
        return $camera[ $group ];
    }
    return $default;
};
$presets_json = ! empty( $presets ) ? wp_json_encode( $presets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) : '';
?>

<div class="pn-product-admin">
    <div class="pn-product-intro">
        <div>
            <h2>اطلاعات محصول در شبیه‌ساز</h2>
            <p>همه فیلدهای این بخش مستقیماً در استودیوی فرانت‌اند استفاده می‌شوند. مدل سه‌بعدی از کلید «مدل» خوانده می‌شود.</p>
        </div>
        <span class="pn-required-note">فیلدهای ستاره‌دار برای نمایش ضروری‌اند.</span>
    </div>

    <div class="pn-meta-grid">
        <label class="pn-meta-field">
            <span>مدل سه‌بعدی <b>*</b></span>
            <select name="pn_model">
                <?php foreach ( $models as $key => $label ) : ?>
                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $model, $key ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
            <small>برای محصولات جدید، مدل باید یکی از مدل‌های موجود باشد.</small>
        </label>

        <label class="pn-meta-field">
            <span>نوع صحنه</span>
            <select name="pn_stage">
                <?php foreach ( $stages as $key => $label ) : ?>
                    <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $stage, $key ); ?>><?php echo esc_html( $label ); ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label class="pn-meta-field">
            <span>خانواده محصول</span>
            <input type="text" name="pn_family" value="<?php echo esc_attr( $family ); ?>" placeholder="مثلاً خانواده گاما">
        </label>

        <label class="pn-meta-field">
            <span>نام کوتاه / ریل</span>
            <input type="text" name="pn_short" value="<?php echo esc_attr( $short ); ?>" placeholder="گاما / خطی کنافی">
        </label>

        <label class="pn-meta-field">
            <span>حرف یونانی</span>
            <input type="text" name="pn_letter" value="<?php echo esc_attr( $letter ); ?>" maxlength="4" placeholder="Γ">
        </label>

        <label class="pn-meta-field">
            <span>ترتیب نمایش</span>
            <input type="number" name="pn_order" value="<?php echo esc_attr( $order ); ?>" min="0" step="1">
        </label>

        <label class="pn-meta-field pn-meta-wide">
            <span>لینک صفحه محصول</span>
            <input type="url" name="pn_url" value="<?php echo esc_url( $url ); ?>" placeholder="https://pishgamnoor.com/products/...">
        </label>

        <label class="pn-meta-field pn-meta-wide">
            <span>توضیح کوتاه</span>
            <textarea name="pn_description" rows="3" placeholder="یک یا دو جمله درباره رفتار نور و کاربرد محصول"><?php echo esc_textarea( $description ); ?></textarea>
        </label>

        <label class="pn-meta-field pn-meta-check">
            <input type="checkbox" name="pn_enabled" value="1" <?php checked( $enabled ); ?>>
            <span>این محصول در شبیه‌ساز نمایش داده شود.</span>
        </label>
    </div>

    <hr class="pn-divider">

    <div class="pn-meta-grid">
        <div class="pn-meta-field pn-meta-wide">
            <span>تصویر محصول</span>
            <div class="pn-image-picker">
                <div class="pn-image-preview">
                    <?php if ( $image_url ) : ?>
                        <img src="<?php echo esc_url( $image_url ); ?>" alt="">
                    <?php else : ?>
                        <span>تصویری انتخاب نشده</span>
                    <?php endif; ?>
                </div>
                <div class="pn-image-controls">
                    <input type="url" name="pn_image_url" id="pn_image_url" value="<?php echo esc_url( $image_url ); ?>" placeholder="آدرس تصویر یا انتخاب از کتابخانه">
                    <button type="button" class="button pn-media-button" data-pn-media-target="pn_image_url">انتخاب از کتابخانه</button>
                    <small>اگر تصویر شاخص محصول را تنظیم کنید، همان اولویت دارد.</small>
                </div>
            </div>
        </div>
    </div>

    <hr class="pn-divider">

    <section class="pn-meta-section">
        <div class="pn-meta-section-head">
            <h3>رنگ‌های بدنه</h3>
            <p>همین رنگ‌ها در پنل فرانت به‌صورت سواچ نمایش داده می‌شوند.</p>
        </div>

        <div class="pn-repeater" data-pn-repeater="finish">
            <div class="pn-rows">
                <?php foreach ( $finishes as $finish ) : ?>
                    <div class="pn-row pn-finish-row">
                        <input type="hidden" name="pn_finishes[id][]" value="<?php echo esc_attr( $finish['id'] ?? '' ); ?>">
                        <label>نام رنگ<input type="text" name="pn_finishes[label][]" value="<?php echo esc_attr( $finish['label'] ?? '' ); ?>" placeholder="مشکی مات"></label>
                        <label>کد رنگ<input type="text" class="pn-color-field" name="pn_finishes[color][]" value="<?php echo esc_attr( $finish['color'] ?? '#1b1c22' ); ?>" data-default-color="#1b1c22"></label>
                        <label>متابلیک<input type="number" name="pn_finishes[metalness][]" value="<?php echo esc_attr( $finish['metalness'] ?? 0.34 ); ?>" min="0" max="1" step="0.05"></label>
                        <label>زبری<input type="number" name="pn_finishes[roughness][]" value="<?php echo esc_attr( $finish['roughness'] ?? 0.56 ); ?>" min="0" max="1" step="0.05"></label>
                        <button type="button" class="button pn-remove-row" aria-label="حذف رنگ">×</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button pn-add-row" data-pn-add="finish">+ افزودن رنگ</button>
        </div>
    </section>

    <section class="pn-meta-section">
        <div class="pn-meta-section-head">
            <h3>دمای رنگ موجود</h3>
            <p>گزینه‌های قابل انتخاب در کنترل CCT.</p>
        </div>
        <div class="pn-cct-options">
            <?php foreach ( array( 3000, 4000, 5000, 6000 ) as $cct ) : ?>
                <label><input type="checkbox" name="pn_ccts[]" value="<?php echo esc_attr( $cct ); ?>" <?php checked( in_array( $cct, $ccts, true ) ); ?>> <?php echo esc_html( number_format_i18n( $cct ) ); ?>K</label>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="pn-meta-section">
        <div class="pn-meta-section-head">
            <h3>مشخصات فنی</h3>
            <p>حداکثر ۸ ردیف برای حفظ نظم پنل پیشنهاد می‌شود؛ امکان افزودن ردیف بیشتر وجود دارد.</p>
        </div>

        <div class="pn-repeater" data-pn-repeater="spec">
            <div class="pn-rows">
                <?php foreach ( $specs as $spec ) : ?>
                    <div class="pn-row pn-spec-row">
                        <label>عنوان<input type="text" name="pn_specs[label][]" value="<?php echo esc_attr( $spec[0] ?? '' ); ?>" placeholder="توان"></label>
                        <label>مقدار<input type="text" name="pn_specs[value][]" value="<?php echo esc_attr( $spec[1] ?? '' ); ?>" placeholder="۲۵ وات / متر"></label>
                        <button type="button" class="button pn-remove-row" aria-label="حذف مشخصه">×</button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button pn-add-row" data-pn-add="spec">+ افزودن مشخصه</button>
        </div>
    </section>

    <details class="pn-details">
        <summary>تنظیمات پیشرفته دوربین و پریست‌ها</summary>
        <p class="pn-note">اگر خالی بماند، دوربین پیش‌فرض همان مدل سه‌بعدی استفاده می‌شود. فقط برای تنظیم دقیق نمای محصول لازم است.</p>

        <div class="pn-meta-grid pn-grid-compact">
            <label class="pn-meta-field"><span>دوربین X</span><input type="number" step="0.1" name="pn_camera[pos_x]" value="<?php echo esc_attr( $camera_val( 'pos', 0 ) ); ?>"></label>
            <label class="pn-meta-field"><span>دوربین Y</span><input type="number" step="0.1" name="pn_camera[pos_y]" value="<?php echo esc_attr( $camera_val( 'pos', 1 ) ); ?>"></label>
            <label class="pn-meta-field"><span>دوربین Z</span><input type="number" step="0.1" name="pn_camera[pos_z]" value="<?php echo esc_attr( $camera_val( 'pos', 2 ) ); ?>"></label>
            <label class="pn-meta-field"><span>هدف X</span><input type="number" step="0.1" name="pn_camera[target_x]" value="<?php echo esc_attr( $camera_val( 'target', 0 ) ); ?>"></label>
            <label class="pn-meta-field"><span>هدف Y</span><input type="number" step="0.1" name="pn_camera[target_y]" value="<?php echo esc_attr( $camera_val( 'target', 1 ) ); ?>"></label>
            <label class="pn-meta-field"><span>هدف Z</span><input type="number" step="0.1" name="pn_camera[target_z]" value="<?php echo esc_attr( $camera_val( 'target', 2 ) ); ?>"></label>
            <label class="pn-meta-field"><span>حداقل فاصله</span><input type="number" step="0.1" name="pn_camera[min]" value="<?php echo esc_attr( $camera_val( 'min' ) ); ?>"></label>
            <label class="pn-meta-field"><span>حداکثر فاصله</span><input type="number" step="0.1" name="pn_camera[max]" value="<?php echo esc_attr( $camera_val( 'max' ) ); ?>"></label>
            <label class="pn-meta-field"><span>حداکثر زاویه قطبی</span><input type="number" step="0.01" name="pn_camera[polar_max]" value="<?php echo esc_attr( $camera_val( 'polarMax' ) ); ?>"></label>
            <label class="pn-meta-field"><span>آزیموت حداقل</span><input type="number" step="0.01" name="pn_camera[azimuth_min]" value="<?php echo esc_attr( $camera_val( 'azimuth', 'min' ) ); ?>"></label>
            <label class="pn-meta-field"><span>آزیموت حداکثر</span><input type="number" step="0.01" name="pn_camera[azimuth_max]" value="<?php echo esc_attr( $camera_val( 'azimuth', 'max' ) ); ?>"></label>
        </div>

        <label class="pn-meta-field pn-meta-wide">
            <span>پریست‌های دوربین (JSON)</span>
            <textarea name="pn_presets_json" rows="7" class="code" placeholder='{"three":[3,2,5],"front":[0,2,5],"side":[5,2,0],"detail":[2,2,3]}'><?php echo esc_textarea( $presets_json ); ?></textarea>
        </label>
    </details>
</div>

<template id="pn-finish-template">
    <div class="pn-row pn-finish-row">
        <input type="hidden" name="pn_finishes[id][]" value="">
        <label>نام رنگ<input type="text" name="pn_finishes[label][]" value="" placeholder="مشکی مات"></label>
        <label>کد رنگ<input type="text" class="pn-color-field" name="pn_finishes[color][]" value="#1b1c22" data-default-color="#1b1c22"></label>
        <label>متابلیک<input type="number" name="pn_finishes[metalness][]" value="0.34" min="0" max="1" step="0.05"></label>
        <label>زبری<input type="number" name="pn_finishes[roughness][]" value="0.56" min="0" max="1" step="0.05"></label>
        <button type="button" class="button pn-remove-row" aria-label="حذف رنگ">×</button>
    </div>
</template>

<template id="pn-spec-template">
    <div class="pn-row pn-spec-row">
        <label>عنوان<input type="text" name="pn_specs[label][]" value="" placeholder="توان"></label>
        <label>مقدار<input type="text" name="pn_specs[value][]" value="" placeholder="۲۵ وات / متر"></label>
        <button type="button" class="button pn-remove-row" aria-label="حذف مشخصه">×</button>
    </div>
</template>
