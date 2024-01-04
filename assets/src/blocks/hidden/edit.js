/**
 * WordPress dependencies
 */
import { InspectorControls, RichText } from '@wordpress/block-editor';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMAdvancedPanelBody from '@Components/advanced-panel-body';
import SRFMTextControl from '@Components/text-control';
import { __ } from '@wordpress/i18n';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import { FieldsPreview } from '../FieldsPreview.jsx';

const Edit = ( { attributes, setAttributes } ) => {
	const { preview, block_id, defaultValue, label } = attributes;

	// show the block preview on hover.
	if ( preview ) {
		const fieldName = fieldsPreview.input_preview;
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
							<SRFMTextControl
								label={ __( 'Default Value', 'sureforms' ) }
								className="srfm-with-dropdown"
								value={ defaultValue }
								withSmartTagDropdown={ true }
								data={ {
									value: defaultValue,
									label: 'defaultValue',
								} }
								onChange={ ( value ) =>
									setAttributes( { defaultValue: value } )
								}
							/>
						</SRFMAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...SRFMTabs.style }></InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<div
				className={ `srfm-main-container srfm-classic-inputs-holder srfm-block-${ block_id }` }
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				<RichText
					tagName="label"
					value={ label }
					onChange={ ( value ) => setAttributes( { label: value } ) }
					className={ 'srfm-classic-label-text' }
					multiline={ false }
					id={ block_id }
				/>
				<input
					type="text"
					value={ defaultValue }
					readOnly
					className="srfm-input-common"
				/>
			</div>
		</>
	);
};

export default compose( AddInitialAttr )( Edit );
