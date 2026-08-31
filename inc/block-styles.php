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
 * Per-block styles, loaded only on pages that render the block.
 *
 * wp_enqueue_block_style() belongs on `init`: it does not enqueue anything itself,
 * it registers a callback that core runs later, when the block is actually rendered.
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
}
add_action( 'init', 'flexa_enqueue_block_styles' );

/**
 * Styles for this theme's custom block styles (button Outline, separator Wavy).
 *
 * ON wp_enqueue_scripts, NOT init, and the difference is not cosmetic. The styles
 * queue is one global list that the admin prints as well: admin_print_styles fires
 * print_admin_styles(), which calls $wp_styles->do_items( false ) over the whole
 * queue without filtering it. `init` runs on admin requests too, so enqueueing here
 * put this stylesheet on every single wp-admin screen - Dashboard, Posts, Users,
 * Settings - one wasted request each, carrying a `border: none !important` into
 * pages that never asked for it.
 *
 * That leak bought nothing in return. The block editor renders in an iframe, and
 * what print_admin_styles() emits lands in the parent document, so these rules
 * never reached the canvas either. The editor gets them through add_editor_style()
 * in inc/setup.php instead, which is what loads into the iframe.
 */
function flexa_enqueue_block_stylesheet() {
	wp_enqueue_style(
		'flexa-block-styles',
		get_template_directory_uri() . '/assets/css/block-styles.css',
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'flexa_enqueue_block_stylesheet' );

/**
 * Register custom block styles.
 *
 * Only Wavy. core/separator ships default, wide and dots, so wavy is genuinely new.
 *
 * core/button's Outline is NOT registered here, because core already declares it in
 * blocks/button/block.json and styles it in blocks/button/style.css. Registering the same
 * name again added a second entry to WP_Block_Styles_Registry beside core's own, silently -
 * the registry overwrites without a notice, so nothing ever reported the collision.
 *
 * Losing the call does not lose the style. `is-style-outline` comes from core either way,
 * which is exactly what assets/css/block-styles.css restyles.
 */
function flexa_register_block_styles() {
	register_block_style(
		'core/separator',
		array(
			'name'  => 'wavy',
			'label' => __( 'Wavy', 'flexa' ),
		)
	);
}
add_action( 'init', 'flexa_register_block_styles' );