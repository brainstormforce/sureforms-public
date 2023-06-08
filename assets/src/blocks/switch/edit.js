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

export default ( { attributes, setAttributes } ) => {
	const { label, checked: isChecked, required, switchHelpText, id } = attributes;

	const blockID = useBlockProps().id.split( '-' ).join( '' );

	const inputStyle = {
		position: 'absolute',
		opacity: 0,
		width: 0,
		height: 0,
	};

	const switchStyle = {
		display: 'inline-block',
		position: 'relative',
		width: '50px',
		height: '25px',
		borderRadius: '25px',
		backgroundColor: isChecked ? '#007CBA' : '#dcdcdc',
		transition: 'background-color 0.2s',
		cursor: 'pointer',
	};

	const thumbStyle = {
		display: 'inline-block',
		position: 'absolute',
		width: '21px',
		height: '21px',
		borderRadius: '50%',
		backgroundColor: '#fff',
		top: '2px',
		left: isChecked ? '27px' : '2px',
		transition: 'left 0.2s',
	};

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
							value={ switchHelpText }
							onChange={ ( value ) =>
								setAttributes( { switchHelpText: value } )
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
				<div style={ switchStyle }>
					<input
						type="checkbox"
						checked={ isChecked }
						style={ inputStyle }
					/>
					<div style={ thumbStyle }></div>
				</div>
				<label htmlFor={ 'switch-block-' + blockID }>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
			</div>
			{ switchHelpText !== '' && (
				<div style={ { color: '#ddd' } }>{ switchHelpText }</div>
			) }
		</div>
	);
};
