/**
 * Block Icon : Settings.
 */
import { memo } from '@wordpress/element';
import { InspectorControls } from '@wordpress/block-editor';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import GeneralSettings from './components/settings/GeneralSettings';
import StyleSettings from './components/settings/StyleSettings';

const Settings = ( props ) => {
	return (
		<InspectorControls>
			<InspectorTabs>
				<InspectorTab { ...UAGTabs.general } parentProps={ props }>
					<GeneralSettings { ...props } />
				</InspectorTab>
				<InspectorTab { ...UAGTabs.style } parentProps={ props }>
					<StyleSettings { ...props } />
				</InspectorTab>
			</InspectorTabs>
		</InspectorControls>
	);
};
export default memo( Settings );
