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
	Disabled,
} from '@wordpress/components';

/**
 * Component Dependencies
 */
import { ScSwitch } from '@surecart/components-react';

export default ( { className, attributes, setAttributes, isSelected } ) => {
	const { label, value, checked, name, required, description } = attributes;

	return (
		<Fragment>
			<InspectorControls>
				<PanelBody title={ __( 'Attributes', 'sureforms' ) }>
					<PanelRow>
						<ToggleControl
							label={ __( 'Required', 'sureforms' ) }
							checked={ required }
							onChange={ ( required ) => setAttributes( { required } ) }
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={ __( 'Name', 'sureforms' ) }
							value={ name }
							onChange={ ( name ) => setAttributes( { name } ) }
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={ __( 'Value', 'sureforms' ) }
							value={ value }
							onChange={ ( value ) => setAttributes( { value } ) }
						/>
					</PanelRow>
					<PanelRow>
						<ToggleControl
							label={ __( 'Checked by default', 'sureforms' ) }
							checked={ checked }
							onChange={ ( checked ) => setAttributes( { checked } ) }
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>

			{ ! isSelected && ! name && <div>Please add a name</div> }

			<ScSwitch
				className={ className }
				name={ name }
				required={ required }
				edit
			>
				<RichText
					tagName="span"
					aria-label={ __( 'Switch label' ) }
					placeholder={ __( 'Add some text…' ) }
					value={ label }
					onChange={ ( label ) => setAttributes( { label } ) }
				/>
				{ ( description || isSelected ) && (
					<RichText
						tagName="span"
						slot="description"
						aria-label={ __( 'Switch label' ) }
						placeholder={ __( 'Enter a description…', 'sureforms' ) }
						value={ description }
						onChange={ ( description ) =>
							setAttributes( { description } )
						}
					/>
				) }
			</ScSwitch>
		</Fragment>
	);
};
