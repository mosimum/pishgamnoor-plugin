<?php
/**
 * Uninstall routine.
 *
 * Products and settings are preserved by default. The admin can opt into
 * deleting them from the settings screen.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

$settings = get_option( 'pishgamnoor_studio_settings', array() );

if ( is_array( $settings ) && ! empty( $settings['delete_data_on_uninstall'] ) ) {
    global $wpdb;

    $ids = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s",
            'pn_product'
        )
    );

    foreach ( $ids as $id ) {
        wp_delete_post( (int) $id, true );
    }

    delete_option( 'pishgamnoor_studio_settings' );
    delete_option( 'pishgamnoor_studio_version' );
}
