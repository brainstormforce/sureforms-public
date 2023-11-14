/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	RichText,
} from '@wordpress/block-editor';
import { ToggleControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import UAGTextControl from '@Components/text-control';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';
import { CheckboxClassicStyle } from './components/CheckboxClassicStyle';
import { CheckboxThemeStyle } from './components/CheckboxThemeStyle';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';

const Edit = ( { attributes, setAttributes, clientId } ) => {
	const {
		label,
		checked: isChecked,
		required,
		labelUrl,
		checkboxHelpText,
		block_id,
		errorMsg,
		formId,
	} = attributes;

	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	return (
		<div { ...useBlockProps() }>
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
								<UAGTextControl
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
							<UAGTextControl
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
							<UAGTextControl
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
						</UAGAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...UAGTabs.style }></InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<div
				className={
					'srfm-main-container srfm-classic-inputs-holder srfm-frontend-inputs-holder'
				}
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
