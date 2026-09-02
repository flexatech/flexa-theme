<?php
/**
 * Flexa - functions.php
 *
 * @package Flexa
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Array of files to include
 */
$flexa_includes = array(
	'inc/setup.php',        // Theme setup and basic stylesheet enqueue
	'inc/block-styles.php', // Block styles registration and enqueues
	'inc/patterns.php',     // Block patterns registration
);

/**
 * Automatically require files declared above
 */
foreach ( $flexa_includes as $file ) {
	$filepath = get_template_directory() . '/' . $file;
	if ( file_exists( $filepath ) ) {
		require_once $filepath;
	}
}