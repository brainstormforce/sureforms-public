import { RichText } from '@wordpress/block-editor';

export const CheckboxThemeStyle = ( {
	attributes,
	blockID,
	setAttributes,
} ) => {
	const { label, checked: isChecked, required, labelUrl } = attributes;
	const isRequired = required ? 'required' : '';
	return (
		<>
			<input
				type="checkbox"
				id={ 'checkbox-block-' + blockID }
				checked={ isChecked }
				required={ required }
			></input>
			<label
				className={ `srfm-text-primary ${ isRequired }` }
				htmlFor={ 'checkbox-block-' + blockID }
			>
				{ labelUrl !== '' ? (
					<RichText
						tagName="a"
						value={ label }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
						className={ `srfm-text-primary ${ isRequired }` }
						multiline={ false }
						id={ blockID }
					/>
				) : (
					<RichText
						tagName="a"
						value={ label }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
						className={ `srfm-text-primary ${ isRequired }` }
						multiline={ false }
						id={ blockID }
					/>
				) }
			</label>
		</>
	);
};
