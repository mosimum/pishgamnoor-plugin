<?php
namespace Pishgamnoor\Studio\Admin;

use Pishgamnoor\Studio\PostTypes\Product;
use Pishgamnoor\Studio\Support\Settings;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin menu, assets, notices and plugin links.
 */
final class Admin {

    /**
     * @var string
     */
    private $settings_hook = '';

    public function __construct() {
        add_action( 'admin_init', array( Settings::class, 'register' ) );
        add_action( 'admin_menu', array( $this, 'menu' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
        add_action( 'admin_notices', array( $this, 'notices' ) );
        add_filter( 'plugin_action_links_' . PISHGAMNOOR_STUDIO_BASENAME, array( $this, 'action_links' ) );
    }

    public function menu() {
        $settings_page = new Settings_Page();

        $this->settings_hook = add_menu_page(
            'اختصاصی پیشگام نور',
            'اختصاصی پیشگام نور',
            'manage_options',
            'pishgamnoor',
            array( $settings_page, 'render' ),
            'dashicons-lightbulb',
            58
        );

        add_submenu_page(
            'pishgamnoor',
            'تنظیمات شبیه‌ساز',
            'تنظیمات شبیه‌ساز',
            'manage_options',
            'pishgamnoor-settings',
            array( $settings_page, 'render' )
        );

        // A visual separator between "settings" and the product manager.
        add_submenu_page(
            'pishgamnoor',
            '',
            '?',
            'manage_options',
            'pishgamnoor-separator',
            '__return_null'
        );

        add_submenu_page(
            'pishgamnoor',
            'شبیه‌ساز محصولات',
            'شبیه‌ساز محصولات',
            'edit_posts',
            'edit.php?post_type=' . Product::POST_TYPE
        );

        add_submenu_page(
            'pishgamnoor',
            'افزودن محصول',
            'افزودن محصول',
            'edit_posts',
            'post-new.php?post_type=' . Product::POST_TYPE
        );
    }

    public function enqueue( $hook ) {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $is_product_screen = $screen && Product::POST_TYPE === $screen->post_type;
        $is_plugin_page    = false !== strpos( (string) $hook, 'pishgamnoor' );

        if ( ! $is_plugin_page && ! $is_product_screen ) {
            return;
        }

        wp_enqueue_style(
            'pishgamnoor-admin',
            PISHGAMNOOR_STUDIO_URL . 'assets/css/admin.css',
            array(),
            PISHGAMNOOR_STUDIO_VERSION
        );

        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        if ( $is_product_screen ) {
            wp_enqueue_media();
        }

        wp_enqueue_script(
            'pishgamnoor-admin',
            PISHGAMNOOR_STUDIO_URL . 'assets/js/admin.js',
            array( 'jquery', 'wp-color-picker' ),
            PISHGAMNOOR_STUDIO_VERSION,
            true
        );

        wp_localize_script(
            'pishgamnoor-admin',
            'PishgamnoorAdmin',
            array(
                'mediaTitle'  => 'انتخاب تصویر محصول',
                'mediaButton' => 'استفاده از این تصویر',
                'copied'      => 'کپی شد',
                'copyFailed'  => 'کپی نشد؛ لطفاً دستی انتخاب کنید.',
            )
        );
    }

    public function notices() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( get_transient( 'pishgamnoor_studio_activated' ) ) {
            delete_transient( 'pishgamnoor_studio_activated' );
            $settings_url = admin_url( 'admin.php?page=pishgamnoor' );
            $products_url = admin_url( 'edit.php?post_type=' . Product::POST_TYPE );
            ?>
            <div class="notice notice-success is-dismissible pn-admin-notice">
                <p><strong>پیشگام نور فعال شد.</strong></p>
                <p>
                    شورت‌کد نمایش:
                    <code>[pishgamnoor_studio]</code>
                    &nbsp;|&nbsp;
                    <a href="<?php echo esc_url( $settings_url ); ?>">تنظیمات ظاهری</a>
                    &nbsp;|&nbsp;
                    <a href="<?php echo esc_url( $products_url ); ?>">مدیریت محصولات</a>
                </p>
            </div>
            <?php
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( $screen && Product::POST_TYPE === $screen->post_type && 'edit' === $screen->base ) {
            $count = wp_count_posts( Product::POST_TYPE );
            $published = isset( $count->publish ) ? (int) $count->publish : 0;
            if ( 0 === $published ) {
                ?>
                <div class="notice notice-warning pn-admin-notice">
                    <p>
                        هنوز محصولی منتشر نشده است. برای نمایش شبیه‌ساز،
                        <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . Product::POST_TYPE ) ); ?>">یک محصول اضافه کنید</a>
                        یا پلاگین را دوباره فعال کنید تا محصولات پیش‌فرض ساخته شوند.
                    </p>
                </div>
                <?php
            }
        }
    }

    public function action_links( $links ) {
        $settings = '<a href="' . esc_url( admin_url( 'admin.php?page=pishgamnoor' ) ) . '">تنظیمات</a>';
        $products = '<a href="' . esc_url( admin_url( 'edit.php?post_type=' . Product::POST_TYPE ) ) . '">محصولات</a>';
        $github   = '<a href="https://github.com/mosimum/pishgamnoor-plugin" target="_blank" rel="noopener">GitHub</a>';

        array_unshift( $links, $settings, $products, $github );
        return $links;
    }
}
