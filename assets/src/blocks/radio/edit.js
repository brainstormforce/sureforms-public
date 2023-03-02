/**
 * Component Dependencies
 */
import { ScRadio } from '@surecart/components-react';
import { InspectorControls, RichText } from '@wordpress/block-editor';
import { useBlockProps } from '@wordpress/block-editor';
import { PanelBody, PanelRow, TextControl, ToggleControl } from '@wordpress/components';
import { Fragment } from '@wordpress/element';
/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';

export default ({ className, attributes, setAttributes }) => {
	const { label, value, checked, name } = attributes;

	return (
		<Fragment>
			<InspectorControls>
				<PanelBody title={__('Attributes', 'sureforms')}>
					<PanelRow>
						<TextControl
							label={__('Name', 'sureforms')}
							value={name}
							onChange={(name) => setAttributes({ name })}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={__('Value', 'sureforms')}
							value={value}
							onChange={(value) => setAttributes({ value })}
						/>
					</PanelRow>
					<PanelRow>
						<ToggleControl
							label={__('Checked by default', 'sureforms')}
							checked={checked}
							onChange={(checked) => setAttributes({ checked })}
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>

			<ScRadio
				name={name}
				checked={checked}
				edit
				{...useBlockProps({
					className,
					style: {
						display: 'block',
					},
				})}
			>
				<RichText
					aria-label={__('Radio Text', 'sureforms')}
					placeholder={__(
						'Click here to add some radio text...',
						'sureforms'
					)}
					value={label}
					onChange={(label) => setAttributes({ label })}
				/>
			</ScRadio>
		</Fragment>
	);
};
