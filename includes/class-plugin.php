<?php
namespace Pishgamnoor\Studio;

use Pishgamnoor\Studio\Admin\Admin;
use Pishgamnoor\Studio\Admin\Product_Columns;
use Pishgamnoor\Studio\Admin\Product_Meta;
use Pishgamnoor\Studio\Frontend\Assets;
use Pishgamnoor\Studio\Frontend\Shortcode;
use Pishgamnoor\Studio\PostTypes\Product;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main plugin bootstrap.
 */
final class Plugin {

    /**
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * @var Admin
     */
    public $admin;

    /**
     * @var Shortcode
     */
    public $shortcode;

    /**
     * @var Assets
     */
    public $assets;

    private function __construct() {
        $this->load_textdomain();

        // Data model.
        $product_type = new Product();
        add_action( 'init', array( $product_type, 'register' ), 5 );

        // Admin.
        if ( is_admin() ) {
            $this->admin = new Admin();
            new Product_Meta();
            new Product_Columns();
        }

        // Frontend.
        $this->assets   = new Assets();
        $this->shortcode = new Shortcode();
        $this->shortcode->register();
        $this->assets->register();

        // Keep the schema in sync for future updates without reactivating.
        add_action( 'init', array( Support\Settings::class, 'maybe_upgrade' ), 20 );

        do_action( 'pishgamnoor_studio_loaded', $this );
    }

    public static function instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function load_textdomain() {
        load_plugin_textdomain(
            'pishgamnoor-studio',
            false,
            dirname( PISHGAMNOOR_STUDIO_BASENAME ) . '/languages'
        );
    }

    private function __clone() {}
    public function __wakeup() {}
}
