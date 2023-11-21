/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl, SelectControl } from '@wordpress/components';
import { InspectorControls, RichText } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import SRFMTextControl from '@Components/text-control';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';
import { CheckboxClassicStyle } from './components/CheckboxClassicStyle';
import { CheckboxThemeStyle } from './components/CheckboxThemeStyle';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';
import { FieldsPreview } from '../FieldsPreview.jsx';

const Edit = ( { attributes, setAttributes, clientId } ) => {
	const {
		fieldWidth,
		label,
		checked: isChecked,
		required,
		labelUrl,
		checkboxHelpText,
		block_id,
		errorMsg,
		preview,
		formId,
	} = attributes;

	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	useEffect( () => {
		const width_req_element = document.getElementById(
			'srfm-checkbox-fieldwidth' + block_id
		);
		const parent_to_width_element = width_req_element.parentElement;
		if ( window.innerWidth < 630 ) {
			parent_to_width_element.style.width = '100%';
		} else {
			parent_to_width_element.style.width =
				'calc( ' + fieldWidth + '% - 20px)';
		}
	}, [ fieldWidth ] );
	// show the block preview on hover.
	if ( preview ) {
		const fieldName = fieldsPreview.checkbox_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

	return (
		<div>
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
								data={ {
									value: label,
									label: 'label',
								} }
								value={ label }
								onChange={ ( value ) => {
									setAttributes( { label: value } );
								} }
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
									data={ {
										value: errorMsg,
										label: 'errorMsg',
									} }
									label={ __( 'Error message', 'sureforms' ) }
									value={ errorMsg }
									onChange={ ( value ) =>
										setAttributes( { errorMsg: value } )
									}
								/>
							) }
							<SRFMTextControl
								data={ {
									value: labelUrl,
									label: 'labelUrl',
								} }
								type="url"
								label={ __( 'Label Url', 'sureforms' ) }
								placeholder="https://example.com/"
								value={ labelUrl }
								onChange={ ( value ) =>
									setAttributes( { labelUrl: value } )
								}
							/>
							<ToggleControl
								label={ __(
									'Checked by default',
									'sureforms'
								) }
								checked={ isChecked }
								onChange={ ( checked ) =>
									setAttributes( { checked } )
								}
							/>
							<SRFMTextControl
								data={ {
									value: checkboxHelpText,
									label: 'checkboxHelpText',
								} }
								label={ __( 'Help', 'sureforms' ) }
								value={ checkboxHelpText }
								onChange={ ( value ) =>
									setAttributes( { checkboxHelpText: value } )
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
				id={ 'srfm-checkbox-fieldwidth' + block_id }
			>
				<div
					style={ {
						display: 'flex',
						gap: '.5rem',
						alignItems: 'center',
					} }
				>
					{ 'classic' === sureforms_keys?._srfm_form_styling ? (
						<CheckboxClassicStyle
							blockID={ block_id }
							setAttributes={ setAttributes }
							attributes={ attributes }
						/>
					) : (
						<CheckboxThemeStyle
							blockID={ block_id }
							setAttributes={ setAttributes }
							attributes={ attributes }
						/>
					) }
				</div>
				{ checkboxHelpText !== '' && (
					<RichText
						tagName="label"
						value={ checkboxHelpText }
						onChange={ ( value ) =>
							setAttributes( { checkboxHelpText: value } )
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
		</div>
	);
};

export default compose( AddInitialAttr )( Edit );
