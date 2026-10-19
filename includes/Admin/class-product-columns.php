<?php
namespace Pishgamnoor\Studio\Admin;

use Pishgamnoor\Studio\PostTypes\Product;
use Pishgamnoor\Studio\Support\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin list table columns for the studio products.
 */
final class Product_Columns {

    public function __construct() {
        add_filter( 'manage_' . Product::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
        add_action( 'manage_' . Product::POST_TYPE . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
        add_filter( 'manage_edit-' . Product::POST_TYPE . '_sortable_columns', array( $this, 'sortable' ) );
        add_action( 'pre_get_posts', array( $this, 'order_by_menu_order' ) );
    }

    public function columns( $columns ) {
        $new = array();
        foreach ( $columns as $key => $label ) {
            if ( 'title' === $key ) {
                $new['pn_image'] = 'تصویر';
            }
            $new[ $key ] = $label;
            if ( 'title' === $key ) {
                $new['pn_model']  = 'مدل';
                $new['pn_family'] = 'خانواده';
                $new['pn_ccts']   = 'CCT';
                $new['pn_order']  = 'ترتیب';
                $new['pn_code']   = 'شورت‌کد';
            }
        }
        return $new;
    }

    public function render_column( $column, $post_id ) {
        switch ( $column ) {
            case 'pn_image':
                $image = get_the_post_thumbnail( $post_id, array( 60, 60 ) );
                if ( ! $image ) {
                    $url = get_post_meta( $post_id, '_pn_image_url', true );
                    if ( $url ) {
                        $image = '<img src="' . esc_url( $url ) . '" alt="" width="60" height="60">';
                    }
                }
                echo $image ? '<span class="pn-list-thumb">' . $image . '</span>' : '—'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                break;

            case 'pn_model':
                $model = sanitize_key( get_post_meta( $post_id, '_pn_model', true ) );
                $models = Product::models();
                echo esc_html( $models[ $model ] ?? $model );
                break;

            case 'pn_family':
                echo esc_html( get_post_meta( $post_id, '_pn_family', true ) );
                break;

            case 'pn_ccts':
                $ccts = array_map( 'intval', Helper::get_json_meta( $post_id, '_pn_ccts', array() ) );
                echo $ccts ? esc_html( implode( ' / ', array_map( 'number_format_i18n', $ccts ) ) . 'K' ) : '—';
                break;

            case 'pn_order':
                echo esc_html( (string) get_post_field( 'menu_order', $post_id ) );
                break;

            case 'pn_code':
                $slug = get_post_field( 'post_name', $post_id );
                echo '<code>[pishgamnoor_studio product="' . esc_html( $slug ) . '"]</code>';
                break;
        }
    }

    public function sortable( $columns ) {
        $columns['pn_order'] = 'menu_order';
        return $columns;
    }

    public function order_by_menu_order( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) {
            return;
        }

        if ( Product::POST_TYPE !== $query->get( 'post_type' ) ) {
            return;
        }

        if ( ! $query->get( 'orderby' ) ) {
            $query->set( 'orderby', 'menu_order' );
            $query->set( 'order', 'ASC' );
        }
    }
}
