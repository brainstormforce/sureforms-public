function initializeDropdown() {
	const dropDownSelector = document.querySelectorAll(
		'.srfm-dropdown-common'
	);

	console.log( 'initializeDropdown initializeDropdown', dropDownSelector );

	dropDownSelector.forEach( ( element ) => {
		if ( element ) {
			let additionalConfig = {};
			const inputName = element.getAttribute( 'name' );
			const errorContainerID = element
				.closest( '.srfm-dropdown-block' )
				.querySelector( '.srfm-error-message' )
				?.getAttribute( 'data-srfm-id' );

			if ( element.getAttribute( 'data-multiple' ) === 'true' ) {
				additionalConfig = {
					maxItems: null, // Enabling multi selection for dropdown.
					hideSelected: false, // Allowing selected options to be visible in the dropdown menu.
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
				plugins: [ 'remove_button', 'clear_button' ], // Adding remove button to the selected options and clear button for the dropdown.
				openOnFocus: false, // Stop the dropdown from opening on focus.
				/**
				 * If the error container is present, assign it to the aria-describedby attribute.
				 * This will ensure that when there is a error, it will be announced by the screen reader
				 * when the field is in focus state.
				 *
				 * If there are selected options, announce them on field focus.
				 */
				onFocus() {
					if ( errorContainerID ) {
						tomInputInstance.control_input.setAttribute(
							'aria-describedby',
							errorContainerID
						);
					}
					if (
						tomInputInstance.hasOptions &&
						tomInputInstance.items
					) {
						wp.a11y.speak( tomInputInstance.items.toString() );
					}
				},
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
							Array.isArray( value )
								? window.srfm.srfmUtility.prepareValue( value )
								: value
						);

						// Dispatch the change event on the hidden input field.
						hiddenInputField.dispatchEvent(
							new Event( 'change', { bubbles: true } )
						);
					}
				},
				// Handle the input state when an item is added or removed.
				onItemAdd() {
					handleInputState( element );
					this.setTextboxValue( '' ); // Clear the dropdown search input.
					this.lastQuery = null; // Clears the internal search query.
					this.refreshOptions( false ); // Removes the highlight from options based on the search without a full reload.
				},
				onItemRemove() {
					handleInputState( element );
				},
				...additionalConfig,
				render: {
					option( data, escape ) {
						return `<div>${ data.icon } <span>${ escape(
							data.text
						) }</span></div>`;
					},
					item( item, escape ) {
						return `<div>${ item.icon } ${ escape(
							item.text
						) }</div>`;
					},
				},
				/**
				 * When dropdown is opened, set the role for the options to `menuitem`
				 * for enhanced accessibility experience by proper announcement of the
				 * dropdown options.
				 *
				 * Default role `option` is added to the options which was causing issues
				 * with the announcement.
				 */
				onDropdownOpen() {
					const options =
						tomInputInstance.dropdown_content.querySelectorAll(
							'.option'
						);
					options.forEach( ( option ) => {
						if ( option.getAttribute( 'role' ) === 'option' ) {
							option.setAttribute( 'role', 'menuitem' );
						}
					} );
				},
			};

			console.log( 'config', { config, element, inputName, errorContainerID } );

			/**
			 * Creates a new TomSelect instance for the given input element and adds it to the global `window.srfm` object.
			 *
			 * The TomSelect input instance is created and stored in the `window.srfm` object, which is a global registry
			 * for managing input instances used by the third-party library. This allows easy access to the input instance
			 * later for manipulation (e.g., focusing the input).
			 *
			 * @param {HTMLElement} element   - The DOM element to initialize TomSelect on.
			 * @param {Object}      config    - The configuration options for the TomSelect instance.
			 * @param {string}      inputName - The key under which the TomSelect instance is stored in the `window.srfm` object.
			 */
			const tomInputInstance = new TomSelect( element, config );
			/**
			 * `keydown` & `click` event listeners to open the dropdown menu programmatically
			 * since we set the `openOnFocus` option to false for the dropdown.
			 *
			 * `keydown` event listener - When user presses Space key then open the dropdown menu if
			 * it is not active.
			 *
			 * `click` event listener - When the user clicks on the input, open the dropdown menu if
			 * it is not active.
			 */
			tomInputInstance.control.addEventListener( 'keydown', ( e ) => {
				if (
					e.key === ' ' &&
					! tomInputInstance.wrapper.classList.contains(
						'dropdown-active'
					)
				) {
					e.preventDefault();
					tomInputInstance.open();
				}
			} );
			tomInputInstance.control.addEventListener( 'click', () => {
				if (
					! tomInputInstance.wrapper.classList.contains(
						'dropdown-active'
					)
				) {
					tomInputInstance.open();
				}
			} );

			// Add the TomSelect instance to the global window.srfm object for future reference
			window?.addGlobalSrfmObject( inputName, tomInputInstance );

			// Clear Icon and Dropdown Arrow SVGs.
			const clearSVG = `<svg aria-hidden="true" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M12 4L4 12" stroke="currentColor" stroke-opacity="0.65" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 4L12 12" stroke="currentColor" stroke-opacity="0.65" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round"/></svg>`;
			const dropdownSVG = `<svg aria-hidden="true" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M4 6L8 10L12 6" stroke="currentColor" stroke-opacity="0.65" stroke-width="1.25" stroke-linecap="round" stroke-linejoin="round" /></svg>`;
			const dropdownWrapper = element
				.closest( '.srfm-dropdown-block' )
				.querySelector( '.ts-control' );

			const clearButton =
				dropdownWrapper.querySelector( '.clear-button' );
			if ( clearButton ) {
				// Replace the default clear icon with the custom clear SVG.
				clearButton.innerHTML = clearSVG;
				// Adding the tabindex to the clear button for keyboard accessibility (tab navigation).
				clearButton.setAttribute( 'tabindex', '0' );
				clearButton.setAttribute( 'role', 'button' ); // Add role button to the clear button for proper announcement.
				/**
				 * Clear the selected options when the clear button is clicked using keyboard.
				 * The clear() method removes all selected options from the control and is available in the TomSelect instance.
				 */
				clearButton.addEventListener( 'keydown', ( e ) => {
					if ( e.key === ' ' || e.key === 'Enter' ) {
						e.preventDefault();
						e.stopPropagation(); // Stop the event from triggering the parent element event.
						tomInputInstance.clear();
					}
				} );
			}
			const dropdownIconDiv = document.createElement( 'div' );
			dropdownIconDiv.classList.add( 'ts-dropdown-icon' );
			dropdownIconDiv.innerHTML = dropdownSVG;
			dropdownWrapper.appendChild( dropdownIconDiv );

			// Add placeholder to the dropdown when Search is disabled.
			if ( config.controlInput === null ) {
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

// If the dropdown has max options selected, disable the input field.
function handleInputState( element ) {
	const tsControl = element
		.closest( '.srfm-dropdown-block' )
		.querySelector( '.ts-control' );
	const tsControlParent = tsControl?.parentElement;
	const tsControlInput = tsControl?.querySelector( 'input' );

	if ( tsControlInput ) {
		if ( tsControlParent.classList.contains( 'full' ) ) {
			tsControlInput.setAttribute( 'readonly', '' );
		} else {
			tsControlInput.removeAttribute( 'readonly' );
		}
	}
}
// make dropdown initialization function available globally
// window.srfmInitializeDropdown = initializeDropdown;

// document.addEventListener( 'DOMContentLoaded', initializeDropdown );

// Re-initialize dropdowns when the block is updated in the editor.
// srfm_form_before_submission
document.addEventListener( 'srfm_form_before_submission', ( e ) => {
	const dropdowns = e.detail?.form.querySelectorAll(
		'.srfm-dropdown-common'
	);
	if ( ! dropdowns ) {
		return;
	}

	// Destroy the existing TomSelect instances before re-initializing.
	dropdowns.forEach( ( dropdown ) => {

		const getTheWrapper = dropdown.closest( '.srfm-block-wrap.srfm-dropdown-common-wrap' );

		if( getTheWrapper ) {
			// remove from the window.srfm name 
			const inputName = dropdown.getAttribute( 'name' );

			// Remove these classes from the getTheWrapper "tomselected ts-hidden-accessible".
			// console.log( 'inputName----> ', {
			// 	dropdown,
			// 	inputName,
			// 	wi: window?.srfm?.[ inputName ],
			// 	id: dropdown.getAttribute( 'id' ),
			// 	classList: dropdown.classList,
			// } );

			dropdown.classList.remove( 'tomselected' );
			dropdown.classList.remove( 'ts-hidden-accessible' );
			dropdown.removeAttribute( 'id' );

			if ( window?.srfm?.[ inputName ] ) {
				delete window.srfm[ inputName ];
			}
			// Destroy the TomSelect instance.
			dropdown?.TomSelect?.destroy();

			const getTheTSWrapper = getTheWrapper.querySelectorAll( 'div.ts-wrapper' );
			// console.log( 'getTheTSWrapper', {getTheTSWrapper} );

			getTheTSWrapper?.forEach( ( wrapper ) => {
				wrapper.remove();
			} );
		}
	} );

	setTimeout( () => {
		initializeDropdown();
	}, 100 );
	
} );
