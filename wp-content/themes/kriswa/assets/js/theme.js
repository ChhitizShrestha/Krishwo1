/* Kriswa theme: small progressive enhancements. */
( function () {
	'use strict';
	var header = document.querySelector( '.kr-header' );
	if ( ! header ) { return; }
	var onScroll = function () { header.classList.toggle( 'is-scrolled', window.scrollY > 40 ); };
	window.addEventListener( 'scroll', onScroll, { passive: true } );
	onScroll();
}() );
