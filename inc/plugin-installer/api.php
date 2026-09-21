<?php
/**
 * Flexa plugins screen - WordPress.org API and cache.
 *
 * @package Flexa
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get the author's plugins from WordPress.org (cached).
 *
 * @param bool $force Skip the cache and hit the API again.
 * @return array|WP_Error Normalized plugin list, or WP_Error when the API call fails.
 */
function flexa_pi_fetch_plugins( $force = false ) {
	if ( ! $force ) {
		$cached = get_transient( FLEXA_PI_TRANSIENT );

		if ( is_array( $cached ) ) {
			return $cached;
		}
	}

	if ( ! function_exists( 'plugins_api' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
	}

	$response = plugins_api(
		'query_plugins',
		array(
			'author'   => FLEXA_PI_AUTHOR,
			'per_page' => 100,
			'page'     => 1,
			'fields'   => array(
				'short_description' => true,
				'icons'             => true,
				'sections'          => false,
				'tags'              => false,
				'ratings'           => false,
				'banners'           => false,
				'reviews'           => false,
				'contributors'      => false,
				'compatibility'     => false,
				'screenshots'       => false,
			),
		)
	);

	// Failures are deliberately not cached, so a WordPress.org outage does not
	// leave the screen empty for the next 12 hours.
	if ( is_wp_error( $response ) ) {
		return $response;
	}

	if ( empty( $response->plugins ) || ! is_array( $response->plugins ) ) {
		set_transient( FLEXA_PI_TRANSIENT, array(), FLEXA_PI_TTL );

		return array();
	}

	$plugins = array();

	// plugins_api() decodes the JSON into associative arrays and only casts the
	// outermost level to an object, so each entry here is an array, not an object.
	foreach ( $response->plugins as $plugin ) {
		$plugin = (array) $plugin;

		if ( empty( $plugin['slug'] ) ) {
			continue;
		}

		$slug = sanitize_key( $plugin['slug'] );

		// Only the handful of fields the screen actually renders are cached,
		// to keep the option row small.
		$plugins[] = array(
			'slug'        => $slug,
			'name'        => isset( $plugin['name'] ) ? flexa_pi_decode( $plugin['name'] ) : $slug,
			'description' => isset( $plugin['short_description'] ) ? flexa_pi_decode( $plugin['short_description'] ) : '',
			'version'     => isset( $plugin['version'] ) ? flexa_pi_decode( $plugin['version'] ) : '',
			'icon'        => flexa_pi_pick_icon( isset( $plugin['icons'] ) ? (array) $plugin['icons'] : array() ),
			'url'         => 'https://wordpress.org/plugins/' . $slug . '/',
		);
	}

	set_transient( FLEXA_PI_TRANSIENT, $plugins, FLEXA_PI_TTL );

	return $plugins;
}

/**
 * Decode HTML entities returned by the API (for example "&amp;" or "&#8211;").
 *
 * @param string $text Raw string from the API.
 * @return string
 */
function flexa_pi_decode( $text ) {
	return wp_strip_all_tags( html_entity_decode( (string) $text, ENT_QUOTES, 'UTF-8' ) );
}

/**
 * Pick the best icon, following WordPress core's order of preference.
 *
 * @param array $icons Icon set from the API.
 * @return string Icon URL, or an empty string.
 */
function flexa_pi_pick_icon( $icons ) {
	foreach ( array( 'svg', '2x', '1x', 'default' ) as $size ) {
		if ( ! empty( $icons[ $size ] ) ) {
			return esc_url_raw( $icons[ $size ] );
		}
	}

	return '';
}

/**
 * Check whether a slug belongs to the author's plugin list.
 *
 * Defense in depth: the AJAX endpoints can only act on Flexa plugins.
 *
 * @param string $slug Slug to check.
 * @return bool
 */
function flexa_pi_is_allowed_slug( $slug ) {
	$plugins = flexa_pi_fetch_plugins();

	if ( is_wp_error( $plugins ) ) {
		return false;
	}

	foreach ( $plugins as $plugin ) {
		if ( $plugin['slug'] === $slug ) {
			return true;
		}
	}

	return false;
}
