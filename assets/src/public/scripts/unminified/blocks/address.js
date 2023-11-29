function initializeAddress() {
	const addressElement = document.getElementsByClassName(
		'srfm-address-container'
	);

	if ( addressElement ) {
		for ( let i = 0; i < addressElement.length; i++ ) {
			const blockID = addressElement[ i ].id.split( '-' )[ 3 ];
			const addressLine1 = document.getElementById(
				`srfm-address-line-1-${ blockID }`
			);
			const addressLine2 = document.getElementById(
				`srfm-address-line-2-${ blockID }`
			);
			const addressCity = document.getElementById(
				`srfm-address-city-${ blockID }`
			);
			const addressState = document.getElementById(
				`srfm-address-state-${ blockID }`
			);
			const addressPostal = document.getElementById(
				`srfm-address-postal-${ blockID }`
			);
			const addressCountry = document.getElementById(
				`srfm-address-country-${ blockID }`
			);

			const fullAddressInput = document.getElementById(
				`srfm-fullAddress-${ blockID }`
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
			if (
				addressLine1 &&
				addressLine2 &&
				addressCity &&
				addressState &&
				addressPostal &&
				addressCountry
			) {
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
	}
}
document.addEventListener( 'DOMContentLoaded', initializeAddress );
