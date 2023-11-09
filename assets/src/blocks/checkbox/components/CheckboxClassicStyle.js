import { useState, useEffect } from '@wordpress/element';

export const CheckboxClassicStyle = ( { attributes } ) => {
	const { label, checked: isChecked, required, labelUrl } = attributes;
	const [ selected, setSelected ] = useState( isChecked );
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
						<a
							target="_blank"
							href={ labelUrl }
							style={ { textDecoration: 'none' } }
							className="srfm-underline"
							rel="noreferrer"
						>
							<label className="srfm-classic-label-text">
								{ label }
							</label>
						</a>
					) : (
						<label className="srfm-classic-label-text">
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
