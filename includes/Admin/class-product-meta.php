<?php
namespace Pishgamnoor\Studio\Admin;

use Pishgamnoor\Studio\PostTypes\Product;
use Pishgamnoor\Studio\Repositories\Product_Repository;
use Pishgamnoor\Studio\Support\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Product fields for the studio. The shortcode and JS only read these fields.
 */
final class Product_Meta {

    const NONCE = 'pishgamnoor_product_nonce';

    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post_' . Product::POST_TYPE, array( $this, 'save' ) );
    }

    public function add_meta_boxes() {
        add_meta_box(
            'pishgamnoor_product_data',
            'اطلاعات شبیه‌ساز محصول',
            array( $this, 'render' ),
            Product::POST_TYPE,
            'normal',
            'high'
        );

        add_meta_box(
            'pishgamnoor_product_side',
            'انتشار و شورت‌کد',
            array( $this, 'render_side' ),
            Product::POST_TYPE,
            'side',
            'high'
        );
    }

    public function render( $post ) {
        $post_id = (int) $post->ID;
        $camera  = Helper::get_json_meta( $post_id, '_pn_camera', array() );
        $presets = Helper::get_json_meta( $post_id, '_pn_presets', array() );

        $data = array(
            'post'        => $post,
            'model'       => sanitize_key( get_post_meta( $post_id, '_pn_model', true ) ) ?: 'gamma',
            'family'      => (string) get_post_meta( $post_id, '_pn_family', true ),
            'short'       => (string) get_post_meta( $post_id, '_pn_short', true ),
            'letter'      => (string) get_post_meta( $post_id, '_pn_letter', true ),
            'description' => (string) get_post_meta( $post_id, '_pn_description', true ),
            'url'         => (string) get_post_meta( $post_id, '_pn_url', true ),
            'stage'       => sanitize_key( get_post_meta( $post_id, '_pn_stage', true ) ) ?: 'indoor',
            'image_url'   => (string) get_post_meta( $post_id, '_pn_image_url', true ),
            'enabled'     => '0' !== (string) get_post_meta( $post_id, '_pn_enabled', true ),
            'order'       => (int) $post->menu_order,
            'finishes'    => Helper::get_json_meta( $post_id, '_pn_finishes', array() ),
            'ccts'        => array_map( 'intval', Helper::get_json_meta( $post_id, '_pn_ccts', array( 3000, 4000 ) ) ),
            'specs'       => Helper::get_json_meta( $post_id, '_pn_specs', array() ),
            'camera'      => is_array( $camera ) ? $camera : array(),
            'presets'     => is_array( $presets ) ? $presets : array(),
        );

        wp_nonce_field( 'pishgamnoor_save_product', self::NONCE );
        Helper::template( 'admin/product-meta.php', $data );
    }

    public function render_side( $post ) {
        Helper::template(
            'admin/product-side.php',
            array(
                'post'    => $post,
                'version' => PISHGAMNOOR_STUDIO_VERSION,
            )
        );
    }

    public function save( $post_id ) {
        if ( ! isset( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), 'pishgamnoor_save_product' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( Product::POST_TYPE !== get_post_type( $post_id ) ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $text_fields = array(
            '_pn_family'      => 'pn_family',
            '_pn_short'       => 'pn_short',
            '_pn_letter'      => 'pn_letter',
            '_pn_description' => 'pn_description',
        );

        foreach ( $text_fields as $meta => $field ) {
            $value = isset( $_POST[ $field ] ) ? wp_unslash( $_POST[ $field ] ) : '';
            $value = '_pn_description' === $meta ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
            update_post_meta( $post_id, $meta, $value );
        }

        $models = array_keys( Product::models() );
        $model  = isset( $_POST['pn_model'] ) ? sanitize_key( wp_unslash( $_POST['pn_model'] ) ) : 'gamma';
        if ( ! in_array( $model, $models, true ) ) {
            $model = 'gamma';
        }
        update_post_meta( $post_id, '_pn_model', $model );

        $stages = array_keys( Product::stages() );
        $stage  = isset( $_POST['pn_stage'] ) ? sanitize_key( wp_unslash( $_POST['pn_stage'] ) ) : 'indoor';
        if ( ! in_array( $stage, $stages, true ) ) {
            $stage = 'indoor';
        }
        update_post_meta( $post_id, '_pn_stage', $stage );

        $url = isset( $_POST['pn_url'] ) ? esc_url_raw( wp_unslash( $_POST['pn_url'] ) ) : '';
        update_post_meta( $post_id, '_pn_url', $url );

        $image_url = isset( $_POST['pn_image_url'] ) ? esc_url_raw( wp_unslash( $_POST['pn_image_url'] ) ) : '';
        update_post_meta( $post_id, '_pn_image_url', $image_url );

        update_post_meta( $post_id, '_pn_enabled', isset( $_POST['pn_enabled'] ) ? '1' : '0' );
        $order = isset( $_POST['pn_order'] ) ? absint( $_POST['pn_order'] ) : 0;
        update_post_meta( $post_id, '_pn_order', $order );
        // Keep native menu_order in sync so the admin list and frontend query use it.
        remove_action( 'save_post_' . Product::POST_TYPE, array( $this, 'save' ) );
        wp_update_post( array( 'ID' => $post_id, 'menu_order' => $order ) );
        add_action( 'save_post_' . Product::POST_TYPE, array( $this, 'save' ) );

        // Color variants.
        $finishes = array();
        if ( isset( $_POST['pn_finishes'] ) && is_array( $_POST['pn_finishes'] ) ) {
            $raw = wp_unslash( $_POST['pn_finishes'] );
            $labels = isset( $raw['label'] ) ? (array) $raw['label'] : array();
            $colors = isset( $raw['color'] ) ? (array) $raw['color'] : array();
            $metals = isset( $raw['metalness'] ) ? (array) $raw['metalness'] : array();
            $roughs = isset( $raw['roughness'] ) ? (array) $raw['roughness'] : array();
            $ids    = isset( $raw['id'] ) ? (array) $raw['id'] : array();

            foreach ( $labels as $index => $label ) {
                if ( '' === trim( (string) $label ) ) {
                    continue;
                }
                $finishes[] = array(
                    'id'        => $ids[ $index ] ?? '',
                    'label'     => $label,
                    'color'     => $colors[ $index ] ?? '',
                    'metalness' => $metals[ $index ] ?? 0.34,
                    'roughness' => $roughs[ $index ] ?? 0.56,
                );
            }
        }
        update_post_meta( $post_id, '_pn_finishes', Helper::encode_json_meta( Product_Repository::sanitize_finishes( $finishes ) ) );

        // CCT options.
        $ccts = isset( $_POST['pn_ccts'] ) ? (array) wp_unslash( $_POST['pn_ccts'] ) : array();
        update_post_meta( $post_id, '_pn_ccts', Helper::encode_json_meta( Product_Repository::sanitize_ccts( $ccts ) ) );

        // Specs.
        $specs = array();
        if ( isset( $_POST['pn_specs'] ) && is_array( $_POST['pn_specs'] ) ) {
            $raw = wp_unslash( $_POST['pn_specs'] );
            $labels = isset( $raw['label'] ) ? (array) $raw['label'] : array();
            $values = isset( $raw['value'] ) ? (array) $raw['value'] : array();
            foreach ( $labels as $index => $label ) {
                $value = $values[ $index ] ?? '';
                if ( '' === trim( (string) $label ) && '' === trim( (string) $value ) ) {
                    continue;
                }
                $specs[] = array( $label, $value );
            }
        }
        update_post_meta( $post_id, '_pn_specs', Helper::encode_json_meta( Product_Repository::sanitize_specs( $specs ) ) );

        // Optional camera override.
        $camera = $this->sanitize_camera( $_POST['pn_camera'] ?? array() );
        update_post_meta( $post_id, '_pn_camera', Helper::encode_json_meta( $camera ) );

        // Optional presets JSON (advanced; invalid JSON is ignored).
        $presets_json = isset( $_POST['pn_presets_json'] ) ? wp_unslash( $_POST['pn_presets_json'] ) : '';
        $presets      = json_decode( (string) $presets_json, true );
        update_post_meta( $post_id, '_pn_presets', Helper::encode_json_meta( is_array( $presets ) ? $presets : array() ) );
    }

    private function sanitize_camera( $camera ) {
        if ( ! is_array( $camera ) ) {
            return array();
        }

        $keys = array( 'pos_x', 'pos_y', 'pos_z', 'target_x', 'target_y', 'target_z' );
        $has_value = false;
        foreach ( $keys as $key ) {
            if ( isset( $camera[ $key ] ) && '' !== trim( (string) $camera[ $key ] ) ) {
                $has_value = true;
                break;
            }
        }

        if ( ! $has_value ) {
            return array();
        }

        $out = array(
            'pos'    => array( (float) ( $camera['pos_x'] ?? 0 ), (float) ( $camera['pos_y'] ?? 0 ), (float) ( $camera['pos_z'] ?? 0 ) ),
            'target' => array( (float) ( $camera['target_x'] ?? 0 ), (float) ( $camera['target_y'] ?? 0 ), (float) ( $camera['target_z'] ?? 0 ) ),
        );

        foreach ( array( 'min' => 'min', 'max' => 'max', 'polar_max' => 'polarMax' ) as $field => $out_key ) {
            if ( isset( $camera[ $field ] ) && '' !== trim( (string) $camera[ $field ] ) ) {
                $out[ $out_key ] = (float) $camera[ $field ];
            }
        }

        if ( isset( $camera['azimuth_min'], $camera['azimuth_max'] ) && '' !== trim( (string) $camera['azimuth_min'] ) && '' !== trim( (string) $camera['azimuth_max'] ) ) {
            $out['azimuth'] = array(
                'min' => (float) $camera['azimuth_min'],
                'max' => (float) $camera['azimuth_max'],
            );
        }

        return $out;
    }
}
