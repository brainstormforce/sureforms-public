function initializeDropdown() {
	const dropDownSelector = document.querySelectorAll(
		'.srfm-dropdown-common'
	);

	dropDownSelector.forEach( ( element ) => {
		const placeholderOption = element.querySelector(
			'.srfm-dropdown-placeholder'
		);
		const placeholderText = placeholderOption
			? placeholderOption.innerText
			: '';
		if ( element ) {
			const config = {
				maxOptions: null,
				onDropdownOpen() {
					wp.a11y.speak( placeholderText );
				},
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
