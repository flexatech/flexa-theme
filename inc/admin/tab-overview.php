<?php
/**
 * Flexa admin - Overview tab.
 *
 * Everything here is read from the theme itself or links to a core screen, so
 * nothing can drift out of date or point somewhere that does not exist.
 *
 * @package Flexa
 * @since   1.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Count the files of one kind the theme ships.
 *
 * @param string $dir     Folder under the theme root.
 * @param string $pattern Glob pattern inside it.
 * @return int
 */
function flexa_admin_count( $dir, $pattern ) {
	$found = glob( get_template_directory() . '/' . $dir . '/' . $pattern );

	return is_array( $found ) ? count( $found ) : 0;
}

/**
 * Plugin totals for the summary card.
 *
 * Reads the cached list only - never triggers a request to WordPress.org. The
 * Plugins tab is where that call belongs, and where it is disclosed, so the
 * Overview tab must not quietly make one. With no cache yet, the card is hidden.
 *
 * @return array{total:int,installed:int,updates:int}|null
 */
function flexa_admin_plugin_totals() {
	$cached = get_transient( FLEXA_PI_TRANSIENT );

	if ( ! is_array( $cached ) || empty( $cached ) ) {
		return null;
	}

	$totals = array(
		'total'     => count( $cached ),
		'installed' => 0,
		'updates'   => 0,
	);

	foreach ( $cached as $plugin ) {
		$state = flexa_pi_get_state( $plugin );

		if ( 'install' !== $state['status'] ) {
			++$totals['installed'];
		}

		if ( 'update' === $state['status'] ) {
			++$totals['updates'];
		}
	}

	return $totals;
}

/**
 * Render the Overview tab.
 */
function flexa_admin_render_overview() {
	$theme  = wp_get_theme( get_template() );
	$user   = wp_get_current_user();
	$parts  = flexa_admin_count( 'parts', '*.html' );
	$totals = flexa_admin_plugin_totals();
	?>
	<div class="flexa-hero">
		<div class="flexa-hero-main">
			<div class="flexa-hero-text">
				<p class="flexa-greet">
					<?php
					printf(
						/* translators: %s: current user's display name. */
						esc_html__( 'Hi %s', 'flexa' ),
						esc_html( $user->display_name )
					);
					?>
					&#128075;
				</p>

				<h2>
					<?php
					printf(
						/* translators: %s: theme name. */
						esc_html__( 'Welcome to %s', 'flexa' ),
						esc_html( $theme->get( 'Name' ) )
					);
					?>
				</h2>

				<p class="flexa-desc"><?php echo esc_html( $theme->get( 'Description' ) ); ?></p>

				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'site-editor.php' ) ); ?>">
					<?php flexa_icon( 'layout' ); ?><?php esc_html_e( 'Start customising', 'flexa' ); ?>
				</a>
			</div>

			<div class="flexa-hero-art" aria-hidden="true">
				<svg viewBox="0 0 180 124" width="180" height="124" focusable="false">
					<rect x="0"   y="0"   width="180" height="124" rx="6" fill="#f6f7f7"/>
					<rect x="12"  y="12"  width="156" height="15"  rx="3" fill="#e6e8ea"/>
					<rect x="12"  y="12"  width="40"  height="15"  rx="3" fill="#0F92F7"/>
					<rect x="12"  y="37"  width="96"  height="9"   rx="3" fill="#3c434a"/>
					<rect x="12"  y="52"  width="132" height="6"   rx="3" fill="#e6e8ea"/>
					<rect x="12"  y="64"  width="112" height="6"   rx="3" fill="#e6e8ea"/>
					<rect x="12"  y="79"  width="48"  height="17"  rx="4" fill="#0F92F7"/>
					<rect x="118" y="37"  width="50"  height="59"  rx="4" fill="#e6e8ea"/>
					<rect x="12"  y="105" width="156" height="7"   rx="3" fill="#e6e8ea"/>
				</svg>
			</div>
		</div>

		<?php if ( $totals && current_user_can( 'install_plugins' ) ) : ?>
			<?php
			$missing = $totals['total'] - $totals['installed'];
			$percent = $totals['total'] ? round( $totals['installed'] / $totals['total'] * 100 ) : 0;
			?>
			<aside class="flexa-hero-side">
				<h3 class="flexa-side-head">
					<?php flexa_icon( 'box', 'flexa-ic-lg' ); ?>
					<?php esc_html_e( 'Flexa plugins', 'flexa' ); ?>
				</h3>

				<div class="flexa-side-figure">
					<b><?php echo absint( $totals['installed'] ); ?></b>
					<span>
						<?php
						printf(
							/* translators: %d: total number of plugins. */
							esc_html__( 'of %d installed', 'flexa' ),
							absint( $totals['total'] )
						);
						?>
					</span>
				</div>

				<div
					class="flexa-meter"
					role="img"
					aria-label="
					<?php
					printf(
						/* translators: %d: percentage installed. */
						esc_attr__( '%d%% installed', 'flexa' ),
						absint( $percent )
					);
					?>
					"
				>
					<i style="width:<?php echo absint( $percent ); ?>%"></i>
				</div>

				<?php if ( ! $totals['updates'] && ! $missing ) : ?>
					<p class="flexa-side-ok">
						<?php flexa_icon( 'check' ); ?><?php esc_html_e( 'All up to date', 'flexa' ); ?>
					</p>
				<?php else : ?>
					<ul class="flexa-side-list">
						<?php if ( $totals['updates'] ) : ?>
							<li>
								<span class="flexa-dot flexa-dot-amber"></span>
								<span>
									<?php
									printf(
										/* translators: %s: number of plugins with updates. */
										esc_html( _n( '%s update available', '%s updates available', $totals['updates'], 'flexa' ) ),
										'<b>' . absint( $totals['updates'] ) . '</b>'
									);
									?>
								</span>
							</li>
						<?php endif; ?>

						<?php if ( $missing ) : ?>
							<li>
								<span class="flexa-dot flexa-dot-grey"></span>
								<span>
									<?php
									printf(
										/* translators: %s: number of plugins not installed. */
										esc_html__( '%s not installed', 'flexa' ),
										'<b>' . absint( $missing ) . '</b>'
									);
									?>
								</span>
							</li>
						<?php endif; ?>
					</ul>
				<?php endif; ?>

				<a class="button" href="<?php echo esc_url( flexa_admin_url( 'plugins' ) ); ?>">
					<?php flexa_icon( 'plugin' ); ?><?php esc_html_e( 'View plugins', 'flexa' ); ?>
				</a>
			</aside>
		<?php endif; ?>
	</div>

	<?php if ( ! flexa_get_setting( 'welcome_hidden' ) ) : ?>
		<div class="flexa-start" id="flexa-start">
			<button
				type="button"
				class="flexa-dismiss"
				id="flexa-dismiss-welcome"
				aria-label="<?php esc_attr_e( 'Dismiss permanently', 'flexa' ); ?>"
			>&times;</button>

			<b><?php esc_html_e( 'Three steps to a finished site', 'flexa' ); ?></b>

			<div class="flexa-steps">
				<div class="flexa-step">
					<span class="flexa-step-n">1</span>
					<div>
						<h3><?php esc_html_e( 'Name and logo', 'flexa' ); ?></h3>
						<p><?php esc_html_e( 'Site title, tagline and icon.', 'flexa' ); ?></p>
						<a class="button" href="<?php echo esc_url( admin_url( 'options-general.php' ) ); ?>">
							<?php flexa_icon( 'settings' ); ?><?php esc_html_e( 'General settings', 'flexa' ); ?>
						</a>
					</div>
				</div>

				<div class="flexa-step">
					<span class="flexa-step-n">2</span>
					<div>
						<h3><?php esc_html_e( 'Edit header &amp; footer', 'flexa' ); ?></h3>
						<p>
							<?php
							printf(
								/* translators: %d: number of template parts. */
								esc_html( _n( '%d template part, edited in the Site Editor.', '%d template parts, edited in the Site Editor.', $parts, 'flexa' ) ),
								absint( $parts )
							);
							?>
						</p>
						<a class="button" href="<?php echo esc_url( admin_url( 'site-editor.php' ) ); ?>">
							<?php flexa_icon( 'layout' ); ?><?php esc_html_e( 'Open Site Editor', 'flexa' ); ?>
						</a>
					</div>
				</div>

				<div class="flexa-step">
					<span class="flexa-step-n">3</span>
					<div>
						<h3><?php esc_html_e( 'Install plugins', 'flexa' ); ?></h3>
						<p><?php esc_html_e( 'Add features from the theme author\'s plugins.', 'flexa' ); ?></p>
						<?php if ( current_user_can( 'install_plugins' ) ) : ?>
							<a class="button" href="<?php echo esc_url( flexa_admin_url( 'plugins' ) ); ?>">
								<?php flexa_icon( 'plugin' ); ?><?php esc_html_e( 'View plugins', 'flexa' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>
			</div>
		</div>
	<?php endif; ?>

	<p class="flexa-colophon">
		<?php echo esc_html( $theme->get( 'Name' ) . ' ' . $theme->get( 'Version' ) ); ?>
		<?php if ( $theme->get( 'License' ) ) : ?>
			&middot; <?php echo esc_html( $theme->get( 'License' ) ); ?>
		<?php endif; ?>
		<?php if ( $theme->get( 'AuthorURI' ) ) : ?>
			&middot;
			<a href="<?php echo esc_url( $theme->get( 'AuthorURI' ) ); ?>" target="_blank" rel="noopener noreferrer">
				<?php echo esc_html( wp_strip_all_tags( $theme->get( 'Author' ) ) ); ?><?php flexa_icon( 'external' ); ?>
			</a>
		<?php endif; ?>
		<?php if ( current_user_can( 'view_site_health_checks' ) ) : ?>
			&middot;
			<a href="<?php echo esc_url( admin_url( 'site-health.php?tab=debug' ) ); ?>">
				<?php esc_html_e( 'System information', 'flexa' ); ?>
			</a>
		<?php endif; ?>
	</p>
	<?php
}
