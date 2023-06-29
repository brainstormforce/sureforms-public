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
} from '@wordpress/components';

export default ( { attributes, setAttributes } ) => {
	const { label, placeholder, help, required, defaultValue } = attributes;

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
						label={ __( 'Default Value', 'sureforms' ) }
						value={ defaultValue }
						onChange={ ( value ) =>
							setAttributes( { defaultValue: value } )
						}
					/>
				</PanelRow>
				<PanelRow>
					<TextControl
						label={ __( 'Placeholder', 'sureforms' ) }
						value={ placeholder }
						onChange={ ( value ) =>
							setAttributes( { placeholder: value } )
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
			</PanelBody>
		</InspectorControls>
	);
};
