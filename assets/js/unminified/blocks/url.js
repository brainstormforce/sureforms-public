/**
 * Toggles the "srfm-error" class on the specified container element based on validation results.
 * Additionally, manages the `id` attribute on the error message element to enable screen reader accessibility.
 *
 * @param {HTMLElement} container - The parent container of the input field to validate.
 * @param {boolean}     hasError  - Determines whether to add or remove the error state.
 *
 *                                Description:
 *                                - When `hasError` is true, the function adds the "srfm-error" class to the container, enabling styling for invalid fields.
 *                                - If an error message element with the `data-temp-id` attribute exists as a child of the container,
 *                                this function assigns its value as the `id` attribute to allow screen readers to announce the error when visible.
 *                                - Conversely, if `hasError` is false, the function removes both the "srfm-error" class and `id` attribute from the
 *                                error message element, making it hidden from screen readers when the field is valid.
 */
function toggleErrorState( container, hasError ) {
	// Check if the current class state already matches the intended state to avoid redundant operations
	if ( container.classList.contains( 'srfm-error' ) === hasError ) {
		return;
	}

	// Toggle the "srfm-error" class based on `hasError` state
	container.classList.toggle( 'srfm-error', hasError );

	// Manage the error message's `id` attribute for screen reader accessibility
	const errorMessage = container.querySelector( '.srfm-error-message' );
	if ( errorMessage ) {
		if ( hasError && errorMessage.hasAttribute( 'data-temp-id' ) ) {
			// Set the `id` to `data-temp-id` value to allow screen readers to announce the error message
			errorMessage.id = errorMessage.getAttribute( 'data-temp-id' );
		} else {
			// Remove the `id` when the field is valid, hiding the message from screen readers
			errorMessage.removeAttribute( 'id' );
		}
	}
}

function initializeURL() {
	const urlFiledContainers =
		document.getElementsByClassName( 'srfm-url-block' );
	if ( urlFiledContainers ) {
		for ( const urlFiledContainer of urlFiledContainers ) {
			const urlInput =
				urlFiledContainer.querySelector( '.srfm-input-url' );
			const validUrlMessage = urlFiledContainer.querySelector(
				'.srfm-error-message'
			);
			if ( urlInput ) {
				urlInput.addEventListener( 'change', () => {
					const pattern = new RegExp(
						'^(https?:\\/\\/)?' + // protocol
							'((([a-z\\d]([a-z\\d-]*[a-z\\d])*)\\.)+[a-z]{2,}|' + // domain name
							'((\\d{1,3}\\.){3}\\d{1,3}))' + // OR IP (v4) address
							'(\\:\\d+)?(\\/[-a-z\\d%_.~+]*)*' + // port and path
							'(\\?[;&a-z\\d%_.~+=-]*)?' + // query string
							'(\\#[-a-z\\d_]*)?$', // fragment locator
						'i'
					);
					const isValidUrl = pattern.test( urlInput.value );
					if ( isValidUrl ) {
						validUrlMessage.style.display = 'none';
						toggleErrorState( urlFiledContainer, false );
						urlFiledContainer.classList.remove( 'srfm-url-error' );
					} else {
						toggleErrorState( urlFiledContainer, true );
						urlFiledContainer.classList.add( 'srfm-url-error' );
						validUrlMessage.style.display = 'block';
						validUrlMessage.innerText = 'Please enter a valid URL.';
					}
				} );
			}
		}
	}
}
document.addEventListener( 'DOMContentLoaded', initializeURL );
