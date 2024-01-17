/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, RichText } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import { SelectControl, ToggleControl } from '@wordpress/components';
import SRFMTextControl from '@Components/text-control';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { RatingComponent } from './components/default';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import { FieldsPreview } from '../FieldsPreview.jsx';
import Range from '@Components/range/Range.js';
import widthOptions from '../width-options.json';

const Edit = ( { attributes, setAttributes, clientId } ) => {
	const {
		block_id,
		required,
		fieldWidth,
		label,
		ratingBoxHelpText,
		showNumbers,
		iconShape,
		maxValue,
		preview,
		errorMsg,
		formId,
	} = attributes;

	const currentFormId = useGetCurrentFormId( clientId );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = fieldsPreview.rating_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

	return (
		<>
			<InspectorControls>
				<InspectorTabs>
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
								data={ {
									value: label,
									label: 'label',
								} }
								value={ label }
								onChange={ ( value ) => {
									setAttributes( { label: value } );
								} }
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
									data={ {
										value: errorMsg,
										label: 'errorMsg',
									} }
									value={ errorMsg }
									onChange={ ( value ) =>
										setAttributes( { errorMsg: value } )
									}
								/>
							) }
							<Range
								label={ __( 'Number of Icons', 'sureforms' ) }
								displayUnit={ false }
								step={ 1 }
								min={ 1 }
								max={ 10 }
								data={ {
									value: maxValue,
									label: 'maxValue',
								} }
								value={ maxValue }
								onChange={ ( value ) => {
									if ( value <= 10 ) {
										setAttributes( {
											maxValue: value,
										} );
									}
								} }
							/>
							<SRFMTextControl
								label={ __( 'Help', 'sureforms' ) }
								value={ ratingBoxHelpText }
								data={ {
									value: ratingBoxHelpText,
									label: 'ratingBoxHelpText',
								} }
								onChange={ ( value ) =>
									setAttributes( {
										ratingBoxHelpText: value,
									} )
								}
							/>
						</SRFMAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...SRFMTabs.style }>
						<SRFMAdvancedPanelBody
							title={ __( 'Icon Styles', 'sureforms' ) }
							initialOpen={ true }
						>
							<ToggleControl
								label={ __( 'Show Numbers', 'sureforms' ) }
								checked={ showNumbers }
								onChange={ ( checked ) =>
									setAttributes( { showNumbers: checked } )
								}
							/>
							<SelectControl
								value={ iconShape }
								label={ __( 'Icon', 'sureforms' ) }
								onChange={ ( value ) =>
									setAttributes( {
										iconShape: value,
									} )
								}
								options={ [
									{ label: 'Star', value: 'star' },
									{ label: 'Heart', value: 'heart' },
									{
										label: 'Smiley',
										value: 'smiley',
									},
								] }
							/>
						</SRFMAdvancedPanelBody>
					</InspectorTab>
				</InspectorTabs>
			</InspectorControls>

			<RatingComponent
				setAttributes={ setAttributes }
				blockID={ block_id }
				attributes={ attributes }
			/>
			{ ratingBoxHelpText !== '' && (
				<RichText
					tagName="label"
					value={ ratingBoxHelpText }
					onChange={ ( value ) =>
						setAttributes( { ratingBoxHelpText: value } )
					}
					className="srfm-description"
					multiline={ false }
					id={ block_id }
					allowedFormats={ [] }
				/>
			) }
		</>
	);
};

export default compose( AddInitialAttr )( Edit );
