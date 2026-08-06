<?php
/**
 * Block styles registration and enqueues
 *
 * @package Flexa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue block-specific styles.
 */
function flexa_enqueue_block_styles() {
	wp_enqueue_block_style(
		'core/navigation',
		array(
			'handle' => 'flexa-block-navigation',
			'src'    => get_template_directory_uri() . '/assets/css/block-style.css',
			'ver'    => wp_get_theme()->get( 'Version' ),
		)
	);

	wp_enqueue_style(
		'flexa-block-styles',
		get_template_directory_uri() . '/assets/css/block-styles.css',
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'init', 'flexa_enqueue_block_styles' );

/**
 * Register custom block styles.
 */
function flexa_register_block_styles() {
	register_block_style(
		'core/button',
		array(
			'name'  => 'outline',
			'label' => __( 'Outline', 'flexa' ),
		)
	);

	register_block_style(
		'core/separator',
		array(
			'name'  => 'wavy',
			'label' => __( 'Wavy', 'flexa' ),
		)
	);
}
add_action( 'init', 'flexa_register_block_styles' );