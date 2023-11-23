function initializeDropdown() {
	const selectFieldContainer = document.getElementsByClassName(
		'srfm-classic-dropdown-container'
	);

	if ( selectFieldContainer ) {
		let i = 0;
		for ( const selectField of selectFieldContainer ) {
			const formElement = selectFieldContainer[ i ].closest( 'form' );
			// eslint-disable-next-line no-undef
			const computedStyle = getComputedStyle( formElement );
			const primaryColor = computedStyle.getPropertyValue(
				'--srfm-primary-color'
			);
			i++;
			const selectFieldButton = selectField.querySelector(
				'.srfm-classic-dropdown-button'
			);

			if ( selectFieldButton ) {
				selectFieldButton.addEventListener( 'focus', () => {
					selectFieldButton
						.querySelector( '.srfm-classic-select-icon ' )
						.classList.add( 'srfm-rotate-180', '!srfm-pl-4' );

					const nextSibling = selectFieldButton.nextElementSibling;
					const options = nextSibling.querySelectorAll(
						'.srfm-classic-dropdown-option'
					);
					if ( '' === primaryColor ) {
						for ( let index = 0; index < options.length; index++ ) {
							options[ index ].classList.remove(
								'hover:!srfm-bg-srfm_primary_color'
							);
							options[ index ].classList.add(
								'hover:!srfm-bg-[#0084C7]'
							);
						}
					}
					const dropdownResultInput = selectField.querySelector(
						'.srfm-classic-dropdown-result'
					);
					nextSibling.style.display = 'block';
					if ( nextSibling ) {
						nextSibling.classList.add( '!srfm-opacity-100' );
						nextSibling.classList.add( '!srfm-z-10' );
						nextSibling.classList.remove( '!srfm-opacity-0' );

						const liElements =
							nextSibling.querySelectorAll( 'ul li' );
						liElements.forEach( ( li ) => {
							li.addEventListener( 'mousedown', ( event ) => {
								selectFieldButton
									.querySelector(
										'.srfm-classic-select-icon '
									)
									.classList.remove(
										'srfm-rotate-180',
										'!srfm-pl-4'
									);
								selectFieldButton
									.querySelector(
										'.srfm-classic-select-icon '
									)
									.classList.add( 'srfm-rotate-0' );
								const selectedValue =
									event.target.textContent.trim();
								selectFieldButton.querySelector(
									'.srfm-dropdown-value'
								).textContent = selectedValue;
								dropdownResultInput.value = selectedValue;

								nextSibling.classList.remove(
									'!srfm-opacity-100'
								);
								nextSibling.classList.remove( '!srfm-z-10' );
								nextSibling.classList.add( '!srfm-opacity-0' );
								nextSibling.style.display = 'none';
							} );
						} );
					}
				} );
				selectFieldButton.addEventListener( 'blur', () => {
					selectFieldButton
						.querySelector( '.srfm-classic-select-icon ' )
						.classList.remove( 'srfm-rotate-180', '!srfm-pl-4' );
					selectFieldButton
						.querySelector( '.srfm-classic-select-icon ' )
						.classList.add( 'srfm-rotate-0' );
					const nextSibling = selectFieldButton.nextElementSibling;
					nextSibling.classList.remove( '!srfm-opacity-100' );
					nextSibling.classList.remove( '!srfm-z-10' );
					nextSibling.classList.add( '!srfm-opacity-0' );
					nextSibling.style.display = 'none';
				} );
			}
		}
	}
}
document.addEventListener( 'DOMContentLoaded', initializeDropdown );
