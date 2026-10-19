<?php
/**
 * Plugin Name:       Pishgamnoor Studio
 * Plugin URI:        https://github.com/mosimum/pishgamnoor-plugin
 * Description:       استودیو نور سه‌بعدی پیشگام نور؛ نمایش محصولات با شورت‌کد، مدیریت محصولات و تنظیمات ظاهری.
 * Version:           1.3
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Mostafa Moosavi
 * Author URI:        https://github.com/mosimum
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pishgamnoor-studio
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'PISHGAMNOOR_STUDIO_VERSION', '1.3' );
define( 'PISHGAMNOOR_STUDIO_FILE', __FILE__ );
define( 'PISHGAMNOOR_STUDIO_PATH', plugin_dir_path( __FILE__ ) );
define( 'PISHGAMNOOR_STUDIO_URL', plugin_dir_url( __FILE__ ) );
define( 'PISHGAMNOOR_STUDIO_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Lightweight PSR-4 style autoloader for the plugin.
 *
 * Pishgamnoor\Studio\Admin\Settings_Page -> includes/Admin/class-settings-page.php
 */
spl_autoload_register(
    static function ( $class ) {
        $prefix = 'Pishgamnoor\\Studio\\';
        if ( 0 !== strpos( $class, $prefix ) ) {
            return;
        }

        $relative = substr( $class, strlen( $prefix ) );
        $parts    = explode( '\\', $relative );
        $file     = array_pop( $parts );
        $path     = PISHGAMNOOR_STUDIO_PATH . 'includes/';

        if ( $parts ) {
            $path .= implode( '/', $parts ) . '/';
        }

        $path .= 'class-' . strtolower( str_replace( '_', '-', $file ) ) . '.php';

        if ( is_readable( $path ) ) {
            require_once $path;
        }
    }
);

register_activation_hook( __FILE__, array( 'Pishgamnoor\\Studio\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Pishgamnoor\\Studio\\Activator', 'deactivate' ) );

add_action(
    'plugins_loaded',
    static function () {
        \Pishgamnoor\Studio\Plugin::instance();
    }
);
