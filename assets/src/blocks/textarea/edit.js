/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, RichText } from '@wordpress/block-editor';
import { ToggleControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import SRFMNumberControl from '@Components/number-control';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';
import { TextareaClassicStyle } from './components/TextareaClassicStyle';
import { TextareaThemeStyle } from './components/TextareaThemeStyle';
import Range from '@Components/range/Range.js';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';

const Edit = ( { clientId, attributes, setAttributes } ) => {
	const {
		label,
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
	} = attributes;

	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );
	const stylingType = sureforms_keys?._srfm_form_styling;

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
					<InspectorTab { ...SRFMTabs.general }>
						<SRFMAdvancedPanelBody
							title={ __( 'Attributes', 'sureforms' ) }
							initialOpen={ true }
						>
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
								label={ __( 'Help', 'sureforms' ) }
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
				</InspectorTabs>
			</InspectorControls>
			<div
				className={ `srfm-main-container  srfm-classic-inputs-holder srfm-block-${ block_id }` }
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				{ 'classic' === stylingType ? (
					<TextareaClassicStyle
						blockID={ block_id }
						setAttributes={ setAttributes }
						attributes={ attributes }
					/>
				) : (
					<TextareaThemeStyle
						blockID={ block_id }
						setAttributes={ setAttributes }
						attributes={ attributes }
					/>
				) }
				{ textAreaHelpText !== '' && (
					<RichText
						tagName="label"
						value={ textAreaHelpText }
						onChange={ ( value ) =>
							setAttributes( { textAreaHelpText: value } )
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
