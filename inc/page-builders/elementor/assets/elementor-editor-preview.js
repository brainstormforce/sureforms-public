( function () {
	window.addEventListener( 'elementor/frontend/init', function () {
		// Listen for elementor frontend init event
		if (
			typeof elementorFrontend !== 'undefined' &&
			elementorFrontend.hooks
		) {
			const checkAndloadPageBreak = function () {
				if (
					typeof srfmElementorData !== 'undefined' &&
					srfmElementorData.isProActive &&
					typeof srfmLoadPageBreak === 'function'
				) {
					srfmLoadPageBreak();
				}

				// initial phone field
				if ( typeof srfmInitializePhoneField === 'function' ) {
					srfmInitializePhoneField();
				}

				// initial dropdown field
				if ( typeof srfmInitializeDropdown === 'function' ) {
					// call the hook after 2 seconds to make sure the dropdown is initialized properly without any issues.
					setTimeout( function () {
						srfmInitializeDropdown();
					}, 2000 );
				}
			};

			elementorFrontend.hooks.addAction(
				'frontend/element_ready/sureforms_form.default',
				checkAndloadPageBreak
			);
			elementorFrontend.hooks.addAction(
				'frontend/element_ready/shortcode.default',
				checkAndloadPageBreak
			);
		}
	} );
}() );
