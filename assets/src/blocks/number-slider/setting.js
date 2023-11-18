/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { useState } from '@wordpress/element';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import SRFMNumberControl from '@Components/number-control';
import Range from '@Components/range/Range.js';

export default ( { attributes, setAttributes, sureforms_keys } ) => {
	const { label, help, min, max, step, valueDisplayText } = attributes;
	const [ error, setError ] = useState( false );
	return (
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
						<SRFMNumberControl
							label={ __( 'Minimum Value', 'sureforms' ) }
							value={ min }
							data={ {
								value: min,
								label: 'min',
							} }
							onChange={ ( value ) => {
								if ( value >= max ) {
									setError( true );
									setAttributes( { min: 0 } );
								} else {
									setError( false );
									setAttributes( { min: value } );
								}
							} }
							min={ 0 }
							displayUnit={ false }
							showControlHeader={ false }
						/>
						<SRFMNumberControl
							label={ __( 'Maximum Value', 'sureforms' ) }
							value={ max }
							data={ {
								value: max,
								label: 'max',
							} }
							onChange={ ( value ) => {
								if ( value <= min ) {
									setError( true );
									setAttributes( {
										max: Number( min ) + 1,
									} );
								} else {
									setError( false );
									setAttributes( { max: value } );
								}
							} }
							min={ 0 }
							displayUnit={ false }
							showControlHeader={ false }
						/>
						{ error && (
							<p className="srfm-min-max-error-styles">
								{ __(
									'Please check the Minimum and Maximum value',
									'sureforms'
								) }
							</p>
						) }
						<p className="components-base-control__help">
							{ __(
								'Note: Maximum value should be greater than minimum value',
								'sureforms'
							) }
						</p>
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
						<Range
							label={ __( 'Step Increment', 'sureforms' ) }
							value={ step }
							min={ 1 }
							max={ max - 1 }
							data={ {
								value: step,
								label: 'step',
							} }
							displayUnit={ false }
							onChange={ ( value ) => {
								setAttributes( { step: value } );
							} }
						/>
						{ 'classic' === sureforms_keys?._srfm_form_styling ? (
							''
						) : (
							<SRFMTextControl
								label={ __(
									'Value Display label',
									'sureforms'
								) }
								data={ {
									value: valueDisplayText,
									label: 'valueDisplayText',
								} }
								value={ valueDisplayText }
								onChange={ ( value ) =>
									setAttributes( { valueDisplayText: value } )
								}
							/>
						) }
						<SRFMTextControl
							label={ __( 'Help', 'sureforms' ) }
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
			</InspectorTabs>
		</InspectorControls>
	);
};
