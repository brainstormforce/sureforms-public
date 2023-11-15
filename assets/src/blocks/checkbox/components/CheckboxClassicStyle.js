import { useState, useEffect } from '@wordpress/element';
import { RichText } from '@wordpress/block-editor';

export const CheckboxClassicStyle = ( {
	attributes,
	setAttributes,
	blockID,
} ) => {
	const { label, checked: isChecked, required, labelUrl } = attributes;
	const [ selected, setSelected ] = useState( isChecked );
	const isRequired = required ? 'srfm-required' : '';
	useEffect( () => {
		setSelected( isChecked );
	}, [ isChecked ] );
	return (
		<>
			<div className="srfm-relative srfm-flex srfm-items-start srfm-flex-row gap-2">
				<div className="srfm-flex srfm-h-6 srfm-items-center">
					<input
						type="checkbox"
						checked={ selected }
						required={ required }
						onClick={ () => setSelected( ! selected ) }
						className="srfm-h-4 srfm-w-4 srfm-rounded srfm-border-[#d1d5db] srfm-classic-checkbox-input checked:!srfm-bg-srfm_primary_color checked:!srfm-order-none"
					/>
				</div>
				<div className="srfm-text-sm srfm-leading-6">
					{ labelUrl ? (
						<RichText
							tagName="label"
							value={ label }
							onChange={ ( value ) =>
								setAttributes( { label: value } )
							}
							className={ `srfm-classic-label-text ${ isRequired }` }
							multiline={ false }
							id={ blockID }
						/>
					) : (
						<RichText
							tagName="label"
							value={ label }
							onChange={ ( value ) =>
								setAttributes( { label: value } )
							}
							className={ `srfm-classic-label-text ${ isRequired }` }
							multiline={ false }
							id={ blockID }
						/>
					) }
				</div>
			</div>
		</>
	);
};
