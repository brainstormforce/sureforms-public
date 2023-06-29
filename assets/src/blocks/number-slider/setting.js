/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	TextControl,
	ToggleControl,
	Flex,
	FlexBlock,
} from '@wordpress/components';
import { useState } from '@wordpress/element';

export default ( { attributes, setAttributes } ) => {
	const { label, help, required, min, max, step, valueDisplayText } =
		attributes;
	const [ error, setError ] = useState( false );
	return (
		<InspectorControls>
			<PanelBody title={ __( 'Attributes', 'sureforms' ) }>
				<PanelRow>
					<ToggleControl
						label={ __( 'Required', 'sureforms' ) }
						checked={ required }
						onChange={ ( checked ) =>
							setAttributes( { required: checked } )
						}
					/>
				</PanelRow>
				<PanelRow>
					<TextControl
						label={ __( 'Label', 'sureforms' ) }
						value={ label }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
					/>
				</PanelRow>
				<PanelRow>
					<TextControl
						label={ __( 'Help', 'sureforms' ) }
						value={ help }
						onChange={ ( value ) =>
							setAttributes( { help: value } )
						}
					/>
				</PanelRow>
				<Flex>
					<FlexBlock>
						<TextControl
							label={ __( 'Min', 'sureforms' ) }
							type="number"
							value={ min }
							onChange={ ( value ) => {
								value = Number( value );
								if ( value >= max ) {
									setError( true );
									setAttributes( { min: 0 } );
								} else {
									setError( false );
									setAttributes( { min: value } );
								}
							}
							}
						/>
					</FlexBlock>
					<FlexBlock>
						<TextControl
							label={ __( 'Max', 'sureforms' ) }
							type="number"
							value={ max }
							onChange={ ( value ) => {
								value = Number( value );
								if ( value <= min ) {
									setError( true );
									setAttributes( { max: Number( min ) + 1 } );
								} else {
									setError( false );
									setAttributes( { max: value } );
								}
							 }
							}
						/>
					</FlexBlock>
				</Flex>
				{ error && <p style={ { fontSize: '12px', fontStyle: 'normal', color: 'red' } }>{ __( 'Please enter greater value than min value', 'sureforms' ) }</p> }
				<p style={ { fontSize: '12px', fontStyle: 'normal', color: 'rgb(117, 117, 117)' } }>{ __( 'Note:Max value should always be greater than min value', 'sureforms' ) }</p>
				<PanelRow>
					<TextControl
						label={ __( 'Step Increment', 'sureforms' ) }
						type="number"
						value={ step }
						onChange={ ( value ) =>
							setAttributes( { step: value } )
						}
					/>
				</PanelRow>
				<PanelRow>
					<TextControl
						label={ __( 'Value Display label', 'sureforms' ) }
						value={ valueDisplayText }
						onChange={ ( value ) =>
							setAttributes( { valueDisplayText: value } )
						}
					/>
				</PanelRow>
			</PanelBody>
		</InspectorControls>
	);
};
