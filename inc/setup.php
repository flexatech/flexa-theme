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
	 * A block theme renders its own navigation through core/navigation, so these are not used by
	 * any template file here. They exist because a generated header may be a plain markup part
	 * that pulls the menu with wp_nav_menu( 'primary' ) instead of a navigation block - and
	 * wp_nav_menu() returns nothing for a location the theme never registered, no matter that a
	 * menu is assigned to it in theme mods. Registering them also brings back the Appearance ->
	 * Menus screen, which is where that menu is edited afterwards.
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