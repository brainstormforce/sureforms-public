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

export default ( { attributes, setAttributes } ) => {
	const { label, placeholder, textAreaHelpText, required, maxlength } =
		attributes;

	const blockID = useBlockProps().id;

	return (
		<div { ...useBlockProps() }>
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
							label={ __( 'Placeholder', 'sureforms' ) }
							value={ placeholder }
							onChange={ ( value ) =>
								setAttributes( { placeholder: value } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={ __( 'Max Length', 'sureforms' ) }
							value={ maxlength }
							onChange={ ( value ) =>
								setAttributes( { maxlength: value } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={ __( 'Help', 'sureforms' ) }
							value={ textAreaHelpText }
							onChange={ ( value ) =>
								setAttributes( { textAreaHelpText: value } )
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
				<label htmlFor={ 'text-area-block-' + blockID }>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				<textarea
					required={ required }
					label={ label }
					placeholder={ placeholder }
					rows={ maxlength }
				></textarea>
				{ textAreaHelpText !== '' && (
					<div style={ { color: '#ddd' } }>{ textAreaHelpText }</div>
				) }
			</div>
		</div>
	);
};
