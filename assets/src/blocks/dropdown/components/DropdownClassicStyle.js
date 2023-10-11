import { faAngleDown } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useState } from '@wordpress/element';

export const DropdownClassicStyle = ( { attributes } ) => {
	const { required, options, label } = attributes;
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
			<label id="listbox-label" className="sf-classic-label-text">
				{ label }
				{ required && label && (
					<span className="text-required_icon_color"> *</span>
				) }
			</label>
			<div className="relative mt-2">
				<input
					aria-required={ required ? 'true' : 'false' }
					type="hidden"
					className="sf-classic-dropdown-result"
					value={ options.length > 0 ? options[ 0 ] : '' }
				/>
				<button
					type="button"
					className={ `sureforms-classic-dropdown-button sf-classic-dropdown-btn` }
					onClick={ () => setOpen( ! open ) }
					onBlur={ () => setOpen( false ) }
				>
					<span className="sf-dropdown-value block truncate">
						{ options.length > 0 ? options[ 0 ] : '\u00A0' }
					</span>
					<span
						className={ `sf-classic-select-icon pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2 duration-300 transition-all rotate-0 ${
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
					className="sf-classic-dropdown-box sf-classic-dropdown-ul"
					tabIndex="-1"
					value="value"
					style={ open ? { ...openStyles } : { ...closedStyles } }
				>
					{ options.length === 0 ? (
						<div className="text-gray-500 relative select-none py-2 pl-3 pr-9">
							No Options Found
						</div>
					) : null }
					{ options.map( ( option, index ) => (
						<li
							key={ index }
							className="sf-classic-dropdown-option sf-classic-dropdown-li"
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
