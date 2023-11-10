import { RichText } from '@wordpress/block-editor';

export const DropdownThemeStyle = ( {
	attributes,
	blockID,
	setAttributes,
} ) => {
	const { required, options, label, placeholder } = attributes;

	const isRequired = required ? 'srfm-required' : '';

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-text-primary ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
			<select id={ 'dropdown-' + blockID } required={ required }>
				{ placeholder && <option>{ placeholder }</option> }
				{ options.map( ( option, i ) => {
					return (
						<option label={ option } key={ i }>
							{ option }
						</option>
					);
				} ) }
			</select>
		</>
	);
};
