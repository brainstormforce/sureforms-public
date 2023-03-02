/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, RichText } from '@wordpress/block-editor';
import { Fragment } from '@wordpress/element';
import {
	PanelBody,
	PanelRow,
	TextControl,
	ToggleControl,
} from '@wordpress/components';

/**
 * Component Dependencies
 */
import { ScCheckbox } from '@surecart/components-react';
import { useBlockProps } from '@wordpress/block-editor';

export default ({ className, attributes, setAttributes }) => {
	const { label, value, checked, name, required } = attributes;

	const blockProps = useBlockProps();

	return (
		<Fragment>
			<InspectorControls>
				<PanelBody title={__('Attributes', 'sureforms')}>
					<PanelRow>
						<ToggleControl
							label={__('Required', 'sureforms')}
							checked={required}
							onChange={(required) => setAttributes({ required })}
						/>
					</PanelRow>
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

			<ScCheckbox
				className={className}
				name={name}
				required={required}
				edit
				{...blockProps}
			>
				<RichText
					aria-label={__('Checkbox Text', 'sureforms')}
					placeholder={__('Add some checkbox text...', 'sureforms')}
					value={label}
					onChange={(label) => setAttributes({ label })}
				/>
			</ScCheckbox>
		</Fragment>
	);
};
