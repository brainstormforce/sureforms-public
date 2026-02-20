/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	InspectorControls,
	RichText,
	InnerBlocks,
} from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import { ToggleControl } from '@wordpress/components';
import SRFMTextControl from '@Components/text-control';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import { FieldsPreview } from '../FieldsPreview.jsx';
import { decodeHtmlEntities } from '@Blocks/util';

import countries from './countries.json';
import ConditionalLogic from '@Components/conditional-logic';
import HelpText from '@Components/misc/HelpText';

const Edit = ( { clientId, attributes, setAttributes } ) => {
	const {
		label,
		block_id,
		formId,
		preview,
		help,
		className,
		enableAutocomplete,
		showMap,
	} = attributes;

	const currentFormId = useGetCurrentFormId( clientId );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = srfm_fields_preview.address_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

	const slug = 'address';
	const blockID = `srfm-${ slug }-${ block_id }`;

	const hasApiKey = !! srfm_block_data?.google_maps_api_key;

	const addressTemplate = [
		[
			'srfm/input',
			{
				label: 'Address Line 1',
				fieldWidth: 50,
			},
		],
		[
			'srfm/input',
			{
				label: 'Address Line 2',
				fieldWidth: 50,
			},
		],
		[
			'srfm/input',
			{
				label: 'City',
				fieldWidth: 50,
			},
		],
		[
			'srfm/input',
			{
				label: 'State',
				fieldWidth: 50,
			},
		],
		[
			'srfm/input',
			{
				label: 'Postal Code',
				fieldWidth: 50,
			},
		],
		[
			'srfm/dropdown',
			{
				label: 'Country',
				options: [
					...countries.map( ( country ) => {
						return { label: country.name, icon: '' };
					} ),
				],
				fieldWidth: 50,
			},
		],
	];

	const allowedBlocks = [ 'srfm/input', 'srfm/dropdown' ];

	return (
		<div className={ className }>
			<InspectorControls>
				<InspectorTabs
					tabs={ [ 'general', 'advance' ] }
					defaultTab={ 'general' }
				>
					<InspectorTab { ...SRFMTabs.general }>
						<SRFMAdvancedPanelBody
							title={ __( 'Attributes', 'sureforms' ) }
							initialOpen={ true }
						>
							<SRFMTextControl
								label={ __( 'Help Text', 'sureforms' ) }
								value={ help }
								data={ {
									value: help,
									label: 'help',
								} }
								onChange={ ( value ) =>
									setAttributes( { help: value } )
								}
							/>
						</SRFMAdvancedPanelBody>
						<SRFMAdvancedPanelBody
							title={ __(
								'Location Services',
								'sureforms'
							) }
							initialOpen={ false }
						>
							<ToggleControl
								label={ __(
									'Enable Google Autocomplete',
									'sureforms'
								) }
								checked={ enableAutocomplete }
								onChange={ ( value ) =>
									setAttributes( {
										enableAutocomplete: value,
									} )
								}
							/>
							{ enableAutocomplete && (
								<>
									{ ! hasApiKey && (
										<p
											className="components-notice is-warning"
											style={ {
												padding: '8px 12px',
												backgroundColor: '#fcf0e3',
												borderLeft:
													'4px solid #dba617',
												margin: '0 0 16px',
												fontSize: '13px',
											} }
										>
											{ __(
												'Google Maps API key is not configured. Go to Settings > Google Maps to add your API key.',
												'sureforms'
											) }
										</p>
									) }
									<ToggleControl
										label={ __(
											'Show Interactive Map',
											'sureforms'
										) }
										checked={ showMap }
										onChange={ ( value ) =>
											setAttributes( {
												showMap: value,
											} )
										}
									/>
								</>
							) }
						</SRFMAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...SRFMTabs.advance }>
						<ConditionalLogic
							{ ...{ setAttributes, attributes } }
						/>
					</InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => {
					setAttributes( {
						label: decodeHtmlEntities( value ),
					} );
				} }
				className={ `srfm-block-label` }
				multiline={ false }
				id={ blockID }
				allowedFormats={ [] }
			/>
			<HelpText
				help={ help }
				setAttributes={ setAttributes }
				block_id={ blockID }
			/>
			<InnerBlocks
				template={ addressTemplate }
				allowedBlocks={ allowedBlocks }
			/>
		</div>
	);
};

export default compose( AddInitialAttr )( Edit );
