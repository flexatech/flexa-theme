/**
 * Flexa Theme admin page (Appearance -> Flexa Theme).
 *
 * Three jobs: dismiss the Getting started panel, filter the plugin cards as the
 * user types, and run install / activate / update over admin-ajax. The
 * flexaAdmin object comes from wp_localize_script(), see inc/admin/page.php.
 */

( function () {
	if ( typeof flexaAdmin === 'undefined' ) {
		return;
	}

	var i18n = flexaAdmin.i18n;

	function post( action, nonce, extra ) {
		var body = new FormData();
		body.append( 'action', action );
		body.append( 'nonce', nonce );

		Object.keys( extra || {} ).forEach( function ( key ) {
			body.append( key, extra[ key ] );
		} );

		return fetch( flexaAdmin.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		} ).then( function ( res ) {
			return res.json();
		} );
	}

	/** Fill %d / %1$d / %2$d / %s in a translated string. */
	function fill( template, a, b ) {
		return String( template )
			.replace( '%1$d', a ).replace( '%2$d', b )
			.replace( '%d', a ).replace( '%s', a );
	}

	/** Build one icon from the sprite already on the page. */
	function iconMarkup( name ) {
		return '<svg class="flexa-ic' + ( 'bolt' === name ? ' flexa-ic-bolt' : '' )
			+ '" aria-hidden="true" focusable="false"><use href="#flexa-i-' + name + '"/></svg>';
	}

	/** Swap a label while keeping the icon, which textContent would wipe. */
	function relabel( button, label, name ) {
		button.innerHTML = iconMarkup( name );
		button.appendChild( document.createTextNode( label ) );
	}

	function spin( button, label ) {
		button.disabled = true;
		button.innerHTML = '<span class="flexa-spinner"></span>';
		button.appendChild( document.createTextNode( label ) );
	}

	/* ---- Getting started ---------------------------------------------- */

	var dismiss = document.getElementById( 'flexa-dismiss-welcome' );

	if ( dismiss ) {
		dismiss.addEventListener( 'click', function () {
			var panel = document.getElementById( 'flexa-start' );

			// Hide at once; the request only records the choice.
			if ( panel ) {
				panel.classList.add( 'flexa-leaving' );
				setTimeout( function () {
					panel.remove();
				}, 200 );
			}

			post( 'flexa_dismiss_welcome', flexaAdmin.nonces.dismiss );
		} );
	}

	/* ---- Filter and search ---------------------------------------------- */

	var grid    = document.getElementById( 'flexa-grid' );
	var empty   = document.getElementById( 'flexa-empty' );
	var counter = document.getElementById( 'flexa-count' );
	var search  = document.getElementById( 'flexa-search' );
	var buttons = document.querySelectorAll( '.flexa-filter' );
	var badges  = document.querySelectorAll( '.flexa-fcount' );
	var bucket  = 'all';
	var wait;

	/** Cards, as a real array, in the order they were rendered. */
	function cards() {
		return grid ? Array.prototype.slice.call( grid.children ) : [];
	}

	/**
	 * Whether a card's status belongs to a filter bucket.
	 *
	 * "installed" is everything that is not waiting to be installed, so an
	 * inactive, active, locked or updatable plugin all count as installed.
	 */
	function inBucket( status, key ) {
		if ( 'all' === key ) {
			return true;
		}

		if ( 'installed' === key ) {
			return 'install' !== status;
		}

		return status === key;
	}

	/** Apply the active filter and the search box together. */
	function apply() {
		var query = search ? search.value.trim().toLowerCase() : '';
		var total = 0;
		var shown = 0;

		cards().forEach( function ( card ) {
			var hit = inBucket( card.getAttribute( 'data-status' ) || '', bucket )
				&& ( ! query
					|| ( card.getAttribute( 'data-search' ) || '' ).indexOf( query ) !== -1 );

			card.classList.toggle( 'hidden', ! hit );
			total++;

			if ( hit ) {
				shown++;
			}
		} );

		counter.textContent = shown === total
			? fill( i18n.countAll, total )
			: fill( i18n.countSome, shown, total );

		grid.classList.toggle( 'hidden', 0 === shown );
		empty.classList.toggle( 'hidden', 0 !== shown );
	}

	/**
	 * Recount the badges from the cards themselves.
	 *
	 * The page is not reloaded after an install or an update, so the numbers
	 * the server printed would otherwise go stale the moment a card changes.
	 */
	function recount() {
		var list = cards();

		Array.prototype.forEach.call( badges, function ( badge ) {
			var key = badge.getAttribute( 'data-count' );
			var n   = 0;

			list.forEach( function ( card ) {
				if ( inBucket( card.getAttribute( 'data-status' ) || '', key ) ) {
					n++;
				}
			} );

			badge.textContent = n;
		} );
	}

	if ( grid && counter ) {
		Array.prototype.forEach.call( buttons, function ( button ) {
			button.addEventListener( 'click', function () {
				bucket = button.getAttribute( 'data-filter' );

				Array.prototype.forEach.call( buttons, function ( other ) {
					other.setAttribute( 'aria-pressed', other === button ? 'true' : 'false' );
				} );

				apply();
			} );
		} );

		if ( search ) {
			search.addEventListener( 'input', function () {
				// Wait for a pause in typing rather than filtering on every keystroke.
				clearTimeout( wait );
				wait = setTimeout( apply, 150 );
			} );

			// Esc clears the field, the way WordPress list tables behave.
			search.addEventListener( 'keydown', function ( event ) {
				if ( 'Escape' === event.key && this.value ) {
					this.value = '';
					apply();
				}
			} );
		}
	}

	/* ---- Install / activate / update ------------------------------------ */

	var ENDPOINTS = {
		install: { action: 'flexa_pi_install', nonce: 'install', busy: 'installing', idle: 'install', icon: 'download' },
		activate: { action: 'flexa_pi_activate', nonce: 'activate', busy: 'activating', idle: 'activate', icon: 'power' },
		update: { action: 'flexa_pi_update', nonce: 'update', busy: 'updating', idle: 'update', icon: 'update' }
	};

	function markActive( card ) {
		var chip = card.querySelector( '.flexa-chip' );

		if ( ! chip ) {
			return;
		}

		chip.className = 'flexa-chip flexa-chip-active';
		chip.innerHTML = iconMarkup( 'check' );
		chip.appendChild( document.createTextNode( i18n.active ) );
	}

	function toActivate( button, card ) {
		button.setAttribute( 'data-action', 'activate' );
		relabel( button, i18n.activate, 'power' );
		button.disabled = false;
		setStatus( card, 'inactive' );
	}

	function toActivated( button, card ) {
		relabel( button, i18n.activated, 'check' );
		button.classList.remove( 'button-primary' );
		button.disabled = true;
		markActive( card );
		setStatus( card, 'active' );
	}

	/** Record the card's new state, then refresh the filter badges. */
	function setStatus( card, status ) {
		card.setAttribute( 'data-status', status );
		recount();
	}

	/** Move the card to whatever state the finished request leaves it in. */
	function settle( button, card, mode, data ) {
		if ( 'install' === mode ) {
			if ( flexaAdmin.canActivate ) {
				toActivate( button, card );
			} else {
				relabel( button, i18n.installed, 'lock' );
				setStatus( card, 'locked' );
			}

			return;
		}

		if ( 'update' === mode ) {
			var line = card.querySelector( '.flexa-cver' );

			// The arrow and the old number go; only the new version remains.
			if ( line && data && data.version ) {
				line.textContent = fill( i18n.version, data.version );
			}

			/*
			 * An update keeps whatever active state the plugin had before, which
			 * the server reports back so the button lands on the right label.
			 */
			if ( data && data.active ) {
				toActivated( button, card );
			} else {
				toActivate( button, card );
			}

			return;
		}

		toActivated( button, card );
	}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.flexa-act' );

		if ( ! button || button.disabled ) {
			return;
		}

		event.preventDefault();

		var mode     = button.getAttribute( 'data-action' );
		var endpoint = ENDPOINTS[ mode ];

		if ( ! endpoint ) {
			return;
		}

		var card = button.closest( '.flexa-card' );
		var msg  = card.querySelector( '.flexa-msg' );
		var slug = button.getAttribute( 'data-slug' );

		function fail( text ) {
			msg.textContent = text;
			msg.className = 'flexa-msg flexa-error flexa-shown';
			button.disabled = false;
			relabel( button, i18n[ endpoint.idle ], endpoint.icon );
		}

		spin( button, i18n[ endpoint.busy ] );
		msg.textContent = '';
		msg.className = 'flexa-msg';

		post( endpoint.action, flexaAdmin.nonces[ endpoint.nonce ], { slug: slug } )
			.then( function ( response ) {
				if ( ! response || ! response.success ) {
					fail(
						response && response.data && response.data.message
							? response.data.message
							: i18n.failed
					);

					return;
				}

				msg.textContent = response.data.message || '';
				msg.className = 'flexa-msg flexa-done flexa-shown';

				// A brief highlight so the eye finds the card that just changed.
				card.classList.add( 'flexa-flash' );
				setTimeout( function () {
					card.classList.remove( 'flexa-flash' );
				}, 1400 );

				settle( button, card, mode, response.data );
			} )
			.catch( function () {
				fail( i18n.failed );
			} );
	} );
}() );
