import { RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import { decodeHtmlEntities } from '@Blocks/util';
import HelpText from '@Components/misc/HelpText';

export const DropdownComponent = ( { attributes, setAttributes, blockID } ) => {
	const { required, label, placeholder, help } = attributes;
	const isRequired = required ? ' srfm-required' : '';
	const placeholderText =
		placeholder === 'Select an option'
			? __( 'Select an option', 'sureforms' )
			: placeholder;
	const slug = 'dropdown';

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
		</>
	);
};
