/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import SRFMSelectControl from '@Components/select-control';
import RangeTypographyControl from './range-typography';
import googleFonts from '@Controls/fonts';
import Select from 'react-select';

const { srfm_select_font_globally, srfm_load_select_font_globally } =
	srfm_blocks_info;

function FontFamilyControl( props ) {
	const fonts = [
		{
			value: 'Default',
			label: __( 'Default', 'sureforms' ),
			weight: [
				'Default',
				'100',
				'200',
				'300',
				'400',
				'500',
				'600',
				'700',
				'800',
				'900',
			],
			google: false,
		},
		{
			value: 'Arial',
			label: 'Arial',
			weight: [
				'Default',
				'100',
				'200',
				'300',
				'400',
				'500',
				'600',
				'700',
				'800',
				'900',
			],
			google: false,
		},
		{
			value: 'Helvetica',
			label: 'Helvetica',
			weight: [
				'Default',
				'100',
				'200',
				'300',
				'400',
				'500',
				'600',
				'700',
				'800',
				'900',
			],
			google: false,
		},
		{
			value: 'Times New Roman',
			label: 'Times New Roman',
			weight: [
				'Default',
				'100',
				'200',
				'300',
				'400',
				'500',
				'600',
				'700',
				'800',
				'900',
			],
			google: false,
		},
		{
			value: 'Georgia',
			label: 'Georgia',
			weight: [
				'Default',
				'100',
				'200',
				'300',
				'400',
				'500',
				'600',
				'700',
				'800',
				'900',
			],
			google: false,
		},
	];

	let fontWeight = '';

	const customFonts = srfm_blocks_info.srfm_custom_fonts;

	//Push Google Fonts into stytem fonts object
	// eslint-disable-next-line array-callback-return
	Object.keys( googleFonts ).map( ( k ) => {
		fonts.push( { value: k, label: k, weight: googleFonts[ k ].weight } );

		if ( k === props.fontFamily.value ) {
			fontWeight = googleFonts[ k ].weight;
		}
	} );

	//Push custom Fonts into stytem fonts object.
	// eslint-disable-next-line array-callback-return
	Object.keys( customFonts ).map( ( k ) => {
		fonts.push( { value: k, label: k, weight: customFonts[ k ].weight } );
		if ( k === props.fontFamily.value ) {
			fontWeight = customFonts[ k ].weight;
		}
	} );

	// check if the font is a system font and then apply the font weight accordingly.
	if ( fontWeight === '' ) {
		fontWeight = fonts[ 0 ].weight;
	}

	const fontWeightObj = [];
	fontWeight.forEach( function ( item ) {
		fontWeightObj.push( {
			value: 'Default' === item ? '' : item,
			label: item,
		} );
	} );

	const onFontfamilyChange = ( value ) => {
		const font = value.value;
		const { loadGoogleFonts, fontFamily } = props; // eslint-disable-line no-shadow
		props.setAttributes( { [ fontFamily.label ]: font } );
		onLoadGoogleFonts( loadGoogleFonts, font );
	};

	const onLoadGoogleFonts = ( loadGoogleFonts, fontFamily ) => {
		let value;

		if (
			fontFamily !== '' &&
			typeof googleFonts[ fontFamily ] !== 'object'
		) {
			value = false;
		} else {
			value = true;
		}

		props.setAttributes( { [ loadGoogleFonts.label ]: value } );
	};

	const gFonts =
		srfm_load_select_font_globally === 'enabled' &&
		srfm_select_font_globally !== 0
			? srfm_select_font_globally
			: fonts;

	const customSelectStyles = {
		container: ( provided ) => ( {
			...provided,
			width: '100%',
		} ),
		control: ( provided ) => ( {
			...provided,
			border: '1px solid #E6E7E9',
			boxShadow: 'none',
			height: '30px',
			minHeight: '30px',
			borderRadius: '3px',
		} ),
		placeholder: ( provided ) => ( {
			...provided,
			color: '#50575E',
		} ),
		menu: ( provided ) => ( {
			...provided,
			color: '#50575E',
		} ),
		singleValue: ( provided ) => ( {
			...provided,
			color: '#50575E',
			top: '50%',
			transform: 'translateY(-50%);',
		} ),
		indicatorSeparator: ( provided ) => ( {
			...provided,
			display: 'none',
		} ),
		dropdownIndicator: ( provided ) => ( {
			...provided,
			color: '#50575E',
		} ),
		valueContainer: ( provided ) => ( {
			...provided,
			height: '30px',
			padding: '0px 8px',
		} ),
	};

	let fontFamilyValue;
	//Push Google Fonts into stytem fonts object
	if ( gFonts ) {
		// eslint-disable-next-line array-callback-return
		gFonts.map( ( font ) => {
			if (
				! props.fontFamily.weight &&
				font.value === props.fontFamily.value
			) {
				fontFamilyValue = {
					...props.fontFamily,
					weight: font.weight,
					label: font.value,
				};
			}
		} );
	}

	let fontSize;
	const fontSizeStepsVal = 'em' === props.fontSizeType.value ? 0.1 : 1; // fractional value when unit is em.
	if ( true !== props.disableFontSize ) {
		fontSize = (
			<RangeTypographyControl
				type={ props.fontSizeType }
				typeLabel={ props.fontSizeType.label }
				sizeMobile={ props.fontSizeMobile }
				sizeMobileLabel={ props.fontSizeMobile.label }
				sizeTablet={ props.fontSizeTablet }
				sizeTabletLabel={ props.fontSizeTablet.label }
				size={ props.fontSize }
				sizeLabel={ props.fontSize.label }
				min={ 0 }
				sizeMobileText={
					! props.fontSizeLabel
						? __( 'Font Size', 'sureforms' )
						: props.fontSizeLabel
				}
				sizeTabletText={
					! props.fontSizeLabel
						? __( 'Font Size', 'sureforms' )
						: props.fontSizeLabel
				}
				sizeText={
					! props.fontSizeLabel
						? __( 'Font Size', 'sureforms' )
						: props.fontSizeLabel
				}
				step={ fontSizeStepsVal }
				{ ...props }
			/>
		);
	}

	return (
		<>
			{ /* Font Family */ }
			<div className="components-base-control srfm-font-family-searchable-select__wrapper">
				<label
					className="components-input-control__label"
					htmlFor="font-family"
				>
					{ __( 'Font Family', 'sureforms' ) }
				</label>
				<Select
					styles={ customSelectStyles }
					placeholder={ __( 'Default', 'sureforms' ) }
					onChange={ onFontfamilyChange }
					options={ gFonts }
					value={ fontFamilyValue }
					defaultValue={ fontFamilyValue }
					isSearchable={ true }
					className="srfm-font-family-searchable-select"
					classNamePrefix="srfm-font-family-select"
				/>
			</div>
			{ /* Font Size*/ }
			{ fontSize }
			{ /* Font Weitght */ }
			<SRFMSelectControl
				label={ __( 'Weight', 'sureforms' ) }
				data={ {
					value: props.fontWeight.value,
					label: props.fontWeight.label,
				} }
				setAttributes={ props.setAttributes }
				options={ fontWeightObj }
			/>
			{ /* Font Style */ }
			{ props.fontStyle && (
				<SRFMSelectControl
					label={ __( 'Style', 'sureforms' ) }
					data={ {
						value: props.fontStyle.value,
						label: props.fontStyle.label,
					} }
					setAttributes={ props.setAttributes }
					options={ [
						{
							value: 'normal',
							label: __( 'Default', 'sureforms' ),
						},
						{
							value: 'italic',
							label: __( 'Italic', 'sureforms' ),
						},
						{
							value: 'oblique',
							label: __( 'Oblique', 'sureforms' ),
						},
					] }
				/>
			) }
		</>
	);
}

export default FontFamilyControl;
