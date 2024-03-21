/**
 * WordPress dependencies
 */
import { RichText } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import Settings from './settings';
import { UrlComponent } from './components/default.js';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import { FieldsPreview } from '../FieldsPreview.jsx';
import { decodeHtmlEntities } from '@Blocks/util';

const Edit = ( { attributes, setAttributes, clientId } ) => {
	const { help, block_id, formId, preview } = attributes;
	const currentFormId = useGetCurrentFormId( clientId );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = srfm_fields_preview.url_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

	return (
		<>
			<Settings
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>
			<UrlComponent
				blockID={ block_id }
				setAttributes={ setAttributes }
				attributes={ attributes }
			/>
			{ help !== '' && (
				<RichText
					tagName="label"
					value={ help }
					onChange={ ( value ) => {
						setAttributes( { help: decodeHtmlEntities( value ) } );
					} }
					className="srfm-description"
					multiline={ false }
					id={ block_id }
					allowedFormats={ [] }
				/>
			) }
		</>
	);
};

export default compose( AddInitialAttr )( Edit );
