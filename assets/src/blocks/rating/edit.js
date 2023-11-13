/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, RichText } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import { SelectControl, ToggleControl } from '@wordpress/components';
import UAGTextControl from '@Components/text-control';
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
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import Range from '@Components/range/Range.js';

const Edit = ( { attributes, setAttributes, clientId } ) => {
	const {
		block_id,
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

	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );

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
							<Range
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
							{ 'classic' !==
								sureforms_keys?._sureforms_form_styling && (
								<>
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
										label={ __(
											'Icon Color',
											'sureforms'
										) }
										setAttributes={ setAttributes }
										colorValue={ iconColor }
										data={ {
											value: iconColor,
											label: 'iconColor',
										} }
										onColorChange={ ( value ) =>
											setAttributes( {
												iconColor: value,
											} )
										}
									/>
								</>
							) }
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
					'srfm-main-container srfm-classic-inputs-holder srfm-frontend-inputs-holder'
				}
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				{ 'classic' === sureforms_keys?._srfm_form_styling ? (
					<RatingClassicStyle
						setAttributes={ setAttributes }
						blockID={ block_id }
						attributes={ attributes }
					/>
				) : (
					<RatingThemeStyle
						setAttributes={ setAttributes }
						blockID={ block_id }
						attributes={ attributes }
					/>
				) }
				{ ratingBoxHelpText !== '' && (
					<RichText
						tagName="label"
						value={ ratingBoxHelpText }
						onChange={ ( value ) =>
							setAttributes( { ratingBoxHelpText: value } )
						}
						className={
							'classic' === sureforms_keys?._srfm_form_styling
								? 'srfm-helper-txt'
								: 'srfm-text-secondary'
						}
						multiline={ false }
						id={ block_id }
					/>
				) }
			</div>
		</>
	);
};

export default compose( AddInitialAttr )( Edit );
