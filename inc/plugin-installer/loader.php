<?php
/**
 * Flexa plugins screen - loader.
 *
 * Lists the theme author's plugins from WordPress.org and installs, activates
 * or updates them over AJAX. Require this file and nothing else; it wires up
 * the rest of the feature.
 *
 * @package Flexa
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Author username on WordPress.org. */
const FLEXA_PI_AUTHOR = 'flexatech';

/**
 * Transient key used to cache the plugin list.
 *
 * The suffix is a schema version, not a theme version. Bump it whenever the
 * shape of the cached array changes - a new key, a renamed key, a different
 * type. Sites hold this cache for 12 hours, so without a bump a visitor who
 * updates the theme mid-cache would have the new code read the old array and
 * emit "Undefined array key" warnings until it expires.
 *
 * Bumping is the whole migration: the old entry is simply never read again and
 * expires on its own. It carries autoload=false (set_transient() does that for
 * any transient with an expiry), so a stale row costs nothing while it waits.
 */
const FLEXA_PI_TRANSIENT = 'flexa_pi_plugins_v1';

/** Cache lifetime: 12 hours. */
const FLEXA_PI_TTL = 12 * HOUR_IN_SECONDS;

/** Page slug under the Appearance menu. */
const FLEXA_PI_PAGE = 'flexa-plugins';

/**
 * Feature modules, in dependency order.
 *
 * api      - talks to WordPress.org and caches the result
 * status   - compares that result against what is installed here
 * assets   - registers the stylesheet and script for the screen
 * card     - renders one plugin card
 * screen   - registers the menu and renders the page
 * ajax     - the install / activate / update endpoints
 */
$flexa_pi_modules = array( 'api', 'status', 'assets', 'card', 'screen', 'ajax' );

foreach ( $flexa_pi_modules as $flexa_pi_module ) {
	require_once __DIR__ . '/' . $flexa_pi_module . '.php';
}

unset( $flexa_pi_modules, $flexa_pi_module );
