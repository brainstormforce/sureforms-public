// URL inputs that already have their listener.
// A WeakSet rather than a marker attribute: Elementor Pro renders each opened
// popup from an outerHTML copy, which would carry the attribute onto nodes
// that have no listeners.
const srfmBoundUrlInputs = new WeakSet();

function initializeURL( root = document ) {
	const urlFiledContainers = root.getElementsByClassName( 'srfm-url-block' );
	if ( urlFiledContainers ) {
		for ( const urlFiledContainer of urlFiledContainers ) {
			const urlInput =
				urlFiledContainer.querySelector( '.srfm-input-url' );
			const validUrlMessage = urlFiledContainer.querySelector(
				'.srfm-error-message'
			);
			if ( urlInput && ! srfmBoundUrlInputs.has( urlInput ) ) {
				srfmBoundUrlInputs.add( urlInput );
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
document.addEventListener( 'DOMContentLoaded', () => initializeURL() );

// Bind forms rendered after page load, such as the copy of the form an Elementor
// Pro popup builds each time it opens.
document.addEventListener( 'srfm_form_before_submission', ( e ) => {
	if ( e.detail?.form ) {
		initializeURL( e.detail.form );
	}
} );
