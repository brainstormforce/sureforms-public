/**
 * WordPress dependencies
 */
import { InspectorControls } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import { ToggleControl } from '@wordpress/components';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import { __ } from '@wordpress/i18n';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { EmailComponent } from './components/default';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import { FieldsPreview } from '../FieldsPreview.jsx';
import { useErrMessage } from '@Blocks/util';
import ConditionalLogic from '@Components/conditional-logic';

const Edit = ( { attributes, setAttributes, clientId } ) => {
	const {
		help,
		required,
		defaultValue,
		isConfirmEmail,
		// might be used.
		// confirmLabel,
		formId,
		block_id,
		errorMsg,
		isUnique,
		duplicateMsg,
		preview,
		className,
		readOnly,
	} = attributes;
	const currentFormId = useGetCurrentFormId( clientId );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	const {
		currentMessage: currentErrorMsg,
		setCurrentMessage: setCurrentErrorMsg,
	} = useErrMessage( 'srfm_email_block_required_text', errorMsg );

	const {
		currentMessage: currentUniqueMessage,
		setCurrentMessage: setCurrentUniqueMessage,
	} = useErrMessage( 'srfm_email_block_unique_text', duplicateMsg );

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = srfm_fields_preview.email_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

	return (
		<div className={ className }>
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
							<SRFMTextControl
								label={ __( 'Default Value', 'sureforms' ) }
								className="srfm-with-dropdown"
								value={ defaultValue }
								withSmartTagDropdown={ true }
								data={ {
									value: defaultValue,
									label: 'defaultValue',
								} }
								onChange={ ( value ) =>
									setAttributes( { defaultValue: value } )
								}
							/>
							{ defaultValue && (
								<ToggleControl
									label={ __( 'Read Only', 'sureforms' ) }
									checked={ readOnly }
									onChange={ ( checked ) =>
										setAttributes( { readOnly: checked } )
									}
								/>
							) }
							<ToggleControl
								label={ __( 'Required', 'sureforms' ) }
								checked={ required }
								onChange={ ( checked ) =>
									setAttributes( { required: checked } )
								}
							/>
							{ required && (
								<SRFMTextControl
									label={ __( 'Error Message', 'sureforms' ) }
									data={ {
										value: errorMsg,
										label: 'errorMsg',
									} }
									value={ currentErrorMsg }
									onChange={ ( value ) => {
										setCurrentErrorMsg( value );
										setAttributes( { errorMsg: value } );
									} }
								/>
							) }
							<ToggleControl
								label={ __(
									'Validate as Unique',
									'sureforms'
								) }
								checked={ isUnique }
								onChange={ ( checked ) =>
									setAttributes( { isUnique: checked } )
								}
							/>
							{ isUnique && (
								<SRFMTextControl
									label={ __(
										'Validation Message for Duplicate ',
										'sureforms'
									) }
									value={ currentUniqueMessage }
									data={ {
										value: duplicateMsg,
										label: 'duplicateMsg',
									} }
									onChange={ ( value ) => {
										setCurrentUniqueMessage( value );
										setAttributes( {
											duplicateMsg: value,
										} );
									} }
								/>
							) }
							<ToggleControl
								label={ __(
									'Enable Email Confirmation',
									'sureforms'
								) }
								checked={ isConfirmEmail }
								onChange={ ( checked ) =>
									setAttributes( { isConfirmEmail: checked } )
								}
							/>
							<SRFMTextControl
														variant="textarea"

								label={ __( 'Help Text', 'sureforms' ) }
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
					<InspectorTab { ...SRFMTabs.advance }>
						<ConditionalLogic
							{ ...{ setAttributes, attributes } }
						/>
					</InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<EmailComponent
				blockID={ block_id }
				setAttributes={ setAttributes }
				attributes={ attributes }
			/>
		</div>
	);
};

export default compose( AddInitialAttr )( Edit );
