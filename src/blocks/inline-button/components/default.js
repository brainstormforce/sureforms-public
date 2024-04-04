import { RichText } from '@wordpress/block-editor';
import { decodeHtmlEntities } from '@Blocks/util';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

export const InputComponent = ( { attributes, blockID, setAttributes } ) => {
	const { label } = attributes;

	const sureformsKeys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);

	return (
		<>
			<div
				style={ {
					height: '1em',
				} }
			/>
			<button
				style={ {
					width: '100%',
					border: 'var( --srfm-btn-border-width ) solid var( --srfm-btn-border-color )',
					borderRadius: 'var( --srfm-btn-border-radius )',
				} }
				className={ `srfm-button srfm-submit-button srfm-inline-submit-button ${
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
		</>
	);
};
