import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/edit-post';
import { TabPanel } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import '@surecart/components/src/themes/base.css';
import '@Admin/editor.scss';

const onSelect = ( tabName ) => {
    console.log( 'Selecting tab', tabName );
};

const PluginDocumentSettingPanelDemo = () => (
    <PluginDocumentSettingPanel
        initialOpen
        title='Form Options Panel'
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
        Custom Panel Contents
    </PluginDocumentSettingPanel>
);

registerPlugin( 'plugin-document-setting-panel-demo', {
    render: PluginDocumentSettingPanelDemo,
    icon: 'palmtree',
} );