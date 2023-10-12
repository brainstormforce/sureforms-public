/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import { SelectControl, ToggleControl } from '@wordpress/components';
import UAGTextControl from '@Components/text-control';
import UAGNumberControl from '@Components/number-control';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import MultiButtonsControl from '@Components/multi-buttons-control';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';
import { RatingClassicStyle } from './components/RatingClassicStyle';
import { RatingThemeStyle } from './components/RatingThemeStyle';

export default function Edit( { attributes, setAttributes, clientId } ) {
	const {
		id,
		required,
		label,
		ratingBoxHelpText,
		width,
		iconColor,
		showNumbers,
		iconShape,
		maxValue,
		errorMsg,
		formId,
	} = attributes;

	const blockID = useBlockProps().id.split( '-' ).join( '' );
	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );

	useEffect( () => {
		if ( id !== '' ) {
			return;
		}
		setAttributes( { id: blockID } );
	}, [ blockID, id, setAttributes ] );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	return (
		<>
			<InspectorControls>
				<InspectorTabs>
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
									label={ __( 'Error message', 'sureforms' ) }
									data={ {
										value: errorMsg,
										label: 'errorMsg',
									} }
									value={ errorMsg }
									onChange={ ( value ) =>
										setAttributes( { errorMsg: value } )
									}
								/>
							) }
							<UAGNumberControl
								label={ __( 'Number of Icons', 'sureforms' ) }
								displayUnit={ false }
								step={ 1 }
								min={ 1 }
								max={ 10 }
								data={ {
									value: maxValue,
									label: 'maxValue',
								} }
								value={ maxValue }
								onChange={ ( value ) => {
									if ( value <= 10 ) {
										setAttributes( {
											maxValue: value,
										} );
									}
								} }
							/>
							<UAGTextControl
								label={ __( 'Help', 'sureforms' ) }
								value={ ratingBoxHelpText }
								data={ {
									value: ratingBoxHelpText,
									label: 'ratingBoxHelpText',
								} }
								onChange={ ( value ) =>
									setAttributes( {
										ratingBoxHelpText: value,
									} )
								}
							/>
						</UAGAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...UAGTabs.style }>
						<UAGAdvancedPanelBody
							title={ __( 'Icon Styles', 'sureforms' ) }
							initialOpen={ true }
						>
							<MultiButtonsControl
								label={ __( 'Width', 'sureforms' ) }
								data={ {
									value: width,
									label: 'width',
								} }
								options={ [
									{
										value: 'halfWidth',
										icon: 'Half Width',
									},
									{
										value: 'fullWidth',
										icon: 'Full Width',
									},
								] }
								showIcons={ true }
								onChange={ ( value ) => {
									if ( width !== value ) {
										setAttributes( {
											width: value,
										} );
									} else {
										setAttributes( {
											width: 'fullWidth',
										} );
									}
								} }
							/>
							<AdvancedPopColorControl
								label={ __( 'Icon Color', 'sureforms' ) }
								setAttributes={ setAttributes }
								colorValue={ iconColor }
								data={ {
									value: iconColor,
									label: 'iconColor',
								} }
								onColorChange={ ( value ) =>
									setAttributes( { iconColor: value } )
								}
							/>
							<ToggleControl
								label={ __( 'Show Numbers', 'sureforms' ) }
								checked={ showNumbers }
								onChange={ ( checked ) =>
									setAttributes( { showNumbers: checked } )
								}
							/>

							<SelectControl
								value={ iconShape }
								label={ __( 'Icon', 'sureforms' ) }
								onChange={ ( value ) =>
									setAttributes( {
										iconShape: value,
									} )
								}
								options={ [
									{ label: 'Star', value: 'star' },
									{ label: 'Heart', value: 'heart' },
									{
										label: 'Smiley',
										value: 'smiley',
									},
								] }
							/>
						</UAGAdvancedPanelBody>
					</InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<div
				className={
					'main-container sf-classic-inputs-holder frontend-inputs-holder'
				}
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				{ 'classic' === sureforms_keys?._sureforms_form_styling ? (
					<RatingClassicStyle attributes={ attributes } />
				) : (
					<RatingThemeStyle attributes={ attributes } />
				) }
				{ ratingBoxHelpText !== '' && (
					<label
						htmlFor={ 'text-input-help-' + blockID }
						className={
							'classic' ===
							sureforms_keys?._sureforms_form_styling
								? 'sforms-helper-txt'
								: 'sf-text-secondary'
						}
					>
						{ ratingBoxHelpText }
					</label>
				) }
			</div>
		</>
	);
}
