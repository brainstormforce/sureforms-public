/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	PanelRow,
	TextControl,
	ToggleControl,
} from '@wordpress/components';
import { useEffect } from '@wordpress/element';

export default ( { className, attributes, setAttributes, isSelected } ) => {
	const { label, placeholder, help, required, id, defaultValue, errorMsg, textLength, isUnique, duplicateMsg } =
		attributes;
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
					{ required && (
						<PanelRow>
							<TextControl
								label={ __( 'Error message', 'sureforms' ) }
								value={ errorMsg }
								onChange={ ( value ) =>
									setAttributes( { errorMsg: value } )
								}
							/>
						</PanelRow>
					) }
					<PanelRow>
						<ToggleControl
							label={ __( 'Validate as unique', 'sureforms' ) }
							checked={ isUnique }
							onChange={ ( checked ) =>
								setAttributes( { isUnique: checked } )
							}
						/>
					</PanelRow>
					{ isUnique && (
						<PanelRow>
							<TextControl
								label={ __( 'Validation Message for Duplicate ', 'sureforms' ) }
								value={ duplicateMsg }
								onChange={ ( value ) =>
									setAttributes( { duplicateMsg: value } )
								}
							/>
						</PanelRow>
					) }
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
							label={ __( 'Default Value', 'sureforms' ) }
							value={ defaultValue }
							onChange={ ( value ) =>
								setAttributes( { defaultValue: value } )
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
							label={ __( 'Help', 'sureforms' ) }
							value={ help }
							onChange={ ( value ) =>
								setAttributes( { help: value } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							as="number"
							label={ __( 'Max text length', 'sureforms' ) }
							value={ textLength }
							onChange={ ( value ) =>
								setAttributes( { textLength: Number( value ) } )
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
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				<label htmlFor={ 'text-input-' + blockID }>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				<input
					id={ 'text-input-' + blockID }
					type="text"
					value={ defaultValue }
					className={ className }
					placeholder={ placeholder }
					required={ required }
				/>
				{ help !== '' && (
					<label
						htmlFor={ 'text-input-help-' + blockID }
						className="text-secondary"
					>
						{ help }
					</label>
				) }
			</div>
		</div>
	);
};
