<?php
namespace Pishgamnoor\Studio\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Frontend assets. The CSS is scoped to .pn-studio and is safe to load
 * globally; the module JS is only enqueued when the shortcode is rendered.
 */
final class Assets {

    const HANDLE = 'pishgamnoor-studio';

    /**
     * @var array|null
     */
    private static $data = null;

    /**
     * @var bool
     */
    private static $enqueued = false;

    public function register() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_style' ) );
        add_action( 'wp_footer', array( $this, 'print_footer_data' ), 1 );
        add_filter( 'script_loader_tag', array( $this, 'module_tag' ), 10, 3 );
    }

    public function enqueue_style() {
        wp_enqueue_style(
            self::HANDLE,
            PISHGAMNOOR_STUDIO_URL . 'assets/css/public.css',
            array(),
            PISHGAMNOOR_STUDIO_VERSION
        );
    }

    public static function enqueue( $data ) {
        self::$data     = $data;
        self::$enqueued = true;

        wp_enqueue_style(
            self::HANDLE,
            PISHGAMNOOR_STUDIO_URL . 'assets/css/public.css',
            array(),
            PISHGAMNOOR_STUDIO_VERSION
        );

        wp_enqueue_script(
            self::HANDLE,
            PISHGAMNOOR_STUDIO_URL . 'assets/js/studio.js',
            array(),
            PISHGAMNOOR_STUDIO_VERSION,
            true
        );
    }

    public function print_footer_data() {
        if ( ! self::$enqueued || null === self::$data ) {
            return;
        }

        $import_map = array(
            'imports' => array(
                'three'         => PISHGAMNOOR_STUDIO_URL . 'assets/vendor/three/three.module.js',
                'three/addons/' => PISHGAMNOOR_STUDIO_URL . 'assets/vendor/three/examples/',
            ),
        );

        wp_print_inline_script_tag(
            wp_json_encode( $import_map, JSON_UNESCAPED_SLASHES ),
            array( 'type' => 'importmap' )
        );

        wp_print_inline_script_tag(
            'window.PishgamnoorStudioData = ' . wp_json_encode(
                self::$data,
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
            ) . ';'
        );
    }

    public function module_tag( $tag, $handle, $src ) {
        if ( self::HANDLE !== $handle ) {
            return $tag;
        }

        if ( false === strpos( $tag, 'type=' ) ) {
            $tag = str_replace( '<script ', '<script type="module" ', $tag );
        }

        return $tag;
    }
}
