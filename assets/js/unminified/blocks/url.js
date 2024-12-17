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
						window?.srfm?.toggleErrorState(
							urlFiledContainer,
							false
						);
						urlFiledContainer.classList.remove( 'srfm-url-error' );
					} else {
						window?.srfm?.toggleErrorState(
							urlFiledContainer,
							true
						);
						urlFiledContainer.classList.add( 'srfm-url-error' );
						validUrlMessage.style.display = 'block';
						validUrlMessage.innerText =
							window?.srfm_submit?.messages?.srfm_valid_url;
					}
				} );
			}
		}
	}
}
document.addEventListener( 'DOMContentLoaded', initializeURL );
