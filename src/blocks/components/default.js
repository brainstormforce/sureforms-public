import { useState, useEffect } from '@wordpress/element';
import { RichText } from '@wordpress/block-editor';
import { decodeHtmlEntities } from '@Blocks/util';

export const CheckboxComponent = ( {
	attributes,
	setAttributes,
	blockID,
	blockType,
} ) => {
	const { label, checked: isChecked, required } = attributes;
	const [ selected, setSelected ] = useState( isChecked );
	let isRequired = required ? 'srfm-required' : '';
	useEffect( () => {
		setSelected( isChecked );
	}, [ isChecked ] );

	if ( blockType === 'gdpr' ) {
		isRequired = 'srfm-required';
	}

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
					setAttributes( { label: decodeHtmlEntities( value ) } );
				} }
				className={ `srfm-block-text srfm-span-wrap ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
		</div>
	);
};
