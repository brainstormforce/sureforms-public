import { useState, useEffect } from '@wordpress/element';

export const CheckboxClassicStyle = ( { attributes } ) => {
	const { label, checked: isChecked, required, labelUrl } = attributes;
	const [ selected, setSelected ] = useState( isChecked );
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
							<label className="srfm-classic-label-text">
								{ label }
							</label>
						</a>
					) : (
						<label className="sf-classic-label-text">
							{ label }
						</label>
					) }
					{ required && label ? (
						<span style={ { color: 'red' } }> *</span>
					) : (
						''
					) }
				</div>
			</div>
		</>
	);
};
