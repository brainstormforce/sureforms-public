/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { ToggleControl, SelectControl } from '@wordpress/components';
import { InspectorControls, RichText } from '@wordpress/block-editor';
import SRFMTextControl from '@Components/text-control';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
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
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';
import { FieldsPreview } from '../FieldsPreview.jsx';

const Edit = ( { attributes, setAttributes, clientId } ) => {
	const {
		required,
		fieldWidth,
		label,
		fileSizeLimit,
		allowedFormats,
		customFormats,
		help,
		block_id,
		preview,
		errorMsg,
		formId,
	} = attributes;
	const maxUploadFileSize = upload_field.upload_max_limit;
	const uploadFormats = upload_field.upload_formats;

	const wpUploadFormats = [ ...uploadFormats, ...customFormats ];

	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	useEffect( () => {
		const width_req_element = document.getElementById(
			'srfm-upload-fieldwidth' + block_id
		);
		const parent_to_width_element = width_req_element.parentElement;
		parent_to_width_element.style.width =
			'calc( ' + fieldWidth + '% - 20px)';
	}, [ fieldWidth ] );
	// show the block preview on hover.
	if ( preview ) {
		const fieldName = fieldsPreview.upload_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

	return (
		<>
			<InspectorControls>
				<InspectorTabs
					tabs={ [ 'general', 'advance' ] }
					defaultTab={ 'general' }
				>
					<InspectorTab { ...SRFMTabs.general }>
						<SRFMAdvancedPanelBody
							title={ __( 'Attributes', 'sureforms' ) }
							initialOpen={ true }
						>
							<SelectControl
								label={ __( 'Field Width', 'sureforms' ) }
								value={ fieldWidth }
								options={ widthOptions }
								onChange={ ( value ) =>
									setAttributes( {
										fieldWidth: Number( value ),
									} )
								}
								__nextHasNoMarginBottom
							/>
							<SRFMTextControl
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
								<SRFMTextControl
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
							<span className="srfm-control-label srfm-control__header">
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
								classNamePrefix="srfm-select"
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

							<SRFMTextControl
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
						</SRFMAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...SRFMTabs.style }></InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<div
				className={
					'srfm-main-container srfm-classic-inputs-holder srfm-frontend-inputs-holder'
				}
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '0.5rem',
				} }
				id={ 'srfm-upload-fieldwidth' + block_id }
			>
				{ 'classic' === sureforms_keys?._srfm_form_styling ? (
					<UploadClassicStyle
						attributes={ attributes }
						blockID={ block_id }
						setAttributes={ setAttributes }
					/>
				) : (
					<UploadThemeStyle
						attributes={ attributes }
						blockID={ block_id }
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
