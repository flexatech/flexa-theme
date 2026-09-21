<?php
/**
 * Flexa plugins screen - installed state detection.
 *
 * @package Flexa
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Map plugin directory slugs to their main file and installed version.
 *
 * The version is read from the plugin header on disk, so it is always the
 * version actually running - unlike the periodically refreshed update cache.
 *
 * @return array<string,array{file:string,version:string}>
 */
function flexa_pi_installed_map() {
	static $map = null;

	if ( null !== $map ) {
		return $map;
	}

	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$map = array();

	foreach ( get_plugins() as $file => $data ) {
		$dir = dirname( $file );

		// Single-file plugins that sit directly inside the plugins directory.
		if ( '.' === $dir ) {
			$dir = basename( $file, '.php' );
		}

		if ( ! isset( $map[ $dir ] ) ) {
			$map[ $dir ] = array(
				'file'     => $file,
				'version'  => isset( $data['Version'] ) ? $data['Version'] : '',
				'requires' => isset( $data['RequiresPlugins'] ) ? $data['RequiresPlugins'] : '',
			);
		}
	}

	return $map;
}

/**
 * Resolve the state of a plugin against what is installed on this site.
 *
 * @param array $plugin Normalized plugin entry from flexa_pi_fetch_plugins().
 * @return array{status:string,file:string,installed:string,active:bool}
 *         status is one of: install | inactive | active | update
 */
function flexa_pi_get_state( $plugin ) {
	$map = flexa_pi_installed_map();

	$state = array(
		'status'        => 'install',
		'file'          => '',
		'installed'     => '',
		'active'        => false,
		'needed_by_pro' => false,
	);

	if ( ! function_exists( 'is_plugin_active' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	if ( ! isset( $map[ $plugin['slug'] ] ) ) {
		$pro = $plugin['slug'] . '-pro';

		if ( isset( $map[ $pro ] ) ) {
			/*
			 * Two different relationships hide behind a "-pro" directory.
			 *
			 * An add-on declares "Requires Plugins: <free slug>" and cannot even
			 * be activated without the free plugin (core enforces the header
			 * since 6.5), so the free one still has to be offered - flagged, so
			 * the card can say why it matters.
			 *
			 * A standalone Pro build declares no such dependency and replaces
			 * the free plugin, so installing the free one over it would be
			 * wrong. Its version is not comparable with the WordPress.org one
			 * either, so no update is offered.
			 */
			if ( flexa_pi_pro_requires( $map[ $pro ]['requires'], $plugin['slug'] ) ) {
				$state['needed_by_pro'] = true;

				return $state;
			}

			$state['status']    = 'pro';
			$state['file']      = $map[ $pro ]['file'];
			$state['installed'] = $map[ $pro ]['version'];
			$state['active']    = is_plugin_active( $map[ $pro ]['file'] );
		}

		return $state;
	}

	$entry = $map[ $plugin['slug'] ];

	$state['file']      = $entry['file'];
	$state['installed'] = $entry['version'];
	$state['active']    = is_plugin_active( $entry['file'] );
	$state['status']    = $state['active'] ? 'active' : 'inactive';

	// An available update takes precedence over the active/inactive state, so
	// the card offers the most useful action first.
	if ( flexa_pi_has_update( $entry['version'], $plugin['version'] ) ) {
		$state['status'] = 'update';
	}

	return $state;
}

/**
 * Whether a "Requires Plugins" header names the given slug.
 *
 * @param string $requires Raw header value: comma separated WordPress.org slugs.
 * @param string $slug     Slug to look for.
 * @return bool
 */
function flexa_pi_pro_requires( $requires, $slug ) {
	if ( '' === (string) $requires ) {
		return false;
	}

	foreach ( explode( ',', (string) $requires ) as $required ) {
		if ( sanitize_key( trim( $required ) ) === $slug ) {
			return true;
		}
	}

	return false;
}

/**
 * Whether the installed version is older than the one on WordPress.org.
 *
 * @param string $installed Version read from the plugin header.
 * @param string $remote    Version reported by WordPress.org.
 * @return bool
 */
function flexa_pi_has_update( $installed, $remote ) {
	if ( '' === $installed || '' === $remote ) {
		return false;
	}

	return version_compare( $installed, $remote, '<' );
}
