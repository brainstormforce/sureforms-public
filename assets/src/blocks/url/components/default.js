import { RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

export const UrlComponent = ( { attributes, blockID, setAttributes } ) => {
	const { label, placeholder, required, defaultValue } = attributes;
	const isRequired = required ? ' srfm-required' : '';
	const slug = 'url';

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-block-label${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
			<div className="srfm-block-wrap">
				<span className="srfm-protocol">
					{ __( 'https://', 'sureforms' ) }
				</span>
				<input
					id={ `srfm-${ slug }-${ blockID }` }
					type="text"
					value={ defaultValue }
					className={ `srfm-input-common srfm-input-${ slug }` }
					placeholder={ placeholder }
					required={ required }
				/>
			</div>
		</>
	);
};
