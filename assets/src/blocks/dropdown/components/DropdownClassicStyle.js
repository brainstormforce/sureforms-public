import { faAngleDown } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

export const DropdownClassicStyle = ( { attributes } ) => {
	const { required, options, label, placeholder } = attributes;
	const [ open, setOpen ] = useState( false );

	const openStyles = {
		display: 'block',
		opacity: 100,
		zIndex: 10,
	};
	const closedStyles = {
		display: 'none',
	};

	return (
		<>
			<label id="srfm-listbox-label" className="srfm-classic-label-text">
				{ label }
				{ required && label && (
					<span className="text-required_icon_color"> *</span>
				) }
			</label>
			<div className="relative mt-2">
				<input
					aria-required={ required ? 'true' : 'false' }
					type="hidden"
					className="srfm-classic-dropdown-result"
					value={ options.length > 0 ? options[ 0 ] : '' }
				/>
				<button
					type="button"
					className={ `srfm-classic-dropdown-button srfm-classic-dropdown-btn` }
					onClick={ () => setOpen( ! open ) }
					onBlur={ () => setOpen( false ) }
				>
					<span className="srfm-dropdown-value block truncate">
						{ placeholder ? placeholder : '\u00A0' }
					</span>
					<span
						className={ `srfm-classic-select-icon pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2 duration-300 transition-all rotate-0 ${
							open ? 'rotate-180 pl-2' : ''
						}` }
					>
						<FontAwesomeIcon
							icon={ faAngleDown }
							className="fa-solid fa-angle-down h-5 w-5 text-gray-400 mt-[5px]"
						/>
					</span>
				</button>
				<ul
					className="srfm-classic-dropdown-box srfm-classic-dropdown-ul"
					tabIndex="-1"
					value="value"
					style={ open ? { ...openStyles } : { ...closedStyles } }
				>
					{ options.length === 0 ? (
						<div className="text-gray-500 relative select-none py-2 pl-3 pr-9">
							{ __( 'No Options Found', 'sureforms' ) }
						</div>
					) : null }
					{ options.map( ( option, index ) => (
						<li
							key={ index }
							className="srfm-classic-dropdown-option srfm-classic-dropdown-li"
							role="option"
						>
							<span className="font-normal block truncate">
								{ option }
							</span>
						</li>
					) ) }
				</ul>
			</div>
		</>
	);
};
