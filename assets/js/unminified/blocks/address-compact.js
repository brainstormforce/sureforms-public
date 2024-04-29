function initializeAddress() {
	const addressBlocks = document.querySelectorAll(
		'.srfm-address-compact-block'
	);

	if ( addressBlocks ) {
		addressBlocks.forEach( ( element ) => {
			const fullAddressInput = element.querySelector(
				'.srfm-input-address-compact-hidden'
			);
			const addressLine1 = element.querySelector(
				'.srfm-input-address-compact-line-1'
			);
			const addressLine2 = element.querySelector(
				'.srfm-input-address-compact-line-2'
			);
			const city = element.querySelector(
				'.srfm-input-address-compact-city'
			);
			const state = element.querySelector(
				'.srfm-input-address-compact-state'
			);
			const country = element.querySelector(
				'.srfm-input-address-compact-country'
			);
			const postalCode = element.querySelector(
				'.srfm-input-address-compact-postal-code'
			);

			const updateFullAddress = () => {
				const addressLine1Value = addressLine1?.value
					? addressLine1.value.trim()
					: '';
				const addressLine2Value = addressLine2?.value
					? addressLine2.value.trim()
					: '';
				const cityValue = city?.value ? city.value.trim() : '';
				const stateValue = state?.value ? state.value.trim() : '';
				const postalCodeValue = postalCode?.value
					? postalCode.value.trim()
					: '';
				const countryValue = country ? country?.value.trim() : '';

				const addressParts = [
					addressLine1Value,
					addressLine2Value,
					cityValue,
					stateValue,
					postalCodeValue,
					countryValue,
				];

				const fullAddress = addressParts
					.filter( ( part ) => part !== '' )
					.join( ', ' );

				fullAddressInput.setAttribute( 'value', fullAddress );
			};

			if ( addressLine1 ) {
				addressLine1.addEventListener( 'change', updateFullAddress );
			}

			if ( addressLine2 ) {
				addressLine2.addEventListener( 'change', updateFullAddress );
			}

			if ( city ) {
				city.addEventListener( 'change', updateFullAddress );
			}

			if ( state ) {
				state.addEventListener( 'change', updateFullAddress );
			}

			if ( country ) {
				country.addEventListener( 'change', updateFullAddress );
			}

			if ( postalCode ) {
				postalCode.addEventListener( 'change', updateFullAddress );
			}
		} );
	}
}
document.addEventListener( 'DOMContentLoaded', initializeAddress );
