/* Kriswa admin: project photo pickers (use the built-in WordPress media library). */
jQuery( function ( $ ) {
	if ( ! window.wp || ! wp.media ) { return; }

	/* ----- Single photo fields (Before / After) ----- */
	$( '[data-kr-image]' ).each( function () {
		var box = $( this ), input = box.find( 'input[type="hidden"]' ), prev = box.find( '.kriswa-img__preview' ),
			pick = box.find( '[data-kr-image-pick]' ), clear = box.find( '[data-kr-image-clear]' ), frame;
		pick.on( 'click', function ( e ) {
			e.preventDefault();
			frame = frame || wp.media( { title: 'Choose a photo', button: { text: 'Use this photo' }, library: { type: 'image' }, multiple: false } );
			frame.off( 'select' ).on( 'select', function () {
				var a = frame.state().get( 'selection' ).first().toJSON();
				var url = ( a.sizes && a.sizes.thumbnail ) ? a.sizes.thumbnail.url : a.url;
				input.val( a.id );
				prev.empty().append( $( '<img>' ).attr( { src: url, width: 120, height: 120 } ) );
				pick.text( 'Change photo' );
				clear.prop( 'hidden', false );
			} );
			frame.open();
		} );
		clear.on( 'click', function () { input.val( '' ); prev.empty(); pick.text( 'Choose photo' ); clear.prop( 'hidden', true ); } );
	} );

	/* ----- Gallery with a "main photo" ----- */
	var box = $( '[data-kr-gallery]' );
	if ( ! box.length ) { return; }
	var ids = box.find( 'input[name="kr_gallery"]' ), main = box.find( 'input[name="kr_main"]' ), list = box.find( '.kriswa-gal__list' ), frame;

	function sync() {
		var all = list.children().map( function () { return $( this ).data( 'id' ); } ).get();
		ids.val( all.join( ',' ) );
		if ( all.indexOf( parseInt( main.val(), 10 ) ) === -1 ) { main.val( all.length ? all[ 0 ] : '' ); }
		list.children().each( function () { $( this ).toggleClass( 'is-main', String( $( this ).data( 'id' ) ) === String( main.val() ) ); } );
	}
	box.on( 'click', '[data-kr-gal-add]', function ( e ) {
		e.preventDefault();
		frame = frame || wp.media( { title: 'Choose project photos', button: { text: 'Add to project' }, library: { type: 'image' }, multiple: 'add' } );
		frame.off( 'select' ).on( 'select', function () {
			frame.state().get( 'selection' ).each( function ( a ) {
				a = a.toJSON();
				if ( list.find( '[data-id="' + a.id + '"]' ).length ) { return; }
				var url = ( a.sizes && a.sizes.thumbnail ) ? a.sizes.thumbnail.url : a.url;
				list.append( $( '<li>' ).attr( 'data-id', a.id ).append(
					$( '<img>' ).attr( { src: url, width: 110, height: 110 } ),
					$( '<button type="button" class="kriswa-gal__star" title="Make main photo" aria-label="Make main photo">&#9733;</button>' ),
					$( '<span class="kriswa-gal__badge">Main photo</span>' ),
					$( '<button type="button" class="kriswa-gal__rm" aria-label="Remove photo">&times;</button>' )
				) );
			} );
			sync();
		} );
		frame.open();
	} );
	box.on( 'click', '.kriswa-gal__star', function () { main.val( $( this ).closest( 'li' ).data( 'id' ) ); sync(); } );
	box.on( 'click', '.kriswa-gal__rm', function () { $( this ).closest( 'li' ).remove(); sync(); } );
	if ( $.fn.sortable ) { list.sortable( { update: sync } ); }
	sync();
} );
