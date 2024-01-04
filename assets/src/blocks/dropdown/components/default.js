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
			/>
			<div className="srfm-block-wrap srfm-dropdown-common-wrap">
				<select
					id={ `srfm-${ slug }-${ blockID }` }
					className={ `srfm-input-common srfm-${ slug }-input` }
					readOnly
				>
					{ placeholder === '' ? (
						''
					) : (
						<option value="" selected disabled hidden>
							{ placeholder }
						</option>
					) }
				</select>
			</div>
		</>
	);
};
