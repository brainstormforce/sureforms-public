/**
 * WordPress dependencies
 */
import { RichText } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import Settings from './settings';
import { UrlThemeStyle } from './components/UrlThemeStyle';
import { UrlClassicStyle } from './components/UrlClassicStyle';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import { FieldsPreview } from '../FieldsPreview.jsx';

const Edit = ( { attributes, setAttributes, clientId } ) => {
	const { help, block_id, formId, fieldWidth, preview } = attributes;
	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	useEffect( () => {
		const width_req_element = document.getElementById(
			'srfm-url-fieldwidth' + block_id
		);
		const parent_to_width_element = width_req_element.parentElement;
		parent_to_width_element.style.width =
			'calc( ' + fieldWidth + '% - 20px)';
	}, [ fieldWidth ] );
	// show the block preview on hover.
	if ( preview ) {
		const fieldName = fieldsPreview.url_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

	return (
		<>
			<Settings
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>
			<div
				className={ 'srfm-main-container srfm-classic-inputs-holder' }
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
				id={ 'srfm-url-fieldwidth' + block_id }
			>
				{ 'classic' === sureforms_keys?._srfm_form_styling ? (
					<UrlClassicStyle
						blockID={ block_id }
						setAttributes={ setAttributes }
						attributes={ attributes }
					/>
				) : (
					<UrlThemeStyle
						blockID={ block_id }
						setAttributes={ setAttributes }
						attributes={ attributes }
					/>
				) }
				{ help !== '' && (
					<RichText
						tagName="label"
						value={ help }
						onChange={ ( value ) =>
							setAttributes( { help: value } )
						}
						className={
							'classic' === sureforms_keys?._srfm_form_styling
								? 'srfm-helper-txt'
								: 'srfm-text-secondary'
						}
						multiline={ false }
						id={ block_id }
					/>
				) }
			</div>
		</>
	);
};

export default compose( AddInitialAttr )( Edit );
