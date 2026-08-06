<?php
/**
 * Block patterns categories
 *
 * @package Flexa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register block pattern category.
 */
function flexa_register_patterns() {
	register_block_pattern_category(
		'flexa',
		array( 'label' => __( 'Flexa', 'flexa' ) )
	);
}
add_action( 'init', 'flexa_register_patterns' );