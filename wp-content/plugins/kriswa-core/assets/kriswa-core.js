/* Kriswa Core front-end behaviour. Plain JavaScript, no dependencies. Everything works without JS too. */
( function () {
	'use strict';

	document.documentElement.classList.add( 'js' );
	var cfg = window.kriswaCore || { ajaxUrl: '/wp-admin/admin-ajax.php', i18n: {} };
	var t = cfg.i18n || {};

	/* ---------- Project filter ---------- */
	document.querySelectorAll( '[data-kr-projects]' ).forEach( function ( wrap ) {
		var bar = wrap.querySelector( '.kr-filter' );
		if ( ! bar ) { return; }
		bar.hidden = false;
		var cards = wrap.querySelectorAll( '.kr-project' );
		bar.addEventListener( 'click', function ( e ) {
			var btn = e.target.closest( '.kr-filter__btn' );
			if ( ! btn ) { return; }
			var f = btn.getAttribute( 'data-filter' );
			bar.querySelectorAll( '.kr-filter__btn' ).forEach( function ( b ) {
				var on = b === btn;
				b.classList.toggle( 'is-active', on );
				b.setAttribute( 'aria-pressed', on ? 'true' : 'false' );
			} );
			cards.forEach( function ( c ) {
				var types = ( c.getAttribute( 'data-types' ) || '' ).split( ' ' );
				c.classList.toggle( 'is-hidden', f !== '*' && types.indexOf( f ) === -1 );
			} );
		} );
	} );

	/* ---------- Before / after slider ---------- */
	document.querySelectorAll( '[data-kr-ba]' ).forEach( function ( ba ) {
		var range = ba.querySelector( '.kr-ba__range' );
		var set = function () { ba.style.setProperty( '--pos', range.value + '%' ); };
		range.addEventListener( 'input', set );
		set();
	} );

	/* ---------- Animated counters ---------- */
	var counters = document.querySelectorAll( '[data-kr-count]' );
	if ( counters.length && 'IntersectionObserver' in window && ! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
		var digits = [ '०', '१', '२', '३', '४', '५', '६', '७', '८', '९' ];
		var io = new IntersectionObserver( function ( entries ) {
			entries.forEach( function ( en ) {
				if ( ! en.isIntersecting ) { return; }
				io.unobserve( en.target );
				var el = en.target;
				var end = parseInt( el.getAttribute( 'data-kr-count' ), 10 ) || 0;
				var suffix = el.getAttribute( 'data-suffix' ) || '';
				var ne = document.documentElement.lang.indexOf( 'ne' ) === 0;
				var start = null;
				var fmt = function ( n ) {
					var s = String( n ) + suffix;
					return ne ? s.replace( /\d/g, function ( d ) { return digits[ d ]; } ) : s;
				};
				var step = function ( ts ) {
					start = start || ts;
					var p = Math.min( ( ts - start ) / 1400, 1 );
					el.textContent = fmt( Math.round( end * ( 1 - Math.pow( 1 - p, 3 ) ) ) );
					if ( p < 1 ) { requestAnimationFrame( step ); }
				};
				requestAnimationFrame( step );
			} );
		}, { threshold: .4 } );
		counters.forEach( function ( c ) { io.observe( c ); } );
	}

	/* ---------- Newsletter ---------- */
	document.querySelectorAll( '[data-kr-news]' ).forEach( function ( form ) {
		var msg = form.querySelector( '.kr-news__msg' );
		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			fetch( cfg.ajaxUrl, { method: 'POST', body: new FormData( form ), credentials: 'same-origin' } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( d ) {
					msg.textContent = d.message || '';
					if ( d.ok ) { form.reset(); }
				} )
				.catch( function () { msg.textContent = t.error || ''; } );
		} );
	} );

	/* ---------- Multi-step quote form ---------- */
	document.querySelectorAll( '[data-kr-quote]' ).forEach( function ( box ) {
		var form = box.querySelector( 'form' );
		if ( ! form ) { return; }
		var steps = Array.prototype.slice.call( form.querySelectorAll( '.kr-step' ) );
		var tabs = form.querySelectorAll( '.kr-steps li' );
		var prev = form.querySelector( '[data-prev]' );
		var next = form.querySelector( '[data-next]' );
		var submit = form.querySelector( '[data-submit]' );
		var errBox = form.querySelector( '.kr-form__error' );
		var cur = 0;

		prev.hidden = false;
		next.hidden = false;

		function show( i ) {
			cur = i;
			steps.forEach( function ( s, n ) { s.classList.toggle( 'is-active', n === i ); } );
			tabs.forEach( function ( tb, n ) {
				tb.classList.toggle( 'is-active', n === i );
				tb.classList.toggle( 'is-done', n < i );
			} );
			prev.style.visibility = i === 0 ? 'hidden' : 'visible';
			form.classList.toggle( 'on-last', i === steps.length - 1 );
			var first = steps[ i ].querySelector( 'input,select,textarea' );
			if ( first && i > 0 ) { first.focus( { preventScroll: false } ); }
		}

		function setErr( name, text ) {
			var holder = form.querySelector( '[data-err="' + name + '"]' );
			if ( holder ) {
				holder.textContent = text || '';
				var field = holder.closest( '.kr-field' );
				if ( field ) { field.classList.toggle( 'has-error', !! text ); }
			}
		}

		function validate( scope ) {
			var ok = true;
			var rules = {
				kr_service: function ( v ) { return v ? '' : t.required; },
				kr_name: function ( v ) { return v.trim().length > 1 ? '' : t.required; },
				kr_phone: function ( v ) { var d = v.replace( /\D/g, '' ); return ( d.length >= 7 && d.length <= 15 ) ? '' : t.phone; },
				kr_email: function ( v ) { return ( ! v || /^[^@\s]+@[^@\s]+\.[^@\s]+$/.test( v ) ) ? '' : t.email; }
			};
			Object.keys( rules ).forEach( function ( name ) {
				var el = scope.querySelector( '[name="' + name + '"]' );
				if ( ! el ) { return; }
				var msg = rules[ name ]( el.value );
				setErr( name, msg );
				if ( msg ) { ok = false; }
			} );
			var consent = scope.querySelector( '[name="kr_consent"]' );
			if ( consent ) {
				setErr( 'kr_consent', consent.checked ? '' : t.required );
				if ( ! consent.checked ) { ok = false; }
			}
			if ( ! ok ) {
				var bad = scope.querySelector( '.has-error input, .has-error select' );
				if ( bad ) { bad.focus(); }
			}
			return ok;
		}

		next.addEventListener( 'click', function () {
			if ( validate( steps[ cur ] ) ) { show( cur + 1 ); }
		} );
		prev.addEventListener( 'click', function () { show( cur - 1 ); } );
		show( 0 );

		form.addEventListener( 'submit', function ( e ) {
			e.preventDefault();
			if ( ! validate( form ) ) {
				// Jump back to the first step that has an error.
				for ( var i = 0; i < steps.length; i++ ) {
					if ( steps[ i ].querySelector( '.has-error' ) ) { show( i ); break; }
				}
				return;
			}
			submit.disabled = true;
			submit.textContent = t.sending || '...';
			errBox.hidden = true;
			var body = new FormData( form );
			body.set( 'action', 'kriswa_quote' );
			fetch( cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( d ) {
					if ( d.ok ) {
						form.hidden = true;
						var done = box.querySelector( '.kr-quote__done' );
						var wa = done.querySelector( '[data-kr-wa]' );
						if ( d.wa && wa ) { wa.href = d.wa; }
						done.hidden = false;
						done.scrollIntoView( { behavior: 'smooth', block: 'center' } );
						return;
					}
					var errs = d.errors || {};
					Object.keys( errs ).forEach( function ( k ) { if ( k !== '_form' ) { setErr( k, errs[ k ] ); } } );
					if ( errs._form ) { errBox.textContent = errs._form; errBox.hidden = false; }
					submit.disabled = false;
					submit.textContent = t.submit || 'Submit';
				} )
				.catch( function () {
					errBox.textContent = t.error || '';
					errBox.hidden = false;
					submit.disabled = false;
					submit.textContent = t.submit || 'Submit';
				} );
		} );
	} );
}() );
