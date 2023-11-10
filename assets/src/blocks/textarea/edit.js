/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, RichText } from '@wordpress/block-editor';
import { ToggleControl } from '@wordpress/components';
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
import { TextareaClassicStyle } from './components/TextareaClassicStyle';
import { TextareaThemeStyle } from './components/TextareaThemeStyle';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import { FieldsPreview } from '../FieldsPreview.jsx';

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
		preview,
	} = attributes;

	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );
	const stylingType = sureforms_keys?._srfm_form_styling;

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	// show the block preview on hover
	if ( preview ) {
		const fieldName = fieldsPreview.textarea_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

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
							<UAGNumberControl
								label={ __( 'Text Max Length', 'sureforms' ) }
								value={ maxLength }
								displayUnit={ false }
								min={ 0 }
								data={ {
									value: maxLength,
									label: 'maxLength',
								} }
								onChange={ ( value ) => {
									setAttributes( {
										maxLength: Number( value ),
									} );
								} }
							/>
							<UAGNumberControl
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
							<UAGNumberControl
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
							<UAGTextControl
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
						</UAGAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...UAGTabs.style }></InspectorTab>
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
