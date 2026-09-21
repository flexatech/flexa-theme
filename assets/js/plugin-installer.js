/**
 * Flexa plugins screen (Appearance -> Flexa Plugins).
 *
 * Drives the install / activate / update buttons over admin-ajax. The flexaPi
 * object is provided by wp_localize_script(), see inc/plugin-installer/assets.php.
 */

( function () {
	var grid = document.querySelector( '.flexa-pi-grid' );

	if ( ! grid || typeof flexaPi === 'undefined' ) {
		return;
	}

	var ENDPOINTS = {
		install: { action: 'flexa_pi_install', nonce: 'installNonce', busy: 'installing', idle: 'install' },
		activate: { action: 'flexa_pi_activate', nonce: 'activateNonce', busy: 'activating', idle: 'activate' },
		update: { action: 'flexa_pi_update', nonce: 'updateNonce', busy: 'updating', idle: 'update' }
	};

	function post( action, slug, nonce ) {
		var body = new FormData();
		body.append( 'action', action );
		body.append( 'slug', slug );
		body.append( 'nonce', nonce );

		return fetch( flexaPi.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		} ).then( function ( res ) {
			return res.json();
		} );
	}

	function toActivate( button ) {
		button.setAttribute( 'data-action', 'activate' );
		button.textContent = flexaPi.i18n.activate;
		button.disabled = false;
	}

	function toActivated( button ) {
		button.textContent = flexaPi.i18n.activated;
		button.disabled = true;
		button.classList.remove( 'button-primary' );
	}

	/**
	 * Move the card to whatever state the finished request leaves it in.
	 */
	function settle( button, card, mode, data ) {
		if ( 'install' === mode ) {
			if ( flexaPi.canActivate ) {
				toActivate( button );
			} else {
				button.textContent = flexaPi.i18n.install;
			}

			return;
		}

		if ( 'update' === mode ) {
			var badge = card.querySelector( '.flexa-pi-badge' );
			var version = card.querySelector( '.flexa-pi-version' );

			if ( badge ) {
				badge.remove();
			}

			if ( version && data && data.version ) {
				version.textContent = flexaPi.i18n.version.replace( '%s', data.version );
			}

			// An update keeps whatever active state the plugin had before, which
			// the server reports back so the button lands on the right label.
			if ( data && data.active ) {
				toActivated( button );
			} else {
				toActivate( button );
			}

			return;
		}

		toActivated( button );
	}

	grid.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.flexa-pi-action' );

		if ( ! button || button.disabled ) {
			return;
		}

		event.preventDefault();

		var mode = button.getAttribute( 'data-action' );
		var endpoint = ENDPOINTS[ mode ];

		if ( ! endpoint ) {
			return;
		}

		var card = button.closest( '.flexa-pi-card' );
		var msg = card.querySelector( '.flexa-pi-msg' );
		var slug = button.getAttribute( 'data-slug' );

		function fail( text ) {
			msg.textContent = text;
			msg.className = 'flexa-pi-msg is-error';
			button.disabled = false;
			button.textContent = flexaPi.i18n[ endpoint.idle ];
		}

		button.disabled = true;
		button.textContent = flexaPi.i18n[ endpoint.busy ];
		msg.textContent = '';
		msg.className = 'flexa-pi-msg';

		post( endpoint.action, slug, flexaPi[ endpoint.nonce ] ).then( function ( response ) {
			if ( ! response || ! response.success ) {
				fail(
					response && response.data && response.data.message
						? response.data.message
						: flexaPi.i18n.failed
				);

				return;
			}

			msg.textContent = response.data.message || '';
			msg.className = 'flexa-pi-msg is-ok';

			settle( button, card, mode, response.data );
		} ).catch( function () {
			fail( flexaPi.i18n.failed );
		} );
	} );
}() );
