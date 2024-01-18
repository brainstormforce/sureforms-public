import { RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

export const NumberSliderComponent = ( {
	attributes,
	blockID,
	setAttributes,
} ) => {
	const { label } = attributes;
	const slug = 'number-slider';
	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-block-label` }
				multiline={ false }
				id={ blockID }
				allowedFormats={ [] }
			/>
			<div className="srfm-block-wrap">
				<div className={ `srfm-${ slug }-wrap` }>
					<div className={ `srfm-${ slug }-inverse` }></div>
					<div className={ `srfm-${ slug }` }></div>
					<span className={ `srfm-${ slug }-thumb` }></span>
					<div className={ `srfm-${ slug }-sign` }>
						<span>{ __( '10', 'sureforms' ) }</span>
					</div>
				</div>
			</div>
		</>
	);
};
