function initializeMultichoice() {
	const multiChoices = document.querySelectorAll(
		'.srfm-multi-choice-block'
	);
	if ( multiChoices ) {
		multiChoices.forEach( ( single ) => {
			const getInputWrappers = single.querySelectorAll(
				'.srfm-multi-choice-single'
			);

			/**
			 * Adds a click event listener to input wrappers to simulate input field clicks.
			 *
			 * This is required because the checkbox is wrapped inside a structured HTML element
			 * (like a `div`) to maintain a specific layout. To make the entire wrapper act as
			 * a clickable input, clicking anywhere inside the wrapper triggers the input field's
			 * click event, ensuring proper functionality.
			 *
			 * The function avoids triggering the event if the target is a label within the wrapper,
			 * ensuring labels behave as intended without additional interactions.
			 *
			 * @param {NodeList} getInputWrappers - A collection of wrapper elements containing inputs.
			 */
			getInputWrappers?.forEach( ( element ) => {
				element.addEventListener( 'click', ( e ) => {
					// Check if the target is NOT the label
					if ( ! e.target.matches( 'label' ) ) {
						const input = element.querySelector(
							'.srfm-input-multi-choice-single'
						);
						if ( input ) {
							// Trigger a click on the input field
							input.click();
							// Stop the event propagation
							e.stopPropagation();
						}
					}
				} );
			} );

			const choices = single.querySelectorAll(
				'.srfm-input-multi-choice-single'
			);
			let savedValues = [];
			let getValue = '';
			choices?.forEach( ( element ) => {
				element.addEventListener( 'click', ( e ) => {
					e.stopPropagation();

					getValue = e.target
						.closest( '.srfm-multi-choice-single' )
						.querySelector( 'label' ).innerText;

					console.log("getValue", {
						getValue,
						savedValues,
						element,
					});

					let hiddenInput = single.querySelector(
						'.srfm-input-multi-choice-hidden'
					);

					if ( ! hiddenInput || ! getValue ) {
						return;
					}

					let setValue = null;
					
					// For Radio Mode / single select.
					if ( single.classList.contains( 'srfm-radio-mode' ) ) {
						if ( e.target.checked ) {
							setValue = getValue;
						}
					} else if ( // For checkbox mode / multi select.
						single.classList.contains( 'srfm-checkbox-mode' )
					) {
						if ( e.target.checked ) {
							savedValues = [ ...savedValues, getValue ];
						} else {
							const arr = savedValues.filter(
								( item ) => item !== getValue
							);
							savedValues = arr;
						}

						setValue = savedValues;
					}

					console.log("setValue", {
						setValue,
						savedValues,
					});

					// Set the value of the hidden input field.
					if ( setValue ){
						hiddenInput.setAttribute( 'value', setValue );
					}

					// Add event in the ".srfm-input-multi-choice-hidden" element.
					hiddenInput.dispatchEvent(
						new Event( 'change', { bubbles: true } )
					);
				} );
			} );
		} );
	}
}

const UTILITY = {
	cleanValue: function ( value ) {
		// Create a new array to store the cleaned values
		const cleanedValues = [];
		// Split the input string by the delimiter
		const values = value.split( '%,%' );
		// Iterate through each value
		values.forEach( ( item ) => {
			// Trim whitespace and check if the item is not empty
			const trimmedItem = item.trim();
			if ( trimmedItem ) {
				cleanedValues.push( trimmedItem );
			}
		} );
		// Return the cleaned values as an array
		return cleanedValues;
	},
	prepareValue: function ( value = [] ) {
		// We have value like ["value 1", "value 2"]
		// We need to join them with "%,%"" so this will be robust solution. then if add label like "some text, another text" then problem will not occur.
		return value.join( '%,%' );
	}
}

document.addEventListener( 'DOMContentLoaded', initializeMultichoice );
