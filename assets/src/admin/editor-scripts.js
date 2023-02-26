import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { TabPanel } from '@wordpress/components';

const onSelect = ( tabName ) => {
    console.log( 'Selecting tab', tabName );
};

const PluginDocumentSettingPanelDemo = () => (
    <>
    <TabPanel
        className="my-tab-panel"
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
    <PluginDocumentSettingPanel
        name="custom-panel"
        title="Custom Panel"
        className="custom-panel"
    >
        Custom Panel Contents
    </PluginDocumentSettingPanel>
    </>
);

registerPlugin( 'plugin-document-setting-panel-demo', {
    render: PluginDocumentSettingPanelDemo,
    icon: 'palmtree',
} );