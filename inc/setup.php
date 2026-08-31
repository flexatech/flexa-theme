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

	/*
	 * Classic menu locations.
	 *
	 * Both header parts render core/navigation, so nothing here calls wp_nav_menu(). These are
	 * still registered for two reasons.
	 *
	 * First, core reads them. A core/navigation block with no menu of its own falls back through
	 * WP_Navigation_Fallback, and its first stop is the classic menu assigned to the "primary"
	 * location - so registering the location is what lets a site that manages its menu in
	 * Appearance -> Menus see that menu in the header at all.
	 *
	 * Second, registering is what puts the Appearance -> Menus screen back and gives the location
	 * a checkbox there. Without it a menu can be assigned in theme mods but never edited by hand.
	 */
	register_nav_menus(
		array(
			'primary' => __( 'Primary Menu', 'flexa' ),
			'footer'  => __( 'Footer Menu', 'flexa' ),
		)
	);
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