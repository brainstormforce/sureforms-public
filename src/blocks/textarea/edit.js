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
import SRFMNumberControl from '@Components/number-control';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { TextareaComponent } from './components/default';
import Range from '@Components/range/Range.js';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';
import { FieldsPreview } from '../FieldsPreview.jsx';
import { useErrMessage, decodeHtmlEntities } from '@Blocks/util';
import ConditionalLogic from '@Components/conditional-logic';

const Edit = ( { clientId, attributes, setAttributes } ) => {
	const {
		fieldWidth,
		placeholder,
		textAreaHelpText,
		required,
		maxLength,
		block_id,
		defaultValue,
		errorMsg,
		rows,
		cols,
		formId,
		preview,
		className,
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
	} = useErrMessage( 'srfm_textarea_block_required_text', errorMsg );

	// show the block preview on hover
	if ( preview ) {
		const fieldName = srfm_fields_preview.textarea_preview;
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
							<SRFMTextControl
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
							<SRFMTextControl
								variant="textarea"
								label={ __( 'Default Value', 'sureforms' ) }
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
							<SRFMNumberControl
								label={ __(
									'Text Maximum Length',
									'sureforms'
								) }
								value={ maxLength }
								displayUnit={ false }
								data={ {
									value: maxLength,
									label: 'maxLength',
								} }
								onChange={ ( value ) => {
									setAttributes( {
										maxLength: Number( value ),
									} );
								} }
								min={ 0 }
								showControlHeader={ false }
							/>
							<Range
								label={ __( 'Rows', 'sureforms' ) }
								value={ rows }
								displayUnit={ false }
								min={ 1 }
								max={ 100 }
								data={ {
									value: rows,
									label: 'rows',
								} }
								onChange={ ( value ) => {
									setAttributes( { rows: Number( value ) } );
								} }
							/>
							<Range
								label={ __( 'Columns', 'sureforms' ) }
								displayUnit={ false }
								value={ cols }
								min={ 1 }
								max={ 100 }
								data={ {
									value: cols,
									label: 'cols',
								} }
								onChange={ ( value ) => {
									setAttributes( { cols: Number( value ) } );
								} }
							/>
							<SRFMTextControl
								label={ __( 'Help Text', 'sureforms' ) }
								value={ textAreaHelpText }
								data={ {
									value: textAreaHelpText,
									label: 'textAreaHelpText',
								} }
								onChange={ ( value ) =>
									setAttributes( { textAreaHelpText: value } )
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
			<TextareaComponent
				blockID={ block_id }
				setAttributes={ setAttributes }
				attributes={ attributes }
			/>
			{ textAreaHelpText !== '' && (
				<RichText
					tagName="label"
					value={ textAreaHelpText }
					onChange={ ( value ) => {
						setAttributes( {
							textAreaHelpText: decodeHtmlEntities( value ),
						} );
					} }
					className="srfm-description"
					multiline={ false }
					id={ block_id }
					allowedFormats={ [] }
				/>
			) }
		</div>
	);
};

export default compose( AddInitialAttr )( Edit );
