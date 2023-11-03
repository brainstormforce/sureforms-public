/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { useState } from '@wordpress/element';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import UAGTextControl from '@Components/text-control';
import UAGNumberControl from '@Components/number-control';

export default ( { attributes, setAttributes, sureforms_keys } ) => {
	const { label, help, min, max, step, valueDisplayText } = attributes;
	const [ error, setError ] = useState( false );
	return (
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
						<UAGNumberControl
							label={ __( 'Min', 'sureforms' ) }
							value={ min }
							data={ {
								value: min,
								label: 'min',
							} }
							displayUnit={ false }
							onChange={ ( value ) => {
								if ( value >= max ) {
									setError( true );
									setAttributes( { min: 0 } );
								} else {
									setError( false );
									setAttributes( { min: value } );
								}
							} }
						/>
						<UAGNumberControl
							label={ __( 'Max', 'sureforms' ) }
							value={ max }
							data={ {
								value: max,
								label: 'max',
							} }
							displayUnit={ false }
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
						/>
						{ error && (
							<p
								style={ {
									fontSize: '12px',
									fontStyle: 'normal',
									color: 'red',
								} }
							>
								{ __(
									'Please check the value of Min and Max',
									'sureforms'
								) }
							</p>
						) }
						<p className="components-base-control__help">
							{ __(
								'Note:Max value should always be greater than min value',
								'sureforms'
							) }
						</p>
						<UAGNumberControl
							label={ __( 'Step Increment', 'sureforms' ) }
							value={ step }
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
							<UAGTextControl
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
						<UAGTextControl
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
					</UAGAdvancedPanelBody>
				</InspectorTab>
				<InspectorTab { ...UAGTabs.style }></InspectorTab>
			</InspectorTabs>
		</InspectorControls>
	);
};
