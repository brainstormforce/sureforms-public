/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/editor';
import { PanelBody, PanelRow, TextControl } from '@wordpress/components';

const MyInspectorControls = ( { attributes, setAttributes } ) => {
	const { label, placeholder, help, name } = attributes;

	return (
		<InspectorControls>
			<PanelBody title={ __( 'Attributes', 'sureforms' ) }>
				<PanelRow>
					<TextControl
						label={ __( 'Name', 'sureforms' ) }
						value={ name }
						onChange={ ( newName ) =>
							setAttributes( { name: newName } )
						}
					/>
				</PanelRow>
				<PanelRow>
					<TextControl
						label={ __( 'Label', 'sureforms' ) }
						value={ label }
						onChange={ ( newLabel ) =>
							setAttributes( { label: newLabel } )
						}
					/>
				</PanelRow>
				<PanelRow>
					<TextControl
						label={ __( 'Placeholder', 'sureforms' ) }
						value={ placeholder }
						onChange={ ( newPlaceholder ) =>
							setAttributes( { placeholder: newPlaceholder } )
						}
					/>
				</PanelRow>
				<PanelRow>
					<TextControl
						label={ __( 'Help', 'sureforms' ) }
						value={ help }
						onChange={ ( newHelp ) =>
							setAttributes( { help: newHelp } )
						}
					/>
				</PanelRow>
			</PanelBody>
		</InspectorControls>
	);
};

export default MyInspectorControls;
