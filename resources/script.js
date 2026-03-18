( function () {
	function init() {
		var $fetchModeSelect = $( '#fetchmode' );
		var $downloadAllBtn = $( '#roblox-extractor-download-all' );

		// Handle fetch mode visibility
		if ( $fetchModeSelect.length && !$fetchModeSelect.data( 'extractor-init' ) ) {
			$fetchModeSelect.data( 'extractor-init', true );

			var $iconSizeGroup = $( '#iconsize' ).closest( '.roblox-extractor-input-group' );
			var $thumbSizeGroup = $( '#thumbsize' ).closest( '.roblox-extractor-input-group' );

			function updateVisibility() {
				var mode = $fetchModeSelect.val();
				if ( $iconSizeGroup.length ) {
					$iconSizeGroup.toggle( mode === 'both' || mode === 'icon' );
				}
				if ( $thumbSizeGroup.length ) {
					$thumbSizeGroup.toggle( mode === 'both' || mode === 'thumb' );
				}
			}

			$fetchModeSelect.on( 'change', updateVisibility );
			updateVisibility();
		}

		// Handle download all button
		if ( $downloadAllBtn.length && !$downloadAllBtn.data( 'extractor-init' ) ) {
			$downloadAllBtn.data( 'extractor-init', true );
			$downloadAllBtn.on( 'click', function ( e ) {
				e.preventDefault();
				var $links = $( '.roblox-extractor-thumb-download' );
				if ( !$links.length ) {
					return;
				}
				
				$links.each( function ( index, link ) {
					// Use setTimeout to space out downloads and avoid browser blocking
					setTimeout( function () {
						link.click();
					}, index * 450 );
				} );
			} );
		}
	}

	$( function () {
		init();
	} );

	// Also hook into wikipage.content for potential AJAX updates
	mw.hook( 'wikipage.content' ).add( function () {
		init();
	} );
}() );