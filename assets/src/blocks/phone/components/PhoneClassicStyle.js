import { useEffect } from '@wordpress/element';
import { RichText } from '@wordpress/block-editor';

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

		const intlTelInputInstance = window.intlTelInput(
			phoneNumber,
			itlOptions
		);
		// Return a cleanup function to destroy the current instance
		return () => {
			intlTelInputInstance.destroy();
		};
	}, [ autoCountry ] );

	const isRequired = required ? 'srfm-required' : '';

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-classic-label-text ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
			<div className="srfm-relative srfm-mt-2">
				<div className="srfm-group srfm-classic-phone-parent">
					<input
						type="tel"
						className="srfm-classic-phone-element"
						id={ `sfrm-classic-phone-${ blockID }` }
						placeholder={ placeholder }
						pattern="[0-9]{10}"
					/>
				</div>
			</div>
		</>
	);
};
