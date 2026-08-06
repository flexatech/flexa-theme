<?php
/**
 * Theme setup and styles enqueue
 *
 * @package Flexa
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sets up theme defaults and registers support for various WordPress features.
 */
function flexa_setup() {
	load_theme_textdomain( 'flexa', get_template_directory() . '/languages' );
	add_theme_support( 'wp-block-styles' );
	add_theme_support( 'editor-styles' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'html5', array(
		'search-form', 'comment-form', 'comment-list', 
		'gallery', 'caption', 'style', 'script', 'navigation-widgets',
	) );
	add_theme_support( 'responsive-embeds' );
	remove_theme_support( 'core-block-patterns' );
	add_editor_style( array( 'style.css', 'assets/css/editor-style.css' ) );
}
add_action( 'after_setup_theme', 'flexa_setup' );

/**
 * Enqueue theme stylesheet.
 */
function flexa_enqueue_styles() {
	wp_enqueue_style(
		'flexa-style',
		get_stylesheet_uri(),
		array(),
		wp_get_theme()->get( 'Version' )
	);
}
add_action( 'wp_enqueue_scripts', 'flexa_enqueue_styles' );