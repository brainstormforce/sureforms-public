/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls } from '@wordpress/block-editor';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import {
	PanelRow
} from '@wordpress/components';

const Edit = ( { attributes, setAttributes } ) => {
	const { label, preview } = attributes;

	// show the block preview on hover.
	// if ( preview ) {
	// 	const fieldName = fieldsPreview.input_preview;
	// 	return <FieldsPreview fieldName={ fieldName } />;
	// }

	return (
		<>
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
							<PanelRow>
								<p className="srfm-settings-notice">
									{ __(
										'For the more options please select the Parent Form Block.',
										'sureforms'
									) }
								</p>
							</PanelRow>
						</SRFMAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...SRFMTabs.style }></InspectorTab>
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
				<div className="srfm-page-break-wrapper">
					<div className="srfm-page-break-divider"></div>
					<div className="srfm-page-break-content">
						<p>{ label }</p>
					</div>
					<div className="srfm-page-break-divider"></div>
				</div>
			</div>
		</>
	);
};

export default compose( AddInitialAttr )( Edit );
