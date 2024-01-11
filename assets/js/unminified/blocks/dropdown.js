function initializeDropdown() {
	const dropDownSelector = document.querySelectorAll(
		'.srfm-dropdown-common'
	);

	dropDownSelector.forEach( ( element ) => {
		if ( element ) {
			// eslint-disable-next-line no-undef
			NiceSelect.bind( element );
			element.style.display = 'none';
		}
	} );
}
document.addEventListener( 'DOMContentLoaded', initializeDropdown );
