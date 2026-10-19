<?php
namespace Pishgamnoor\Studio;

use Pishgamnoor\Studio\PostTypes\Product;
use Pishgamnoor\Studio\Repositories\Product_Repository;
use Pishgamnoor\Studio\Support\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Activation / deactivation lifecycle.
 */
final class Activator {

    public static function activate() {
        // Register the CPT in this request so seed posts can be created.
        $product_type = new Product();
        $product_type->register();

        Settings::install_defaults();
        Product_Repository::seed_defaults();
        // Also repairs legacy 1.1 escaped-Unicode meta on manual reactivation.
        Product_Repository::repair_stored_json();

        update_option( 'pishgamnoor_studio_version', PISHGAMNOOR_STUDIO_VERSION );
        set_transient( 'pishgamnoor_studio_activated', 1, 120 );
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }
}
