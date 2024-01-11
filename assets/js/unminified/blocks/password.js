function initializePasswordField() {
	const passwordElement = document.querySelectorAll(
		'.srfm-password-block-wrap .srfm-block'
	);

	if ( passwordElement ) {
		passwordElement.forEach( ( element ) => {
			const inputField = element.querySelector( 'input' );
			if ( inputField ) {
				inputField.addEventListener( 'input', function ( e ) {
					const password = e.target.value;
					const passwordStrength = element.querySelector(
						'.srfm-error-message'
					);

					element.classList.add( 'srfm-password-validate' );

					const isRequired =
						inputField.getAttribute( 'aria-required' );

					if ( isRequired && true === isRequired ) {
						element.classList.add( 'srfm-error' );
					}

					const strength = calculatePasswordStrength( password );
					updatePasswordStrength( strength, passwordStrength );

					if ( strength >= 2 ) {
						element.classList.remove( 'srfm-password-error' );
					} else {
						element.classList.add( 'srfm-password-error' );
					}

					if ( password.length <= 0 ) {
						resetCondition( element );
					}
				} );

				inputField.addEventListener( 'change', function ( e ) {
					const password = e.target.value;

					if ( password.length <= 0 ) {
						resetCondition( element );
					}
				} );
			}
		} );
	}

	function resetCondition( element ) {
		element.classList.remove( 'srfm-password-error' );
		element.classList.remove( 'srfm-password-validate' );
		element.classList.remove( 'srfm-strength-1' );
		element.classList.remove( 'srfm-strength-2' );
		element.classList.remove( 'srfm-strength-3' );
		element.classList.remove( 'srfm-strength-4' );
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

		const prevSibling = passwordStrength.closest( '.srfm-block' );

		switch ( strength ) {
			case 0:
				passwordStrength.textContent = '';
				break;
			case 1:
				prevSibling.classList.add( 'srfm-strength-1' );
				prevSibling.classList.remove( 'srfm-strength-2' );
				prevSibling.classList.remove( 'srfm-strength-3' );
				prevSibling.classList.remove( 'srfm-strength-4' );
				passwordStrength.textContent = 'Your password strength is weak';

				break;
			case 2:
				prevSibling.classList.remove( 'srfm-strength-1' );
				prevSibling.classList.add( 'srfm-strength-2' );
				prevSibling.classList.remove( 'srfm-strength-3' );
				prevSibling.classList.remove( 'srfm-strength-4' );
				passwordStrength.textContent =
					'Your password strength is moderate';
				break;
			case 4:
				prevSibling.classList.remove( 'srfm-strength-1' );
				prevSibling.classList.remove( 'srfm-strength-2' );
				prevSibling.classList.add( 'srfm-strength-3' );
				prevSibling.classList.remove( 'srfm-strength-4' );
				passwordStrength.textContent =
					'Your password strength is strong';
				break;
			case 5:
				prevSibling.classList.remove( 'srfm-strength-1' );
				prevSibling.classList.remove( 'srfm-strength-2' );
				prevSibling.classList.remove( 'srfm-strength-3' );
				prevSibling.classList.add( 'srfm-strength-4' );
				passwordStrength.textContent =
					'Your password strength is very strong';
				break;
			default:
				break;
		}
	}
}
document.addEventListener( 'DOMContentLoaded', initializePasswordField );
