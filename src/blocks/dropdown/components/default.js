import { RichText } from '@wordpress/block-editor';
import { decodeHtmlEntities } from '@Blocks/util';
import HelpText from '@Components/misc/HelpText';

export const DropdownComponent = ( { attributes, setAttributes, blockID } ) => {
	const { required, label, placeholder, help } = attributes;
	const isRequired = required ? ' srfm-required' : '';
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
					placeholder={ placeholder }
					readOnly
				/>
			</div>
		</>
	);
};
