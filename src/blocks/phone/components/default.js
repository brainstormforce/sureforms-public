import { RichText } from '@wordpress/block-editor';
import IntlTelInput from 'intl-tel-input/reactWithUtils';
import { useCallback, useEffect, useState } from '@wordpress/element';
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

	// Determine initial country based on filter settings.
	// Memoised so the useEffect below can safely list it as a dependency without
	// re-running on every render.
	const getValidCountry = useCallback(
		( detectedCountry ) => {
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
		},
		[
			enableCountryFilter,
			countryFilterType,
			includeCountries,
			excludeCountries,
		]
	);

	useEffect( () => {
		if ( autoCountry && country === '' ) {
			// Derive country from WP site locale set on <html lang="en-US">.
			// Guard against BCP 47 script subtags (sr-Latn, zh-Hans, zh-Hant,
			// az-Latn) — only accept a final 2-char region subtag, else fall back.
			const htmlLang = document.documentElement.lang || '';
			let detectedCountry = 'us';
			if ( htmlLang.includes( '-' ) ) {
				const parts = htmlLang.split( '-' );
				const lastPart = parts[ parts.length - 1 ] || '';
				if ( lastPart.length === 2 ) {
					detectedCountry = lastPart.toLowerCase();
				}
			}
			detectedCountry = getValidCountry( detectedCountry );
			setCountry( detectedCountry );
		}
	}, [ autoCountry, country, getValidCountry ] );

	// Calculate the country to display
	const displayCountry = autoCountry
		? country
		: getValidCountry( defaultCountry || 'us' );

	// Build initOptions with country filtering
	const initOptions = {
		initialCountry: displayCountry || 'us',
		separateDialCode: true,
		autoPlaceholder: 'off',
		countrySearch: true,
		formatOnDisplay: true,
		allowPhonewords: true,
	};

	// Apply country filtering if enabled
	if ( enableCountryFilter ) {
		if ( countryFilterType === 'include' && includeCountries?.length > 0 ) {
			initOptions.onlyCountries = includeCountries;
		} else if (
			countryFilterType === 'exclude' &&
			excludeCountries?.length > 0
		) {
			initOptions.excludeCountries = excludeCountries;
		}
	}

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
					inputProps={ {
						className: `srfm-input-common srfm-input-${ slug }`,
						id: `srfm-input-${ slug }-${ blockID }`,
						placeholder: placeholder || '',
					} }
					initOptions={ initOptions }
				/>
			</div>
		</>
	);
};
