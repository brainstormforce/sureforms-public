/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import {
	useBlockProps,
	InspectorControls,
	RichText,
} from '@wordpress/block-editor';
import { ToggleControl } from '@wordpress/components';
import UAGTextControl from '@Components/text-control';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import Range from '@Components/range/Range.js';

/**
 * Components dependencies
 */
import Select from 'react-select';
import { UploadClassicStyle } from './components/UploadClassicStyle';
import { UploadThemeStyle } from './components/UploadThemeStyle';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';

export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		required,
		label,
		fileSizeLimit,
		allowedFormats,
		customFormats,
		help,
		id,
		errorMsg,
		formId,
	} = attributes;
	const maxUploadFileSize = upload_field.upload_max_limit;
	const uploadFormats = upload_field.upload_formats;

	const wpUploadFormats = [ ...uploadFormats, ...customFormats ];

	const blockID = useBlockProps().id.split( '-' ).join( '' );
	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );

	useEffect( () => {
		if ( id !== '' ) {
			return;
		}
		setAttributes( { id: blockID } );
	}, [ blockID, id, setAttributes ] );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	return (
		<>
			<InspectorControls>
				<InspectorTabs
					tabs={ [ 'general', 'advance' ] }
					defaultTab={ 'general' }
				>
					<InspectorTab { ...UAGTabs.general }>
						<UAGAdvancedPanelBody
							title={ __( 'Attributes', 'sureforms' ) }
							initialOpen={ true }
						>
							<UAGTextControl
								label={ __( 'Label', 'sureforms' ) }
								value={ label }
								data={ {
									value: label,
									label: 'label',
								} }
								onChange={ ( value ) =>
									setAttributes( { label: value } )
								}
							/>
							<ToggleControl
								label={ __( 'Required', 'sureforms' ) }
								checked={ required }
								onChange={ ( checked ) =>
									setAttributes( { required: checked } )
								}
							/>
							{ required && (
								<UAGTextControl
									label={ __( 'Error message', 'sureforms' ) }
									value={ errorMsg }
									data={ {
										value: errorMsg,
										label: 'errorMsg',
									} }
									onChange={ ( value ) =>
										setAttributes( { errorMsg: value } )
									}
								/>
							) }
							<Range
								label={ __( 'File Size Limit', 'sureforms' ) }
								value={ fileSizeLimit }
								help={ __(
									'Select the maximum file size limit of the file that can be upload',
									'sureforms'
								) }
								min={ 1 }
								max={ maxUploadFileSize }
								displayUnit={ false }
								setAttributes={ setAttributes }
								data={ {
									value: fileSizeLimit,
									label: 'fileSizeLimit',
								} }
							/>
							<span className="uag-control-label uagb-control__header">
								{ __( 'Allowed Types', 'sureforms' ) }
							</span>
							<Select
								options={ wpUploadFormats.map( ( format ) => {
									return {
										value: format,
										label: format,
									};
								} ) }
								value={ allowedFormats }
								isMulti
								isClearable
								classNamePrefix="sureforms-select"
								onChange={ ( value ) => {
									setAttributes( {
										allowedFormats: [ ...value ],
									} );
								} }
							/>
							<p className="components-base-control__help">
								{ __(
									'Search for the File type or you can add your custom File types. If no types are selected then all types will be allowed',
									'sureforms'
								) }
							</p>

							<UAGTextControl
								label={ __( 'Help', 'sureforms' ) }
								value={ help }
								data={ {
									value: help,
									label: 'help',
								} }
								onChange={ ( value ) =>
									setAttributes( { help: value } )
								}
							/>
						</UAGAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...UAGTabs.style }></InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<div
				className={
					'main-container sf-classic-inputs-holder frontend-inputs-holder'
				}
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '0.5rem',
				} }
			>
				{ 'classic' === sureforms_keys?._sureforms_form_styling ? (
					<UploadClassicStyle
						attributes={ attributes }
						blockID={ blockID }
						setAttributes={ setAttributes }
					/>
				) : (
					<UploadThemeStyle
						attributes={ attributes }
						blockID={ blockID }
						setAttributes={ setAttributes }
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
							'classic' ===
							sureforms_keys?._sureforms_form_styling
								? 'sforms-helper-txt'
								: 'sf-text-secondary'
						}
						multiline={ false }
						id={ blockID }
					/>
				) }
			</div>
		</>
	);
}
