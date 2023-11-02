/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { ToggleControl, SelectControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import UAGTextControl from '@Components/text-control';
import UAGNumberControl from '@Components/number-control';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';
import { InputClassicStyle } from './components/InputClassicStyle';
import { InputThemeStyle } from './components/InputThemeStyle';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';

const Edit = ( { clientId, attributes, setAttributes } ) => {
	const {
		label,
		fieldWidth,
		placeholder,
		help,
		required,
		block_id,
		defaultValue,
		errorMsg,
		textLength,
		isUnique,
		duplicateMsg,
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
								label={ __( 'Field Width', 'sureforms' ) }
								value={ fieldWidth }
								data={ {
									value: fieldWidth,
									label: 'fieldWidth',
								} }
								onChange={ ( value ) =>
									setAttributes( { fieldWidth: value } )
								}
							/>
							<SelectControl
								label={ __( 'Column Width', 'sureforms' ) }
								value={ fieldWidth }
								options={ [
									{
										label: '25%',
										value: 25,
									},
									{
										label: '30%',
										value: 30,
									},
									{
										label: '50%',
										value: 50,
									},
									{
										label: '70%',
										value: 70,
									},
									{
										label: '75%',
										value: 75,
									},
									{
										label: '100%',
										value: 100,
									},
								] }
								onChange={ ( value ) =>
									setAttributes( { fieldWidth: value } )
								}
								__nextHasNoMarginBottom
							/>
							<p className="components-base-control__help">
								{ __(
									'Update settings to view changes on page',
									'sureforms'
								) }
							</p>
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
							<UAGTextControl
								label={ __( 'Placeholder', 'sureforms' ) }
								value={ placeholder }
								data={ {
									value: placeholder,
									label: 'placeholder',
								} }
								onChange={ ( value ) =>
									setAttributes( { placeholder: value } )
								}
							/>
							<UAGTextControl
								label={ __( 'Default Value', 'sureforms' ) }
								value={ defaultValue }
								data={ {
									value: defaultValue,
									label: 'defaultValue',
								} }
								onChange={ ( value ) =>
									setAttributes( { defaultValue: value } )
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
							<ToggleControl
								label={ __(
									'Validate as unique',
									'sureforms'
								) }
								checked={ isUnique }
								onChange={ ( checked ) =>
									setAttributes( { isUnique: checked } )
								}
							/>
							{ isUnique && (
								<UAGTextControl
									label={ __(
										'Validation Message for Duplicate ',
										'sureforms'
									) }
									value={ duplicateMsg }
									data={ {
										value: duplicateMsg,
										label: 'duplicateMsg',
									} }
									onChange={ ( value ) =>
										setAttributes( { duplicateMsg: value } )
									}
								/>
							) }
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
							<UAGNumberControl
								label={ __( 'Max text length', 'sureforms' ) }
								displayUnit={ false }
								value={ textLength }
								min={ 0 }
								data={ {
									value: textLength,
									label: 'textLength',
								} }
								onChange={ ( value ) =>
									setAttributes( {
										textLength: Number( value ),
									} )
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
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				{ 'classic' === sureforms_keys?._srfm_form_styling ? (
					<InputClassicStyle
						attributes={ attributes }
						blockID={ block_id }
					/>
				) : (
					<InputThemeStyle
						attributes={ attributes }
						blockID={ block_id }
					/>
				) }

				{ help !== '' && (
					<label
						htmlFor={ 'srfm-text-input-help-' + block_id }
						className={
							'classic' === sureforms_keys?._srfm_form_styling
								? 'srfm-helper-txt'
								: 'srfm-text-secondary'
						}
					>
						{ help }
					</label>
				) }
			</div>
		</>
	);
};

export default compose( AddInitialAttr )( Edit );
