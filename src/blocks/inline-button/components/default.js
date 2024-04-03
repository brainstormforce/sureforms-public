import { RichText } from '@wordpress/block-editor';
import { decodeHtmlEntities } from '@Blocks/util';

export const InputComponent = ( { attributes, blockID, setAttributes } ) => {
	const { label } = attributes;
	return (
		<>
			<div
				className="srfm-block-label"
				style={ {
					height: '1em',
				} }
			/>
			<button
				style={ {
					// TODO: disable button alignment
					width: '100%',
					// backgroundColor: 'var( --srfm-btn-bg-color )',
					border: 'var( --srfm-btn-border-width ) solid var( --srfm-btn-border-color )',
					borderRadius: 'var( --srfm-btn-border-radius )',
				} }
				className={ `srfm-button srfm-submit-button srfm-inline-submit-button` }
			>
				<RichText
					value={ label }
					onChange={ ( value ) => {
						setAttributes( {
							label: decodeHtmlEntities( value ),
						} );
					} }
					multiline={ false }
					id={ blockID }
					allowedFormats={ [] }
				/>
			</button>
		</>
	);
};
