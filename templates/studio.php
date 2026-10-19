<?php
/**
 * Frontend studio template.
 *
 * @var array  $settings
 * @var array  $products
 * @var array  $default
 * @var string $default_id
 * @var array  $show
 * @var string $style
 * @var string $custom_css
 * @var string $root_id
 * @var string $root_class
 * @var string $link_label
 * @var array  $atts
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$default_ccts = ! empty( $default['ccts'] ) ? $default['ccts'] : array( 3000, 4000 );
$default_cct  = (int) $default_ccts[0];
$cct_colors   = array(
    3000 => '#ffb46b',
    4000 => '#ffd9b0',
    5000 => '#fff1dd',
    6000 => '#eaf2ff',
);
$finishes     = ! empty( $default['finishes'] ) ? $default['finishes'] : array();
$specs        = ! empty( $default['specs'] ) ? $default['specs'] : array();
$default_url  = ! empty( $default['url'] ) ? $default['url'] : '';
$disclaimer   = ! empty( $settings['disclaimer'] ) ? $settings['disclaimer'] : 'رنگ‌ها و دمای نور تقریبی‌اند. برای تأیید نهایی، نمونه فیزیکی و دیتاشیت محصول را دریافت کنید.';
?>

<div id="<?php echo esc_attr( $root_id ); ?>" class="<?php echo esc_attr( $root_class ); ?>" style="<?php echo esc_attr( $style ); ?>">

    <svg class="pn-svg-sprite" aria-hidden="true" xmlns="http://www.w3.org/2000/svg">
        <symbol id="pn-i-arrow" viewBox="0 0 24 24"><path d="M19 12H5M11 18l-6-6 6-6" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></symbol>
        <symbol id="pn-i-power" viewBox="0 0 24 24"><path d="M12 3v8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M7.1 6.6a7.5 7.5 0 1 0 9.8 0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></symbol>
        <symbol id="pn-i-rotate" viewBox="0 0 24 24"><path d="M20 11a8 8 0 1 0-2.3 5.7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/><path d="M20 4v7h-7" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></symbol>
        <symbol id="pn-i-expand" viewBox="0 0 24 24"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></symbol>
        <symbol id="pn-i-close" viewBox="0 0 24 24"><path d="m6 6 12 12M18 6 6 18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></symbol>
    </svg>

    <?php if ( $custom_css ) : ?>
        <style><?php echo $custom_css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- admin-controlled CSS, tags stripped on save. ?></style>
    <?php endif; ?>

    <section class="studio-section" aria-label="استودیو نور">
        <div class="pn-container">
            <div class="studio-shell">
                <div class="studio-stage" id="studioStage">
                    <canvas id="studioCanvas" tabindex="0" aria-label="نمای سه‌بعدی تعاملی محصول. با کلیدهای جهت‌دار بچرخانید و با Enter روشن یا خاموش کنید."></canvas>

                    <div class="studio-loader" id="studioLoader">
                        <span class="loader-ring"></span>
                        <span>آماده‌سازی استودیو…</span>
                    </div>

                    <img class="studio-fallback" id="studioFallbackImg" src="<?php echo esc_url( $default['image'] ); ?>" alt="<?php echo esc_attr( $default['name'] ); ?>">

                    <div class="studio-topbar">
                        <span class="live-pill"><i></i> LIVE 3D <b>360°</b></span>
                        <div class="studio-tools">
                            <button class="icon-btn" id="studioRotate" aria-label="چرخش خودکار" aria-pressed="true" title="چرخش خودکار"><svg><use href="#pn-i-rotate"/></svg></button>
                            <?php if ( $show['fullscreen'] ) : ?>
                                <button class="icon-btn" id="studioFull" aria-label="تمام‌صفحه" aria-pressed="false" title="تمام‌صفحه"><svg><use href="#pn-i-expand"/></svg></button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ( $show['cct'] ) : ?>
                        <div class="studio-cct-readout" id="studioCctReadout">
                            <span class="cct-dot"></span>
                            <b><?php echo esc_html( number_format_i18n( $default_cct ) ); ?>K</b>
                            <span>دمای نور</span>
                        </div>
                    <?php endif; ?>

                    <?php if ( $show['power'] ) : ?>
                        <div class="studio-power-wrap">
                            <button class="power-btn is-on" id="studioPower" aria-pressed="true" aria-label="روشن و خاموش کردن محصول">
                                <span class="power-ring"></span>
                                <svg><use href="#pn-i-power"/></svg>
                            </button>
                            <span class="power-label" id="powerLabel">روشن</span>
                        </div>
                    <?php endif; ?>

                    <div class="studio-presets" role="group" aria-label="نمای دوربین">
                        <button data-view="three" class="is-active">سه‌چهارم</button>
                        <button data-view="front">روبرو</button>
                        <button data-view="side">پهلو</button>
                        <button data-view="detail">جزئیات</button>
                    </div>

                    <div class="studio-hint">درگ برای چرخش · اسکرول برای زوم · Enter برای روشن/خاموش</div>
                </div>

                <aside class="studio-panel" aria-label="تنظیمات و مشخصات محصول">
                    <div class="studio-panel-head">
                        <span class="studio-family" id="studioFamily"><?php echo esc_html( $default['family'] ); ?></span>
                        <h3 id="studioTitle"><?php echo esc_html( $default['name'] ); ?></h3>
                        <p id="studioDesc"><?php echo esc_html( $default['desc'] ); ?></p>
                    </div>

                    <?php if ( $show['finish'] ) : ?>
                        <div class="config-block">
                            <div class="config-label">
                                <span>رنگ بدنه</span>
                                <b id="finishLabel"><?php echo esc_html( $finishes[0]['label'] ?? '—' ); ?></b>
                            </div>
                            <div class="swatches" id="finishSwatches" role="group" aria-label="رنگ بدنه">
                                <?php foreach ( $finishes as $finish_index => $finish ) : ?>
                                    <button type="button" data-finish="<?php echo esc_attr( $finish['id'] ); ?>" class="<?php echo 0 === $finish_index ? 'is-active' : ''; ?>" style="--sw:<?php echo esc_attr( $finish['color'] ); ?>" aria-label="<?php echo esc_attr( $finish['label'] ); ?>" aria-pressed="<?php echo 0 === $finish_index ? 'true' : 'false'; ?>"></button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( $show['cct'] ) : ?>
                        <div class="config-block">
                            <div class="config-label">
                                <span>دمای رنگ</span>
                                <b id="cctLabel"><?php echo esc_html( number_format_i18n( $default_cct ) ); ?>K</b>
                            </div>
                            <div class="cct-chips" id="cctChips" role="group" aria-label="دمای رنگ">
                                <?php foreach ( $default_ccts as $cct ) : ?>
                                    <button type="button" data-cct="<?php echo esc_attr( $cct ); ?>" class="<?php echo (int) $cct === $default_cct ? 'is-active' : ''; ?>" aria-pressed="<?php echo (int) $cct === $default_cct ? 'true' : 'false'; ?>"><i style="--c:<?php echo esc_attr( $cct_colors[ $cct ] ?? '#ffb46b' ); ?>"></i><?php echo esc_html( number_format_i18n( $cct ) ); ?>K</button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ( $show['dimmer'] ) : ?>
                        <div class="config-block">
                            <div class="config-label">
                                <span>شدت نور</span>
                                <b id="dimLabel">۱۰۰٪</b>
                            </div>
                            <input class="dim-range" id="dimRange" type="range" min="10" max="100" value="100" step="1" aria-label="شدت نور">
                        </div>
                    <?php endif; ?>

                    <?php if ( $show['specs'] ) : ?>
                        <div class="spec-grid" id="specGrid">
                            <?php foreach ( $specs as $spec ) : ?>
                                <div class="spec-item"><span><?php echo esc_html( $spec[0] ); ?></span><b><?php echo esc_html( $spec[1] ); ?></b></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="studio-actions">
                        <?php if ( $show['product_link'] && $default_url ) : ?>
                            <a class="btn btn-primary btn-full" id="studioProductLink" href="<?php echo esc_url( $default_url ); ?>" target="_blank" rel="noopener">
                                <?php echo esc_html( $link_label ); ?> <svg><use href="#pn-i-arrow"/></svg>
                            </a>
                        <?php endif; ?>
                    </div>
                </aside>

                <?php if ( $show['rail'] ) : ?>
                    <div class="studio-rail" id="studioRail" role="tablist" aria-label="انتخاب محصول">
                        <?php foreach ( $products as $product ) : ?>
                            <button class="rail-item<?php echo $product['id'] === $default_id ? ' is-active' : ''; ?>" data-product="<?php echo esc_attr( $product['id'] ); ?>" role="tab" aria-selected="<?php echo $product['id'] === $default_id ? 'true' : 'false'; ?>" aria-label="<?php echo esc_attr( $product['name'] ); ?>">
                                <span class="rail-thumb"><img src="<?php echo esc_url( $product['image'] ); ?>" alt="" loading="lazy"></span>
                                <span class="rail-text"><strong><?php echo esc_html( $product['short'] ); ?></strong><span><?php echo esc_html( $product['family'] ); ?></span></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <p class="studio-note studio-note-bottom"><?php echo esc_html( $disclaimer ); ?></p>
        </div>
    </section>
</div>
