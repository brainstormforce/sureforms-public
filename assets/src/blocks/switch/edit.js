/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	useBlockProps,
	store as blockEditorStore,
} from '@wordpress/block-editor';
import { ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import UAGTextControl from '@Components/text-control';

export default ( { clientId, attributes, setAttributes, isSelected } ) => {
	const {
		label,
		checked: isChecked,
		required,
		switchHelpText,
		id,
		errorMsg,
		formId,
	} = attributes;

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

	const currentFormId = useSelect( ( select ) => {
		// parent block id attribute.
		const parents = select( blockEditorStore ).getBlockParents( clientId );
		const parentBlock = select( blockEditorStore ).getBlocksByClientId(
			parents?.[ 0 ]
		);
		// current post id.
		const post_id = select( 'core/editor' ).getCurrentPostId();
		return parentBlock?.[ 0 ]?.attributes?.id || post_id;
	} );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	console.log( formId );

	useEffect( () => {
		if ( id !== '' ) {
			return;
		}
		setAttributes( { id: blockID } );
	}, [ blockID, id, setAttributes ] );

	return (
		<>
			<InspectorControls>
				<InspectorTabs
					tabs={ [ 'general', 'advance' ] }
					defaultTab={ 'general' }
				>
					<InspectorTab { ...UAGTabs.general }>
						<UAGAdvancedPanelBody
							title={ __( 'Attributes', 'sureforms' ) }
							initialOpen={ true }
						>
							<UAGTextControl
								label={ __( 'Label', 'sureforms' ) }
								value={ label }
								data={ {
									value: label,
									label: 'label',
								} }
								onChange={ ( value ) =>
									setAttributes( { label: value } )
								}
							/>
							<ToggleControl
								label={ __( 'Required', 'sureforms' ) }
								checked={ required }
								onChange={ ( checked ) =>
									setAttributes( { required: checked } )
								}
							/>
							{ required && (
								<UAGTextControl
									label={ __( 'Error message', 'sureforms' ) }
									value={ errorMsg }
									data={ {
										value: errorMsg,
										label: 'errorMsg',
									} }
									onChange={ ( value ) =>
										setAttributes( { errorMsg: value } )
									}
								/>
							) }
							<ToggleControl
								label={ __(
									'Checked by default',
									'sureforms'
								) }
								checked={ isChecked }
								onChange={ ( checked ) =>
									setAttributes( { checked } )
								}
							/>
							<UAGTextControl
								label={ __( 'Help', 'sureforms' ) }
								value={ switchHelpText }
								data={ {
									value: switchHelpText,
									label: 'switchHelpText',
								} }
								onChange={ ( value ) =>
									setAttributes( { switchHelpText: value } )
								}
							/>
						</UAGAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...UAGTabs.style }></InspectorTab>
				</InspectorTabs>
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
				<div style={ switchStyle }>
					<input
						type="checkbox"
						checked={ isChecked }
						style={ inputStyle }
					/>
					<div style={ thumbStyle }></div>
				</div>
				<label
					className="text-primary"
					htmlFor={ 'switch-block-' + blockID }
				>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
			</div>
			{ switchHelpText !== '' && (
				<div className="text-secondary">{ switchHelpText }</div>
			) }
		</>
	);
};
