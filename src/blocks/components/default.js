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

	let inputClassname = 'srfm-input-checkbox';

	if ( blockType === 'gdpr' ) {
		isRequired = 'srfm-required';
		inputClassname = 'srfm-input-gdpr';
	}

	return (
		<div className="srfm-block-wrap">
			<input
				type="checkbox"
				checked={ selected }
				className={ `srfm-input-common screen-reader-text srfm-input-checkbox ${ inputClassname }` }
			/>
			<label
				className="srfm-cbx"
				htmlFor={ `srfm-checkbox-${ blockID }` }
			>
				<span className="srfm-span-wrap">
					<svg className="srfm-check-icon" width="12px" height="10px">
						<svg className="srfm-inline-svg" viewBox="0 0 12 10">
							<polyline points="1.5 6 4.5 9 10.5 1"></polyline>
						</svg>
					</svg>
				</span>
				<span
					className={ `srfm-span-wrap srfm-block-label ${ isRequired }` }
				>
					<RichText
						tagName="label"
						value={ label }
						onChange={ ( value ) => {
							setAttributes( {
								label: decodeHtmlEntities( value ),
							} );
						} }
						multiline={ false }
						id={ blockID }
					/>
				</span>
			</label>
		</div>
	);
};
