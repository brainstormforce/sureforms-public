/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/editor';
import { PanelBody, PanelRow, TextControl } from '@wordpress/components';

export default ( { attributes, setAttributes } ) => {
	const { label, placeholder, help, name } = attributes;

	return (
		<InspectorControls>
			<PanelBody title={ __( 'Attributes', 'sureforms' ) }>
				<PanelRow>
					<TextControl
						label={ __( 'Name', 'sureforms' ) }
						value={ name }
						onChange={ ( value ) =>
							setAttributes( { name: value } )
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
