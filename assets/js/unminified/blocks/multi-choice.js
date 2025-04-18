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

					let hiddenInput = null;
					if ( getValue ) {
						// For Radio Mode / single select.
						if ( single.classList.contains( 'srfm-radio-mode' ) ) {
							if ( e.target.checked ) {
								hiddenInput = single.querySelector(
									'.srfm-input-multi-choice-hidden'
								);
								hiddenInput.setAttribute( 'value', getValue );
							}
						}
						// For checkbox mode / multi select.
						if (
							single.classList.contains( 'srfm-checkbox-mode' )
						) {
							if ( e.target.checked ) {
								savedValues = [ ...savedValues, getValue ];
								hiddenInput = single.querySelector(
									'.srfm-input-multi-choice-hidden'
								);
								hiddenInput.setAttribute(
									'value',
									savedValues
								);
							} else {
								const arr = savedValues.filter(
									( item ) => item !== getValue
								);
								savedValues = arr;
								hiddenInput = single.querySelector(
									'.srfm-input-multi-choice-hidden'
								);
								hiddenInput.setAttribute(
									'value',
									savedValues
								);
							}
						}

						// Add event in the ".srfm-input-multi-choice-hidden" element.
						if ( hiddenInput ) {
							hiddenInput.dispatchEvent(
								new Event( 'change', { bubbles: true } )
							);
						}
					}
				} );
			} );
		} );
	}
}
document.addEventListener( 'DOMContentLoaded', initializeMultichoice );
