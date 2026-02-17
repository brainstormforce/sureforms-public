import { RichText } from '@wordpress/block-editor';
import { applyFilters } from '@wordpress/hooks';
import { decodeHtmlEntities } from '@Blocks/util';
import HelpText from '@Components/misc/HelpText';

// Default dropdown placeholder for translation matching.
const DEFAULT_DROPDOWN_PLACEHOLDER = 'Select an option';

// Get translated default text from PHP (falls back to English if not available).
const getTranslatedDropdownPlaceholder = () => {
	return (
		window.srfm_block_data?.default_translations?.dropdown_placeholder ||
		DEFAULT_DROPDOWN_PLACEHOLDER
	);
};

export const DropdownComponent = ( { attributes, setAttributes, blockID } ) => {
	const { required, label, placeholder, help } = attributes;
	const isRequired = required ? ' srfm-required' : '';
	const placeholderText =
		placeholder === DEFAULT_DROPDOWN_PLACEHOLDER
			? getTranslatedDropdownPlaceholder()
			: placeholder;
	const slug = 'dropdown';

	// Allow filtering the dropdown view (e.g., to show a notice when dynamic options are empty).
	const filteredView = applyFilters(
		'srfm.dropdown.block.view',
		null,
		attributes
	);

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => {
					setAttributes( { label: decodeHtmlEntities( value ) } );
				} }
				className={ `srfm-block-label${ isRequired }` }
				multiline={ false }
				id={ `srfm-listbox-label ${ blockID }` }
				allowedFormats={ [] }
			/>
			<HelpText
				help={ help }
				setAttributes={ setAttributes }
				block_id={ blockID }
			/>
			{ filteredView ? (
				filteredView
			) : (
				<div className={ `srfm-block-wrap srfm-dropdown-common-wrap` }>
					<input
						type="text"
						className={ `srfm-input-common srfm-${ slug }-input` }
						id={ `srfm-${ slug }-state-${ blockID }` }
						aria-required={ required ? 'true' : 'false' }
						placeholder={ placeholderText }
						readOnly
					/>
					<div className="ts-dropdown-icon">
						<svg
							width="16"
							height="16"
							viewBox="0 0 16 16"
							fill="none"
							xmlns="http://www.w3.org/2000/svg"
						>
							<path
								d="M4 6L8 10L12 6"
								stroke="currentColor"
								strokeOpacity="0.65"
								strokeWidth="1.25"
								strokeLinecap="round"
								strokeLinejoin="round"
							/>
						</svg>
					</div>
				</div>
			) }
		</>
	);
};
