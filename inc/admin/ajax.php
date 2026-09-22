<?php
/**
 * Flexa admin - AJAX endpoints owned by the page itself.
 *
 * The plugin install / activate / update endpoints live with that feature, in
 * inc/plugin-installer/ajax.php.
 *
 * @package Flexa
 * @since   1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * AJAX: hide the Getting started panel for good.
 */
function flexa_ajax_dismiss_welcome() {
	check_ajax_referer( 'flexa_dismiss_welcome', 'nonce' );

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to do that.', 'flexa' ) ), 403 );
	}

	flexa_update_setting( 'welcome_hidden', true );

	wp_send_json_success();
}
add_action( 'wp_ajax_flexa_dismiss_welcome', 'flexa_ajax_dismiss_welcome' );
