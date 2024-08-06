/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';
import { ToggleControl, SelectControl } from '@wordpress/components';
import { InspectorControls } from '@wordpress/block-editor';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import { PhoneComponent } from './components/default';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';
import { FieldsPreview } from '../FieldsPreview.jsx';
import { useErrMessage } from '@Blocks/util';
import ConditionalLogic from '@Components/conditional-logic';

const Edit = ( { attributes, setAttributes, clientId } ) => {
	const {
		required,
		fieldWidth,
		help,
		block_id,
		isUnique,
		duplicateMsg,
		preview,
		errorMsg,
		formId,
		autoCountry,
		className,
	} = attributes;
	const currentFormId = useGetCurrentFormId( clientId );
	// eslint-disable-next-line no-unused-vars
	const [ code, setCode ] = useState( null );

	function handleChange( e ) {
		setCode( e.target.value );
	}

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	const {
		currentMessage: currentErrorMsg,
		setCurrentMessage: setCurrentErrorMsg,
	} = useErrMessage( 'srfm_phone_block_required_text', errorMsg );

	const {
		currentMessage: currentUniqueMessage,
		setCurrentMessage: setCurrentUniqueMessage,
	} = useErrMessage( 'srfm_phone_block_unique_text', duplicateMsg );

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = srfm_fields_preview.phone_preview;
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
									'Enable Auto Country Detection',
									'sureforms'
								) }
								checked={ autoCountry }
								onChange={ ( value ) =>
									setAttributes( { autoCountry: value } )
								}
							/>
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
			<PhoneComponent
				attributes={ attributes }
				blockID={ block_id }
				handleChange={ handleChange }
				setAttributes={ setAttributes }
			/>
			<div className="srfm-error-wrap"></div>
		</div>
	);
};

export default compose( AddInitialAttr )( Edit );
