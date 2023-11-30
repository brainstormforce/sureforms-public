function initializeURL() {
	const urlFiledContainers = document.getElementsByClassName(
		'srfm-classic-input-url-container'
	);
	if ( urlFiledContainers ) {
		for ( const urlFiledContainer of urlFiledContainers ) {
			const urlInput =
				urlFiledContainer.querySelector( '.srfm-url-input' );
			const validUrlMessage = urlFiledContainer.querySelector(
				'.srfm-validation-url-message'
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
						urlInput.classList.remove(
							'!srfm-ring-red-500',
							'!srfm-border-red-500',
							'placeholder:!srfm-text-red-300'
						);
					} else {
						validUrlMessage.style.display = 'block';
						urlInput.classList.add(
							'!srfm-ring-red-500',
							'!srfm-border-red-500',
							'placeholder:!srfm-text-red-300'
						);
					}
				} );
			}
		}
	}
}
document.addEventListener( 'DOMContentLoaded', initializeURL );
