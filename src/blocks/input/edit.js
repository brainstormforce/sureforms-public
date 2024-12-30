/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	ToggleControl,
	SelectControl,
	ExternalLink,
} from '@wordpress/components';
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
import { FieldsPreview } from '../FieldsPreview.jsx';
import { useErrMessage } from '@Blocks/util';
import ConditionalLogic from '@Components/conditional-logic';
import { applyFilters } from '@wordpress/hooks';

const Edit = ( props ) => {
	const { clientId, attributes, setAttributes } = props;
	const {
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

	const attributeOptions = [
		{
			id: 'input-pattern',
			component: (
				<SelectControl
					label={ __( 'Input Pattern', 'sureforms' ) }
					value={ inputMask }
					options={ [
						{
							label: __( 'None', 'sureforms' ),
							value: 'none',
						},
						{
							label: __( '(###) ###-####', 'sureforms' ),
							value: '(###) ###-####',
						},
						{
							label: __( '(##) ####-####', 'sureforms' ),
							value: '(##) ####-####',
						},
						{
							label: __( '27/08/2024', 'sureforms' ),
							value: 'dd/mm/yyyy',
						},
						{
							label: __( '23:59:59', 'sureforms' ),
							value: 'hh:mm:ss',
						},
						{
							label: __( '27/08/2024 23:59:59', 'sureforms' ),
							value: 'dd/mm/yyyy hh:mm:ss',
						},
						{
							label: __( 'Custom', 'sureforms' ),
							value: 'custom-mask',
						},
					] }
					onChange={ ( value ) => {
						setAttributes( { inputMask: value } );
					} }
				/>
			),
		},
		{
			id: 'custom-mask',
			component:
				inputMask === 'custom-mask' ? (
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
						help={
							<>
								{ __(
									'Please check the documentation to manage custom input pattern ',
									'sureforms'
								) }
								<ExternalLink
									href="https://sureforms.com/docs/input-pattern"
									target="_blank"
									rel="noreferrer"
									className="srfm-block-url"
								>
									{ __( 'here', 'sureforms' ) }
								</ExternalLink>
							</>
						}
					/>
				) : null,
		},
		{
			id: 'default-value',
			component: (
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
			),
		},
		{
			id: 'required',
			component: (
				<ToggleControl
					label={ __( 'Required', 'sureforms' ) }
					checked={ required }
					onChange={ ( checked ) =>
						setAttributes( { required: checked } )
					}
				/>
			),
		},
		{
			id: 'error-message',
			component: required ? (
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
			) : null,
		},
		{
			id: 'unique',
			component: (
				<ToggleControl
					label={ __( 'Validate as Unique', 'sureforms' ) }
					checked={ isUnique }
					onChange={ ( checked ) =>
						setAttributes( { isUnique: checked } )
					}
				/>
			),
		},
		{
			id: 'unique-message',
			component: isUnique ? (
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
						setAttributes( { duplicateMsg: value } );
					} }
				/>
			) : null,
		},
		{
			id: 'help-text',
			component: (
				<SRFMTextControl
					label={ __( 'Help Text', 'sureforms' ) }
					value={ help }
					data={ {
						value: help,
						label: 'help',
					} }
					onChange={ ( value ) => setAttributes( { help: value } ) }
				/>
			),
		},
		{
			id: 'max-text-length',
			component: (
				<Range
					label={ __( 'Maximum Text Length', 'sureforms' ) }
					displayUnit={ false }
					value={ textLength }
					min={ 0 }
					max={ 1000 }
					data={ {
						value: textLength,
						label: 'textLength',
					} }
					onChange={ ( value ) =>
						setAttributes( { textLength: Number( value ) } )
					}
				/>
			),
		},
	];

	const filterOptions = applyFilters(
		'srfm.block.attributes.panel.body',
		attributeOptions,
		props
	);

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
							{ filterOptions.map(
								( option ) => option.component
							) }
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
