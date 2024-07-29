function initializeDropdown() {
	const dropDownSelector = document.querySelectorAll(
		'.srfm-dropdown-common'
	);

	dropDownSelector.forEach( ( element ) => {
		if ( element ) {
			let additionalConfig = {};
			if ( element.getAttribute( 'data-multiple' ) === 'true' ) {
				additionalConfig = {
					maxItems: null, // Enabling multi selection for dropdown.
					hideSelected: false, // Allowing selected options to be visible in the dropdown menu.
					plugins: [ 'remove_button' ], // Adding remove button to the selected options.
				};
			}
			const config = {
				maxOptions: null,
				onChange( value ) {
					// In case of multi-select dropdown, the value will be an array.
					if ( Array.isArray( value ) ) {
						value.forEach( ( item ) => {
							wp.a11y.speak( item );
						} );
					} else {
						wp.a11y.speak( value );
					}

					// Update the hidden input field with the selected values.
					const hiddenInputField = element
						.closest( '.srfm-dropdown-block' )
						.querySelector( '.srfm-input-dropdown-hidden' );
					if ( hiddenInputField ) {
						hiddenInputField.setAttribute(
							'value',
							Array.isArray( value ) ? value.join( ',' ) : value
						);
					}
				},
				...additionalConfig,
			};
			new TomSelect( element, config );
			// Disable the select element to submit selected options through hidden input field.
			element.disabled = true;
		}
	} );
}
document.addEventListener( 'DOMContentLoaded', initializeDropdown );
