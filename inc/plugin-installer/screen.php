<?php
/**
 * Flexa plugins tab - page output and the refresh action.
 *
 * This feature does not own a menu entry. It is the Plugins tab of the Flexa
 * Theme page, which registers the menu, loads the assets and calls
 * flexa_pi_render_tab().
 *
 * @package Flexa
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// The refresh handler must run before any output, so it hangs off the tab's
// load action rather than the render callback.
add_action( 'flexa_admin_load_plugins', 'flexa_pi_handle_refresh' );

/**
 * Handle the "Refresh list" button before the page renders.
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
 * URL of this tab, optionally with extra query arguments.
 *
 * @param array $args Extra query arguments.
 * @return string
 */
function flexa_pi_page_url( $args = array() ) {
	return flexa_admin_url( 'plugins', $args );
}

/**
 * When the cached list was built, as a human sentence.
 *
 * The transient stores its own expiry, so the moment it was written is that
 * minus the lifetime - no extra record needed.
 *
 * @return string Empty when there is no cache.
 */
function flexa_pi_cached_at() {
	$expires = get_option( '_transient_timeout_' . FLEXA_PI_TRANSIENT );

	if ( ! $expires ) {
		return '';
	}

	return sprintf(
		/* translators: %s: how long ago the list was fetched, e.g. "2 hours". */
		__( 'Updated %s ago', 'flexa' ),
		human_time_diff( (int) $expires - FLEXA_PI_TTL, time() )
	);
}

/**
 * Order the list by what the user can act on first.
 *
 * Not installed comes before out of date: a missing plugin is a gap in the
 * set, an outdated one already works.
 *
 * @param array $a First plugin.
 * @param array $b Second plugin.
 * @return int
 */
function flexa_pi_sort( $a, $b ) {
	$rank = array( 'install' => 0, 'update' => 1, 'locked' => 2, 'pro' => 2, 'inactive' => 3, 'active' => 4 );

	$sa = flexa_pi_get_state( $a );
	$sb = flexa_pi_get_state( $b );
	$ra = isset( $rank[ $sa['status'] ] ) ? $rank[ $sa['status'] ] : 9;
	$rb = isset( $rank[ $sb['status'] ] ) ? $rank[ $sb['status'] ] : 9;

	return $ra === $rb ? strcasecmp( $a['name'], $b['name'] ) : $ra - $rb;
}

/**
 * How many plugins sit in each filter bucket.
 *
 * Counted server side so the buttons are already right on first paint; the
 * script keeps them in step afterwards, as cards change state without a reload.
 *
 * @param array $plugins Plugin list.
 * @return array<string,int>
 */
function flexa_pi_counts( $plugins ) {
	$counts = array(
		'all'       => count( $plugins ),
		'install'   => 0,
		'installed' => 0,
		'update'    => 0,
	);

	foreach ( $plugins as $plugin ) {
		$status = flexa_pi_get_state( $plugin )['status'];

		if ( 'install' === $status ) {
			++$counts['install'];
		} else {
			++$counts['installed'];
		}

		if ( 'update' === $status ) {
			++$counts['update'];
		}
	}

	return $counts;
}

/**
 * The filter buttons, in order.
 *
 * @return array<string,string> Bucket key => label.
 */
function flexa_pi_filters() {
	return array(
		'all'       => __( 'All', 'flexa' ),
		'install'   => __( 'Not installed', 'flexa' ),
		'installed' => __( 'Installed', 'flexa' ),
		'update'    => __( 'Update', 'flexa' ),
	);
}

/**
 * Render the Plugins tab.
 */
function flexa_pi_render_tab() {
	if ( ! current_user_can( 'install_plugins' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'flexa' ) );
	}

	$notice      = isset( $_GET['flexa_pi_notice'] ) ? sanitize_key( wp_unslash( $_GET['flexa_pi_notice'] ) ) : '';
	$refresh_url = wp_nonce_url( flexa_pi_page_url( array( 'flexa_pi_refresh' => 1 ) ), 'flexa_pi_refresh' );
	$plugins     = flexa_pi_fetch_plugins();
	$cached_at   = flexa_pi_cached_at();
	?>
	<div class="flexa-pi">
		<?php
		/*
		 * The list is fetched on page load rather than behind a confirmation
		 * step, so say plainly what leaves the site and where it goes. The
		 * request is made by plugins_api(), whose user agent carries home_url().
		 */
		?>
		<p class="flexa-intro">
			<?php esc_html_e( 'This list comes straight from WordPress.org. Opening this page connects your site to api.wordpress.org, which receives your site address and WordPress version. Nothing else is sent, and the result is cached here for 12 hours.', 'flexa' ); ?>
			<a href="https://wordpress.org/about/privacy/" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'WordPress.org privacy policy', 'flexa' ); ?>
			</a>
		</p>

		<?php if ( 'refreshed' === $notice ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Cache cleared and the plugin list reloaded.', 'flexa' ); ?></p>
			</div>
		<?php endif; ?>

		<?php if ( is_wp_error( $plugins ) ) : ?>
			<div class="notice notice-error inline">
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
			<div class="notice notice-warning inline">
				<p><?php esc_html_e( 'No plugins found for this author on WordPress.org.', 'flexa' ); ?></p>
			</div>
		<?php else : ?>
			<?php
			$count  = count( $plugins );
			$counts = flexa_pi_counts( $plugins );
			?>

			<div class="flexa-pbar">
				<div class="flexa-filters" role="group" aria-label="<?php esc_attr_e( 'Filter plugins', 'flexa' ); ?>">
					<?php foreach ( flexa_pi_filters() as $key => $label ) : ?>
						<button
							type="button"
							class="flexa-filter"
							data-filter="<?php echo esc_attr( $key ); ?>"
							aria-pressed="<?php echo 'all' === $key ? 'true' : 'false'; ?>"
						>
							<?php echo esc_html( $label ); ?>
							<span class="flexa-fcount" data-count="<?php echo esc_attr( $key ); ?>">
								<?php echo absint( $counts[ $key ] ); ?>
							</span>
						</button>
					<?php endforeach; ?>
				</div>

				<div class="flexa-pbar-right">
					<label class="screen-reader-text" for="flexa-search">
						<?php esc_html_e( 'Search plugins', 'flexa' ); ?>
					</label>
					<input
						type="search"
						id="flexa-search"
						class="flexa-search"
						placeholder="<?php esc_attr_e( 'Search plugins…', 'flexa' ); ?>"
					>

					<a href="<?php echo esc_url( $refresh_url ); ?>" class="button">
						<?php flexa_icon( 'update' ); ?><?php esc_html_e( 'Refresh list', 'flexa' ); ?>
					</a>
				</div>
			</div>

			<p class="flexa-meta flexa-pmeta">
				<b id="flexa-count">
					<?php
					printf(
						/* translators: %d: number of plugins. */
						esc_html( _n( '%d plugin', '%d plugins', $count, 'flexa' ) ),
						absint( $count )
					);
					?>
				</b>
				<?php if ( $cached_at ) : ?>
					&middot; <?php echo esc_html( $cached_at ); ?>
				<?php endif; ?>
			</p>

			<div class="flexa-grid" id="flexa-grid">
				<?php
				usort( $plugins, 'flexa_pi_sort' );

				foreach ( $plugins as $plugin ) {
					flexa_pi_render_card( $plugin );
				}
				?>
			</div>

			<div class="flexa-empty hidden" id="flexa-empty">
				<b><?php esc_html_e( 'Nothing to show here.', 'flexa' ); ?></b>
				<?php esc_html_e( 'Try another filter, or clear the search field.', 'flexa' ); ?>
			</div>
		<?php endif; ?>
	</div>
	<?php
}
