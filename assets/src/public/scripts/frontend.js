// Sender's Email.

const emailElements = document.getElementsByClassName(
	'srfm-input-email-container'
);

if ( emailElements.length > 0 ) {
	const emailAddress = document.getElementsByClassName( 'srfm-input-email' );
	emailAddress[ 0 ].addEventListener( 'input', ( e ) => {
		document.querySelector( '#srfm-sender-email' ).value = e.target.value;
	} );
}

//submit-button CSS

const submitButton = document.getElementsByClassName( 'srfm-button' );
if ( submitButton ) {
	// eslint-disable-next-line
	const rootStyles = getComputedStyle( document.documentElement );
	const primaryColorValue = rootStyles.getPropertyValue(
		'--srfm-primary-color'
	);
	const secondaryColorValue = rootStyles.getPropertyValue(
		'--srfm-secondary-color'
	);

	if ( primaryColorValue !== '' ) {
		for ( let i = 0; i < submitButton.length; i++ ) {
			submitButton[ i ].style.backgroundColor = primaryColorValue;
		}
	}
	if ( secondaryColorValue !== '' ) {
		for ( let i = 0; i < submitButton.length; i++ ) {
			submitButton[ i ].style.color = secondaryColorValue;
		}
	}
}

//page-break
document.addEventListener( 'DOMContentLoaded', function () {
	const forms = Array.from( document.querySelectorAll( '.srfm-form' ) );
	for ( const form of forms ) {
		const pageBreakContainers = form.querySelectorAll( '.srfm-page-break' );
		const submitBtn = form.querySelector( '.srfm-submit-container' );
		const progress = form.querySelector( '.srfm-progress' );
		const pageBreakLength = pageBreakContainers.length;
		const wraps = form.querySelectorAll( '.srfm-text-wrap' );
		let currentActive = 0;
		pageBreakContainers[ currentActive ].style.display = 'block';
		submitBtn.style.display = 'none';
		submitBtn.style.width = 'auto';
		submitBtn.style.margin = '0';
		const preBtn = form.querySelector( '.srfm-pre-btn' );
		const nxtBtn = form.querySelector( '.srfm-nxt-btn' );
		const originalNxtBtn = nxtBtn;
		if ( currentActive === 0 ) {
			preBtn.disabled = true;
		}
		nxtBtn.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			currentActive++;
			if ( currentActive === pageBreakLength - 1 ) {
				nxtBtn.style.display = 'none';
				submitBtn.style.display = 'block';
				nxtBtn.replaceWith( submitBtn );
			}
			if ( currentActive !== 0 ) {
				preBtn.disabled = false;
			}
			for ( let i = 0; i < pageBreakLength; i++ ) {
				if ( i === currentActive ) {
					pageBreakContainers[ currentActive ].style.display =
						'block';
				} else {
					pageBreakContainers[ i ].style.display = 'none';
				}
			}
			update();
		} );
		preBtn.addEventListener( 'click', ( e ) => {
			e.preventDefault();
			currentActive--;
			if ( currentActive <= 0 ) {
				preBtn.disabled = true;
			}
			if ( currentActive !== pageBreakLength - 1 ) {
				nxtBtn.disabled = false;
				submitBtn.style.display = 'none';
				originalNxtBtn.style.display = 'block';
				submitBtn.replaceWith( originalNxtBtn );
			}
			for ( let i = 0; i < pageBreakLength; i++ ) {
				if ( i === currentActive ) {
					pageBreakContainers[ currentActive ].style.display =
						'block';
				} else {
					pageBreakContainers[ i ].style.display = 'none';
				}
			}
			update();
		} );
		function update() {
			wraps.forEach( ( wrap, index ) => {
				if ( index <= currentActive ) {
					wrap.classList.add( 'active' );
				} else {
					wrap.classList.remove( 'active' );
				}
			} );

			progress.style.width =
				( currentActive / ( wraps.length - 1 ) ) * 90 + '%';
		}
	}
} );
