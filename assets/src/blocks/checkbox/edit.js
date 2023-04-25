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
	const {
		label,
		checked: isChecked,
		required,
		labelUrl,
		checkboxHelpText,
	} = attributes;

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
							type="url"
							label={ __( 'Label Url', 'sureforms' ) }
							placeholder="https://example.com/"
							value={ labelUrl }
							onChange={ ( value ) =>
								setAttributes( { labelUrl: value } )
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
							value={ checkboxHelpText }
							onChange={ ( value ) =>
								setAttributes( { checkboxHelpText: value } )
							}
						/>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<div
				style={ {
					display: 'flex',
					alignItems: 'center',
					gap: '.4rem',
				} }
			>
				<input
					type="checkbox"
					id={ 'checkbox-block-' + blockID }
					checked={ isChecked }
					required={ required }
				></input>
				<label htmlFor={ 'checkbox-block-' + blockID }>
					{ labelUrl !== '' ? (
						<a
							href={ labelUrl }
							style={ { textDecoration: 'none' } }
						>
							{ label }
						</a>
					) : (
						label
					) }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
			</div>
			{ checkboxHelpText !== '' && (
				<div style={ { color: '#ddd' } }>{ checkboxHelpText }</div>
			) }
		</div>
	);
};
