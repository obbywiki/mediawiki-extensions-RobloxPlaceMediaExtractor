document.addEventListener( 'DOMContentLoaded', function () {
	var fetchModeSelect = document.getElementById( 'fetchmode' );
	var iconSizeGroup = document.getElementById( 'iconsize' ) ? document.getElementById( 'iconsize' ).parentElement : null;
	var thumbSizeGroup = document.getElementById( 'thumbsize' ) ? document.getElementById( 'thumbsize' ).parentElement : null;

	if ( !fetchModeSelect ) {
		return;
	}

	function updateVisibility() {
		var mode = fetchModeSelect.value;
		if ( iconSizeGroup ) {
			iconSizeGroup.style.display = ( mode === 'both' || mode === 'icon' ) ? '' : 'none';
		}
		if ( thumbSizeGroup ) {
			thumbSizeGroup.style.display = ( mode === 'both' || mode === 'thumb' ) ? '' : 'none';
		}
	}

	fetchModeSelect.addEventListener( 'change', updateVisibility );
	updateVisibility();
} );