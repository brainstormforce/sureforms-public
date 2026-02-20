/**
 * SureForms Address Autocomplete
 *
 * Handles Google Places Autocomplete integration for address blocks.
 * Supports multiple address fields per page, map preview with draggable marker,
 * country restriction, and place selection validation.
 *
 * @package SureForms
 * @since x.x.x
 */
( () => {
	'use strict';

	/**
	 * Promise that resolves when Google Maps API is loaded.
	 * Uses singleton pattern to prevent duplicate loads.
	 *
	 * @type {Promise|null}
	 */
	let googleMapsLoadPromise = null;

	/**
	 * Loads the Google Maps JavaScript API lazily.
	 * Only loads once even if called multiple times.
	 * Reuses existing API if already loaded by another script.
	 *
	 * @param {string} apiKey The Google Maps API key.
	 * @return {Promise} Resolves when the API is ready.
	 */
	function loadGoogleMapsAPI( apiKey ) {
		if ( window.google && window.google.maps && window.google.maps.places ) {
			return Promise.resolve();
		}

		if ( googleMapsLoadPromise ) {
			return googleMapsLoadPromise;
		}

		googleMapsLoadPromise = new Promise( ( resolve, reject ) => {
			const script = document.createElement( 'script' );
			script.src = 'https://maps.googleapis.com/maps/api/js?key=' + encodeURIComponent( apiKey ) + '&libraries=places';
			script.async = true;
			script.defer = true;
			script.onload = () => {
				resolve();
			};
			script.onerror = () => {
				googleMapsLoadPromise = null;
				reject( new Error( 'Failed to load Google Maps API' ) );
			};
			document.head.appendChild( script );
		} );

		return googleMapsLoadPromise;
	}

	/**
	 * Clears all hidden field values within a container.
	 *
	 * @param {Element} container The address block container.
	 */
	function clearHiddenFields( container ) {
		const hiddenFields = container.querySelectorAll(
			'.srfm-address-autocomplete-structured, .srfm-address-autocomplete-place-id, .srfm-address-autocomplete-lat, .srfm-address-autocomplete-lng'
		);
		for ( let i = 0; i < hiddenFields.length; i++ ) {
			hiddenFields[ i ].value = '';
		}
	}

	/**
	 * Parses a Google Place result into a structured address object.
	 * Extracts individual address components with fallback chains
	 * for international address format support.
	 *
	 * @param {Object} place Google Place result object.
	 * @return {Object} Structured address data.
	 */
	function parsePlace( place ) {
		const result = {
			formatted_address: place.formatted_address || '',
			street_number: '',
			route: '',
			city: '',
			state: '',
			postal_code: '',
			country: '',
			lat: '',
			lng: '',
			place_id: place.place_id || '',
		};

		if ( place.geometry && place.geometry.location ) {
			result.lat = place.geometry.location.lat().toString();
			result.lng = place.geometry.location.lng().toString();
		}

		if ( place.address_components ) {
			for ( let i = 0; i < place.address_components.length; i++ ) {
				const component = place.address_components[ i ];
				const types = component.types;

				if ( types.indexOf( 'street_number' ) !== -1 ) {
					result.street_number = component.long_name;
				} else if ( types.indexOf( 'route' ) !== -1 ) {
					result.route = component.long_name;
				} else if ( types.indexOf( 'locality' ) !== -1 ) {
					result.city = component.long_name;
				} else if ( types.indexOf( 'administrative_area_level_3' ) !== -1 && ! result.city ) {
					// Fallback for city: some countries use admin_area_level_3.
					result.city = component.long_name;
				} else if ( types.indexOf( 'sublocality' ) !== -1 && ! result.city ) {
					// Fallback for city: sublocality.
					result.city = component.long_name;
				} else if ( types.indexOf( 'postal_town' ) !== -1 && ! result.city ) {
					// Fallback for city: postal_town (UK).
					result.city = component.long_name;
				} else if ( types.indexOf( 'administrative_area_level_1' ) !== -1 ) {
					result.state = component.long_name;
				} else if ( types.indexOf( 'postal_code' ) !== -1 ) {
					result.postal_code = component.long_name;
				} else if ( types.indexOf( 'postal_code_prefix' ) !== -1 && ! result.postal_code ) {
					// Fallback for postal code.
					result.postal_code = component.long_name;
				} else if ( types.indexOf( 'country' ) !== -1 ) {
					result.country = component.long_name;
				}
			}
		}

		return result;
	}

	/**
	 * Updates hidden fields in the container with parsed address data.
	 * Dispatches change events so form submission picks up the values.
	 *
	 * @param {Element} container The address block container.
	 * @param {Object}  data      Parsed address data from parsePlace().
	 */
	function updateHiddenFields( container, data ) {
		const structured = container.querySelector( '.srfm-address-autocomplete-structured' );
		const placeId = container.querySelector( '.srfm-address-autocomplete-place-id' );
		const lat = container.querySelector( '.srfm-address-autocomplete-lat' );
		const lng = container.querySelector( '.srfm-address-autocomplete-lng' );

		if ( structured ) {
			structured.value = JSON.stringify( data );
			structured.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}
		if ( placeId ) {
			placeId.value = data.place_id;
			placeId.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}
		if ( lat ) {
			lat.value = data.lat;
			lat.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}
		if ( lng ) {
			lng.value = data.lng;
			lng.dispatchEvent( new Event( 'change', { bubbles: true } ) );
		}
	}

	/**
	 * Populates the inner block sub-fields with parsed address components.
	 * Maps data to Address Line 1, City, State, Postal Code inputs and
	 * Country dropdown (supports both native select and Tom Select).
	 *
	 * @param {Element} container The address block container.
	 * @param {Object}  data      Parsed address data.
	 */
	function populateSubFields( container, data ) {
		const blockWrap = container.querySelector( '.srfm-block-wrap' );
		if ( ! blockWrap ) {
			return;
		}

		const inputFields = blockWrap.querySelectorAll( '.srfm-input-input' );
		const dropdownFields = blockWrap.querySelectorAll( '.srfm-dropdown-input' );

		// Build address line 1 from street number + route.
		const addressLine1 = [ data.street_number, data.route ].filter( Boolean ).join( ' ' );

		// Map sub-fields by their ordering in the address template:
		// 0: Address Line 1, 1: Address Line 2, 2: City, 3: State, 4: Postal Code.
		if ( inputFields.length >= 1 && addressLine1 ) {
			inputFields[ 0 ].value = addressLine1;
			inputFields[ 0 ].dispatchEvent( new Event( 'input', { bubbles: true } ) );
		}
		// Address Line 2 is left empty (autocomplete doesn't provide unit/apt info).
		if ( inputFields.length >= 3 && data.city ) {
			inputFields[ 2 ].value = data.city;
			inputFields[ 2 ].dispatchEvent( new Event( 'input', { bubbles: true } ) );
		}
		if ( inputFields.length >= 4 && data.state ) {
			inputFields[ 3 ].value = data.state;
			inputFields[ 3 ].dispatchEvent( new Event( 'input', { bubbles: true } ) );
		}
		if ( inputFields.length >= 5 && data.postal_code ) {
			inputFields[ 4 ].value = data.postal_code;
			inputFields[ 4 ].dispatchEvent( new Event( 'input', { bubbles: true } ) );
		}

		// Country dropdown - uses Tom Select library in SureForms.
		if ( dropdownFields.length >= 1 && data.country ) {
			const dropdown = dropdownFields[ 0 ];

			if ( dropdown.tomselect ) {
				// Tom Select: find matching option by text.
				const options = dropdown.tomselect.options;
				for ( const key in options ) {
					if ( options[ key ].text === data.country ) {
						dropdown.tomselect.setValue( key );
						break;
					}
				}
			} else {
				// Native select fallback.
				const selectOptions = dropdown.querySelectorAll( 'option' );
				for ( let i = 0; i < selectOptions.length; i++ ) {
					if ( selectOptions[ i ].textContent.trim() === data.country ) {
						dropdown.value = selectOptions[ i ].value;
						dropdown.dispatchEvent( new Event( 'change', { bubbles: true } ) );
						break;
					}
				}
			}
		}
	}

	/**
	 * Renders or updates the Google Map preview with a draggable marker.
	 * Creates a new map on first call, updates position on subsequent calls.
	 * Marker drag updates lat/lng hidden fields.
	 *
	 * @param {Element} container The address block container.
	 * @param {Object}  data      Parsed address data with lat/lng.
	 */
	function renderMap( container, data ) {
		const mapDiv = container.querySelector( '.srfm-address-autocomplete-map-preview' );
		if ( ! mapDiv ) {
			return;
		}

		const lat = parseFloat( data.lat );
		const lng = parseFloat( data.lng );

		if ( isNaN( lat ) || isNaN( lng ) ) {
			mapDiv.style.display = 'none';
			return;
		}

		mapDiv.style.display = 'block';
		const position = { lat: lat, lng: lng };

		if ( mapDiv._srfmMap ) {
			// Update existing map.
			mapDiv._srfmMap.setCenter( position );
			if ( mapDiv._srfmMarker ) {
				mapDiv._srfmMarker.setPosition( position );
			}
		} else {
			// Create new map.
			const map = new window.google.maps.Map( mapDiv, {
				center: position,
				zoom: 15,
				disableDefaultUI: true,
				zoomControl: true,
			} );

			// Force map to recalculate its size after being revealed from display:none.
			// Without this, the map tiles render blank because the container had 0 dimensions.
			window.google.maps.event.trigger( map, 'resize' );
			map.setCenter( position );

			const marker = new window.google.maps.Marker( {
				position: position,
				map: map,
				draggable: true,
			} );

			// Update lat/lng hidden fields when marker is dragged.
			marker.addListener( 'dragend', function() {
				const newPos = marker.getPosition();
				const latField = container.querySelector( '.srfm-address-autocomplete-lat' );
				const lngField = container.querySelector( '.srfm-address-autocomplete-lng' );

				if ( latField ) {
					latField.value = newPos.lat().toString();
					latField.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				}
				if ( lngField ) {
					lngField.value = newPos.lng().toString();
					lngField.dispatchEvent( new Event( 'change', { bubbles: true } ) );
				}

				// Update structured data with new coordinates.
				const structuredField = container.querySelector( '.srfm-address-autocomplete-structured' );
				if ( structuredField && structuredField.value ) {
					try {
						const structured = JSON.parse( structuredField.value );
						structured.lat = newPos.lat().toString();
						structured.lng = newPos.lng().toString();
						structuredField.value = JSON.stringify( structured );
						structuredField.dispatchEvent( new Event( 'change', { bubbles: true } ) );
					} catch ( e ) {
						// Silently ignore parse errors.
					}
				}
			} );

			mapDiv._srfmMap = map;
			mapDiv._srfmMarker = marker;
		}
	}

	/**
	 * Initializes autocomplete on a single address block.
	 * Attaches Google Places Autocomplete, sets up event listeners for
	 * place selection, input changes, validation, and form submission prevention.
	 *
	 * @param {Element} block The `.srfm-address-autocomplete-block` container.
	 */
	function initializeBlock( block ) {
		const input = block.querySelector( '.srfm-input-address-autocomplete' );
		if ( ! input || input.getAttribute( 'data-srfm-autocomplete-initialized' ) === 'true' ) {
			return;
		}

		if ( ! window.google || ! window.google.maps || ! window.google.maps.places ) {
			return;
		}

		input.setAttribute( 'data-srfm-autocomplete-initialized', 'true' );

		const country = block.getAttribute( 'data-country' );
		const requireSelection = block.getAttribute( 'data-require-selection' ) === 'true';
		const enableMap = block.getAttribute( 'data-show-map' ) === 'true';

		const autocompleteOptions = {
			fields: [ 'address_components', 'formatted_address', 'geometry', 'place_id' ],
		};

		if ( country && country.length === 2 ) {
			autocompleteOptions.componentRestrictions = { country: country };
		}

		const autocomplete = new window.google.maps.places.Autocomplete( input, autocompleteOptions );
		let placeSelected = false;

		// Handle place selection from autocomplete dropdown.
		autocomplete.addListener( 'place_changed', function() {
			const place = autocomplete.getPlace();
			if ( ! place || ! place.place_id ) {
				placeSelected = false;
				return;
			}

			placeSelected = true;
			const parsed = parsePlace( place );

			input.value = parsed.formatted_address;
			updateHiddenFields( block, parsed );
			populateSubFields( block, parsed );
			input.dispatchEvent( new Event( 'change', { bubbles: true } ) );

			if ( enableMap ) {
				renderMap( block, parsed );
			}

			// Clear error state if present.
			if ( window.srfm && window.srfm.toggleErrorState ) {
				window.srfm.toggleErrorState( block, false );
			}
		} );

		// Clear hidden fields when user types over autocomplete selection.
		input.addEventListener( 'input', function() {
			placeSelected = false;
			clearHiddenFields( block );
		} );

		// Hide map and clear fields when input is emptied.
		input.addEventListener( 'input', function() {
			if ( input.value.trim() === '' ) {
				clearHiddenFields( block );
				const mapDiv = block.querySelector( '.srfm-address-autocomplete-map-preview' );
				if ( mapDiv ) {
					mapDiv.style.display = 'none';
				}
			}
		} );

		// Validate on blur if require-selection is enabled.
		if ( requireSelection ) {
			input.addEventListener( 'blur', function() {
				if ( input.value.trim() !== '' && ! placeSelected ) {
					if ( window.srfm && window.srfm.toggleErrorState ) {
						window.srfm.toggleErrorState( block, true );
					}
				}
			} );
		}

		// Prevent form submission when pressing Enter in autocomplete input.
		input.addEventListener( 'keydown', function( e ) {
			if ( e.key === 'Enter' ) {
				e.preventDefault();
			}
		} );
	}

	/**
	 * Main initialization function.
	 * Finds all autocomplete-enabled address blocks on the page,
	 * loads Google Maps API lazily, then initializes each block.
	 * Degrades gracefully if API key is missing or API fails to load.
	 */
	function initializeAll() {
		const blocks = document.querySelectorAll( '.srfm-address-autocomplete-block' );
		if ( ! blocks.length ) {
			return;
		}

		// Get API key from the first block's data attribute.
		let apiKey = '';
		for ( let i = 0; i < blocks.length; i++ ) {
			apiKey = blocks[ i ].getAttribute( 'data-api-key' );
			if ( apiKey ) {
				break;
			}
		}

		if ( ! apiKey ) {
			return; // No API key configured, address fields work as normal inputs.
		}

		loadGoogleMapsAPI( apiKey )
			.then( function() {
				for ( let i = 0; i < blocks.length; i++ ) {
					initializeBlock( blocks[ i ] );
				}
			} )
			.catch( function() {
				// Graceful degradation: API failed to load.
				// Address fields remain functional as normal text inputs.
			} );
	}

	// Export global initialization function for manual re-initialization.
	window.srfmInitializeAddressAutocomplete = initializeAll;

	// Auto-initialize on DOM ready.
	if ( document.readyState === 'complete' || document.readyState === 'interactive' ) {
		initializeAll();
	} else {
		document.addEventListener( 'DOMContentLoaded', initializeAll );
	}

	// Validate required place selection on form submission.
	document.addEventListener( 'srfm_form_before_submission', function() {
		const blocks = document.querySelectorAll( '.srfm-address-autocomplete-block' );
		for ( let i = 0; i < blocks.length; i++ ) {
			const input = blocks[ i ].querySelector( '.srfm-input-address-autocomplete' );
			if ( ! input ) {
				continue;
			}
			const requireSelection = blocks[ i ].getAttribute( 'data-require-selection' ) === 'true';
			const placeIdField = blocks[ i ].querySelector( '.srfm-address-autocomplete-place-id' );

			if ( requireSelection && input.value.trim() !== '' && ( ! placeIdField || ! placeIdField.value ) ) {
				if ( window.srfm && window.srfm.toggleErrorState ) {
					window.srfm.toggleErrorState( blocks[ i ], true );
				}
			}
		}
	} );
} )();
