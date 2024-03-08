import { useState, useEffect } from '@wordpress/element';
import { RichText } from '@wordpress/block-editor';
import { decodeHtmlEntities } from '@Blocks/util';

export const CheckboxComponent = ( { attributes, setAttributes, blockID } ) => {
	const { label, checked: isChecked, required } = attributes;
	const [ selected, setSelected ] = useState( isChecked );
	const isRequired = required ? 'srfm-required' : '';
	useEffect( () => {
		setSelected( isChecked );
	}, [ isChecked ] );
	return (
		<div className="srfm-block-wrap">
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
				onChange={ ( value ) => {
					const decodedValue = decodeHtmlEntities( value );
					setAttributes( { label: decodedValue } );
				} }
				className={ `srfm-block-text srfm-span-wrap ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
		</div>
	);
};
