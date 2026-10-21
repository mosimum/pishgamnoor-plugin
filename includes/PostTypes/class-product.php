<?php
namespace Pishgamnoor\Studio\PostTypes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * "pn_product" is the data model for the studio. It is intentionally not a
 * public post type; products are rendered only through the studio shortcode.
 */
final class Product {

    const POST_TYPE = 'pn_product';

    /**
     * Available procedural 3D models in assets/js/studio.js.
     * Adding a model here + a builder in studio.js is enough for future products.
     */
    public static function models() {
        $models = array(
            'gamma'       => 'گاما / خطی کنافی',
            'theta'       => 'تتا / خطی کرو',
            'omega'       => 'امگا / خطی ایستاده',
            'zeous'       => 'زئوس / محوطه',
            'magnet'      => 'مگنت / لنزی ۲۴',
            'spot2r'      => 'اسپات ۲R / دوگانه',
            'omegalinear' => 'امگا / خطی آویز',
            'inout'       => 'ایناوت / توکار',
            'spotre'      => 'اسپات RE / روکار',
            'beta2'       => 'بتا ۲ / دیواری',
        );

        /**
         * Filter available procedural 3D models.
         *
         * @param array $models
         */
        return apply_filters( 'pishgamnoor_studio_models', $models );
    }

    public static function stages() {
        return array(
            'indoor'  => 'فضای داخلی',
            'outdoor' => 'فضای بیرونی / محوطه',
        );
    }

    /**
     * Product creation is temporarily closed from the WordPress dashboard.
     * Existing products stay editable; a future release can re-enable the
     * add-new screen with:
     * add_filter( 'pishgamnoor_studio_enable_product_creation', '__return_true' );
     *
     * @return bool
     */
    public static function product_creation_enabled() {
        return (bool) apply_filters( 'pishgamnoor_studio_enable_product_creation', false );
    }

    public static function register() {
        $labels = array(
            'name'                  => 'محصولات شبیه‌ساز',
            'singular_name'         => 'محصول شبیه‌ساز',
            'menu_name'             => 'شبیه‌ساز محصولات',
            'name_admin_bar'        => 'محصول شبیه‌ساز',
            'add_new'               => 'افزودن محصول',
            'add_new_item'          => 'افزودن محصول جدید',
            'new_item'              => 'محصول جدید',
            'edit_item'             => 'ویرایش محصول',
            'view_item'             => 'مشاهده محصول',
            'all_items'             => 'همه محصولات',
            'search_items'          => 'جستجوی محصولات',
            'not_found'             => 'محصولی پیدا نشد.',
            'not_found_in_trash'    => 'محصولی در زباله‌دان پیدا نشد.',
            'featured_image'        => 'تصویر محصول',
            'set_featured_image'    => 'انتخاب تصویر محصول',
            'remove_featured_image' => 'حذف تصویر محصول',
            'archives'              => 'آرشیو محصولات',
        );

        register_post_type(
            self::POST_TYPE,
            array(
                'labels'              => $labels,
                'public'              => false,
                'publicly_queryable'  => false,
                'show_ui'             => true,
                'show_in_menu'        => false,
                'show_in_admin_bar'   => false,
                'show_in_rest'        => false,
                'query_var'           => false,
                'rewrite'             => false,
                'capability_type'     => 'post',
                'map_meta_cap'        => true,
                'capabilities'        => self::product_creation_enabled()
                    ? array()
                    : array( 'create_posts' => 'do_not_allow' ),
                'has_archive'         => false,
                'hierarchical'        => false,
                'menu_icon'           => 'dashicons-lightbulb',
                'supports'            => array( 'title', 'thumbnail', 'page-attributes' ),
                'delete_with_user'    => false,
            )
        );
    }

    public static function is_product_screen( $hook = '' ) {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen ) {
            return false;
        }

        return self::POST_TYPE === $screen->post_type
            && in_array( $screen->base, array( 'post', 'edit' ), true );
    }
}
