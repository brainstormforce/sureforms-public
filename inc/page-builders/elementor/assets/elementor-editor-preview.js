( function () {
	window.addEventListener( 'elementor/frontend/init', function () {
		// Listen for elementor frontend init event
		if (
			typeof elementorFrontend !== 'undefined' &&
			elementorFrontend.hooks
		) {
			const checkAndLoadPageBreak = function () {
				if (
					typeof srfmElementorData !== 'undefined' &&
					srfmElementorData.isProActive &&
					typeof loadPageBreak === 'function'
				) {
					loadPageBreak();
				}

				// initial phone field
				if ( typeof initializePhoneField === 'function' ) {
					initializePhoneField();
				}

				// initial dropdown field
				if ( typeof initializeDropdown === 'function' ) {
					// call the hook after 2 seconds to make sure the dropdown is initialized properly without any issues.
					setTimeout( function () {
						initializeDropdown();
					}, 2000 );
				}
			};

			elementorFrontend.hooks.addAction(
				'frontend/element_ready/sureforms_form.default',
				checkAndLoadPageBreak
			);
			elementorFrontend.hooks.addAction(
				'frontend/element_ready/shortcode.default',
				checkAndLoadPageBreak
			);
		}
	} );
}() );
