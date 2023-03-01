/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, PanelRow, TextControl } from '@wordpress/components';

export default ({ attributes, setAttributes }) => {
	const { label, placeholder, help } = attributes;

	return (
		<InspectorControls>
			<PanelBody title={__('Attributes', 'surecart')}>
				<PanelRow>
					<TextControl
						label={__('Label', 'surecart')}
						value={label}
						onChange={(label) => setAttributes({ label })}
					/>
				</PanelRow>
				<PanelRow>
					<TextControl
						label={__('Placeholder', 'surecart')}
						value={placeholder}
						onChange={(placeholder) =>
							setAttributes({ placeholder })
						}
					/>
				</PanelRow>
				<PanelRow>
					<TextControl
						label={__('Help', 'surecart')}
						value={help}
						onChange={(help) => setAttributes({ help })}
					/>
				</PanelRow>
			</PanelBody>
		</InspectorControls>
	);
};
