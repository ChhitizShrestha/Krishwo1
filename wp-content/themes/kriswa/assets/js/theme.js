/* Kriswa theme: gentle scroll-reveal (skipped for reduced-motion and when IntersectionObserver is missing). */
( function () {
	'use strict';
	var root = document.documentElement;
	if ( ! ( 'IntersectionObserver' in window ) || window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) { return; }
	root.classList.add( 'kr-js' );
	var targets = document.querySelectorAll( '.kr-sechead, .kr-card, .kr-feature, .kr-process li, .kr-stats__grid li, .kr-hero__visual, .kr-faq__item, .kr-quote, .kr-tile, .kr-seg' );
	var io = new IntersectionObserver( function ( entries ) {
		entries.forEach( function ( e ) {
			if ( e.isIntersecting ) { e.target.classList.add( 'is-in' ); io.unobserve( e.target ); }
		} );
	}, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 } );
	targets.forEach( function ( t ) { t.classList.add( 'kr-reveal' ); io.observe( t ); } );
}() );
