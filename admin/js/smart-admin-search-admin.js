(function( $ ) {
	'use strict';

	// Save plugin's Select2.
	$.fn.sasSelect2 = $.fn.select2;

	// Restore previous Select2, if any.
	if ( typeof currentSelect2 !== 'undefined' && currentSelect2 !== null ) {
		$.fn.select2 = currentSelect2;
	}

	$(function() {

		/*
		 * ELEMENTS
		 */

		let sasDocument          = $( document );
		let sasBody              = $( 'body' );
		let sasSearchModal       = $( '.sas-search-modal' );
		let sasSearchModalClose  = $( '.sas-search-modal__close' );
		let sasSearchModalSelect = $( '.sas-search-modal__select' );
		let sasAdminBarIcon      = $( '#wp-admin-bar-sas_icon' );

		/*
		 * ADMIN BAR
		 */

		sasAdminBarIcon.on( 'mousedown', function() {
			showSearchModal();
		} );

		sasAdminBarIcon.on( 'click', function( e ) {
			e.preventDefault();
		} );

		/*
		 * GLOBAL KEY PRESS
		 */

		// Get search keys shortcut, sorting items and converting them to int values.
		let searchKeysShortcut = sas_values.options.search_keys_shortcut.sort().map( Number );

		// Array of pressed keys.
		let pressedKeys = [];

		// Check the pressed keys.
		sasDocument.on( 'keydown', function( e ) {
			if ( ! $( e.srcElement ).hasClass( 'sas-skip-global-keypress' ) ) {

				// Add pressed key to the array if not already added.
				if ( pressedKeys.includes( e.which ) === false ) {
					pressedKeys.push( e.which );
				}

				// If pressed keys are the same as the shortcut keys, open the search box.
				if ( JSON.stringify( pressedKeys.sort() ) === JSON.stringify( searchKeysShortcut ) ) {
					showSearchModal();
				} else if ( pressedKeys.includes( 27 ) ) {
					// 27 = ESC key.
					hideSearchModal();
				}

			}
		} );

		// Reset pressed keys array.
		sasDocument.on( 'keyup', function( e ) {
			if ( ! $( e.srcElement ).hasClass( 'sas-skip-global-keypress' ) ) {
				pressedKeys = [];
			}
		} );

		/*
		 * SEARCH MODAL
		 */

		function formatSearchResult( result ) {
			if ( result.loading ) {
				return result.text;
			}

			// Add the result preview.
			let preview = '';
			
			if ( typeof result.preview !== 'undefined' && result.preview !== null && result.preview !== '' ) {
				preview = `
					<div class="sas-search-result__preview">${result.preview}</div>
				`;
			}

			// Add the result link.
			// Show a line by default.
			let link_content = '<hr>';

			// If the setting is enabled, show the link url.
			if ( sas_values.options.show_results_url == 1 ) {
				link_content = result.link_url;
			}

			// If the url is missing, show a message.
			if ( result.link_url === '' || result.link_url === null ) {
				link_content = sas_values.strings.no_permissions;
			}

			let link_container = `
				<div class="sas-search-result__link-url">${link_content}</div>
			`;

			// Create the result template.
			let template = $(
				`
				<div class="sas-search-result">
					<div class="sas-search-result__icon wp-menu-image dashicons-before ${result.icon_class}" style="${result.style}"></div>
					<div class="sas-search-result__info">
						<div class="sas-search-result__name">${result.text}</div>
						<div class="sas-search-result__description">${result.description}</div>
						${link_container}
					</div>
					${preview}
				</div>
				`
			);

			return template;
		}

		function formatSearchResultSelection( result ) {
			if ( result.id === '' ) {
				return sas_values.strings.search_select_placeholder;
			}

			return result.text;
		}

		function showSearchModal() {
			sasSearchModal.addClass( 'sas-search-modal--opened' );

			sasSearchModalSelect.sasSelect2( {
				dropdownParent    : sasSearchModal,
				width             : '100%',
				placeholder       : sas_values.strings.search_select_placeholder,
				minimumInputLength: 3,
				allowClear        : true,
				templateResult    : formatSearchResult,
				templateSelection : formatSearchResultSelection,
				ajax              : {
					method        : 'GET',
					url           : sas_values.ajax.search_url,
					delay         : 400,
					beforeSend    : function( xhr ) {
										xhr.setRequestHeader( 'X-WP-NONCE', sas_values.ajax.nonce );
									},
					data          : function( params ) {
										return {
											query: params.term
										};
									},
					processResults: function( result ) {
										return {
											results: result
										};
									}
				}
			} );

			setTimeout( function() {
				sasSearchModalSelect.sasSelect2( 'open' );
			}, 300 ); // Time must be the same as (or greater than) the css animation duration.
		}

		function hideSearchModal() {
			if ( sasSearchModalSelect.hasClass( 'select2-hidden-accessible' ) ) {
				sasSearchModal.removeClass( 'sas-search-modal--opened' );
				sasSearchModalSelect.sasSelect2( 'destroy' );
				sasSearchModalSelect.empty();
			}
		}

		sasSearchModal.on( 'click', function( e ) {
			if ( e.target === this ) {
				hideSearchModal();
			}
		} );

		sasSearchModalClose.on( 'click', function( e ) {
			if ( e.target === this ) {
				hideSearchModal();
			}
		} );

		// Event triggered when a select item is selected.
		sasSearchModalSelect.on( 'select2:select', function ( e ) {
			let item_data = e.params.data;

			if ( item_data.link_url !== null && item_data.link_url !== '' ) {
				window.location.href = item_data.link_url;
			}
		} );

		// Event triggered when the select is opened.
		sasSearchModalSelect.on( 'select2:open', function() {
			// Fix bug with Select2 and Jquery 3.6.0:
			// set focus on the search field by vanilla JS.
			document.querySelector( '.sas-search-modal--opened .select2-search__field' ).focus();
		} );
	});

})( jQuery );
