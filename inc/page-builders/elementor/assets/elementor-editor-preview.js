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

				initializeDropdown();
				initializePhoneField();
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
