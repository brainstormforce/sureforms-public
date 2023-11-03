import { useState, useEffect } from '@wordpress/element';
import { RichText } from '@wordpress/block-editor';

export const CheckboxClassicStyle = ( {
	attributes,
	setAttributes,
	blockID,
} ) => {
	const { label, checked: isChecked, required, labelUrl } = attributes;
	const [ selected, setSelected ] = useState( isChecked );
	const isRequired = required ? 'required' : '';
	useEffect( () => {
		setSelected( isChecked );
	}, [ isChecked ] );
	return (
		<>
			<div className="relative flex items-start flex-row gap-2">
				<div className="flex h-6 items-center">
					<input
						type="checkbox"
						checked={ selected }
						required={ required }
						onClick={ () => setSelected( ! selected ) }
						className="h-4 w-4 rounded border-[#d1d5db] srfm-classic-checkbox-input checked:!bg-srfm_primary_color checked:!border-none"
					/>
				</div>
				<div className="text-sm leading-6">
					{ labelUrl ? (
						<a
							target="_blank"
							href={ labelUrl }
							style={ { textDecoration: 'none' } }
							className="underline"
							rel="noreferrer"
						>
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
						</a>
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
