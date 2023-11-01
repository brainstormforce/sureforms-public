import { useState, useEffect } from '@wordpress/element';
import { RichText } from '@wordpress/block-editor';

export const SwitchClassicStyle = ( { attributes, sureforms_keys,setAttributes,blockID } ) => {
	const { label, checked: isChecked, required } = attributes;
	const [ toggle, setToggle ] = useState( isChecked );
	let color = sureforms_keys._sureforms_color1;
	if ( color === '' ) {
		color = '#0284C7';
	}
	const isRequired = required ? 'required' : '';
	useEffect( () => {
		setToggle( isChecked );
	}, [ isChecked ] );

	return (
		<>
			<label className="sureforms-switch-label">
				<div className="sf-text-primary !flex !items-start !gap-2 !mt-1">
					<div
						className="switch-background sf-classic-toggle-bg mt-[5px] mr-[5px]"
						style={ {
							backgroundColor: toggle ? color : '#dcdcdc',
						} }
					>
						<input
							className="sureforms-switch sf-classic-switch-input !p-0"
							type="checkbox"
							checked={ toggle }
							aria-required={ required ? 'true' : 'false' }
							onClick={ () => setToggle( ! toggle ) }
						/>
						<div
							className="switch-toggle !-top-[3px] !shadow !border !border-gray-200 !h-5 !w-5"
							style={ { left: toggle ? '24px' : '0' } }
						>
							<span
								className={ `sf-classic-toggle-icon-container sf-classic-toggle-icon ${
									toggle ? '!opacity-100' : '!opacity-0'
								}` }
								aria-hidden="true"
							>
								<svg
									style={ { fill: color } }
									className="!h-3 !w-3 sf-classic-toggle-icon"
									viewBox="0 0 12 12"
								>
									<path d="M3.707 5.293a1 1 0 00-1.414 1.414l1.414-1.414zM5 8l-.707.707a1 1 0 001.414 0L5 8zm4.707-3.293a1 1 0 00-1.414-1.414l1.414 1.414zm-7.414 2l2 2 1.414-1.414-2-2-1.414 1.414zm3.414 2l4-4-1.414-1.414-4 4 1.414 1.414z" />
								</svg>
							</span>
						</div>
					</div>
					{/* <RichText
						tagName="label"
						value={ label }
						onChange={ ( value ) => setAttributes( { label: value } ) }
						className={ `sf-classic-label-text ${ isRequired }` }
						multiline={ false }
						id={ blockID }
					/> */}
					<span className="sf-classic-label-text">
						{ label }{ ' ' }
						{ required && label ? (
							<span className="!text-required_icon_color">
								{ ' ' }
								*
							</span>
						) : (
							''
						) }
					</span>
				</div>
			</label>
		</>
	);
};
