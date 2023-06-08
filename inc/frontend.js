// Rating Field.

const ratingElements = document.getElementsByClassName(
	'sureforms-rating-field'
);

if ( ratingElements ) {
	const randomIds = document.getElementsByClassName(
		'sureforms-rating-random-id'
	);
	const inputLabels = [];

	for ( let i = 0; i < randomIds.length; i++ ) {
		const inputLabel = document.getElementsByClassName(
			'sureforms-rating-' + randomIds[ i ].value
		);
		if ( inputLabel.length > 0 ) {
			inputLabels.push( inputLabel );
		}
	}

	let selectedRatingIndex = -1;

	for ( let i = 0; i < ratingElements.length; i++ ) {
		ratingElements[ i ].addEventListener( 'click', () => {
			const clickedRatingIndex = i.toString();
			if ( selectedRatingIndex === clickedRatingIndex ) {
				selectedRatingIndex = -1;
			} else {
				selectedRatingIndex = clickedRatingIndex;
			}
			for ( let j = 0; j < inputLabels.length; j++ ) {
				for ( let k = 0; k < inputLabels[ j ].length; k++ ) {
					if (
						k <= parseInt( selectedRatingIndex ) &&
						inputLabels[ j ][ k ]
					) {
						inputLabels[ j ][ k ].style.color =
							document.getElementsByClassName(
								'sureforms-rating-icon-color'
							)[ 0 ].value;
					} else if ( inputLabels[ j ][ k ] ) {
						inputLabels[ j ][ k ].style.color = '#ddd';
					}
					if ( k === parseInt( selectedRatingIndex ) ) {
						inputLabels[ j ][ k ].style.fontSize = '30px';
					} else {
						inputLabels[ j ][ k ].style.fontSize = '25px';
					}
				}
			}
		} );

		ratingElements[ i ].setAttribute( 'hidden', 'true' );
	}
}

// Upload Field.

const uploadFields = document.getElementsByClassName(
	'sureforms-upload-field'
);

if ( uploadFields ) {
	const fileSizeField = document.getElementsByClassName(
		'sureforms-upload-size'
	);
	for ( let i = 0; i < uploadFields.length; i++ ) {
		uploadFields[ i ].addEventListener( 'change', ( e ) => {
			const file = e.target.files[ 0 ];
			const maxFileSize =
				parseInt( fileSizeField[ 0 ].value ) * 1024 * 1024;
			if ( file ) {
				if ( file.size > maxFileSize ) {
					e.target.value = '';
					document
						.getElementById( 'upload-field-error' )
						.removeAttribute( 'hidden' );
				} else {
					document
						.getElementById( 'upload-field-error' )
						.setAttribute( 'hidden', true );
					const fileName =
						file.name.length > 20
							? file.name.substring( 0, 17 ) +
							  '...' +
							  file.name.split( '.' ).pop()
							: file.name;
					document.getElementById(
						'sureforms-upload-title'
					).innerHTML =
						`<div style="display:flex; gap:0.4rem; align-items:center">
                        <i class="fa-solid fa-file-lines"></i> ` +
						fileName +
						' ' +
						( file.size / 1000000 ).toFixed( 2 ) +
						`MB <i class="fa-sharp fa-solid fa-trash-can" id="reset-upload-field" style="cursor:pointer"></i></div>`;
					document
						.getElementById( 'reset-upload-field' )
						.addEventListener( 'click', () => {
							uploadFields.value = '';
							document.getElementById(
								'sureforms-upload-title'
							).innerHTML = `<i class="fa-solid fa-cloud-arrow-up"></i>
                                <span>Click to choose the file</span>`;
						} );
				}
			}
		} );
	}
}

// Toggle Switch

const toggleSwitches = document.getElementsByClassName( 'sureforms-switch' );

if ( toggleSwitches ) {
	for ( let i = 0; i < toggleSwitches.length; i++ ) {
		toggleSwitches[ i ].addEventListener( 'click', () => {
			const currentValue = toggleSwitches[ i ].value;
			toggleSwitches[ i ].value =
				currentValue === 'true' ? 'false' : 'true';
			const switchBackground =
				document.getElementsByClassName( 'switch-background' );
			const switchToggle =
				document.getElementsByClassName( 'switch-toggle' );
			if ( toggleSwitches[ i ].value === 'true' ) {
				switchBackground[ i ].style.backgroundColor = '#007CBA';
				switchToggle[ i ].style.left = '27px';
			} else {
				switchBackground[ i ].style.backgroundColor = '#dcdcdc';
				switchToggle[ i ].style.left = '2px';
			}
		} );
	}
}

// Multi Choice

const multiChoices = document.getElementsByClassName(
	'sureforms-multi-choice'
);

if ( multiChoices ) {
	let selectedOptions = [];

	for ( let i = 0; i < multiChoices.length; i++ ) {
		multiChoices[ i ].addEventListener( 'click', () => {
			const sureformsMultiChoiceLabel = document.getElementsByClassName(
				'sureforms-multi-choice-label'
			);

			if (
				'buttons' ===
				document.getElementById( 'sureforms-multi-choice-style' ).value
			) {
				const singleSelection = document.getElementById(
					'sureforms-multi-choice-selection'
				).value;
				if ( singleSelection ) {
					// Reset background color and text color for all labels
					for (
						let j = 0;
						j < sureformsMultiChoiceLabel.length;
						j++
					) {
						sureformsMultiChoiceLabel[ j ].style.backgroundColor =
							'white';
						sureformsMultiChoiceLabel[ j ].style.color = 'black';
					}

					// Set background color and text color for the selected label
					sureformsMultiChoiceLabel[ i - 1 ].style.backgroundColor =
						'black';
					sureformsMultiChoiceLabel[ i - 1 ].style.color = 'white';
				} else {
					const backgroundColor =
						sureformsMultiChoiceLabel[ i - 1 ].style
							.backgroundColor;
					const color =
						sureformsMultiChoiceLabel[ i - 1 ].style.color;
					if ( backgroundColor === 'black' && color === 'white' ) {
						sureformsMultiChoiceLabel[
							i - 1
						].style.backgroundColor = 'white';
						sureformsMultiChoiceLabel[ i - 1 ].style.color =
							'black';
					} else {
						sureformsMultiChoiceLabel[
							i - 1
						].style.backgroundColor = 'black';
						sureformsMultiChoiceLabel[ i - 1 ].style.color =
							'white';
					}
				}
			}
			const singleSelection = document.getElementById(
				'sureforms-multi-choice-selection'
			).value;
			if ( singleSelection ) {
				if ( ! selectedOptions.includes( i ) ) {
					selectedOptions = [ i ];
				} else {
					selectedOptions = [];
				}
			} else if ( selectedOptions.includes( i ) ) {
				const index = selectedOptions.indexOf( i );
				if ( index !== -1 ) {
					selectedOptions.splice( index, 1 );
				}
				console.log( { index, selectedOptions } );
			} else {
				selectedOptions.push( i );
			}

			const multiChoiceValueField = document.getElementsByClassName(
				'sureforms-multi-choice'
			);
			multiChoiceValueField[ 0 ].value = selectedOptions;
		} );
	}
}

// Address Field

const addressElement = document.getElementsByClassName(
	'sureforms-address-container'
);

if ( addressElement ) {
	for ( let i = 0; i < addressElement.length; i++ ) {
		const blockID = addressElement[ i ].id.split( '-' )[ 3 ];
		const addressLine1 = document.getElementById(
			`sureforms-address-line-1-${ blockID }`
		);
		const addressLine2 = document.getElementById(
			`sureforms-address-line-2-${ blockID }`
		);
		const addressCity = document.getElementById(
			`sureforms-address-city-${ blockID }`
		);
		const addressState = document.getElementById(
			`sureforms-address-state-${ blockID }`
		);
		const addressPostal = document.getElementById(
			`sureforms-address-postal-${ blockID }`
		);
		const addressCountry = document.getElementById(
			`sureforms-address-country-${ blockID }`
		);

		const fullAddressInput = document.getElementById(
			`fullAddress-${ blockID }`
		);

		const updateFullAddress = () => {
			const addressLine1Value = addressLine1.value.trim();
			const addressLine2Value = addressLine2.value.trim();
			const addressCityValue = addressCity.value.trim();
			const addressStateValue = addressState.value.trim();
			const addressPostalValue = addressPostal.value.trim();
			const addressCountryValue = addressCountry.value.trim();

			const addressParts = [
				addressLine1Value,
				addressLine2Value,
				addressCityValue,
				addressStateValue,
				addressPostalValue,
				addressCountryValue,
			];

			const fullAddress = addressParts
				.filter( ( part ) => part !== '' )
				.join( ', ' );

			fullAddressInput.value = fullAddress;
			//console.log('fullAdd',fullAddress)
		};

		addressLine1.addEventListener( 'change', updateFullAddress );
		addressLine2.addEventListener( 'change', updateFullAddress );
		addressCity.addEventListener( 'change', updateFullAddress );
		addressState.addEventListener( 'change', updateFullAddress );
		addressPostal.addEventListener( 'change', updateFullAddress );
		addressCountry.addEventListener( 'change', updateFullAddress );
	}
}

// Phone Field

const phoneElement = document.getElementsByClassName(
	'sureforms-input-phone-container'
);

if ( phoneElement ) {
	for ( let i = 0; i < phoneElement.length; i++ ) {
		const blockID = phoneElement[ i ].id.split( '-' )[ 3 ];
		const countryCode = document.getElementById(
			`sureforms-country-code-${ blockID }`
		);
		const phoneNumber = document.getElementById(
			`sureforms-phone-number-${ blockID }`
		);
		const fullPhoneNumberInput = document.getElementById(
			`fullPhoneNumber-${ blockID }`
		);
		const updateFullPhoneNumber = () => {
			const countryCodeValue = countryCode.value.trim();
			const phoneNumberValue = phoneNumber.value.trim();
			fullPhoneNumberInput.value = `(${ countryCodeValue }) ${ phoneNumberValue }`;
		};

		countryCode.addEventListener( 'change', updateFullPhoneNumber );
		phoneNumber.addEventListener( 'change', updateFullPhoneNumber );
	}
}
