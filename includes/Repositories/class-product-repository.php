<?php
namespace Pishgamnoor\Studio\Repositories;

use Pishgamnoor\Studio\PostTypes\Product;
use Pishgamnoor\Studio\Support\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Reads products from the pn_product post type and exposes a frontend-safe
 * array. All studio JS data flows through this repository.
 */
final class Product_Repository {

    public static function default_file() {
        return PISHGAMNOOR_STUDIO_PATH . 'data/default-products.json';
    }

    public static function get_default_products() {
        $file = self::default_file();
        if ( ! is_readable( $file ) ) {
            return array();
        }

        $raw = file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        if ( false === $raw ) {
            return array();
        }

        $data = json_decode( $raw, true );
        return is_array( $data ) ? $data : array();
    }

    /**
     * Idempotent seed. Runs on activation and on schema upgrades.
     */
    public static function seed_defaults() {
        $existing = get_posts(
            array(
                'post_type'      => Product::POST_TYPE,
                'post_status'    => 'any',
                'posts_per_page' => 1,
                'fields'         => 'ids',
            )
        );

        if ( ! empty( $existing ) ) {
            return;
        }

        $defaults = self::get_default_products();
        $valid_models = array_keys( Product::models() );
        $valid_stages = array_keys( Product::stages() );

        foreach ( $defaults as $item ) {
            $slug = sanitize_title( $item['slug'] ?? '' );
            if ( ! $slug ) {
                continue;
            }

            $post_id = wp_insert_post(
                array(
                    'post_type'   => Product::POST_TYPE,
                    'post_status' => 'publish',
                    'post_title'  => sanitize_text_field( $item['title'] ?? $slug ),
                    'post_name'   => $slug,
                    'menu_order'  => absint( $item['order'] ?? 0 ),
                ),
                true
            );

            if ( is_wp_error( $post_id ) ) {
                continue;
            }

            $model = sanitize_key( $item['model'] ?? 'gamma' );
            if ( ! in_array( $model, $valid_models, true ) ) {
                $model = 'gamma';
            }

            $stage = sanitize_key( $item['stage'] ?? 'indoor' );
            if ( ! in_array( $stage, $valid_stages, true ) ) {
                $stage = 'indoor';
            }

            $image = basename( (string) ( $item['image'] ?? '' ) );
            $image_url = $image
                ? PISHGAMNOOR_STUDIO_URL . 'assets/img/products/' . $image
                : self::default_image_for_model( $model );

            update_post_meta( $post_id, '_pn_model', $model );
            update_post_meta( $post_id, '_pn_family', sanitize_text_field( $item['family'] ?? '' ) );
            update_post_meta( $post_id, '_pn_short', sanitize_text_field( $item['short'] ?? '' ) );
            update_post_meta( $post_id, '_pn_letter', sanitize_text_field( $item['letter'] ?? '' ) );
            update_post_meta( $post_id, '_pn_description', sanitize_textarea_field( $item['description'] ?? '' ) );
            update_post_meta( $post_id, '_pn_url', esc_url_raw( $item['url'] ?? '' ) );
            update_post_meta( $post_id, '_pn_stage', $stage );
            update_post_meta( $post_id, '_pn_image_url', esc_url_raw( $image_url ) );
            update_post_meta( $post_id, '_pn_finishes', Helper::encode_json_meta( self::sanitize_finishes( $item['finishes'] ?? array() ) ) );
            update_post_meta( $post_id, '_pn_ccts', Helper::encode_json_meta( self::sanitize_ccts( $item['ccts'] ?? array() ) ) );
            update_post_meta( $post_id, '_pn_specs', Helper::encode_json_meta( self::sanitize_specs( $item['specs'] ?? array() ) ) );
            update_post_meta( $post_id, '_pn_camera', Helper::encode_json_meta( array() ) );
            update_post_meta( $post_id, '_pn_presets', Helper::encode_json_meta( array() ) );
            update_post_meta( $post_id, '_pn_enabled', '1' );
            update_post_meta( $post_id, '_pn_order', absint( $item['order'] ?? 0 ) );
        }
    }

    /**
     * Repair JSON meta written by 1.1, where update_post_meta() stripped the
     * backslashes from JSON \uXXXX escapes (e.g. "u06af" instead of "گ").
     * Runs once during the 1.2 upgrade and is safe to run repeatedly.
     */
    public static function repair_stored_json() {
        $posts = get_posts(
            array(
                'post_type'      => Product::POST_TYPE,
                'post_status'    => 'any',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            )
        );

        $keys = array( '_pn_finishes', '_pn_ccts', '_pn_specs', '_pn_camera', '_pn_presets' );

        foreach ( $posts as $post_id ) {
            foreach ( $keys as $key ) {
                $raw = get_post_meta( $post_id, $key, true );

                if ( ! is_array( $raw ) && ( ! is_string( $raw ) || '' === trim( $raw ) ) ) {
                    continue;
                }

                $decoded = Helper::get_json_meta( $post_id, $key, array() );
                update_post_meta( $post_id, $key, Helper::encode_json_meta( $decoded ) );
            }
        }
    }

    public static function get_products() {
        $posts = get_posts(
            array(
                'post_type'      => Product::POST_TYPE,
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'orderby'        => 'menu_order title',
                'order'          => 'ASC',
            )
        );

        $products = array();
        foreach ( $posts as $post ) {
            if ( '0' === (string) get_post_meta( $post->ID, '_pn_enabled', true ) ) {
                continue;
            }
            $products[] = self::map_product( $post );
        }

        /**
         * Filter the products sent to the studio.
         *
         * @param array $products
         */
        return apply_filters( 'pishgamnoor_studio_products', $products );
    }

    public static function get_choices() {
        $choices = array();
        foreach ( self::get_products() as $product ) {
            $choices[ $product['id'] ] = $product['name'];
        }
        return $choices;
    }

    public static function get_product( $id ) {
        foreach ( self::get_products() as $product ) {
            if ( $product['id'] === $id ) {
                return $product;
            }
        }
        return null;
    }

    public static function map_product( $post ) {
        $post_id = (int) $post->ID;
        $id      = $post->post_name ? $post->post_name : 'pn-' . $post_id;
        $model   = sanitize_key( get_post_meta( $post_id, '_pn_model', true ) );
        if ( ! array_key_exists( $model, Product::models() ) ) {
            $model = 'gamma';
        }

        $stage = sanitize_key( get_post_meta( $post_id, '_pn_stage', true ) );
        if ( ! array_key_exists( $stage, Product::stages() ) ) {
            $stage = 'indoor';
        }

        $image = get_the_post_thumbnail_url( $post_id, 'large' );
        if ( ! $image ) {
            $image = get_post_meta( $post_id, '_pn_image_url', true );
        }
        if ( ! $image ) {
            $image = self::default_image_for_model( $model );
        }

        $camera  = Helper::get_json_meta( $post_id, '_pn_camera', array() );
        $presets = Helper::get_json_meta( $post_id, '_pn_presets', array() );

        $camera  = ( is_array( $camera ) && ! empty( $camera['pos'] ) ) ? $camera : null;
        $presets = ( is_array( $presets ) && ! empty( $presets['three'] ) ) ? $presets : null;

        $product = array(
            'id'          => $id,
            'name'        => get_the_title( $post_id ),
            'family'      => (string) get_post_meta( $post_id, '_pn_family', true ),
            'short'       => (string) get_post_meta( $post_id, '_pn_short', true ),
            'letter'      => (string) get_post_meta( $post_id, '_pn_letter', true ),
            'desc'        => (string) get_post_meta( $post_id, '_pn_description', true ),
            'url'         => esc_url_raw( get_post_meta( $post_id, '_pn_url', true ) ),
            'stage'       => $stage,
            'model'       => $model,
            'image'       => esc_url_raw( $image ),
            'finishes'    => Helper::get_json_meta( $post_id, '_pn_finishes', array() ),
            'ccts'        => array_values( array_map( 'intval', Helper::get_json_meta( $post_id, '_pn_ccts', array() ) ) ),
            'specs'       => Helper::get_json_meta( $post_id, '_pn_specs', array() ),
            'camera'      => $camera,
            'presets'     => $presets,
            'order'       => (int) $post->menu_order,
            'enabled'     => true,
        );

        /**
         * Filter a single product array.
         *
         * @param array   $product
         * @param WP_Post $post
         */
        return apply_filters( 'pishgamnoor_studio_product', $product, $post );
    }

    public static function default_image_for_model( $model ) {
        $map = array(
            'gamma'       => 'gamma-kd.webp',
            'theta'       => 'theta-c.webp',
            'omega'       => 'omega-stand.webp',
            'zeous'       => 'zeous-2.webp',
            'magnet'      => 'magnet-lenz-24.webp',
            'spot2r'      => 'spot-2r-20.webp',
            'omegalinear' => 'omega-linear.webp',
            'inout'       => 'inout.webp',
            'spotre'      => 'spot-re.webp',
            'beta2'       => 'beta-2.webp',
        );

        $file = $map[ $model ] ?? 'gamma-kd.webp';
        return PISHGAMNOOR_STUDIO_URL . 'assets/img/products/' . $file;
    }

    public static function sanitize_finishes( $finishes ) {
        $out = array();
        if ( ! is_array( $finishes ) ) {
            return $out;
        }

        $index = 0;
        foreach ( $finishes as $finish ) {
            if ( ! is_array( $finish ) ) {
                continue;
            }
            $label = sanitize_text_field( $finish['label'] ?? '' );
            $color = sanitize_hex_color( $finish['color'] ?? '' );
            if ( ! $label || ! $color ) {
                continue;
            }
            $id = sanitize_key( $finish['id'] ?? '' );
            if ( ! $id ) {
                $id = 'color-' . $index;
            }
            $out[] = array(
                'id'        => $id,
                'label'     => $label,
                'color'     => $color,
                'metalness' => max( 0, min( 1, (float) ( $finish['metalness'] ?? 0.34 ) ) ),
                'roughness' => max( 0, min( 1, (float) ( $finish['roughness'] ?? 0.56 ) ) ),
            );
            $index++;
        }

        return $out;
    }

    public static function sanitize_ccts( $ccts ) {
        $allowed = array( 3000, 4000, 5000, 6000 );
        $ccts    = is_array( $ccts ) ? array_map( 'intval', $ccts ) : array();
        return array_values( array_intersect( $allowed, $ccts ) );
    }

    public static function sanitize_specs( $specs ) {
        $out = array();
        if ( ! is_array( $specs ) ) {
            return $out;
        }

        foreach ( $specs as $spec ) {
            if ( ! is_array( $spec ) || count( $spec ) < 2 ) {
                continue;
            }
            $label = sanitize_text_field( $spec[0] ?? '' );
            $value = sanitize_text_field( $spec[1] ?? '' );
            if ( '' === $label && '' === $value ) {
                continue;
            }
            $out[] = array( $label, $value );
        }

        return $out;
    }
}
