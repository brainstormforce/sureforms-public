import { RichText } from '@wordpress/block-editor';
import IntlTelInput from 'react-intl-tel-input';
import 'react-intl-tel-input/dist/main.css';
import { useEffect, useState } from '@wordpress/element';
import { decodeHtmlEntities } from '@Blocks/util';
import HelpText from '@Components/misc/HelpText';

export const PhoneComponent = ( { setAttributes, attributes, blockID } ) => {
	const {
		label,
		placeholder,
		required,
		autoCountry,
		defaultCountry,
		help,
		enableCountryFilter,
		countryFilterType,
		includeCountries,
		excludeCountries,
	} = attributes;
	const [ country, setCountry ] = useState( '' );

	const isRequired = required ? ' srfm-required' : '';
	const slug = 'phone';

	// Determine initial country based on filter settings
	const getValidCountry = ( detectedCountry ) => {
		let validCountry = detectedCountry;

		if (
			enableCountryFilter &&
			countryFilterType === 'include' &&
			includeCountries?.length > 0
		) {
			// If include filter is active, check if country is in the list
			const countryLower = validCountry.toLowerCase();
			if ( ! includeCountries.includes( countryLower ) ) {
				// Country not in include list, use first country from the list
				validCountry = includeCountries[ 0 ];
			}
		} else if (
			enableCountryFilter &&
			countryFilterType === 'exclude' &&
			excludeCountries?.length > 0
		) {
			// If exclude filter is active, check if country is excluded
			const countryLower = validCountry.toLowerCase();
			if ( excludeCountries.includes( countryLower ) ) {
				// Country is excluded, use fallback
				validCountry = excludeCountries.includes( 'us' ) ? 'gb' : 'us';
			}
		}

		return validCountry;
	};

	useEffect( () => {
		if ( autoCountry && country === '' ) {
			fetch( 'https://ipapi.co/json' )
				.then( ( res ) => res.json() )
				.then( ( res ) => {
					let current_loc = res.country_code;
					current_loc = current_loc.toLowerCase();
					// Validate against filters
					current_loc = getValidCountry( current_loc );
					setCountry( current_loc );
				} )
				.catch( ( e ) => {
					console.log( e );
					// On error, set validated fallback
					const fallback = getValidCountry( 'us' );
					setCountry( fallback );
				} );
		}
	}, [
		autoCountry,
		enableCountryFilter,
		countryFilterType,
		includeCountries,
		excludeCountries,
	] );

	// Calculate the country to display
	const displayCountry = autoCountry
		? country
		: getValidCountry( defaultCountry || 'us' );

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
					defaultCountry={ displayCountry }
					separateDialCode={ true }
					onlyCountries={
						enableCountryFilter &&
						countryFilterType === 'include' &&
						includeCountries?.length > 0
							? includeCountries
							: undefined
					}
					excludeCountries={
						enableCountryFilter &&
						countryFilterType === 'exclude' &&
						excludeCountries?.length > 0
							? excludeCountries
							: undefined
					}
				/>
			</div>
		</>
	);
};
