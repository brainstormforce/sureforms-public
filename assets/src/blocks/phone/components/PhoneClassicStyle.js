import { useEffect } from '@wordpress/element';

export const PhoneClassicStyle = ( { attributes, blockID } ) => {
	const { label, placeholder, required, autoCountry } = attributes;
	useEffect( () => {
		const phoneNumber = document.getElementById(
			`sfrm-classic-phone-${ blockID }`
		);
		const itlOptions = {};
		if ( autoCountry ) {
			itlOptions.initialCountry = 'auto';
			itlOptions.geoIpLookup = function ( callback ) {
				fetch( 'https://ipapi.co/json' )
					.then( function ( res ) {
						return res.json();
					} )
					.then( function ( data ) {
						callback( data.country_code );
					} )
					.catch( function () {
						callback( 'us' );
					} );
			};
		}
		window.intlTelInput( phoneNumber, itlOptions );
	}, [ autoCountry ] );

	return (
		<>
			<label
				className="sf-classic-label-text"
				htmlFor={ 'text-input-' + blockID }
			>
				{ label }
				{ required && label && (
					<span style={ { color: 'red' } }> *</span>
				) }
			</label>
			<div className="relative mt-2">
				<div className="group sf-classic-phone-parent">
					<input
						type="tel"
						className="sf-classic-phone-element"
						id={ `sfrm-classic-phone-${ blockID }` }
						placeholder={ placeholder }
						pattern="[0-9]{10}"
					/>
				</div>
			</div>
		</>
	);
};
