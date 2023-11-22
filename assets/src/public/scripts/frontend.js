
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

//password strength
const passwordContainer = Array.from(
	document.getElementsByClassName( 'srfm-input-password-container' )
);
if ( passwordContainer ) {
	for ( const passwordInput of passwordContainer ) {
		const isClassic = passwordInput.classList.contains(
			'srfm-classic-inputs-holder'
		);
		if ( isClassic ) {
			continue;
		}
		const inputField = passwordInput.querySelector( 'input' );
		if ( inputField ) {
			inputField.addEventListener( 'input', function () {
				const password = inputField.value;
				const passwordStrength = passwordInput.querySelector(
					'.srfm-password-strength-message'
				);
				passwordInput.querySelector(
					'.srfm-error-message'
				).style.display = 'none';
				if ( passwordInput.querySelector( '.srfm-info-icon' ) ) {
					passwordInput.querySelector(
						'.srfm-info-icon'
					).style.display = 'inline-block';
				}
				const strength = calculatePasswordStrength( password );
				updatePasswordStrength( strength, passwordStrength );
			} );
		}
	}
}

function calculatePasswordStrength( password ) {
	let strength = 0;

	// Evaluate the strength based on your desired criteria
	if ( password.length >= 8 ) {
		strength += 1;
	}
	if ( /[a-z]/.test( password ) ) {
		strength += 1;
	}
	if ( /[A-Z]/.test( password ) ) {
		strength += 1;
	}
	if ( /\d/.test( password ) ) {
		strength += 1;
	}
	if ( /[!@#$%^&*]/.test( password ) ) {
		strength += 1;
	}

	return strength;
}

function updatePasswordStrength( strength, passwordStrength ) {
	// Update the UI to reflect the password strength
	switch ( strength ) {
		case 0:
			passwordStrength.textContent = '';
			break;
		case 1:
			passwordStrength.style.color = '#FF0000';
			passwordStrength.textContent = 'Your password strength is weak';
			break;
		case 2:
			passwordStrength.style.color = '#FFBF00';
			passwordStrength.textContent = 'Your password strength is moderate';
			break;
		case 4:
			passwordStrength.style.color = '#00FF7F';
			passwordStrength.textContent = 'Your password strength is strong';
			break;
		case 5:
			passwordStrength.style.color = '#008000';
			passwordStrength.textContent =
				'Your password strength is very strong';
			break;
		default:
			break;
	}
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
