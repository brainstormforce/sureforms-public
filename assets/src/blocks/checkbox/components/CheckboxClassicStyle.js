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
						className="h-4 w-4 rounded border-[#d1d5db] sureforms-classic-checkbox-input checked:!bg-sf_primary_color checked:!border-none"
					/>
				</div>
				<div className="text-sm leading-6">
					<label className="sf-classic-label-text">
						{ labelUrl ? (
							<a
								target="_blank"
								href={ labelUrl }
								style={ { textDecoration: 'none' } }
								className="underline"
								rel="noreferrer"
							>
								{ label }
							</a>
						) : (
							label
						) }
						{ required && label ? (
							<span style={ { color: 'red' } }> *</span>
						) : (
							''
						) }
					</label>
				</div>
			</div>
		</>
	);
};
