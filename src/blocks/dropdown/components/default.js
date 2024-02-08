import { RichText } from '@wordpress/block-editor';

export const DropdownComponent = ( { attributes, setAttributes, blockID } ) => {
	const { required, label, placeholder } = attributes;
	const isRequired = required ? ' srfm-required' : '';
	const slug = 'dropdown';

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-block-label${ isRequired }` }
				multiline={ false }
				id={ `srfm-listbox-label ${ blockID }` }
				allowedFormats={ [] }
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
