<?php
/**
 * Flexa plugins tab - one plugin card.
 *
 * @package Flexa
 * @since   1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A stable colour for a plugin, derived from its slug.
 *
 * Icons are not downloaded from WordPress.org: guideline 9 forbids loading
 * images from another domain, so each plugin gets a monogram instead. Deriving
 * the colour from the slug keeps it the same on every visit.
 *
 * @param string $slug Plugin slug.
 * @return string Hex colour.
 */
function flexa_pi_colour( $slug ) {
	$palette = array( '#0F92F7', '#3858e9', '#00786c', '#8c5e00', '#993955', '#4f5d75', '#1a6b3c', '#6d4aa7' );
	$hash    = 0;

	for ( $i = 0, $len = strlen( $slug ); $i < $len; $i++ ) {
		$hash = ( $hash * 31 + ord( $slug[ $i ] ) ) % 4294967296;
	}

	return $palette[ $hash % count( $palette ) ];
}

/**
 * The one or two letters shown in the monogram.
 *
 * @param string $name Plugin name.
 * @return string
 */
function flexa_pi_initials( $name ) {
	$words = preg_split( '/\s+/', trim( preg_replace( '/[^A-Za-z ]/', ' ', $name ) ), -1, PREG_SPLIT_NO_EMPTY );

	if ( empty( $words ) ) {
		return '?';
	}

	$out = substr( $words[0], 0, 1 );

	if ( isset( $words[1] ) ) {
		$out .= substr( $words[1], 0, 1 );
	}

	return strtoupper( $out );
}

/**
 * Status chip for a state: CSS class, label and icon.
 *
 * @param string $status install | inactive | active | update | pro | locked
 * @return array{0:string,1:string,2:string}|null Null when no chip is shown.
 */
function flexa_pi_chip( $status ) {
	$chips = array(
		'active'   => array( 'flexa-chip-active', __( 'Active', 'flexa' ), 'check' ),
		'inactive' => array( 'flexa-chip-inactive', __( 'Inactive', 'flexa' ), 'power' ),
		'update'   => array( 'flexa-chip-update', __( 'Update available', 'flexa' ), 'alert' ),
		'pro'      => array( 'flexa-chip-pro', __( 'Pro version installed', 'flexa' ), 'bolt' ),
		'locked'   => array( 'flexa-chip-inactive', __( 'Installed', 'flexa' ), 'lock' ),
	);

	return isset( $chips[ $status ] ) ? $chips[ $status ] : null;
}

/**
 * Render a single plugin card.
 *
 * @param array $plugin Normalized plugin entry from flexa_pi_fetch_plugins().
 */
function flexa_pi_render_card( $plugin ) {
	$state  = flexa_pi_get_state( $plugin );
	$status = $state['status'];

	/*
	 * Without the capability there is nothing to offer, so show the state
	 * rather than a button that would come back with a 403.
	 */
	if ( 'update' === $status && ! current_user_can( 'update_plugins' ) ) {
		$status = $state['active'] ? 'active' : 'locked';
	}

	if ( 'inactive' === $status && ! current_user_can( 'activate_plugins' ) ) {
		$status = 'locked';
	}

	// Show the version actually installed, not the one on WordPress.org, so an
	// outdated plugin cannot look current.
	$shown = $state['installed'] ? $state['installed'] : $plugin['version'];
	$chip  = flexa_pi_chip( $status );

	// Searching happens in the browser; this is what it matches against.
	$haystack = strtolower( $plugin['name'] . ' ' . $plugin['description'] );
	?>
	<div class="flexa-card" data-status="<?php echo esc_attr( $status ); ?>" data-search="<?php echo esc_attr( $haystack ); ?>">
		<div class="flexa-chead">
			<span class="flexa-mono" style="background:<?php echo esc_attr( flexa_pi_colour( $plugin['slug'] ) ); ?>" aria-hidden="true">
				<?php echo esc_html( flexa_pi_initials( $plugin['name'] ) ); ?>
				<?php if ( ! empty( $plugin['icon'] ) ) : ?>
					<?php
					/*
					 * The icon sits on top of the monogram rather than replacing it,
					 * so a plugin whose icon 404s or is blocked still shows its
					 * letters instead of an empty square. onerror drops the image
					 * the moment it fails, which is before any script could run.
					 */
					?>
					<img src="<?php echo esc_url( $plugin['icon'] ); ?>" alt="" loading="lazy" decoding="async" onerror="this.remove()">
				<?php endif; ?>
			</span>

			<div class="flexa-chead-text">
				<h3 class="flexa-ctitle">
					<a href="<?php echo esc_url( $plugin['url'] ); ?>" target="_blank" rel="noopener noreferrer">
						<?php echo esc_html( $plugin['name'] ); ?>
					</a>
				</h3>

				<span class="flexa-cver">
					<?php
					printf(
						/* translators: %s: plugin version number. */
						esc_html__( 'Version %s', 'flexa' ),
						esc_html( $shown )
					);
					?>
					<?php if ( 'update' === $status ) : ?>
						&rarr; <span class="flexa-new"><?php echo esc_html( $plugin['version'] ); ?></span>
					<?php endif; ?>
				</span>

				<?php if ( $chip ) : ?>
					<span class="flexa-chip <?php echo esc_attr( $chip[0] ); ?>">
						<?php
						if ( $chip[2] ) {
							flexa_icon( $chip[2] );
						}
						echo esc_html( $chip[1] );
						?>
					</span>
				<?php endif; ?>
			</div>
		</div>

		<p class="flexa-cdesc"><?php echo esc_html( $plugin['description'] ); ?></p>

		<div class="flexa-cfoot">
			<?php flexa_pi_render_action( $plugin['slug'], $status ); ?>
			<span class="flexa-msg" role="status"></span>
		</div>
	</div>
	<?php
}

/**
 * Render the action button for a card.
 *
 * @param string $slug   Plugin slug.
 * @param string $status install | inactive | active | update | pro | locked
 */
function flexa_pi_render_action( $slug, $status ) {
	$done = array(
		'active' => array( __( 'Activated', 'flexa' ), 'check' ),
		'pro'    => array( __( 'Pro installed', 'flexa' ), 'bolt' ),
		'locked' => array( __( 'Installed', 'flexa' ), 'lock' ),
	);

	// Nothing left for this user to do here.
	if ( isset( $done[ $status ] ) ) {
		?>
		<button type="button" class="button" disabled>
			<?php flexa_icon( $done[ $status ][1] ); ?><?php echo esc_html( $done[ $status ][0] ); ?>
		</button>
		<?php
		return;
	}

	$actions = array(
		'update'   => array( __( 'Update', 'flexa' ), 'update', 'update' ),
		'inactive' => array( __( 'Activate', 'flexa' ), 'power', 'activate' ),
		'install'  => array( __( 'Install', 'flexa' ), 'download', 'install' ),
	);

	$action = isset( $actions[ $status ] ) ? $actions[ $status ] : $actions['install'];
	?>
	<button
		type="button"
		class="button button-primary flexa-act"
		data-action="<?php echo esc_attr( $action[2] ); ?>"
		data-slug="<?php echo esc_attr( $slug ); ?>"
	>
		<?php flexa_icon( $action[1] ); ?><?php echo esc_html( $action[0] ); ?>
	</button>
	<?php
}
