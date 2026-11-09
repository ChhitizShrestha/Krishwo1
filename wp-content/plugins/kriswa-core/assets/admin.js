/* Kriswa admin: project photo gallery picker (uses the built-in WordPress media library). */
jQuery( function ( $ ) {
	var box = $( '[data-kr-gallery]' );
	if ( ! box.length || ! window.wp || ! wp.media ) { return; }
	var input = box.find( 'input[name="kr_gallery"]' ), list = box.find( '.kriswa-gal__list' ), frame;
	function sync() {
		input.val( list.children().map( function () { return $( this ).data( 'id' ); } ).get().join( ',' ) );
	}
	box.on( 'click', '[data-kr-gal-add]', function ( e ) {
		e.preventDefault();
		frame = frame || wp.media( { title: 'Choose project photos', button: { text: 'Add to project' }, library: { type: 'image' }, multiple: 'add' } );
		frame.off( 'select' ).on( 'select', function () {
			frame.state().get( 'selection' ).each( function ( a ) {
				a = a.toJSON();
				if ( list.find( '[data-id="' + a.id + '"]' ).length ) { return; }
				var url = ( a.sizes && a.sizes.thumbnail ) ? a.sizes.thumbnail.url : a.url;
				list.append( $( '<li>' ).attr( 'data-id', a.id ).append( $( '<img>' ).attr( { src: url, width: 90, height: 90 } ), $( '<button type="button" class="kriswa-gal__rm" aria-label="Remove photo">&times;</button>' ) ) );
			} );
			sync();
		} );
		frame.open();
	} );
	box.on( 'click', '.kriswa-gal__rm', function () { $( this ).closest( 'li' ).remove(); sync(); } );
	if ( $.fn.sortable ) { list.sortable( { update: sync } ); }
} );
