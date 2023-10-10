import { useState } from '@wordpress/element';

export const SliderThemeStyle = ( { attributes, blockID } ) => {
	const { label, placeholder, required, valueDisplayText, min, max, step } =
		attributes;
	const [ slideValue, setSlideValue ] = useState( 0 );

	return (
		<>
			<label
				className="sf-text-primary"
				htmlFor={ 'number-slider-input-' + blockID }
			>
				{ label }
				{ required && label && (
					<span style={ { color: 'red' } }> *</span>
				) }
			</label>
			<input
				id={ 'number-slider-input-' + blockID }
				type="range"
				min={ min }
				max={ max }
				step={ step }
				value={ slideValue }
				onChange={ ( e ) => setSlideValue( e.target.value ) }
				placeholder={ placeholder }
				required={ required }
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
