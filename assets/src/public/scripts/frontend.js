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
	const selectedRatingIndex = new Map();
	for ( let x = 0; x < inputLabels.length; x++ ) {
		selectedRatingIndex.set( inputLabels[ x ][ 0 ].className, -1 );
	}

	for ( let i = 0; i < ratingElements.length; i++ ) {
		ratingElements[ i ].addEventListener( 'click', ( e ) => {
			const clickArr = e.target.id.split( '-' );
			const clickedStarId = clickArr[ 2 ];
			const clickIndexId = Number( clickArr[ 3 ] );
			const selectedBlock = `sureforms-rating-${ clickedStarId }`;
			const isSelected = e.target;
			const label =
				ratingElements[ i ].nextElementSibling.querySelector( 'label' );
			const colorDataValue = label.getAttribute( 'color-data' );
			const iconColor = document.querySelector(
				`.sureforms-rating-icon-color-${ clickedStarId }`
			).value;

			if ( colorDataValue === iconColor ) {
				isSelected.value = '';
			} else {
				isSelected.value = clickIndexId + 1;
			}
			if ( selectedRatingIndex.get( selectedBlock ) === clickIndexId ) {
				selectedRatingIndex.set( selectedBlock, -1 );
			} else {
				selectedRatingIndex.set( selectedBlock, clickIndexId );
			}
			for ( let j = 0; j < inputLabels.length; j++ ) {
				for ( let k = 0; k < inputLabels[ j ].length; k++ ) {
					const hasClassName = inputLabels[ j ][
						k
					].classList.contains(
						`sureforms-rating-${ clickedStarId }`
					);

					if (
						k <=
							parseInt(
								selectedRatingIndex.get( selectedBlock )
							) &&
						inputLabels[ j ][ k ] &&
						hasClassName
					) {
						inputLabels[ j ][ k ].style.color = iconColor;
						inputLabels[ j ][ k ].setAttribute(
							'color-data',
							iconColor
						);
					} else if ( inputLabels[ j ][ k ] && hasClassName ) {
						inputLabels[ j ][ k ].style.color = '#ddd';
						inputLabels[ j ][ k ].setAttribute(
							'color-data',
							'#ddd'
						);
					}
					if (
						k ===
							parseInt(
								selectedRatingIndex.get( selectedBlock )
							) &&
						hasClassName
					) {
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

// Sender's Email.

const emailElements = document.getElementsByClassName(
	'sureforms-input-email-container'
);

if ( emailElements.length > 0 ) {
	const emailAddress = document.getElementsByClassName(
		'sureforms-input-email'
	);
	emailAddress[ 0 ].addEventListener( 'input', ( e ) => {
		document.querySelector( '#sureforms-sender-email' ).value =
			e.target.value;
	} );
}

// Upload Field.

const uploadFieldsContainer = document.getElementsByClassName(
	'sureforms-upload-container'
);

if ( uploadFieldsContainer ) {
	for ( const uploadField of uploadFieldsContainer ) {
		const fileSizeField = uploadField.querySelector(
			'.sureforms-upload-size'
		);
		const uploadInput = uploadField.querySelector(
			'.sureforms-upload-field'
		);

		uploadInput.addEventListener( 'change', ( e ) => {
			const id = e.target.id.split( '-' )[ 2 ];
			const file = e.target.files[ 0 ];
			const isError = uploadField.querySelector( '.error-message' );
			if ( isError ) {
				isError.style.display = 'none';
			}
			const maxFileSize = parseInt( fileSizeField.value ) * 1024 * 1024;
			if ( file ) {
				if ( file.size > maxFileSize ) {
					e.target.value = '';
					uploadField
						.querySelector( `#upload-field-error-${ id }` )
						.removeAttribute( 'hidden' );
				} else {
					const fileName =
						file.name.length > 20
							? file.name.substring( 0, 17 ) +
							  '...' +
							  file.name.split( '.' ).pop()
							: file.name;
					const isClassic = uploadField.classList.contains(
						'sf-classic-inputs-holder'
					);
					if ( ! isClassic ) {
						uploadField
							.querySelector( `#upload-field-error-${ id }` )
							.setAttribute( 'hidden', true );
						uploadField.querySelector(
							`#sureforms-upload-title-${ id }`
						).innerHTML =
							`<div class="sf-text-primary" style="display:flex; gap:0.4rem; align-items:center">
                        <i class="fa-solid fa-file-lines sf-text-primary"></i> ` +
							fileName +
							' ' +
							( file.size / 1000000 ).toFixed( 2 ) +
							`MB <i class="fa-sharp fa-solid fa-trash-can sf-text-primary" id="reset-upload-field" style="cursor:pointer"></i></div>`;
					}
					if ( isClassic ) {
						/* eslint-disable no-undef */
						const reader = new FileReader();
						reader.onload = function ( event ) {
							const imgSrc = event.target.result;
							const imageFormats = [
								'image/jpeg',
								'image/png',
								'image/gif',
								'image/bmp',
								'image/tiff',
								'image/webp',
								'image/svg+xml',
								'image/heif',
								'image/x-icon',
							];
							const icon = imageFormats.includes( file.type )
								? `<img class="rounded-md" src="${ imgSrc }" height="50px" width="50px"/>`
								: '<div style="font-size:35px" class="text-gray-300"><i class="fa-solid fa-file-lines"></i></div>';
							const uploadResultContainer =
								uploadField.querySelector(
									`#sureforms-upload-field-result-${ id }`
								);
							uploadResultContainer.innerHTML = `<div class="sf-text-primary w-full flex gap-2 p-[10px]">
								${ icon }
								<div class="w-full flex justify-between">
									<div>
										<div class="text-base">${ fileName }</div>
										<div class="text-sm text-gray-500"> ${ ( file.size / 1000000 ).toFixed(
		2
	) }MB</div>
									</div>
									<div>
  										<i class="fa-sharp fa-solid fa-trash-can text-gray-400" id="reset-upload-field" style="cursor:pointer"></i>
									</div>
								</div>
							</div>`;
							uploadResultContainer.style.display = 'flex';
							uploadField
								.querySelector( '#reset-upload-field' )
								.addEventListener( 'click', () => {
									uploadInput.value = '';
									uploadField.querySelector(
										`#sureforms-upload-field-result-${ id }`
									).style.display = 'none';
								} );
						};
						reader.readAsDataURL( file );
					}
				}
			}
		} );
	}
}

// Toggle Switch

const toggleSwitchesContainer = document.getElementsByClassName(
	'sf-classic-switch-container'
);

if ( toggleSwitchesContainer ) {
	for ( let i = 0; i < toggleSwitchesContainer.length; i++ ) {
		const toggleSwitch =
			toggleSwitchesContainer[ i ].querySelector( '.sureforms-switch' );
		const toggleLabel = toggleSwitchesContainer[ i ].querySelector(
			'.sureforms-switch-label'
		);
		const toggleSwitchCurrentId = toggleSwitch.getAttribute( 'id' );
		if ( toggleSwitch && toggleLabel ) {
			toggleSwitch.id = toggleSwitchCurrentId + i;
			toggleLabel.htmlFor = toggleSwitchCurrentId + i;
		}
		toggleSwitch.addEventListener( 'click', () => {
			const formElement = toggleSwitch.closest( 'form' );
			// eslint-disable-next-line no-undef

			if (
				toggleSwitch.classList.contains( 'sf-classic-switch-input' )
			) {
				const computedStyle = getComputedStyle( formElement );
				const primaryColor =
					computedStyle.getPropertyValue( '--sf-primary-color' );
				const currentValue = toggleSwitch.value;

				toggleSwitch.value = currentValue === 'true' ? 'false' : 'true';
				const switchBackground =
					toggleSwitchesContainer[ i ].querySelector(
						'.switch-background'
					);
				const switchToggle =
					toggleSwitchesContainer[ i ].querySelector(
						'.switch-toggle'
					);
				const switchTickIcon = toggleSwitchesContainer[
					i
				].querySelector( '.sf-classic-toggle-icon' );

				if ( toggleSwitch.value === 'true' ) {
					switchBackground.style.backgroundColor =
						primaryColor !== ''
							? 'var(--sf-primary-color)'
							: '#0284c7';
					switchTickIcon.style.fill =
						primaryColor !== ''
							? 'var(--sf-primary-color)'
							: '#0284c7';
					// will be used later

					// switchBackground[ i ].style.backgroundColor =
					// 	primaryColor !== '' ? 'var(--sf-primary-color)' : '#0284c7';
					// switchBackground[ i ].classList.remove( '!bg-[#E4E7EB]' );
					if (
						switchBackground
							.querySelector(
								'.sf-classic-toggle-icon-container'
							)
							.classList.contains( '!opacity-0' )
					) {
						switchBackground
							.querySelector(
								'.sf-classic-toggle-icon-container'
							)
							.classList.remove( '!opacity-0' );
						switchBackground
							.querySelector(
								'.sf-classic-toggle-icon-container'
							)
							.classList.add( '!opacity-100' );
						switchToggle.style.left = '24px';
						toggleSwitch.value = 'true';
					} else {
						switchBackground.style.backgroundColor = '#dcdcdc';

						switchBackground
							.querySelector(
								'.sf-classic-toggle-icon-container'
							)
							.classList.add( '!opacity-0' );
						switchBackground
							.querySelector(
								'.sf-classic-toggle-icon-container'
							)
							.classList.remove( '!opacity-100' );
						switchToggle.style.left = '0';
						toggleSwitch.value = 'false';
					}
					// will be used later

					// switchBackground[ i ].classList.add(
					// 	'sureform-toggle-background'
					// );
				} else {
					// will be used later
					// switchBackground[ i ].classList.remove(
					// 	'sureform-toggle-background'
					// );

					// switchBackground[ i ].classList.add( '!bg-[#E4E7EB]' );
					// will be used later
					// switchToggle[ i ].style.left = '2px';
					// eslint-disable-next-line no-lonely-if
					if (
						switchBackground
							.querySelector(
								'.sf-classic-toggle-icon-container'
							)
							.classList.contains( '!opacity-100' )
					) {
						switchBackground
							.querySelector(
								'.sf-classic-toggle-icon-container'
							)
							.classList.remove( '!opacity-100' );
						switchBackground
							.querySelector(
								'.sf-classic-toggle-icon-container'
							)
							.classList.add( '!opacity-0' );
						switchToggle.style.left = '0';
						switchBackground.style.backgroundColor = '#dcdcdc';
						toggleSwitch.value = 'false';
					} else {
						switchBackground
							.querySelector(
								'.sf-classic-toggle-icon-container'
							)
							.classList.remove( '!opacity-0' );
						switchBackground
							.querySelector(
								'.sf-classic-toggle-icon-container'
							)
							.classList.add( '!opacity-100' );
						switchToggle.style.left = '24px';
						switchBackground.style.backgroundColor =
							primaryColor !== ''
								? 'var(--sf-primary-color)'
								: '#0284c7';
						switchTickIcon.style.fill =
							primaryColor !== ''
								? 'var(--sf-primary-color)'
								: '#0284c7';
						toggleSwitch.value = 'true';
					}
				}
			} else {
				const currentValue = toggleSwitch.value;
				toggleSwitch.value = currentValue === 'true' ? 'false' : 'true';
				const switchBackground =
					toggleSwitchesContainer[ i ].querySelector(
						'.switch-background'
					);
				const switchToggle =
					toggleSwitchesContainer[ i ].querySelector(
						'.switch-toggle'
					);
				if ( toggleSwitch.value === 'true' ) {
					switchBackground.style.backgroundColor = '#007CBA';
					switchToggle.style.left = '27px';
				} else {
					switchBackground[ i ].style.backgroundColor = '#dcdcdc';
					switchToggle.style.left = '2px';
				}
			}
		} );
	}
}

// default switch

const toggleSwitches = document.getElementsByClassName( 'sf-default-switch' );

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
	const selectedOptions = new Map();
	for ( let i = 0; i < multiChoices.length; i++ ) {
		multiChoices[ i ].addEventListener( 'click', ( e ) => {
			const clickArr = e.target.id.split( '-' );
			const clickedId = clickArr[ 3 ];
			const selectedInd = Number( clickArr[ 4 ] );

			const sureformsMultiChoiceLabel = document.getElementsByClassName(
				`sureforms-multi-choice-label-${ clickedId }`
			);

			if (
				'buttons' ===
				document.getElementById(
					`sureforms-multi-choice-style-${ clickedId }`
				).value
			) {
				const selectContainer = document.getElementById(
					`sureforms-multi-choice-container-${ clickedId }`
				);
				selectContainer.classList.add( 'sf--focus' );
				const singleSelection = document.getElementById(
					`sureforms-multi-choice-selection-${ clickedId }`
				).value;
				if (
					singleSelection &&
					sureformsMultiChoiceLabel.length !== 0
				) {
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
					sureformsMultiChoiceLabel[
						selectedInd
					].style.backgroundColor = 'black';
					sureformsMultiChoiceLabel[ selectedInd ].style.color =
						'white';
				} else if ( sureformsMultiChoiceLabel.length !== 0 ) {
					const backgroundColor =
						sureformsMultiChoiceLabel[ selectedInd ].style
							.backgroundColor;
					const color =
						sureformsMultiChoiceLabel[ selectedInd ].style.color;
					if ( backgroundColor === 'black' && color === 'white' ) {
						sureformsMultiChoiceLabel[
							selectedInd
						].style.backgroundColor = 'white';
						sureformsMultiChoiceLabel[ selectedInd ].style.color =
							'black';
					} else {
						sureformsMultiChoiceLabel[
							selectedInd
						].style.backgroundColor = 'black';
						sureformsMultiChoiceLabel[ selectedInd ].style.color =
							'white';
					}
				}
			}
			const singleSelection = document.getElementById(
				`sureforms-multi-choice-selection-${ clickedId }`
			).value;
			const selectedValue = document.getElementById(
				`multi-choice-option-${ clickedId }-${ selectedInd }`
			).innerText;
			if ( ! selectedOptions.has( clickedId ) ) {
				selectedOptions.set( clickedId, [] );
			}
			const curr_block = selectedOptions.get( clickedId );
			if ( singleSelection ) {
				if ( ! curr_block.includes( selectedInd + 1 ) ) {
					selectedOptions.set( clickedId, [ selectedValue ] );
				} else {
					selectedOptions.set( clickedId, [] );
				}
			} else if ( curr_block.includes( selectedValue ) ) {
				const index = curr_block.indexOf( selectedValue );
				if ( index !== -1 ) {
					curr_block.splice( index, 1 );
				}
			} else {
				curr_block.push( selectedValue );
			}

			const multiChoiceValueField = document.getElementsByClassName(
				`sureforms-multi-choice-${ clickedId }`
			);
			multiChoiceValueField[ 0 ].value = selectedOptions
				.get( clickedId )
				.join( ', ' );
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
		};

		addressLine1.addEventListener( 'change', updateFullAddress );
		addressLine2.addEventListener( 'change', updateFullAddress );
		addressCity.addEventListener( 'change', updateFullAddress );
		addressState.addEventListener( 'change', updateFullAddress );
		addressPostal.addEventListener( 'change', updateFullAddress );
		addressCountry.addEventListener( 'change', updateFullAddress );
		addressCountry.addEventListener( 'click', () => {
			addressCountry.style.color = '#2c3338';
		} );
	}
}

// Date & Time Picker

const dateTimeElement = document.getElementsByClassName(
	'sureforms-input-date-container'
);

if ( dateTimeElement ) {
	for ( let i = 0; i < dateTimeElement.length; i++ ) {
		const blockID = dateTimeElement[ i ].id.split( '-' )[ 4 ];
		const dateInput = document.getElementById(
			`sureforms-input-date-${ blockID }`
		);
		const timeInput = document.getElementById(
			`sureforms-input-time-${ blockID }`
		);

		const fullDateTimeInput = document.getElementById(
			`sureforms-full-date-time-${ blockID }`
		);

		const updateFullDateTime = () => {
			let date = '';
			if ( dateInput ) {
				date = dateInput.value
					.trim()
					.split( /[\/-]/ )
					.reverse()
					.join( '-' );
			}
			let time = '';
			if ( timeInput ) {
				time = timeInput.value.trim();
			}
			const dateTimeParts = [ date, time ];

			const fullDateTime = dateTimeParts
				.filter( ( part ) => part !== '' )
				.join( ', ' );

			fullDateTimeInput.value = fullDateTime;
		};

		if ( dateInput ) {
			dateInput.addEventListener( 'change', updateFullDateTime );
		}
		if ( timeInput ) {
			timeInput.addEventListener( 'change', updateFullDateTime );
		}
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
			const countryCodeValue = countryCode.value
				.trim()
				.replace( /[^\d+]/g, '' );
			const phoneNumberValue = phoneNumber.value.trim();
			fullPhoneNumberInput.value = `(${ countryCodeValue }) ${ phoneNumberValue }`;
			if ( ! phoneNumberValue ) {
				fullPhoneNumberInput.value = '';
			}
		};

		countryCode.addEventListener( 'change', updateFullPhoneNumber );
		phoneNumber.addEventListener( 'change', updateFullPhoneNumber );
	}
}

//input range field

const sliderElement = document.getElementsByClassName(
	'sureforms-number-slider-input'
);

if ( sliderElement ) {
	for ( let i = 0; i < sliderElement.length; i++ ) {
		const blockID = sliderElement[ i ].id.split( '-' )[ 3 ];
		const sliderInput = document.getElementById(
			`sureforms-number-slider-${ blockID }`
		);
		sliderInput.addEventListener( 'input', ( e ) => {
			const slideValue = e.target.value;
			document.getElementById(
				`sureforms-number-slider-value-${ blockID }`
			).innerText = slideValue;
		} );
	}
}

//Number field
const numberElements = Array.from(
	document.getElementsByClassName( 'sureforms-input-number-container' )
);

if ( numberElements ) {
	for ( const numberContainer of numberElements ) {
		const numberInput = numberContainer.querySelector( 'input' );
		numberInput.addEventListener( 'input', ( e ) => {
			const formatType = numberInput.getAttribute( 'format-type' );
			let inputValue = e.target.value;
			if ( formatType === 'none' ) {
				return;
			}
			if ( formatType === 'non-decimal' ) {
				inputValue = inputValue.replace( /[^0-9]/g, '' );
			} else {
				inputValue = inputValue.replace( /[^0-9.]/g, '' );
				const dotCount = inputValue.split( '.' ).length - 1;
				if ( dotCount > 1 ) {
					inputValue = inputValue.replace( /\.+$/g, '' );
				}
			}
			numberInput.value = inputValue;
		} );
	}
}

const inputContainers = document.querySelectorAll( '.main-container' );

inputContainers.forEach( ( container ) => {
	const inputs = container.querySelectorAll( 'input, textarea, select' );

	inputs.forEach( ( input ) => {
		const isRatingInput = input.classList.contains(
			'sureforms-rating-field'
		);
		const isMultiInput = input.classList.contains(
			'sureforms-multi-choice-container'
		);
		const isFileInput = input.type === 'file';
		if ( isRatingInput || isFileInput || isMultiInput ) {
			input.addEventListener( 'click', () => {
				const focusedDivs = document.querySelector( '.sf--focus' );
				if ( focusedDivs ) {
					focusedDivs.classList.remove( 'sf--focus' );
				}
				container.classList.add( 'sf--focus' );
			} );
		} else {
			input.addEventListener( 'focus', () => {
				const focusedDivs = document.querySelector( '.sf--focus' );
				if ( focusedDivs ) {
					focusedDivs.classList.remove( 'sf--focus' );
				}
				container.classList.add( 'sf--focus' );
			} );

			input.addEventListener( 'blur', () => {
				container.classList.remove( 'sf--focus' );
			} );
		}
	} );
} );

const checkboxContainers = document.querySelectorAll(
	'.sureforms-checkbox-container'
);

if ( checkboxContainers ) {
	for ( let i = 0; i < checkboxContainers.length; i++ ) {
		{
			const formElement = checkboxContainers[ i ].closest( 'form' );
			// eslint-disable-next-line no-undef
			const computedStyle = getComputedStyle( formElement );
			const primaryColor =
				computedStyle.getPropertyValue( '--sf-primary-color' );
			const checkboxInputs = checkboxContainers[ i ].querySelectorAll(
				'.sureforms-classic-checkbox-input'
			);
			checkboxInputs.forEach( ( checkboxInput ) => {
				if ( '' === primaryColor ) {
					checkboxInput.classList.add(
						'!text-[#0084C7]',
						'focus:!ring-[#0084C7]',
						'checked:!bg-[#0084C7]',
						'checked:!border-none'
					);
				} else {
					checkboxInput.classList.add(
						'!text-sf_primary_color',
						'focus:!ring-sf_primary_color',
						'checked:!bg-sf_primary_color',
						'checked:!border-none'
					);
				}
			} );
		}
	}
}

//text-area field
const textAreaContainer = Array.from(
	document.getElementsByClassName( 'sureforms-textarea-container' )
);
if ( textAreaContainer ) {
	for ( const areaInput of textAreaContainer ) {
		const areaField = areaInput.querySelector( 'textarea' );
		areaField.addEventListener( 'input', function () {
			const textAreaValue = areaField.value;
			const maxLength = areaField.getAttribute( 'maxLength' );
			if ( maxLength !== '' ) {
				const counterDiv = areaInput.querySelector(
					'.sureforms-text-area-counter'
				);
				const remainingLength = maxLength - textAreaValue.length;
				counterDiv.innerText = remainingLength + '/' + maxLength;
			}
		} );
	}
}
//password strength
const passwordContainer = Array.from(
	document.getElementsByClassName( 'sureforms-input-password-container' )
);
if ( passwordContainer ) {
	for ( const passwordInput of passwordContainer ) {
		const isClassic = passwordInput.classList.contains(
			'sf-classic-inputs-holder'
		);
		if ( isClassic ) {
			continue;
		}
		const inputField = passwordInput.querySelector( 'input' );
		inputField.addEventListener( 'input', function () {
			const password = inputField.value;
			const passwordStrength = passwordInput.querySelector(
				'.password-strength-message'
			);
			passwordInput.querySelector( '.error-message' ).style.display =
				'none';
			if ( passwordInput.querySelector( '.info-icon' ) ) {
				passwordInput.querySelector( '.info-icon' ).style.display =
					'inline-block';
			}
			const strength = calculatePasswordStrength( password );
			updatePasswordStrength( strength, passwordStrength );
		} );
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

const selectFieldContainer = document.getElementsByClassName(
	'sureforms-classic-dropdown-container'
);

if ( selectFieldContainer ) {
	let i = 0;
	for ( const selectField of selectFieldContainer ) {
		const formElement = selectFieldContainer[ i ].closest( 'form' );
		// eslint-disable-next-line no-undef
		const computedStyle = getComputedStyle( formElement );
		const primaryColor =
			computedStyle.getPropertyValue( '--sf-primary-color' );
		i++;
		const selectFieldButton = selectField.querySelector(
			'.sureforms-classic-dropdown-button'
		);

		selectFieldButton.addEventListener( 'focus', () => {
			selectFieldButton
				.querySelector( '.sf-classic-select-icon ' )
				.classList.add( 'rotate-180', '!pl-4' );

			const nextSibling = selectFieldButton.nextElementSibling;
			const options = nextSibling.querySelectorAll(
				'.sf-classic-dropdown-option'
			);
			if ( '' === primaryColor ) {
				for ( let index = 0; index < options.length; index++ ) {
					options[ index ].classList.remove(
						'hover:!bg-sf_primary_color'
					);
					options[ index ].classList.add( 'hover:!bg-[#0084C7]' );
				}
			}
			const dropdownResultInput = selectField.querySelector(
				'.sf-classic-dropdown-result'
			);
			nextSibling.style.display = 'block';
			if ( nextSibling ) {
				nextSibling.classList.add( '!opacity-100' );
				nextSibling.classList.add( '!z-10' );
				nextSibling.classList.remove( '!opacity-0' );

				const liElements = nextSibling.querySelectorAll( 'ul li' );
				liElements.forEach( ( li ) => {
					li.addEventListener( 'click', ( event ) => {
						selectFieldButton
							.querySelector( '.sf-classic-select-icon ' )
							.classList.remove( 'rotate-180', '!pl-4' );
						selectFieldButton
							.querySelector( '.sf-classic-select-icon ' )
							.classList.add( 'rotate-0' );
						const selectedValue = event.target.textContent.trim();
						selectFieldButton.querySelector(
							'.sf-dropdown-value'
						).textContent = selectedValue;
						dropdownResultInput.value = selectedValue;

						nextSibling.classList.remove( '!opacity-100' );
						nextSibling.classList.remove( '!z-10' );
						nextSibling.classList.add( '!opacity-0' );
						nextSibling.style.display = 'none';
					} );
				} );
			}
		} );
		selectFieldButton.addEventListener( 'blur', () => {
			selectFieldButton
				.querySelector( '.sf-classic-select-icon ' )
				.classList.remove( 'rotate-180', '!pl-4' );
			selectFieldButton
				.querySelector( '.sf-classic-select-icon ' )
				.classList.add( 'rotate-0' );
			const nextSibling = selectFieldButton.nextElementSibling;
			nextSibling.classList.remove( '!opacity-100' );
			nextSibling.classList.remove( '!z-10' );
			nextSibling.classList.add( '!opacity-0' );
		} );
	}
}

//submit-button CSS

const submitButton = document.getElementsByClassName( 'sureforms-button' );
if ( submitButton ) {
	// eslint-disable-next-line
	const rootStyles = getComputedStyle( document.documentElement );
	const primaryColorValue =
		rootStyles.getPropertyValue( '--sf-primary-color' );
	const secondaryColorValue = rootStyles.getPropertyValue(
		'--sf-secondary-color'
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

const numberSliderContainer = document.getElementsByClassName(
	'sf-classic-number-slider'
);

if ( numberSliderContainer ) {
	// Bg Init
	const bgInit = ( element, val = 0, min = 0, max = 255, color ) => {
		color = color === '' ? '#0284c7' : color;
		// Background Change
		const valBg = ( ( val - min ) / ( max - min ) ) * 100;
		element.style.background = `linear-gradient(to right, ${ color } 0%, ${ color } ${ valBg }%, #fff ${ valBg }%, white 100%)`;
	};

	// Pre Init
	const preInit = ( sliderContainer, primaryColor ) => {
		const rangeSliders =
			sliderContainer.querySelector( '#range-slider-sf' );
		const val = Number( rangeSliders.value );
		const min = Number( rangeSliders.getAttribute( 'min' ) );
		const max = Number( rangeSliders.getAttribute( 'max' ) );
		bgInit( rangeSliders, val, min, max, primaryColor );
	};

	// Toggle Class
	const init = ( sliderContainer, primaryColor ) => {
		// Slider Range Change or Input
		const rangeSliders =
			sliderContainer.querySelector( '#range-slider-sf' );
		const numberInput = sliderContainer.querySelector(
			'.number-input-slider-sf'
		);
		rangeSliders.addEventListener( 'input', function ( e ) {
			// Prevent Default
			e.preventDefault();
			e.stopPropagation();
			// Background Change
			const val = Number( rangeSliders.value );
			const min = Number( rangeSliders.getAttribute( 'min' ) );
			const max = Number( rangeSliders.getAttribute( 'max' ) );
			bgInit( rangeSliders, val, min, max, primaryColor );

			// Assign value to slider input
			numberInput.value = rangeSliders.value;
		} );
		// Input Slider Input
		numberInput.addEventListener( 'input', function ( event ) {
			// Prevent Default
			event.preventDefault();
			event.stopPropagation();

			// Background Change
			const thisInput = this;
			let inputVal = Number( thisInput.value );
			const inputMin = Number( thisInput.getAttribute( 'min' ) );
			const inputMax = Number( thisInput.getAttribute( 'max' ) );

			// Max Validation
			if ( inputVal > inputMax ) {
				inputVal = inputMax;
				thisInput.value = inputVal;
			}

			// Min Validation
			if ( inputVal < inputMin ) {
				inputVal = inputMin;
				thisInput.value = inputVal;
			}

			// Background Change
			const sliderEle = rangeSliders;
			bgInit( sliderEle, inputVal, inputMin, inputMax, primaryColor );

			// Assign value to slider range.
			sliderEle.value = inputVal;
		} );
	};
	for ( const numberSlider of numberSliderContainer ) {
		const formElement = numberSlider.closest( 'form' );
		// eslint-disable-next-line no-undef
		const computedStyle = getComputedStyle( formElement );
		const primaryColor =
			computedStyle.getPropertyValue( '--sf-primary-color' );
		preInit( numberSlider, primaryColor );
		init( numberSlider, primaryColor );
	}
}
const classicRatingContainers = document.getElementsByClassName(
	'sureforms-classic-rating-container'
);
for ( const classicRating of classicRatingContainers ) {
	const icon = classicRating.querySelectorAll(
		'.sf-classic-event [data-te-rating-icon-ref]'
	);
	const ratingResult = classicRating.querySelector(
		'.sf-rating-field-result'
	);
	icon.forEach( ( el ) => {
		el.addEventListener( 'onSelect.te.rating', ( e ) => {
			const ratingValue = e.value;
			ratingResult.value = ratingValue;
		} );
	} );
}

const datePickerContainers = document.getElementsByClassName(
	'sf-classic-date-time-container'
);
if ( datePickerContainers ) {
	for ( const datePickerContainer of datePickerContainers ) {
		const datePicker = datePickerContainer.querySelector(
			'.sf-classic-date-time-picker'
		);
		const resultInput = datePickerContainer.querySelector(
			'.sf-classic-date-time-result'
		);
		const fieldType = resultInput.getAttribute( 'field-type' );
		const dateTimeInput = datePickerContainer.querySelector(
			'.sureforms-input-data-time'
		);
		let buttonAttribute = '';
		let eventType = '';
		if ( fieldType === 'date' ) {
			eventType = 'dateChange.te.datepicker';
			buttonAttribute = 'data-te-datepicker-toggle-ref';
		} else if ( fieldType === 'dateTime' ) {
			eventType = 'close.te.datetimepicker';
			buttonAttribute = 'data-te-date-timepicker-toggle-ref';
		} else {
			eventType = 'input.te.timepicker';
			buttonAttribute = 'data-te-timepicker-icon';
		}
		const button = datePickerContainer.querySelector(
			`button[${ buttonAttribute }]`
		);
		dateTimeInput.addEventListener( 'click', () => {
			const clickEvent = new Event( 'click' );
			if ( button ) {
				button.dispatchEvent( clickEvent );
			}
		} );
		if ( button ) {
			button.addEventListener( 'click', () => {
				datePicker.addEventListener( eventType, () => {
					formattedDate = dateTimeInput.value.replaceAll( '/', '-' );
					resultInput.value = formattedDate;
				} );
			} );
		} else {
			datePicker.addEventListener( eventType, () => {
				formattedDate = dateTimeInput.value.replaceAll( '/', '-' );
				resultInput.value = formattedDate;
			} );
		}
	}
}

const urlFiledContainers = document.getElementsByClassName(
	'sureforms-classic-input-url-container'
);
if ( urlFiledContainers ) {
	for ( const urlFiledContainer of urlFiledContainers ) {
		const urlInput = urlFiledContainer.querySelector(
			'.sureforms-url-input'
		);
		const validUrlMessage = urlFiledContainer.querySelector(
			'.validation-url-message'
		);
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
					'!ring-red-500',
					'!border-red-500',
					'placeholder:!text-red-300'
				);
			} else {
				validUrlMessage.style.display = 'block';
				urlInput.classList.add(
					'!ring-red-500',
					'!border-red-500',
					'placeholder:!text-red-300'
				);
			}
		} );
	}
}
