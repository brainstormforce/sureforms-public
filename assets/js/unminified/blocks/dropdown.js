function initializeDropdown() {
	const dropDownSelector = document.querySelectorAll(
		'.srfm-dropdown-common'
	);

	dropDownSelector.forEach( ( element ) => {
		if ( element ) {
			const config = {};
			new TomSelect( element, config );
		}
	} );
}
document.addEventListener( 'DOMContentLoaded', initializeDropdown );
