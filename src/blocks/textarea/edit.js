/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
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
import { FieldsPreview } from '../FieldsPreview.jsx';
import { useErrMessage } from '@Blocks/util';
import ConditionalLogic from '@Components/conditional-logic';
import { attributeOptionsWithFilter } from '@Components/hooks';

const Edit = ( props ) => {
	const { clientId, attributes, setAttributes } = props;
	const {
		help,
		required,
		maxLength,
		block_id,
		defaultValue,
		errorMsg,
		rows,
		formId,
		preview,
		className,
		isRichText,
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
	} = useErrMessage( 'srfm_textarea_block_required_text', errorMsg );

	// show the block preview on hover
	if ( preview ) {
		const fieldName = srfm_fields_preview.textarea_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

	const attributeOptions = [
		{
			id: 'default-value',
			component: (
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
			),
		},
		{
			id: 'read-only',
			component: (
				<ToggleControl
					label={ __( 'Read Only', 'sureforms' ) }
					checked={ readOnly }
					onChange={ ( checked ) =>
						setAttributes( { readOnly: checked } )
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
			id: 'rich-text',
			component: (
				<ToggleControl
					label={ __( 'Rich Text Editor', 'sureforms' ) }
					checked={ isRichText }
					onChange={ ( checked ) =>
						setAttributes( { isRichText: checked } )
					}
				/>
			),
		},
		{
			id: 'error-message',
			component: required && (
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
			),
		},
		{
			id: 'max-length',
			component: ! isRichText && (
				<>
					<SRFMNumberControl
						label={ __( 'Text Maximum Length', 'sureforms' ) }
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
							setAttributes( {
								rows: Number( value ),
							} );
						} }
					/>
				</>
			),
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
	];

	const filterOptions = attributeOptionsWithFilter( attributeOptions, props );

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
			<div className="srfm-error-wrap"></div>
		</div>
	);
};

export default compose( AddInitialAttr )( Edit );
