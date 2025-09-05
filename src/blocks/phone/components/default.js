import { RichText } from '@wordpress/block-editor';
import IntlTelInput from 'react-intl-tel-input';
import 'react-intl-tel-input/dist/main.css';
import { useEffect, useState } from '@wordpress/element';
import { decodeHtmlEntities } from '@Blocks/util';
import HelpText from '@Components/misc/HelpText';

export const PhoneComponent = ( { setAttributes, attributes, blockID } ) => {
	const { label, placeholder, required, autoCountry, defaultCountry, help } =
		attributes;
	const [ country, setCountry ] = useState( '' );

	const isRequired = required ? ' srfm-required' : '';
	const slug = 'phone';
	useEffect( () => {
		if ( autoCountry ) {
			fetch( 'https://ipapi.co/json' )
				.then( ( res ) => res.json() )
				.then( ( res ) => {
					let current_loc = res.country_code;
					current_loc = current_loc.toLowerCase();
					setCountry( current_loc );
				} )
				.catch( ( e ) => console.log( e ) );
		}
	}, [ autoCountry ] );

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => {
					setAttributes( { label: decodeHtmlEntities( value ) } );
				} }
				className={ `srfm-block-label${ isRequired }` }
				multiline={ false }
				id={ blockID }
				allowedFormats={ [] }
			/>
			<HelpText
				help={ help }
				setAttributes={ setAttributes }
				block_id={ blockID }
			/>
			<div className="srfm-block-wrap">
				<IntlTelInput
					containerClassName="intl-tel-input"
					inputClassName={ `srfm-input-common srfm-input-${ slug }` }
					fieldId={ `srfm-input-${ slug }-${ blockID }` }
					placeholder={ placeholder }
					autoPlaceholder={ false }
					pattern="[0-9]{10}"
					defaultCountry={
						autoCountry ? country : defaultCountry || 'us'
					}
					separateDialCode={ true }
				/>
			</div>
		</>
	);
};
