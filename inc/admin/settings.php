<?php
/**
 * Flexa admin - the theme's single stored option.
 *
 * Everything the theme persists lives in one array option, following
 * docs/storage-conventions.md and WordPress.org guideline 12: a theme may add
 * only one database option, prefixed with the theme slug.
 *
 * @package Flexa
 * @since   1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** The one and only option this theme writes. */
const FLEXA_OPTION = 'flexa_settings';

/** Shape version, so a later change can migrate instead of guessing. */
const FLEXA_OPTION_SCHEMA = 1;

/**
 * The whole settings array, with defaults filled in.
 *
 * @return array
 */
function flexa_settings() {
	$saved = get_option( FLEXA_OPTION, array() );

	if ( ! is_array( $saved ) ) {
		$saved = array();
	}

	return wp_parse_args(
		$saved,
		array(
			'schema'         => FLEXA_OPTION_SCHEMA,
			'welcome_hidden' => false,
		)
	);
}

/**
 * Read one setting.
 *
 * @param string $key     Setting name.
 * @param mixed  $default Returned when the key is missing.
 * @return mixed
 */
function flexa_get_setting( $key, $default = null ) {
	$all = flexa_settings();

	return array_key_exists( $key, $all ) ? $all[ $key ] : $default;
}

/**
 * Write one setting, leaving the rest of the array alone.
 *
 * @param string $key   Setting name.
 * @param mixed  $value Value to store. The caller sanitizes it.
 * @return bool
 */
function flexa_update_setting( $key, $value ) {
	$all           = flexa_settings();
	$all[ $key ]   = $value;
	$all['schema'] = FLEXA_OPTION_SCHEMA;

	return update_option( FLEXA_OPTION, $all );
}
