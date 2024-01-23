/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { InspectorControls, RichText } from '@wordpress/block-editor';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import { PanelRow } from '@wordpress/components';
import { FieldsPreview } from '../FieldsPreview.jsx';

const Edit = ( { attributes, setAttributes } ) => {
	const { label, preview, block_id } = attributes;
	if ( preview ) {
		const fieldName = fieldsPreview.pagebreak_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

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
			<div className="srfm-page-break-wrapper">
				<div className="srfm-page-break-divider"></div>
				<div className="srfm-page-break-content">
					<RichText
						tagName="p"
						value={ label }
						onChange={ ( value ) =>
							setAttributes( { label: value } )
						}
						multiline={ false }
						id={ block_id }
					/>
				</div>
				<div className="srfm-page-break-divider"></div>
			</div>
		</>
	);
};

export default compose( AddInitialAttr )( Edit );
