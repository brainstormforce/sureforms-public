/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
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
import { ScInput } from '@surecart/components-react';

export default ( { className, attributes, setAttributes, isSelected } ) => {
	const { label, placeholder, help, name, required } = attributes;

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
							label={ __( 'Label', 'sureforms' ) }
							value={ label }
							onChange={ ( label ) => setAttributes( { label } ) }
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={ __( 'Placeholder', 'sureforms' ) }
							value={ placeholder }
							onChange={ ( placeholder ) =>
								setAttributes( { placeholder } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={ __( 'Help', 'sureforms' ) }
							value={ help }
							onChange={ ( help ) => setAttributes( { help } ) }
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>

			{ ! isSelected && ! name && <div>Please add a name</div> }
			<ScInput
				className={ className }
				required={ required }
				name={ name }
				label={ label }
				placeholder={ placeholder }
				help={ help }
			></ScInput>
		</Fragment>
	);
};
