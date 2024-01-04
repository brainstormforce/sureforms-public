function initializeDropdown() {
	const dropDownSelector = document.querySelectorAll(
		'.srfm-dropdown-common'
	);

	dropDownSelector.forEach( ( element ) => {
		// eslint-disable-next-line no-undef
		NiceSelect.bind( element );
	} );
}
document.addEventListener( 'DOMContentLoaded', initializeDropdown );
