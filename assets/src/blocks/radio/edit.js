/**
 * Component Dependencies
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import {
	PanelBody,
	PanelRow,
	TextControl,
	ToggleControl,
} from '@wordpress/components';

export default ( { attributes, setAttributes } ) => {
	const { required, label, checked: isChecked, radioHelpText } = attributes;
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
						<ToggleControl
							label={ __( 'Checked by default', 'sureforms' ) }
							checked={ isChecked }
							onChange={ ( checked ) =>
								setAttributes( { checked } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={ __( 'Help', 'sureforms' ) }
							value={ radioHelpText }
							onChange={ ( value ) =>
								setAttributes( { radioHelpText: value } )
							}
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<div
				style={ {
					display: 'flex',
					gap: '.4rem',
					alignItems: 'center',
				} }
			>
				<input
					type="radio"
					id={ 'radio-block-' + blockID }
					checked={ isChecked }
					required={ required }
					style={ { marginTop: '1px' } }
				></input>
				<label htmlFor={ 'radio-block-' + blockID }>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
			</div>
			{ radioHelpText !== '' && (
				<div style={ { color: '#ddd' } }>{ radioHelpText }</div>
			) }
		</div>
	);
};
