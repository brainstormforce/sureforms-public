import { RichText } from '@wordpress/block-editor';

export const UrlClassicStyle = ( { attributes, blockID, setAttributes } ) => {
	const { label, placeholder, required, defaultValue } = attributes;
	const isRequired = required ? 'srfm-required' : '';

	return (
		<>
			<div className="srfm-classic-inputs-holder">
				<RichText
					tagName="label"
					value={ label }
					onChange={ ( value ) => setAttributes( { label: value } ) }
					className={ `srfm-classic-label-text ${ isRequired }` }
					multiline={ false }
					id={ blockID }
				/>
				<div className="srfm-mt-2 srfm-flex srfm-rounded-md srfm-shadow-sm">
					<span className="srfm-classic-url-prefix">https://</span>
					<input
						id={ 'srfm-text-input-' + blockID }
						type="text"
						value={ defaultValue }
						className={ 'srfm-classic-url-element' }
						placeholder={ placeholder }
						required={ required }
					/>
				</div>
			</div>
		</>
	);
};
