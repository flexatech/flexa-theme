<?php
/**
 * Flexa plugins screen - one plugin card.
 *
 * @package Flexa
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render a single plugin card.
 *
 * @param array $plugin Normalized plugin entry from flexa_pi_fetch_plugins().
 */
function flexa_pi_render_card( $plugin ) {
	$state  = flexa_pi_get_state( $plugin );
	$status = $state['status'];

	// Without the capability there is nothing to offer, so fall back to the
	// plain installed/active view.
	if ( 'update' === $status && ! current_user_can( 'update_plugins' ) ) {
		$status = $state['active'] ? 'active' : 'inactive';
	}

	// Show the version actually installed, not the one on WordPress.org, so an
	// outdated plugin cannot look current.
	$shown_version = $state['installed'] ? $state['installed'] : $plugin['version'];
	?>
	<div class="flexa-pi-card">
		<div class="flexa-pi-head">
			<?php if ( $plugin['icon'] ) : ?>
				<img
					class="flexa-pi-icon"
					src="<?php echo esc_url( $plugin['icon'] ); ?>"
					alt=""
					width="56"
					height="56"
				/>
			<?php else : ?>
				<span class="flexa-pi-icon-empty" aria-hidden="true"></span>
			<?php endif; ?>

			<div>
				<h2 class="flexa-pi-title">
					<a href="<?php echo esc_url( $plugin['url'] ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $plugin['name'] ); ?>
					</a>
				</h2>

				<?php if ( $shown_version ) : ?>
					<span class="flexa-pi-version">
						<?php
						printf(
							/* translators: %s: plugin version number. */
							esc_html__( 'Version %s', 'flexa' ),
							esc_html( $shown_version )
						);
						?>

						<?php if ( 'update' === $status ) : ?>
							&rarr;
							<span class="flexa-pi-new"><?php echo esc_html( $plugin['version'] ); ?></span>
						<?php endif; ?>
					</span>
				<?php endif; ?>

				<?php if ( 'update' === $status ) : ?>
					<span class="flexa-pi-badge"><?php esc_html_e( 'Update available', 'flexa' ); ?></span>
				<?php elseif ( 'pro' === $status ) : ?>
					<span class="flexa-pi-badge is-pro"><?php esc_html_e( 'Pro version installed', 'flexa' ); ?></span>
				<?php elseif ( $state['needed_by_pro'] ) : ?>
					<span class="flexa-pi-badge is-pro"><?php esc_html_e( 'Required by the Pro version', 'flexa' ); ?></span>
				<?php endif; ?>
			</div>
		</div>

		<p class="flexa-pi-desc"><?php echo esc_html( $plugin['description'] ); ?></p>

		<div class="flexa-pi-foot">
			<?php flexa_pi_render_action( $plugin['slug'], $status ); ?>
			<span class="flexa-pi-msg"></span>
		</div>
	</div>
	<?php
}

/**
 * Render the action button for a card.
 *
 * @param string $slug   Plugin slug.
 * @param string $status install | inactive | active | update
 */
function flexa_pi_render_action( $slug, $status ) {
	// Nothing to offer for these two: an active plugin is already done, and the
	// free build must never be installed over a Pro one.
	if ( 'active' === $status || 'pro' === $status ) {
		?>
		<button type="button" class="button" disabled>
			<?php
			if ( 'pro' === $status ) {
				esc_html_e( 'Pro installed', 'flexa' );
			} else {
				esc_html_e( 'Activated', 'flexa' );
			}
			?>
		</button>
		<?php
		return;
	}

	$labels = array(
		'update'   => __( 'Update', 'flexa' ),
		'inactive' => __( 'Activate', 'flexa' ),
		'install'  => __( 'Install', 'flexa' ),
	);

	// 'inactive' means installed but off, so the action to offer is "activate".
	$action = 'inactive' === $status ? 'activate' : $status;
	$label  = isset( $labels[ $status ] ) ? $labels[ $status ] : $labels['install'];
	?>
	<button
		type="button"
		class="button button-primary flexa-pi-action"
		data-action="<?php echo esc_attr( $action ); ?>"
		data-slug="<?php echo esc_attr( $slug ); ?>"
	>
		<?php echo esc_html( $label ); ?>
	</button>
	<?php
}
