import { RichText } from '@wordpress/block-editor';
import IntlTelInput from 'react-intl-tel-input';
import 'react-intl-tel-input/dist/main.css';
import { useEffect, useState } from '@wordpress/element';

export const PhoneClassicStyle = ( { setAttributes, attributes, blockID } ) => {
	const { label, placeholder, required, autoCountry } = attributes;
	const [ country, setCountry ] = useState( '' );

	const isRequired = required ? 'srfm-required' : '';

	useEffect( () => {
		fetch( 'https://ipapi.co/json' )
			.then( ( res ) => res.json() )
			.then( ( res ) => {
				let current_loc = res.country_code;
				current_loc = current_loc.toLowerCase();
				setCountry( current_loc );
			} )
			.catch( ( e ) => console.log( e ) );
	}, [ autoCountry ] );

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
				<IntlTelInput
					containerClassName="intl-tel-input srfm-group srfm-classic-phone-parent"
					inputClassName="srfm-classic-phone-element"
					fieldId={ `sfrm-classic-phone-${ blockID }` }
					placeholder={ placeholder }
					pattern="[0-9]{10}"
					defaultCountry={ autoCountry ? country : 'us' }
				/>
			</div>
		</>
	);
};
