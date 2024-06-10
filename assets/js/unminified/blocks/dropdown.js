const { speak } = wp.a11y;

function initializeDropdown() {
	const dropDownSelector = document.querySelectorAll(
		'.srfm-dropdown-common'
	);

	dropDownSelector.forEach( ( element ) => {
		const placeholderOption = element.querySelector( '.srfm-dropdown-placeholder' );
		const placeholderText = placeholderOption ? placeholderOption.innerText : '';
		if ( element ) {
			const config = {
				onDropdownOpen( dropdown ) {
					speak( placeholderText );
				},
				onChange( value ) {
					if ( value ) speak( value );
				}
			};
			new TomSelect( element, config );
		}
	} );
}
document.addEventListener( 'DOMContentLoaded', initializeDropdown );
