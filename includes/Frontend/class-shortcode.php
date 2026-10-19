<?php
namespace Pishgamnoor\Studio\Frontend;

use Pishgamnoor\Studio\Repositories\Product_Repository;
use Pishgamnoor\Studio\Support\Helper;
use Pishgamnoor\Studio\Support\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * [pishgamnoor_studio] shortcode.
 *
 * One instance per page keeps the DOM IDs and the WebGL renderer stable.
 */
final class Shortcode {

    /**
     * @var bool
     */
    private static $rendered = false;

    public function register() {
        add_shortcode( 'pishgamnoor_studio', array( $this, 'render' ) );
    }

    public function render( $atts = array(), $content = '' ) {
        if ( self::$rendered ) {
            if ( current_user_can( 'manage_options' ) ) {
                return '<div class="pn-studio-notice">در هر برگه فقط یک نمونه از استودیو پیشگام نور قابل نمایش است.</div>';
            }
            return '';
        }

        $settings = Settings::get();
        $products = Product_Repository::get_products();

        if ( empty( $products ) ) {
            if ( current_user_can( 'manage_options' ) ) {
                return '<div class="pn-studio-notice">هنوز محصولی برای استودیو ساخته نشده است. از پیشخوان، «اختصاصی پیشگام نور ← شبیه‌ساز محصولات» یک محصول اضافه کنید.</div>';
            }
            return '';
        }

        $atts = shortcode_atts(
            array(
                'product'           => $settings['default_product'],
                'accent'            => $settings['accent'],
                'bg'                => $settings['bg'],
                'panel'             => $settings['panel'],
                'panel_2'           => $settings['panel_2'],
                'text'              => $settings['text'],
                'muted'             => $settings['muted'],
                'radius'            => $settings['radius'],
                'font_size'         => $settings['font_size'],
                'title_size'        => $settings['title_size'],
                'small_size'        => $settings['small_size'],
                'shell_height'      => $settings['shell_height'],
                'stage_height'      => $settings['stage_height'],
                'section_padding'   => $settings['section_padding'],
                'show_rail'         => $settings['show_rail'],
                'show_finish'       => $settings['show_finish'],
                'show_cct'          => $settings['show_cct'],
                'show_dimmer'       => $settings['show_dimmer'],
                'show_specs'        => $settings['show_specs'],
                'show_power'        => $settings['show_power'],
                'show_fullscreen'   => $settings['show_fullscreen'],
                'show_product_link' => $settings['show_product_link'],
                'product_link_label'=> $settings['product_link_label'],
                'class'             => '',
            ),
            $atts,
            'pishgamnoor_studio'
        );

        $atts = apply_filters( 'pishgamnoor_studio_shortcode_atts', $atts, $settings, $products );

        $show = array(
            'rail'         => $this->flag( $atts['show_rail'] ),
            'finish'       => $this->flag( $atts['show_finish'] ),
            'cct'          => $this->flag( $atts['show_cct'] ),
            'dimmer'       => $this->flag( $atts['show_dimmer'] ),
            'specs'        => $this->flag( $atts['show_specs'] ),
            'power'        => $this->flag( $atts['show_power'] ),
            'fullscreen'   => $this->flag( $atts['show_fullscreen'] ),
            'product_link' => $this->flag( $atts['show_product_link'] ),
        );

        $default_id = sanitize_title( $atts['product'] );
        $default    = null;
        foreach ( $products as $product ) {
            if ( $product['id'] === $default_id ) {
                $default = $product;
                break;
            }
        }
        if ( ! $default ) {
            $default    = $products[0];
            $default_id = $default['id'];
        }

        $style       = Helper::css_vars( $settings, $atts );
        $custom_css  = (string) $settings['custom_css'];
        $root_id     = 'pn-studio-' . substr( md5( uniqid( '', true ) ), 0, 8 );
        $root_class  = 'pn-studio ' . sanitize_html_class( $atts['class'] );
        $link_label  = sanitize_text_field( $atts['product_link_label'] );

        $js_settings = array(
            'autoRotate'       => (bool) $settings['auto_rotate'],
            'showRail'         => $show['rail'],
            'showFinish'       => $show['finish'],
            'showCCT'          => $show['cct'],
            'showDimmer'       => $show['dimmer'],
            'showSpecs'        => $show['specs'],
            'showPower'        => $show['power'],
            'showFullscreen'   => $show['fullscreen'],
            'showProductLink'  => $show['product_link'],
        );

        $data = array(
            'products'       => array_values( $products ),
            'settings'       => $js_settings,
            'defaultProduct' => $default_id,
            'i18n'           => array(
                'on'        => 'روشن',
                'off'       => 'خاموش',
                'loading'   => 'آماده‌سازی استودیو…',
                'notReady'  => 'مرورگر شما از نمایش سه‌بعدی پشتیبانی نمی‌کند.',
            ),
        );

        Assets::enqueue( $data );

        self::$rendered = true;

        $template_data = array(
            'settings'    => $settings,
            'products'    => $products,
            'default'     => $default,
            'default_id'  => $default_id,
            'show'        => $show,
            'style'       => $style,
            'custom_css'  => $custom_css,
            'root_id'     => $root_id,
            'root_class'  => $root_class,
            'link_label'  => $link_label,
            'atts'        => $atts,
        );

        return Helper::template( 'studio.php', $template_data, false );
    }

    private function flag( $value ) {
        if ( is_bool( $value ) ) {
            return $value;
        }
        return ! in_array( (string) $value, array( '0', 'false', 'no', 'off', '' ), true );
    }
}
