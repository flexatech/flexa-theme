<?php
/**
 * Flexa admin - the Appearance page, its tabs and its assets.
 *
 * One admin page split into tabs with ?tab=. The tab strip uses core's own
 * nav-tab markup, the same one about.php uses, as guideline 12 asks for core
 * UI elements.
 *
 * NOTE ON GUIDELINE 12: it says a theme "may optionally add custom sub-pages
 * under Appearance", which reads as sub-pages only. This page is registered as
 * a top-level menu instead, by request. To move it back under Appearance,
 * swap the add_menu_page() call in flexa_admin_menu() for
 *
 *     add_submenu_page( 'themes.php', $title, $title, 'edit_theme_options',
 *         FLEXA_ADMIN_PAGE, 'flexa_admin_render_page' );
 *
 * and change admin.php back to themes.php in flexa_admin_url(). Nothing else
 * depends on where the page lives.
 *
 * @package Flexa
 * @since   1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Page slug under Appearance. */
const FLEXA_ADMIN_PAGE = 'flexa-theme';

/**
 * The tabs, in order, filtered down to the ones this user may see.
 *
 * A tab the user lacks the capability for is dropped rather than shown and
 * then refused, so the strip never offers a dead end.
 *
 * @return array<string,array{label:string,cap:string,render:callable}>
 */
function flexa_admin_tabs() {
	$tabs = array(
		'overview' => array(
			'label'  => __( 'Overview', 'flexa' ),
			'cap'    => 'edit_theme_options',
			'render' => 'flexa_admin_render_overview',
		),
		'plugins'  => array(
			'label'  => __( 'Plugins', 'flexa' ),
			'cap'    => 'install_plugins',
			'render' => 'flexa_pi_render_tab',
		),
	);

	return array_filter(
		$tabs,
		static function ( $tab ) {
			return current_user_can( $tab['cap'] );
		}
	);
}

/**
 * The tab being viewed, falling back to the first one available.
 *
 * @return string
 */
function flexa_admin_current_tab() {
	$tabs = flexa_admin_tabs();

	if ( empty( $tabs ) ) {
		return '';
	}

	$asked = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : '';

	return isset( $tabs[ $asked ] ) ? $asked : array_key_first( $tabs );
}

/**
 * URL of this page, on a given tab.
 *
 * @param string $tab  Tab slug. Empty for the default tab.
 * @param array  $args Extra query arguments.
 * @return string
 */
function flexa_admin_url( $tab = '', $args = array() ) {
	$query = array( 'page' => FLEXA_ADMIN_PAGE );

	if ( $tab ) {
		$query['tab'] = $tab;
	}

	// Top-level menu, so admin.php - a sub-page of Appearance would be themes.php.
	return add_query_arg( array_merge( $query, $args ), admin_url( 'admin.php' ) );
}

/**
 * Register the page as its own top-level menu.
 *
 * Position 59.8 puts it immediately above Appearance (60). A fractional
 * position is deliberate: whole numbers are taken by core and colliding with
 * one would silently displace whichever menu got there second.
 */
function flexa_admin_menu() {
	$hook = add_menu_page(
		esc_html__( 'Flexa Theme', 'flexa' ),
		esc_html__( 'Flexa Theme', 'flexa' ),
		'edit_theme_options',
		FLEXA_ADMIN_PAGE,
		'flexa_admin_render_page',
		'dashicons-layout',
		59.8
	);

	if ( ! $hook ) {
		return;
	}

	// Gives each tab a chance to act before any output, so redirects still work.
	add_action( 'load-' . $hook, 'flexa_admin_load' );

	add_action(
		'admin_enqueue_scripts',
		static function ( $current ) use ( $hook ) {
			if ( $current === $hook ) {
				flexa_admin_enqueue();
			}
		}
	);
}
add_action( 'admin_menu', 'flexa_admin_menu' );

/**
 * Fires on load-{$hook}, before the page renders.
 */
function flexa_admin_load() {
	$tab = flexa_admin_current_tab();

	if ( $tab ) {
		do_action( 'flexa_admin_load_' . $tab );
	}
}

/**
 * Stylesheet and script, loaded only on this screen.
 */
function flexa_admin_enqueue() {
	$version = wp_get_theme( get_template() )->get( 'Version' );
	$base    = get_template_directory_uri() . '/assets';

	wp_enqueue_style( 'flexa-admin', $base . '/css/admin.css', array(), $version );
	wp_enqueue_script( 'flexa-admin', $base . '/js/admin.js', array(), $version, true );

	wp_localize_script( 'flexa-admin', 'flexaAdmin', flexa_admin_script_data() );
}

/**
 * Data handed to the script.
 *
 * Capabilities are checked again server side; these flags only decide which
 * controls are worth showing.
 *
 * @return array
 */
function flexa_admin_script_data() {
	return array(
		'ajaxUrl' => admin_url( 'admin-ajax.php' ),
		'nonces'  => array(
			'dismiss'  => wp_create_nonce( 'flexa_dismiss_welcome' ),
			'install'  => wp_create_nonce( 'flexa_pi_install' ),
			'activate' => wp_create_nonce( 'flexa_pi_activate' ),
			'update'   => wp_create_nonce( 'flexa_pi_update' ),
		),
		'canActivate' => current_user_can( 'activate_plugins' ),
		'i18n'    => array(
			'installing' => __( 'Installing…', 'flexa' ),
			'activating' => __( 'Activating…', 'flexa' ),
			'updating'   => __( 'Updating…', 'flexa' ),
			'install'    => __( 'Install', 'flexa' ),
			'activate'   => __( 'Activate', 'flexa' ),
			'activated'  => __( 'Activated', 'flexa' ),
			'installed'  => __( 'Installed', 'flexa' ),
			'update'     => __( 'Update', 'flexa' ),
			/* translators: %s: plugin version number. */
			'version'    => __( 'Version %s', 'flexa' ),
			'active'     => __( 'Active', 'flexa' ),
			'failed'     => __( 'Something went wrong. Please try again.', 'flexa' ),
			'noMatch'    => __( 'No plugins match your search.', 'flexa' ),
			'noMatchHint' => __( 'Try a different word, or clear the field.', 'flexa' ),
			/* translators: 1: number of matches, 2: total number of plugins. */
			'countSome'  => __( '%1$d of %2$d plugins', 'flexa' ),
			/* translators: %d: number of plugins. */
			'countAll'   => __( '%d plugins', 'flexa' ),
		),
	);
}

/**
 * The icon sprite, printed once per page.
 *
 * Inline SVG rather than an icon font or image files: guideline 9 forbids
 * loading resources from another domain, and stroking with currentColor lets
 * each icon take the colour of the text around it.
 */
function flexa_admin_icons() {
	?>
	<svg width="0" height="0" style="position:absolute" aria-hidden="true" focusable="false">
		<defs>
			<symbol id="flexa-i-settings" viewBox="0 0 24 24">
				<line x1="4" y1="8" x2="20" y2="8"/><circle cx="9" cy="8" r="2.4"/>
				<line x1="4" y1="16" x2="20" y2="16"/><circle cx="16" cy="16" r="2.4"/>
			</symbol>
			<symbol id="flexa-i-layout" viewBox="0 0 24 24">
				<rect x="3" y="4" width="18" height="16" rx="2"/>
				<line x1="3" y1="9" x2="21" y2="9"/><line x1="9.5" y1="9" x2="9.5" y2="20"/>
			</symbol>
			<symbol id="flexa-i-plugin" viewBox="0 0 24 24">
				<rect x="4" y="8" width="16" height="12" rx="2"/>
				<line x1="9" y1="8" x2="9" y2="4"/><line x1="15" y1="8" x2="15" y2="4"/>
			</symbol>
			<symbol id="flexa-i-download" viewBox="0 0 24 24">
				<line x1="12" y1="3" x2="12" y2="14.5"/><polyline points="7.5 10 12 14.5 16.5 10"/>
				<path d="M4.5 18v2a1 1 0 0 0 1 1h13a1 1 0 0 0 1-1v-2"/>
			</symbol>
			<symbol id="flexa-i-update" viewBox="0 0 24 24">
				<path d="M20 12a8 8 0 1 1-2.4-5.7"/><polyline points="20 3 20 8 15 8"/>
			</symbol>
			<symbol id="flexa-i-power" viewBox="0 0 24 24">
				<path d="M7.6 6.6a8 8 0 1 0 8.8 0"/><line x1="12" y1="3" x2="12" y2="12"/>
			</symbol>
			<symbol id="flexa-i-check" viewBox="0 0 24 24">
				<polyline points="4.5 12.5 9.5 17.5 19.5 6.5"/>
			</symbol>
			<symbol id="flexa-i-lock" viewBox="0 0 24 24">
				<rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>
			</symbol>
			<symbol id="flexa-i-alert" viewBox="0 0 24 24">
				<circle cx="12" cy="12" r="9"/><line x1="12" y1="7.5" x2="12" y2="12.5"/>
				<line x1="12" y1="16.3" x2="12" y2="16.4"/>
			</symbol>
			<symbol id="flexa-i-bolt" viewBox="0 0 24 24">
				<polygon points="13 2 4.5 13.5 11 13.5 10 22 19.5 10.5 13 10.5"/>
			</symbol>
			<symbol id="flexa-i-box" viewBox="0 0 24 24">
				<path d="M3 8l9-5 9 5v8l-9 5-9-5z"/><path d="M3 8l9 5 9-5"/>
				<line x1="12" y1="13" x2="12" y2="21"/>
			</symbol>
			<symbol id="flexa-i-external" viewBox="0 0 24 24">
				<path d="M14 4h6v6"/><line x1="20" y1="4" x2="11.5" y2="12.5"/>
				<path d="M18 14.5V19a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h4.5"/>
			</symbol>
		</defs>
	</svg>
	<?php
}

/**
 * Print one icon from the sprite.
 *
 * @param string $name  Symbol name without the flexa-i- prefix.
 * @param string $extra Extra class, e.g. "flexa-ic-lg".
 */
function flexa_icon( $name, $extra = '' ) {
	$class = 'flexa-ic' . ( 'bolt' === $name ? ' flexa-ic-bolt' : '' ) . ( $extra ? ' ' . $extra : '' );

	printf(
		'<svg class="%s" aria-hidden="true" focusable="false"><use href="#flexa-i-%s"/></svg>',
		esc_attr( $class ),
		esc_attr( $name )
	);
}

/**
 * Render the page: heading, tab strip, then the active tab.
 */
function flexa_admin_render_page() {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'flexa' ) );
	}

	$tabs    = flexa_admin_tabs();
	$current = flexa_admin_current_tab();
	$theme   = wp_get_theme( get_template() );

	flexa_admin_icons();
	?>
	<div class="wrap flexa-admin">
		<h1>
			<?php
			printf(
				/* translators: %s: theme name, e.g. "Flexa". */
				esc_html__( '%s Theme', 'flexa' ),
				esc_html( $theme->get( 'Name' ) )
			);
			?>
		</h1>
		<p class="flexa-sub">
			<?php
			printf(
				/* translators: %s: theme version number. */
				esc_html__( 'Version %s', 'flexa' ),
				esc_html( $theme->get( 'Version' ) )
			);
			?>
		</p>

		<nav class="nav-tab-wrapper" aria-label="<?php esc_attr_e( 'Flexa sections', 'flexa' ); ?>">
			<?php foreach ( $tabs as $slug => $tab ) : ?>
				<a
					href="<?php echo esc_url( flexa_admin_url( $slug ) ); ?>"
					class="nav-tab<?php echo $slug === $current ? ' nav-tab-active' : ''; ?>"
					<?php echo $slug === $current ? 'aria-current="page"' : ''; ?>
				>
					<?php echo esc_html( $tab['label'] ); ?>
				</a>
			<?php endforeach; ?>
		</nav>

		<?php
		if ( isset( $tabs[ $current ] ) && is_callable( $tabs[ $current ]['render'] ) ) {
			call_user_func( $tabs[ $current ]['render'] );
		}
		?>
	</div>
	<?php
}
