/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { ToggleControl, SelectControl } from '@wordpress/components';
import { InspectorControls, RichText } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import UAGTextControl from '@Components/text-control';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';
import { AddressThemeStyle } from './components/addressThemeStyle';
import { AddressClassicStyle } from './components/addressClassicStyle';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import widthOptions from '../width-options.json';

import countries from './countries.json';

const Edit = ( { clientId, attributes, setAttributes, isSelected } ) => {
	const {
		required,
		fieldWidth,
		label,
		block_id,
		errorMsg,
		lineOnePlaceholder,
		lineTwoPlaceholder,
		cityPlaceholder,
		statePlaceholder,
		postalPlaceholder,
		lineOneLabel,
		lineTwoLabel,
		cityLabel,
		stateLabel,
		countryLabel,
		countryPlaceholder,
		postalLabel,
		formId,
		help,
	} = attributes;
	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	useEffect( () => {
		const width_req_element = document.getElementById(
			'srfm-address-fieldwidth' + block_id
		);
		const parent_to_width_element = width_req_element.parentElement;
		parent_to_width_element.style.width =
			'calc( ' + fieldWidth + '% - 20px)';
	}, [ fieldWidth ] );

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
							<SelectControl
								label={ __( 'Field Width', 'sureforms' ) }
								value={ fieldWidth }
								options={ widthOptions }
								onChange={ ( value ) =>
									setAttributes( {
										fieldWidth: Number( value ),
									} )
								}
								__nextHasNoMarginBottom
							/>
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
							<UAGAdvancedPanelBody
								title={ __( 'Address Line 1', 'sureforms' ) }
								initialOpen={ false }
							>
								{ 'classic' ===
								sureforms_keys?._srfm_form_styling ? null : (
										<UAGTextControl
											data={ {
												value: lineOneLabel,
												label: 'lineOneLabel',
											} }
											label={ __( 'Label', 'sureforms' ) }
											value={ lineOneLabel }
											onChange={ ( value ) =>
												setAttributes( {
													lineOneLabel: value,
												} )
											}
										/>
									) }
								<UAGTextControl
									data={ {
										value: lineOnePlaceholder,
										label: 'lineOnePlaceholder',
									} }
									label={ __( 'Placeholder', 'sureforms' ) }
									value={ lineOnePlaceholder }
									onChange={ ( value ) =>
										setAttributes( {
											lineOnePlaceholder: value,
										} )
									}
								/>
							</UAGAdvancedPanelBody>
							<UAGAdvancedPanelBody
								title={ __( 'Address Line 2', 'sureforms' ) }
								initialOpen={ false }
							>
								{ 'classic' ===
								sureforms_keys?._srfm_form_styling ? null : (
										<UAGTextControl
											data={ {
												value: lineTwoLabel,
												label: 'lineTwoLabel',
											} }
											label={ __( 'Label', 'sureforms' ) }
											value={ lineTwoLabel }
											onChange={ ( value ) =>
												setAttributes( {
													lineTwoLabel: value,
												} )
											}
										/>
									) }
								<UAGTextControl
									data={ {
										value: lineTwoPlaceholder,
										label: 'lineTwoPlaceholder',
									} }
									label={ __( 'Placeholder', 'sureforms' ) }
									value={ lineTwoPlaceholder }
									onChange={ ( value ) =>
										setAttributes( {
											lineTwoPlaceholder: value,
										} )
									}
								/>
							</UAGAdvancedPanelBody>
							<UAGAdvancedPanelBody
								title={ __( 'City', 'sureforms' ) }
								initialOpen={ false }
							>
								{ 'classic' ===
								sureforms_keys?._srfm_form_styling ? null : (
										<UAGTextControl
											data={ {
												value: cityLabel,
												label: 'cityLabel',
											} }
											label={ __( 'Label', 'sureforms' ) }
											value={ cityLabel }
											onChange={ ( value ) =>
												setAttributes( {
													cityLabel: value,
												} )
											}
										/>
									) }
								<UAGTextControl
									data={ {
										value: cityPlaceholder,
										label: 'cityPlaceholder',
									} }
									label={ __( 'Placeholder', 'sureforms' ) }
									value={ cityPlaceholder }
									onChange={ ( value ) =>
										setAttributes( {
											cityPlaceholder: value,
										} )
									}
								/>
							</UAGAdvancedPanelBody>
							<UAGAdvancedPanelBody
								title={ __( 'State', 'sureforms' ) }
								initialOpen={ false }
							>
								{ 'classic' ===
								sureforms_keys?._srfm_form_styling ? null : (
										<UAGTextControl
											data={ {
												value: stateLabel,
												label: 'stateLabel',
											} }
											label={ __( 'Label', 'sureforms' ) }
											value={ stateLabel }
											onChange={ ( value ) =>
												setAttributes( {
													stateLabel: value,
												} )
											}
										/>
									) }
								<UAGTextControl
									data={ {
										value: statePlaceholder,
										label: 'statePlaceholder',
									} }
									label={ __( 'Placeholder', 'sureforms' ) }
									value={ statePlaceholder }
									onChange={ ( value ) =>
										setAttributes( {
											statePlaceholder: value,
										} )
									}
								/>
							</UAGAdvancedPanelBody>
							<UAGAdvancedPanelBody
								title={ __( 'Postal Code', 'sureforms' ) }
								initialOpen={ false }
							>
								{ 'classic' ===
								sureforms_keys?._srfm_form_styling ? null : (
										<UAGTextControl
											data={ {
												value: postalLabel,
												label: 'postalLabel',
											} }
											label={ __( 'Label', 'sureforms' ) }
											value={ postalLabel }
											onChange={ ( value ) =>
												setAttributes( {
													postalLabel: value,
												} )
											}
										/>
									) }
								<UAGTextControl
									data={ {
										value: postalPlaceholder,
										label: 'postalPlaceholder',
									} }
									label={ __( 'Placeholder', 'sureforms' ) }
									value={ postalPlaceholder }
									onChange={ ( value ) =>
										setAttributes( {
											postalPlaceholder: value,
										} )
									}
								/>
							</UAGAdvancedPanelBody>
							<UAGAdvancedPanelBody
								title={ __( 'Country', 'sureforms' ) }
								initialOpen={ false }
							>
								{ 'classic' ===
								sureforms_keys?._srfm_form_styling ? null : (
										<UAGTextControl
											data={ {
												value: countryLabel,
												label: 'countryLabel',
											} }
											label={ __( 'Label', 'sureforms' ) }
											value={ countryLabel }
											onChange={ ( value ) =>
												setAttributes( {
													countryLabel: value,
												} )
											}
										/>
									) }
								<UAGTextControl
									data={ {
										value: countryPlaceholder,
										label: 'countryPlaceholder',
									} }
									label={ __( 'Placeholder', 'sureforms' ) }
									value={ countryPlaceholder }
									onChange={ ( value ) =>
										setAttributes( {
											countryPlaceholder: value,
										} )
									}
								/>
							</UAGAdvancedPanelBody>
							<span className="uag-control-label uagb-control__header" />
							<UAGTextControl
								label={ __( 'Help', 'sureforms' ) }
								value={ help }
								data={ {
									value: help,
									label: 'help',
								} }
								onChange={ ( value ) =>
									setAttributes( { help: value } )
								}
							/>
						</UAGAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...UAGTabs.style }></InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<div
				className={
					'srfm-main-container srfm-classic-inputs-holder ' +
					( isSelected ? ' sf--focus' : '' )
				}
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
				id={ 'srfm-address-fieldwidth' + block_id }
			>
				{ 'classic' === sureforms_keys?._srfm_form_styling ? (
					<AddressClassicStyle
						countries={ countries }
						attributes={ attributes }
						blockID={ block_id }
						setAttributes={ setAttributes }
					/>
				) : (
					<AddressThemeStyle
						countries={ countries }
						attributes={ attributes }
						blockID={ block_id }
						setAttributes={ setAttributes }
					/>
				) }
				{ help !== '' && (
					<RichText
						tagName="label"
						value={ help }
						onChange={ ( value ) =>
							setAttributes( { help: value } )
						}
						className={
							'classic' ===
							sureforms_keys?._sureforms_form_styling
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
