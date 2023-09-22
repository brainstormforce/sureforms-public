/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { ToggleControl } from '@wordpress/components';
import { useEffect } from '@wordpress/element';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import UAGTextControl from '@Components/text-control';
import UAGNumberControl from '@Components/number-control';

export default ( { attributes, setAttributes, isSelected } ) => {
	const {
		label,
		placeholder,
		textAreaHelpText,
		required,
		maxLength,
		id,
		defaultValue,
		errorMsg,
		rows,
		cols,
	} = attributes;

	const blockID = useBlockProps().id.split( '-' ).join( '' );
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
							<UAGTextControl
								label={ __( 'Placeholder', 'sureforms' ) }
								value={ placeholder }
								data={ {
									value: placeholder,
									label: 'placeholder',
								} }
								onChange={ ( value ) =>
									setAttributes( { placeholder: value } )
								}
							/>
							<UAGTextControl
								variant="textarea"
								label={ __( 'Default Value', 'sureforms' ) }
								value={ defaultValue }
								data={ {
									value: defaultValue,
									label: 'defaultValue',
								} }
								onChange={ ( value ) =>
									setAttributes( { defaultValue: value } )
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
							<UAGNumberControl
								label={ __( 'Text Max Length', 'sureforms' ) }
								value={ maxLength }
								displayUnit={ false }
								min={ 0 }
								data={ {
									value: maxLength,
									label: 'maxLength',
								} }
								onChange={ ( value ) => {
									setAttributes( {
										maxLength: Number( value ),
									} );
								} }
							/>
							<UAGNumberControl
								label={ __( 'Rows', 'sureforms' ) }
								value={ rows }
								displayUnit={ false }
								min={ 1 }
								max={ 100 }
								data={ {
									value: rows,
									label: 'rows',
								} }
								onChange={ ( value ) => {
									setAttributes( { rows: Number( value ) } );
								} }
							/>
							<UAGNumberControl
								label={ __( 'Columns', 'sureforms' ) }
								displayUnit={ false }
								value={ cols }
								min={ 1 }
								max={ 100 }
								data={ {
									value: cols,
									label: 'cols',
								} }
								onChange={ ( value ) => {
									setAttributes( { cols: Number( value ) } );
								} }
							/>
							<UAGTextControl
								label={ __( 'Help', 'sureforms' ) }
								value={ textAreaHelpText }
								data={ {
									value: textAreaHelpText,
									label: 'textAreaHelpText',
								} }
								onChange={ ( value ) =>
									setAttributes( { textAreaHelpText: value } )
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
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				<label
					className="text-primary"
					htmlFor={ 'text-area-block-' + blockID }
				>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				<textarea
					required={ required }
					label={ label }
					placeholder={ placeholder }
					value={ defaultValue }
					rows={ rows }
					cols={ cols }
					maxLength={ maxLength }
				></textarea>
				{ textAreaHelpText !== '' && (
					<div className="text-secondary">{ textAreaHelpText }</div>
				) }
			</div>
		</>
	);
};
