import { RichText } from '@wordpress/block-editor';

export const UrlClassicStyle = ( { attributes, blockID, setAttributes } ) => {
	const { label, placeholder, required, defaultValue } = attributes;
	const isRequired = required ? 'required' : '';

	return (
		<>
			<div className="sf-classic-inputs-holder">
				<RichText
					tagName="label"
					value={ label }
					onChange={ ( value ) => setAttributes( { label: value } ) }
					className={ `sf-classic-label-text ${ isRequired }` }
					multiline={ false }
					id={ blockID }
				/>
				<div className="mt-2 flex rounded-md shadow-sm">
					<span className="sf-classic-url-prefix">https://</span>
					<input
						id={ 'text-input-' + blockID }
						type="text"
						value={ defaultValue }
						className={ 'sf-classic-url-element' }
						placeholder={ placeholder }
						required={ required }
					/>
				</div>
			</div>
		</>
	);
};
