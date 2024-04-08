import { RichText } from '@wordpress/block-editor';
import { decodeHtmlEntities } from '@Blocks/util';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

export const InlineButton = ( { attributes, blockID, setAttributes } ) => {
	const { label } = attributes;

	const sureformsKeys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);

	return (
		<>
			<div className="srfm-block-wrap">
				<label className="srfm-block-labelblock-editor-rich-text__editable srfm-block-label rich-text">
					{ /* empty space to align the button with the input field */ }
					‎
				</label>
				<button
					style={ {
						width: '100%',
						border: 'var( --srfm-btn-border-width ) solid var( --srfm-btn-border-color )',
						borderRadius: 'var( --srfm-btn-border-radius )',
						lineHeight: 'normal',
						height: '45px',
					} }
					className={ `rfm-input-common srfm-button srfm-submit-button srfm-inline-submit-button ${
						sureformsKeys._srfm_inherit_theme_button
							? 'wp-block-button__link'
							: 'srfm-inline-submit-bg-color'
					} ` }
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
			</div>
		</>
	);
};
