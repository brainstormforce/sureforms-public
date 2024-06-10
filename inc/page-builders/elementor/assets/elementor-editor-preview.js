( function () {
	window.addEventListener( 'elementor/frontend/init', function () {
		// Listen for elementor frontend init event
		if (
			typeof elementorFrontend !== 'undefined' &&
			elementorFrontend.hooks
		) {
			elementorFrontend.hooks.addAction(
				'frontend/element_ready/sureforms_form.default',
				function () {
					if (
						typeof srfmElementorData !== 'undefined' &&
						srfmElementorData.isProActive &&
						typeof loadPageBreak === 'function'
					) {
						loadPageBreak();
					}
				}
			);
		}
	} );
}() );
