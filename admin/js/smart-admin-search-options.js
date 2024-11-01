(function( $ ) {
	'use strict';

	$(function() {

		/*
		 * ELEMENTS
		 */

		let sasCaptureSearchKeys      = $( '#sas-capture-search-keys' );
		let sasCaptureSearchKeysReset = $( '#sas-capture-search-keys-reset' );
		let sasSearchKeysShortcut     = $( '#sas_search_keys_shortcut' );

		/*
		 * OPTIONS PAGE
		 */

		// Get current search keys shortcut.
		let currentSearchKeysShortcut = sasSearchKeysShortcut.val();

		// Array of pressed keys.
		let optionsPressedKeys = [];

		sasCaptureSearchKeys.on( 'keydown', function( e ) {
			e.preventDefault();

			if ( optionsPressedKeys.includes( e.which + '|' + e.key ) === false ) {
				// Add pressed key to the array.
				optionsPressedKeys.push( e.which + '|' + e.key );

				// Add pressed key to the textbox.
				if ( $(this).val() === '' ) {
					$(this).val( e.key );
				} else {
					$(this).val( $(this).val() + '+' + e.key );
				}
			}
		} );

		sasCaptureSearchKeys.on( 'keyup', function() {
			sasSearchKeysShortcut.val( optionsPressedKeys.join() );
		} );

		sasCaptureSearchKeysReset.on( 'click', function() {
			// Clear the pressed keys array.
			optionsPressedKeys = [];

			// Clear the textbox content and set focus on it.
			sasCaptureSearchKeys.val( '' ).focus();

			// Reset the option field with the current shortcut.
			sasSearchKeysShortcut.val( currentSearchKeysShortcut );
		} );

	});

})( jQuery );
