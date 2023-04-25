import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const onSelect = ( tabName ) => {
	console.log( 'Selecting tab', tabName );
};

const PluginDocumentSettingPanelDemo = () => (
	<PluginDocumentSettingPanel
		initialOpen
		title={ __( 'Form Options Panel', 'sureforms' ) }
		name="sureforms-forms-panel"
		className="sureforms--panel"
	>
		<TabPanel
			activeClass="active-tab"
			onSelect={ onSelect }
			tabs={ [
				{
					name: 'tab1',
					title: 'Tab 1',
					className: 'tab-one',
				},
				{
					name: 'tab2',
					title: 'Tab 2',
					className: 'tab-two',
				},
			] }
		>
			{ ( tab ) => <p>{ tab.title }</p> }
		</TabPanel>
	</PluginDocumentSettingPanel>
);

registerPlugin( 'plugin-document-setting-panel-demo', {
	render: PluginDocumentSettingPanelDemo,
	icon: 'palmtree',
} );
