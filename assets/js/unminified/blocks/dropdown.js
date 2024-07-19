function initializeDropdown() {
	const dropDownSelector = document.querySelectorAll(
		'.srfm-dropdown-common'
	);

	dropDownSelector.forEach( ( element ) => {
		if ( element ) {
			const config = {
				maxOptions: null,
				onChange( value ) {
					if ( value ) {
						wp.a11y.speak( value );
					}
				},
			};
			new TomSelect( element, config );
		}
	} );
}
document.addEventListener( 'DOMContentLoaded', initializeDropdown );
