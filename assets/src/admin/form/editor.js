import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import AppearanceSettings from './appearanceSettings';
import Settings from './Settings';

const onSelect = () => {};

const PluginDocumentSettingPanelDemo = () => (
	<PluginDocumentSettingPanel
		className="sureforms--panel"
		name="sureforms-sidebar"
		title={ __( 'Form Options', 'sureforms' ) }
		icon={ '' }
	>
		<TabPanel
			activeClass="active"
			onSelect={ onSelect }
			tabs={ [
				{
					name: 'sureforms-appearance',
					title: 'Appearance',
					className: 'components-panel__body-toggle',
				},
				{
					name: 'sureforms-settings',
					title: 'Settings',
					className:
						'components-panel__body-toggle sureforms-toggle-settings',
				},
			] }
		>
			{ ( tab ) => {
				switch ( tab.title ) {
					case 'Appearance':
						return <AppearanceSettings />;
					case 'Settings':
						return <Settings />;
					default:
						return <AppearanceSettings />;
				}
			} }
		</TabPanel>
	</PluginDocumentSettingPanel>
);

registerPlugin( 'plugin-document-setting-panel-demo', {
	render: PluginDocumentSettingPanelDemo,
	icon: 'palmtree',
} );
