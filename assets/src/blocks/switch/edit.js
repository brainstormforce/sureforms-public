/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl, SelectControl } from '@wordpress/components';
import { InspectorControls, RichText } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';
import { SwitchClassicStyle } from './components/SwitchClassicStyle';
import { SwitchThemeStyle } from './components/SwitchThemeStyle';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';
import { FieldsPreview } from '../FieldsPreview.jsx';

const Edit = ( { clientId, attributes, setAttributes } ) => {
	const {
		fieldWidth,
		label,
		checked: isChecked,
		required,
		switchHelpText,
		block_id,
		errorMsg,
		formId,
		preview,
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
			'srfm-switch-fieldwidth' + block_id
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
		const fieldName = fieldsPreview.switch_preview;
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
								label={ __( 'Help', 'sureforms' ) }
								value={ switchHelpText }
								data={ {
									value: switchHelpText,
									label: 'switchHelpText',
								} }
								onChange={ ( value ) =>
									setAttributes( { switchHelpText: value } )
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
				id={ 'srfm-switch-fieldwidth' + block_id }
			>
				<div>
					{ 'classic' === sureforms_keys?._srfm_form_styling ? (
						<SwitchClassicStyle
							attributes={ attributes }
							sureforms_keys={ sureforms_keys }
							blockID={ block_id }
							setAttributes={ setAttributes }
						/>
					) : (
						<SwitchThemeStyle
							blockID={ block_id }
							setAttributes={ setAttributes }
							attributes={ attributes }
						/>
					) }
				</div>
				{ switchHelpText !== '' && (
					<RichText
						tagName="label"
						value={ switchHelpText }
						onChange={ ( value ) =>
							setAttributes( { switchHelpText: value } )
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
