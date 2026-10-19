<?php
namespace Pishgamnoor\Studio\Support;

use Pishgamnoor\Studio\Repositories\Product_Repository;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Single source of truth for plugin settings.
 */
final class Settings {

    const OPTION = 'pishgamnoor_studio_settings';
    const VERSION_OPTION = 'pishgamnoor_studio_version';

    /**
     * Default values. Everything in the admin UI is derived from this list.
     */
    public static function defaults() {
        return array(
            'accent'                   => '#f7ce26',
            'bg'                       => '#06071a',
            'panel'                    => '#0b0d1b',
            'panel_2'                  => '#080a14',
            'text'                     => '#fffdf7',
            'muted'                    => '#a9a69e',
            'font_family'              => 'IRANSansX',
            'font_size'                => 16,
            'title_size'               => 24,
            'small_size'               => 13,
            'heading_size'             => 48,
            'radius'                   => 12,
            'shell_height'             => 820,
            'stage_height'             => 820,
            'section_padding'          => 48,
            'heading'                  => 'محصول را در دست بگیرید، قبل از اینکه به پروژه برود.',
            'subheading'               => 'چرخش ۳۶۰ درجه، انتخاب رنگ بدنه و دمای نور، شدت روشنایی و کلید روشن/خاموش. یک نمایش تعاملی از محصول، در صحنه‌ای که رفتار نور را نشان می‌دهد.',
            'default_product'          => 'gamma',
            'auto_rotate'              => 1,
            'show_rail'                => 1,
            'show_finish'              => 1,
            'show_cct'                 => 1,
            'show_dimmer'              => 1,
            'show_specs'               => 1,
            'show_power'               => 1,
            'show_fullscreen'          => 1,
            'show_product_link'        => 1,
            'product_link_label'       => 'مشاهده محصول',
            'disclaimer'               => 'رنگ‌ها و دمای نور تقریبی‌اند. برای تأیید نهایی، نمونه فیزیکی و دیتاشیت محصول را دریافت کنید.',
            'custom_css'               => '',
            'delete_data_on_uninstall' => 0,
        );
    }

    public static function get() {
        $saved = get_option( self::OPTION, array() );
        if ( ! is_array( $saved ) ) {
            $saved = array();
        }

        $settings = wp_parse_args( $saved, self::defaults() );

        /**
         * Filter plugin settings before they are used.
         *
         * @param array $settings
         */
        return apply_filters( 'pishgamnoor_studio_settings', $settings );
    }

    /**
     * Register the option with the Settings API.
     */
    public static function register() {
        register_setting(
            'pishgamnoor_studio',
            self::OPTION,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( self::class, 'sanitize' ),
                'default'           => self::defaults(),
            )
        );
    }

    /**
     * Sanitize every field explicitly. This keeps the option safe even when
     * the form is posted by a different integration.
     */
    public static function sanitize( $input ) {
        $defaults = self::defaults();
        $current  = self::get();
        $input    = is_array( $input ) ? $input : array();
        $out      = $current;

        foreach ( array( 'accent', 'bg', 'panel', 'panel_2', 'text', 'muted' ) as $color ) {
            if ( isset( $input[ $color ] ) ) {
                $sanitized = sanitize_hex_color( $input[ $color ] );
                $out[ $color ] = $sanitized ? $sanitized : $defaults[ $color ];
            }
        }

        $fonts = array( 'IRANSansX', 'Vazirmatn', 'system' );
        if ( isset( $input['font_family'] ) && in_array( $input['font_family'], $fonts, true ) ) {
            $out['font_family'] = $input['font_family'];
        }

        $ranges = array(
            'font_size'       => array( 12, 22 ),
            'title_size'      => array( 18, 40 ),
            'small_size'      => array( 10, 18 ),
            'heading_size'    => array( 28, 72 ),
            'radius'          => array( 0, 24 ),
            'shell_height'    => array( 600, 1000 ),
            'stage_height'    => array( 400, 1000 ),
            'section_padding' => array( 0, 120 ),
        );

        foreach ( $ranges as $key => $range ) {
            if ( isset( $input[ $key ] ) ) {
                $out[ $key ] = max( $range[0], min( $range[1], absint( $input[ $key ] ) ) );
            }
        }

        foreach ( array( 'heading', 'subheading', 'product_link_label', 'disclaimer' ) as $text ) {
            if ( isset( $input[ $text ] ) ) {
                $out[ $text ] = sanitize_text_field( $input[ $text ] );
            }
        }

        if ( isset( $input['default_product'] ) ) {
            $out['default_product'] = sanitize_title( $input['default_product'] );
        }

        $bools = array(
            'auto_rotate',
            'show_rail',
            'show_finish',
            'show_cct',
            'show_dimmer',
            'show_specs',
            'show_power',
            'show_fullscreen',
            'show_product_link',
            'delete_data_on_uninstall',
        );

        foreach ( $bools as $key ) {
            $out[ $key ] = ! empty( $input[ $key ] ) ? 1 : 0;
        }

        if ( isset( $input['custom_css'] ) ) {
            $css = wp_strip_all_tags( (string) $input['custom_css'] );
            $out['custom_css'] = str_replace( '</style', '', $css );
        }

        return $out;
    }

    public static function install_defaults() {
        if ( false === get_option( self::OPTION, false ) ) {
            add_option( self::OPTION, self::defaults() );
        }
        update_option( self::VERSION_OPTION, PISHGAMNOOR_STUDIO_VERSION, false );
    }

    /**
     * Add new keys on upgrades without overwriting existing user values.
     */
    public static function maybe_upgrade() {
        $installed = get_option( self::VERSION_OPTION );
        if ( $installed === PISHGAMNOOR_STUDIO_VERSION ) {
            return;
        }

        $saved = get_option( self::OPTION, array() );
        if ( ! is_array( $saved ) ) {
            $saved = array();
        }

        update_option( self::OPTION, wp_parse_args( $saved, self::defaults() ) );
        update_option( self::VERSION_OPTION, PISHGAMNOOR_STUDIO_VERSION, false );

        // A safe idempotent call: seeds only when no products exist.
        Product_Repository::seed_defaults();
    }

    public static function font_stack( $family = null ) {
        $family = $family ?: ( self::get()['font_family'] ?? 'IRANSansX' );
        if ( 'Vazirmatn' === $family ) {
            return '"Vazirmatn", "IRANSansX", sans-serif';
        }
        if ( 'system' === $family ) {
            return 'system-ui, -apple-system, "Segoe UI", Tahoma, sans-serif';
        }

        return '"IRANSansX", "Vazirmatn", sans-serif';
    }
}
