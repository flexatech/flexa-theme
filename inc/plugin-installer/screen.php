<?php
/**
 * Flexa plugins screen - menu registration and page output.
 *
 * @package Flexa
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the submenu page under Appearance.
 */
function flexa_pi_admin_menu() {
	$hook = add_submenu_page(
		'themes.php',
		esc_html__( 'Flexa Plugins', 'flexa' ),
		esc_html__( 'Flexa Plugins', 'flexa' ),
		'install_plugins',
		FLEXA_PI_PAGE,
		'flexa_pi_render_page'
	);

	if ( ! $hook ) {
		return;
	}

	add_action( 'load-' . $hook, 'flexa_pi_handle_refresh' );

	add_action(
		'admin_enqueue_scripts',
		static function ( $current ) use ( $hook ) {
			if ( $current === $hook ) {
				flexa_pi_enqueue_assets();
			}
		}
	);
}
add_action( 'admin_menu', 'flexa_pi_admin_menu' );

/**
 * Handle the "Refresh list" button before the page renders.
 *
 * Runs on load-{$hook} so wp_safe_redirect() is still usable.
 */
function flexa_pi_handle_refresh() {
	if ( ! isset( $_GET['flexa_pi_refresh'] ) ) {
		return;
	}

	if ( ! current_user_can( 'install_plugins' ) ) {
		return;
	}

	$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'flexa_pi_refresh' ) ) {
		wp_die( esc_html__( 'This link has expired. Please try again.', 'flexa' ) );
	}

	delete_transient( FLEXA_PI_TRANSIENT );

	wp_safe_redirect( flexa_pi_page_url( array( 'flexa_pi_notice' => 'refreshed' ) ) );
	exit;
}

/**
 * URL of this screen, optionally with extra query arguments.
 *
 * @param array $args Extra query arguments.
 * @return string
 */
function flexa_pi_page_url( $args = array() ) {
	return add_query_arg(
		array_merge( array( 'page' => FLEXA_PI_PAGE ), $args ),
		admin_url( 'themes.php' )
	);
}

/**
 * Render the admin screen.
 */
function flexa_pi_render_page() {
	if ( ! current_user_can( 'install_plugins' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'flexa' ) );
	}

	$notice      = isset( $_GET['flexa_pi_notice'] ) ? sanitize_key( wp_unslash( $_GET['flexa_pi_notice'] ) ) : '';
	$refresh_url = wp_nonce_url( flexa_pi_page_url( array( 'flexa_pi_refresh' => 1 ) ), 'flexa_pi_refresh' );
	$plugins     = flexa_pi_fetch_plugins();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Flexa Plugins', 'flexa' ); ?></h1>

		<?php
		/*
		 * The list is fetched on page load rather than behind a confirmation
		 * step, so say plainly what leaves the site and where it goes. The
		 * request is made by plugins_api(), whose user agent carries home_url().
		 */
		?>
		<p class="flexa-pi-intro">
			<?php esc_html_e( 'This list comes straight from WordPress.org. Opening this page connects your site to api.wordpress.org, which receives your site address and WordPress version. Nothing else is sent, and the result is cached here for 12 hours.', 'flexa' ); ?>
			<a href="https://wordpress.org/about/privacy/" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'WordPress.org privacy policy', 'flexa' ); ?>
			</a>
		</p>

		<p>
			<a href="<?php echo esc_url( $refresh_url ); ?>" class="button">
				<?php esc_html_e( 'Refresh list', 'flexa' ); ?>
			</a>
		</p>

		<?php if ( 'refreshed' === $notice ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Cache cleared and the plugin list reloaded.', 'flexa' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( is_wp_error( $plugins ) ) : ?>
			<div class="notice notice-error">
				<p>
					<?php
					printf(
						/* translators: %s: error message returned by WordPress.org. */
						esc_html__( 'Could not load the plugin list: %s', 'flexa' ),
						esc_html( $plugins->get_error_message() )
					);
					?>
				</p>
			</div>
		<?php elseif ( empty( $plugins ) ) : ?>
			<div class="notice notice-warning">
				<p><?php esc_html_e( 'No plugins found for this author on WordPress.org.', 'flexa' ); ?></p>
			</div>
		<?php else : ?>
			<div class="flexa-pi-grid">
				<?php foreach ( $plugins as $plugin ) : ?>
					<?php flexa_pi_render_card( $plugin ); ?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}
