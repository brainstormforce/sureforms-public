import { useState } from '@wordpress/element';
import { RichText } from '@wordpress/block-editor';

export const SliderThemeStyle = ( { attributes, blockID, setAttributes } ) => {
	const { label, placeholder, valueDisplayText, min, max, step } = attributes;
	const [ slideValue, setSlideValue ] = useState( 0 );

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-text-primary` }
				multiline={ false }
				id={ blockID }
			/>
			<input
				id={ 'number-slider-input-' + blockID }
				type="range"
				min={ min }
				max={ max }
				step={ step }
				value={ slideValue }
				onChange={ ( e ) => setSlideValue( e.target.value ) }
				placeholder={ placeholder }
			/>
			<div
				style={ {
					fontSize: '14px',
					fontWeight: 600,
					color: 'black',
				} }
			>
				{ valueDisplayText + slideValue }
			</div>
		</>
	);
};
