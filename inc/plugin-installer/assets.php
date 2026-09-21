<?php
/**
 * Flexa plugins screen - stylesheet and script.
 *
 * @package Flexa
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue the CSS and JS. Only called on this screen, see screen.php.
 */
function flexa_pi_enqueue_assets() {
	$version = wp_get_theme()->get( 'Version' );

	wp_enqueue_style(
		'flexa-plugin-installer',
		get_template_directory_uri() . '/assets/css/plugin-installer.css',
		array(),
		$version
	);

	wp_enqueue_script(
		'flexa-plugin-installer',
		get_template_directory_uri() . '/assets/js/plugin-installer.js',
		array(),
		$version,
		true
	);

	wp_localize_script( 'flexa-plugin-installer', 'flexaPi', flexa_pi_script_data() );
}

/**
 * Data handed to the script: endpoints, nonces and translated labels.
 *
 * Each action carries its own nonce; the capability behind it is checked again
 * server side, this only decides which buttons are worth showing.
 *
 * @return array
 */
function flexa_pi_script_data() {
	return array(
		'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
		'installNonce'  => wp_create_nonce( 'flexa_pi_install' ),
		'activateNonce' => wp_create_nonce( 'flexa_pi_activate' ),
		'updateNonce'   => wp_create_nonce( 'flexa_pi_update' ),
		'canActivate'   => current_user_can( 'activate_plugins' ),
		'i18n'          => array(
			'installing' => __( 'Installing…', 'flexa' ),
			'activating' => __( 'Activating…', 'flexa' ),
			'updating'   => __( 'Updating…', 'flexa' ),
			'install'    => __( 'Install', 'flexa' ),
			'activate'   => __( 'Activate', 'flexa' ),
			'activated'  => __( 'Activated', 'flexa' ),
			'update'     => __( 'Update', 'flexa' ),
			/* translators: %s: plugin version number. */
			'version'    => __( 'Version %s', 'flexa' ),
			'failed'     => __( 'Something went wrong. Please try again.', 'flexa' ),
		),
	);
}
