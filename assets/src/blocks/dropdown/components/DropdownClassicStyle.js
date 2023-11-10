import { faAngleDown } from '@fortawesome/free-solid-svg-icons';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { RichText } from '@wordpress/block-editor';

export const DropdownClassicStyle = ( {
	attributes,
	setAttributes,
	blockID,
} ) => {
	const { required, options, label, placeholder } = attributes;
	const [ open, setOpen ] = useState( false );

	const openStyles = {
		display: 'block',
		opacity: 100,
		zIndex: 10,
	};
	const isRequired = required ? 'required' : '';
	const closedStyles = {
		display: 'none',
	};

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-classic-label-text ${ isRequired }` }
				multiline={ false }
				id={ `srfm-listbox-label ${ blockID }` }
			/>
			<div className="srfm-relative srfm-mt-2">
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
					<span className="srfm-dropdown-value srfm-block srfm-truncate">
						{ placeholder ? placeholder : '\u00A0' }
					</span>
					<span
						className={ `srfm-classic-select-icon srfm-pointer-events-none srfm-absolute srfm-inset-y-0 srfm-right-0 srfm-flex srfm-items-center srfm-pr-3 srfm-duration-300 srfm-transition-all srfm-rotate-0 ${
							open ? 'srfm-rotate-180 srfm-pl-2' : ''
						}` }
					>
						<FontAwesomeIcon
							icon={ faAngleDown }
							className="fa-solid fa-angle-down srfm-h-3 srfm-w-3 srfm-text-gray-600 srfm-mt-[5px]"
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
						<div className="srfm-text-gray-500 srfm-relative srfm-select-none srfm-py-2 srfm-pl-3 srfm-pr-9">
							{ __( 'No Options Found', 'sureforms' ) }
						</div>
					) : null }
					{ options.map( ( option, index ) => (
						<li
							key={ index }
							className="srfm-classic-dropdown-option srfm-classic-dropdown-li"
							role="option"
						>
							<span className="srfm-font-normal srfm-block srfm-truncate">
								{ option }
							</span>
						</li>
					) ) }
				</ul>
			</div>
		</>
	);
};
