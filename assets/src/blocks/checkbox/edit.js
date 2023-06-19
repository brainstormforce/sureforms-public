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
import { useEffect } from '@wordpress/element';

export default ( { attributes, setAttributes, isSelected } ) => {
	const {
		label,
		checked: isChecked,
		required,
		labelUrl,
		checkboxHelpText,
		id,
	} = attributes;

	const blockID = useBlockProps().id.split( '-' ).join( '' );
	useEffect( () => {
		if ( id !== '' ) {
			return;
		}
		setAttributes( { id: blockID } );
	}, [ blockID, id, setAttributes ] );

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
				className={
					'main-container' + ( isSelected ? ' sf--focus' : '' )
				}
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
				<label
					className="text-primary"
					htmlFor={ 'checkbox-block-' + blockID }
				>
					{ labelUrl !== '' ? (
						<a
							href={ labelUrl }
							className="text-primary"
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
				<div className="text-secondary">{ checkboxHelpText }</div>
			) }
		</div>
	);
};
