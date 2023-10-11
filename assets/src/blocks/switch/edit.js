/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { ToggleControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import UAGTextControl from '@Components/text-control';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';
import { SwitchClassicStyle } from './components/SwitchClassicStyle';
import { SwitchThemeStyle } from './components/SwitchThemeStyle';

export default ( { clientId, attributes, setAttributes } ) => {
	const {
		label,
		checked: isChecked,
		required,
		switchHelpText,
		id,
		errorMsg,
		formId,
	} = attributes;

	const blockID = useBlockProps().id.split( '-' ).join( '' );

	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	useEffect( () => {
		if ( id !== '' ) {
			return;
		}
		setAttributes( { id: blockID } );
	}, [ blockID, id, setAttributes ] );

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
					alignItems: 'center',
					gap: '.4rem',
				} }
			>
				{ 'classic' === sureforms_keys?._sureforms_form_styling ? (
					<SwitchClassicStyle attributes={ attributes } />
				) : (
					<SwitchThemeStyle attributes={ attributes } />
				) }
			</div>
			{ switchHelpText !== '' && (
				<div className="sf-text-secondary">{ switchHelpText }</div>
			) }
		</>
	);
};
