import { RichText } from '@wordpress/block-editor';

export const NumberClassicStyle = ( {
	attributes,
	blockID,
	handleInput,
	setAttributes,
} ) => {
	const {
		label,
		placeholder,
		required,
		defaultValue,
		minValue,
		maxValue,
		formatType,
	} = attributes;

	const isRequired = required ? 'srfm-required' : '';

	return (
		<>
			<div className="srfm-classic-inputs-holder">
				<RichText
					tagName="label"
					value={ label }
					onChange={ ( value ) => setAttributes( { label: value } ) }
					className={ `srfm-classic-label-text ${ isRequired }` }
					multiline={ false }
					id={ blockID }
				/>
				<input
					className={ ' srfm-classic-number-element' }
					id={ 'srfm-number-input-' + blockID }
					type={ formatType === 'none' ? 'number' : 'text' }
					value={ defaultValue }
					onChange={ handleInput }
					placeholder={ placeholder }
					required={ required }
					min={ minValue }
					max={ maxValue }
				/>
			</div>
		</>
	);
};
