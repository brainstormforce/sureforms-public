/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	TextControl,
	ToggleControl,
} from '@wordpress/components';

const SureformInput = ( { className, attributes, setAttributes } ) => {
	const { label, placeholder, help, required } = attributes;
	const blockID = useBlockProps().id;

	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Attributes', 'sureforms' ) }>
					<PanelRow>
						<ToggleControl
							label={ __( 'Required', 'sureforms' ) }
							checked={ required }
							onChange={ ( newValue ) =>
								setAttributes( { required: newValue } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={ __( 'Label', 'sureforms' ) }
							value={ label }
							onChange={ ( newValue ) =>
								setAttributes( { label: newValue } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={ __( 'Placeholder', 'sureforms' ) }
							value={ placeholder }
							onChange={ ( newValue ) =>
								setAttributes( { placeholder: newValue } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={ __( 'Help', 'sureforms' ) }
							value={ help }
							onChange={ ( newValue ) =>
								setAttributes( { help: newValue } )
							}
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<div
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				<label htmlFor={ 'number-input-' + blockID }>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				<input
					id={ 'number-input-' + blockID }
					type="number"
					className={ className }
					placeholder={ placeholder }
					required={ required }
				/>
				{ help !== '' && (
					<label
						htmlFor={ 'number-input-help-' + blockID }
						style={ { color: '#ddd' } }
					>
						{ help }
					</label>
				) }
			</div>
		</div>
	);
};

export default SureformInput;
