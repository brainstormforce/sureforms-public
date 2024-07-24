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
			if ( element.getAttribute( 'data-searchable' ) === 'false' ) {
				additionalConfig = {
					...additionalConfig,
					controlInput: null, // Disabling search option for dropdown.
				};
			}
			const config = {
				maxOptions: null,
				hidePlaceholder: true, // Hide the placeholder text after an option is selected.
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

			// Add placeholder to the dropdown when Search is disabled.
			if ( config.controlInput === null ) {
				const dropdownWrapper = element
					.closest( '.srfm-dropdown-block' )
					.querySelector( '.ts-control' );
				const placeholderText = element
					.closest( '.srfm-dropdown-block' )
					.querySelector( '.srfm-dropdown-placeholder' );
				const placeholderElement = document.createElement( 'span' );
				placeholderElement.classList.add( 'ts-control-placeholder' );
				placeholderElement.textContent = placeholderText.textContent;
				dropdownWrapper.prepend( placeholderElement );
			}

			// Disable the select element to submit selected options through hidden input field.
			element.disabled = true;
		}
	} );
}
document.addEventListener( 'DOMContentLoaded', initializeDropdown );
