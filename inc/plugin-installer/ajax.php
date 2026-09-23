<?php
/**
 * Flexa plugins screen - AJAX endpoints.
 *
 * @package Flexa
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared front door for the endpoints below.
 *
 * Verifies the nonce, the capability and the slug, ending the request with a
 * JSON error if any of them fails.
 *
 * @param string $nonce_action Nonce action tied to this endpoint.
 * @param string $capability   Capability required to run it.
 * @param string $denied       Message shown when the capability is missing.
 * @return string The sanitized, allow-listed plugin slug.
 */
function flexa_pi_guard( $nonce_action, $capability, $denied ) {
	check_ajax_referer( $nonce_action, 'nonce' );

	if ( ! current_user_can( $capability ) ) {
		wp_send_json_error( array( 'message' => $denied ), 403 );
	}

	$slug = isset( $_POST['slug'] ) ? sanitize_key( wp_unslash( $_POST['slug'] ) ) : '';

	if ( '' === $slug ) {
		wp_send_json_error( array( 'message' => __( 'Missing plugin slug.', 'flexa' ) ), 400 );
	}

	// Defense in depth: these endpoints only ever touch this author's plugins.
	if ( ! flexa_pi_is_allowed_slug( $slug ) ) {
		wp_send_json_error( array( 'message' => __( 'This plugin is not part of the Flexa plugin list.', 'flexa' ) ), 400 );
	}

	return $slug;
}

/**
 * Load the upgrader and the admin helpers it depends on.
 */
function flexa_pi_load_upgrader() {
	require_once ABSPATH . 'wp-admin/includes/plugin.php';
	require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/misc.php';
	// This file also pulls in WP_Ajax_Upgrader_Skin.
	require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
}

/**
 * Turn an upgrader run into a JSON error when anything went wrong.
 *
 * Errors surface in three separate places, and a null result means the
 * filesystem could not be reached, so all of them have to be checked.
 *
 * @param WP_Ajax_Upgrader_Skin $skin   Skin used for the run.
 * @param mixed                 $result Whatever install() or upgrade() returned.
 */
function flexa_pi_bail_on_error( $skin, $result ) {
	if ( is_wp_error( $result ) ) {
		wp_send_json_error( array( 'message' => $result->get_error_message() ) );
	}

	if ( is_wp_error( $skin->result ) ) {
		wp_send_json_error( array( 'message' => $skin->result->get_error_message() ) );
	}

	if ( $skin->get_errors()->has_errors() ) {
		wp_send_json_error( array( 'message' => $skin->get_error_messages() ) );
	}

	if ( null === $result ) {
		wp_send_json_error(
			array( 'message' => __( 'Could not connect to the filesystem. Please check your FTP credentials or directory permissions.', 'flexa' ) )
		);
	}
}

/**
 * AJAX: install a plugin from WordPress.org.
 */
function flexa_pi_ajax_install() {
	$slug = flexa_pi_guard(
		'flexa_pi_install',
		'install_plugins',
		__( 'You do not have permission to install plugins.', 'flexa' )
	);

	flexa_pi_load_upgrader();

	$api = plugins_api(
		'plugin_information',
		array(
			'slug'   => $slug,
			'fields' => array( 'sections' => false ),
		)
	);

	if ( is_wp_error( $api ) ) {
		wp_send_json_error( array( 'message' => $api->get_error_message() ) );
	}

	if ( empty( $api->download_link ) ) {
		wp_send_json_error( array( 'message' => __( 'No download link found for this plugin.', 'flexa' ) ) );
	}

	$skin     = new WP_Ajax_Upgrader_Skin();
	$upgrader = new Plugin_Upgrader( $skin );
	$result   = $upgrader->install( $api->download_link );

	flexa_pi_bail_on_error( $skin, $result );

	wp_send_json_success(
		array(
			'slug'    => $slug,
			'message' => __( 'Installed successfully.', 'flexa' ),
		)
	);
}
add_action( 'wp_ajax_flexa_pi_install', 'flexa_pi_ajax_install' );

/**
 * AJAX: activate an installed plugin.
 */
function flexa_pi_ajax_activate() {
	$slug = flexa_pi_guard(
		'flexa_pi_activate',
		'activate_plugins',
		__( 'You do not have permission to activate plugins.', 'flexa' )
	);

	require_once ABSPATH . 'wp-admin/includes/plugin.php';

	$file = flexa_pi_require_installed( $slug );

	if ( ! current_user_can( 'activate_plugin', $file ) ) {
		wp_send_json_error( array( 'message' => __( 'You do not have permission to activate this plugin.', 'flexa' ) ), 403 );
	}

	if ( is_plugin_active( $file ) ) {
		wp_send_json_success( array( 'message' => __( 'This plugin is already active.', 'flexa' ) ) );
	}

	$activated = activate_plugin( $file );

	/*
	 * "unexpected_output" means the plugin printed something while loading but
	 * was still activated, so it is not a failure worth reporting.
	 */
	if ( is_wp_error( $activated ) && 'unexpected_output' !== $activated->get_error_code() ) {
		wp_send_json_error( array( 'message' => $activated->get_error_message() ) );
	}

	wp_send_json_success(
		array(
			'slug'    => $slug,
			'message' => __( 'Activated successfully.', 'flexa' ),
		)
	);
}
add_action( 'wp_ajax_flexa_pi_activate', 'flexa_pi_ajax_activate' );

/**
 * AJAX: update an installed plugin to the latest version on WordPress.org.
 */
function flexa_pi_ajax_update() {
	$slug = flexa_pi_guard(
		'flexa_pi_update',
		'update_plugins',
		__( 'You do not have permission to update plugins.', 'flexa' )
	);

	flexa_pi_load_upgrader();

	$file = flexa_pi_require_installed( $slug );

	// The upgrader reads the package URL out of the update_plugins site transient
	// and reports "up to date" when the plugin is missing from it, so the cache
	// has to be refreshed first.
	wp_update_plugins();

	$was_active = is_plugin_active( $file );

	$skin     = new WP_Ajax_Upgrader_Skin();
	$upgrader = new Plugin_Upgrader( $skin );

	/*
	 * bulk_upgrade(), not upgrade(), even for a single plugin - this is what core
	 * does in wp_ajax_update_plugin() and the difference is load bearing.
	 *
	 * upgrade() registers Plugin_Upgrader::deactivate_plugin_before_upgrade(),
	 * which silently deactivates the plugin, and nothing turns it back on:
	 * active_before()/active_after() only toggle maintenance mode, and only
	 * during cron. So upgrade() leaves an active plugin switched off.
	 * bulk_upgrade() never deactivates, so the plugin stays as it was.
	 */
	$results = $upgrader->bulk_upgrade( array( $file ) );

	flexa_pi_bail_on_error( $skin, $results );

	// bulk_upgrade() returns false outright when the filesystem is unreachable.
	if ( false === $results ) {
		wp_send_json_error(
			array( 'message' => __( 'Could not connect to the filesystem. Please check your FTP credentials or directory permissions.', 'flexa' ) )
		);
	}

	if ( ! is_array( $results ) || empty( $results[ $file ] ) ) {
		wp_send_json_error( array( 'message' => __( 'The update could not be completed.', 'flexa' ) ) );
	}

	// true means the plugin was already at the latest version.
	if ( true === $results[ $file ] ) {
		wp_send_json_error( array( 'message' => __( 'No update is available for this plugin.', 'flexa' ) ) );
	}

	// Read the version back off disk rather than trusting the cached API value.
	$updated = get_plugin_data( WP_PLUGIN_DIR . '/' . $file, false, false );

	wp_send_json_success(
		array(
			'slug'    => $slug,
			'version' => isset( $updated['Version'] ) ? $updated['Version'] : '',
			'active'  => $was_active && is_plugin_active( $file ),
			'message' => __( 'Updated successfully.', 'flexa' ),
		)
	);
}
add_action( 'wp_ajax_flexa_pi_update', 'flexa_pi_ajax_update' );

/**
 * Resolve a slug to its plugin file, ending the request if it is not installed.
 *
 * @param string $slug Allow-listed plugin slug.
 * @return string Plugin file, relative to the plugins directory.
 */
function flexa_pi_require_installed( $slug ) {
	$map = flexa_pi_installed_map();

	if ( ! isset( $map[ $slug ] ) ) {
		wp_send_json_error( array( 'message' => __( 'This plugin is not installed yet.', 'flexa' ) ) );
	}

	$file = $map[ $slug ]['file'];

	if ( 0 !== validate_file( $file ) ) {
		wp_send_json_error( array( 'message' => __( 'Invalid plugin path.', 'flexa' ) ), 400 );
	}

	return $file;
}
