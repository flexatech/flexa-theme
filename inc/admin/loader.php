<?php
/**
 * Flexa admin - loader.
 *
 * One page under Appearance with two tabs. Require this file and nothing else;
 * it pulls in the rest, including the plugins feature that supplies the second
 * tab.
 *
 * @package Flexa
 * @since   1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/settings.php';
require_once __DIR__ . '/page.php';
require_once __DIR__ . '/tab-overview.php';
require_once __DIR__ . '/ajax.php';

// Supplies flexa_pi_render_tab() and the install / activate / update endpoints.
require_once get_template_directory() . '/inc/plugin-installer/loader.php';
