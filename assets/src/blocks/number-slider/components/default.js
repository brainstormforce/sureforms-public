import { useState, useEffect } from '@wordpress/element';
import { RichText } from '@wordpress/block-editor';

export const NumberSliderComponent = ( {
	attributes,
	blockID,
	setAttributes,
} ) => {
	const { label, placeholder, min, max, step } = attributes;
	const slug = "number-slider";
	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-block-label` }
				multiline={ false }
				id={ blockID }
			/>
			<input
				id={ `srfm-${slug}-${blockID}` }
				type="range"
				min={ min }
				max={ max }
				step={ step }
				placeholder={ placeholder }
				className={`srfm-input-${slug}`}
			/>
		</>
	);
};
