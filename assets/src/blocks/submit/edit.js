/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import { ToggleControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { useEffect } from '@wordpress/element';
import UAGTextControl from '@Components/text-control';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import MultiButtonsControl from '@Components/multi-buttons-control';
import { FontAwesomeIcon } from '@fortawesome/react-fontawesome';
import {
	faAlignLeft,
	faAlignRight,
	faAlignCenter,
} from '@fortawesome/free-solid-svg-icons';

export default ( { attributes, setAttributes } ) => {
	const { text, full, buttonAlignment, id } = attributes;

	const buttonStyles = {
		width: full && '100%',
	};

	const formId = useSelect( ( select ) => {
		const post_id = select( 'core/editor' ).getCurrentPostId();
		return post_id;
	} );

	useEffect( () => {
		if ( id !== formId ) {
			setAttributes( { id: formId } );
		}
	}, [ formId, setAttributes, id ] );

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
								data={ {
									value: text,
									label: 'text',
								} }
								label={ __( 'Button Text', 'sureforms' ) }
								value={ text }
								onChange={ ( value ) =>
									setAttributes( { text: value } )
								}
							/>
							<ToggleControl
								label={ __( 'Full', 'sureforms' ) }
								checked={ full }
								onChange={ ( checked ) =>
									setAttributes( { full: checked } )
								}
							/>
							{ full === false && (
								<UAGAdvancedPanelBody
									title={ __(
										'Button Alignment',
										'sureforms'
									) }
									initialOpen={ false }
								>
									<MultiButtonsControl
										label={ __(
											'Button Alignment',
											'sureforms'
										) }
										data={ {
											value: buttonAlignment,
											label: 'buttonAlignment',
										} }
										options={ [
											{
												value: 'left',
												icon: (
													<FontAwesomeIcon
														icon={ faAlignLeft }
													/>
												),
												tooltip: __(
													'Left',
													'sureforms'
												),
											},
											{
												value: 'center',
												icon: (
													<FontAwesomeIcon
														icon={ faAlignCenter }
													/>
												),
												tooltip: __(
													'Center',
													'sureforms'
												),
											},
											{
												value: 'right',
												icon: (
													<FontAwesomeIcon
														icon={ faAlignRight }
													/>
												),
												tooltip: __(
													'Right',
													'sureforms'
												),
											},
										] }
										showIcons={ true }
										onChange={ ( value ) => {
											if ( buttonAlignment !== value ) {
												setAttributes( {
													buttonAlignment: value,
												} );
											} else {
												setAttributes( {
													buttonAlignment: 'left',
												} );
											}
										} }
									/>
								</UAGAdvancedPanelBody>
							) }
						</UAGAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...UAGTabs.style }></InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<div style={ { textAlign: buttonAlignment } }>
				<button
					className="wp-block-button__link sureforms-button"
					style={ buttonStyles }
				>
					{ text }
				</button>
			</div>
		</>
	);
};
