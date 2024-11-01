let currentSelect2 = null;

(function( $ ) {
	'use strict';
	
	// Check if Select2 already exists.
	const select2Exists = $.isFunction( $.fn.select2 );
	
	if ( select2Exists ) {
		
		// Save current Select2.
		currentSelect2 = $.fn.select2;
		
		// Remove current Select2.
		delete $.fn.select2;
		
	}
})( jQuery );