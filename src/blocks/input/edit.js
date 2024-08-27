/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl, SelectControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { InputComponent } from './components/default.js';
import Range from '@Components/range/Range.js';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';
import { FieldsPreview } from '../FieldsPreview.jsx';
import { useErrMessage } from '@Blocks/util';
import ConditionalLogic from '@Components/conditional-logic';

const Edit = ( { clientId, attributes, setAttributes } ) => {
	const {
		fieldWidth,
		help,
		required,
		block_id,
		defaultValue,
		errorMsg,
		textLength,
		isUnique,
		duplicateMsg,
		formId,
		preview,
		className,
		inputMask,
		customInputMask,
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
	} = useErrMessage( 'srfm_input_block_required_text', errorMsg );

	const {
		currentMessage: currentUniqueMessage,
		setCurrentMessage: setCurrentUniqueMessage,
	} = useErrMessage( 'srfm_input_block_unique_text', duplicateMsg );

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = srfm_fields_preview.input_preview;
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
							<SelectControl
								label={ __( 'Input Mask', 'sureforms' ) }
								value={ inputMask }
								options={ [
									{
										label: __(
											'None',
											'sureforms'
										),
										value: 'none',
									},
									{
										label: __(
											'(###) ###-####',
											'sureforms'
										),
										value: '(000) 000-0000',
									},
									{
										label: __(
											'(##) ####-####',
											'sureforms'
										),
										value: '(00) 0000-0000',
									},
									{
										label: __(
											'27/08/2024',
											'sureforms'
										),
										value: '00/00/0000',
									},
									{
										label: __(
											'23:59:59',
											'sureforms'
										),
										value: '00:00:00',
									},
									{
										label: __(
											'27/08/2024 23:59:59',
											'sureforms'
										),
										value: '00/00/0000 00:00:00',
									},
									{
										label: __(
											'Custom',
											'sureforms'
										),
										value: 'custom-mask',
									},
								] }
								onChange={ ( value ) => {
									setAttributes( { inputMask: value } );
								} }
							/>
							{ inputMask === 'custom-mask' && (
								<SRFMTextControl
									label={ __( 'Custom Mask', 'sureforms' ) }
									value={ customInputMask }
									data={ {
										value: customInputMask,
										label: 'customInputMask',
									} }
									onChange={ ( value ) =>
										setAttributes( { customInputMask: value } )
									}
								/>
							) }
							<SRFMTextControl
								label={ __( 'Default Value', 'sureforms' ) }
								className="srfm-with-dropdown"
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
							<SRFMTextControl
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
							<Range
								label={ __(
									'Maximum Text Length',
									'sureforms'
								) }
								displayUnit={ false }
								value={ textLength }
								min={ 0 }
								max={ 1000 }
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
						</SRFMAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...SRFMTabs.advance }>
						<ConditionalLogic
							{ ...{ setAttributes, attributes } }
						/>
					</InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<>
				<InputComponent
					blockID={ block_id }
					setAttributes={ setAttributes }
					attributes={ attributes }
				/>
				<div className="srfm-error-wrap"></div>
			</>
		</div>
	);
};

export default compose( AddInitialAttr )( Edit );
