/**
 * WordPress dependencies
 */
import { RichText } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import Settings from './setting';
import { NumberSliderComponent } from './components/default';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import { FieldsPreview } from '../FieldsPreview.jsx';

const Edit = ( { clientId, attributes, setAttributes } ) => {
	const { help, block_id, formId, preview } = attributes;
	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = fieldsPreview.number_slider_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

	return (
		<>
			<Settings
				attributes={ attributes }
				setAttributes={ setAttributes }
				sureforms_keys={ sureforms_keys }
			/>
			<NumberSliderComponent
				attributes={ attributes }
				blockID={ block_id }
				sureforms_keys={ sureforms_keys }
				setAttributes={ setAttributes }
			/>
			{ help !== '' && (
				<RichText
					tagName="label"
					value={ help }
					onChange={ ( value ) => setAttributes( { help: value } ) }
					className="srfm-description"
					multiline={ false }
					id={ block_id }
				/>
			) }
		</>
	);
};

export default compose( AddInitialAttr )( Edit );
