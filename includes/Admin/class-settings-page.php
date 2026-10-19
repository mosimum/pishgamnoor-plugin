<?php
namespace Pishgamnoor\Studio\Admin;

use Pishgamnoor\Studio\PostTypes\Product;
use Pishgamnoor\Studio\Repositories\Product_Repository;
use Pishgamnoor\Studio\Support\Helper;
use Pishgamnoor\Studio\Support\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Settings screen. The markup lives in templates/admin/settings.php so it can
 * be overridden by a theme if needed.
 */
final class Settings_Page {

    public function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings  = Settings::get();
        $defaults  = Settings::defaults();
        $products  = Product_Repository::get_choices();
        $shortcode = '[pishgamnoor_studio]';

        Helper::template(
            'admin/settings.php',
            array(
                'settings'          => $settings,
                'defaults'          => $defaults,
                'products'          => $products,
                'shortcode'         => $shortcode,
                'version'           => PISHGAMNOOR_STUDIO_VERSION,
                'developer'         => 'Mostafa Moosavi',
                'product_post_type' => Product::POST_TYPE,
                'font_options'      => array(
                    'IRANSansX' => 'IRANSansX (فونت برند)',
                    'Vazirmatn' => 'Vazirmatn',
                    'system'    => 'فونت سیستم',
                ),
            )
        );
    }
}
