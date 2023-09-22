/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { ToggleControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import UAGTextControl from '@Components/text-control';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';

export default ( { attributes, setAttributes, isSelected } ) => {
	const {
		label,
		checked: isChecked,
		required,
		labelUrl,
		checkboxHelpText,
		id,
		errorMsg,
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
								data={ {
									value: label,
									label: 'label',
								} }
								value={ label }
								onChange={ ( value ) => {
									setAttributes( { label: value } );
								} }
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
									data={ {
										value: errorMsg,
										label: 'errorMsg',
									} }
									label={ __( 'Error message', 'sureforms' ) }
									value={ errorMsg }
									onChange={ ( value ) =>
										setAttributes( { errorMsg: value } )
									}
								/>
							) }
							<UAGTextControl
								data={ {
									value: labelUrl,
									label: 'labelUrl',
								} }
								type="url"
								label={ __( 'Label Url', 'sureforms' ) }
								placeholder="https://example.com/"
								value={ labelUrl }
								onChange={ ( value ) =>
									setAttributes( { labelUrl: value } )
								}
							/>
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
								data={ {
									value: checkboxHelpText,
									label: 'checkboxHelpText',
								} }
								label={ __( 'Help', 'sureforms' ) }
								value={ checkboxHelpText }
								onChange={ ( value ) =>
									setAttributes( { checkboxHelpText: value } )
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
