import { useState, useEffect } from '@wordpress/element';
import { RichText } from '@wordpress/block-editor';

export const CheckboxComponent = ( {
	attributes,
	setAttributes,
	blockID,
} ) => {
	const { label, checked: isChecked, required } = attributes;
	const [ selected, setSelected ] = useState( isChecked );
	const isRequired = required ? 'srfm-required' : '';
	useEffect( () => {
		setSelected( isChecked );
	}, [ isChecked ] );
	return (
		<div class="srfm-block-wrap">
			<input
				type="checkbox"
				checked={ selected }
				required={ required }
				onClick={ () => setSelected( ! selected ) }
				className=""
			/>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) =>
					setAttributes( { label: value } )
				}
				className={ `srfm-block-text srfm-span-wrap ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
		</div>
	);
};
