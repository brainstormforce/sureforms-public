import { registerPlugin } from '@wordpress/plugins';
import {
	PluginDocumentSettingPanel,
	PluginPostPublishPanel,
} from '@wordpress/edit-post';
import {
	TabPanel,
	ClipboardButton,
	PanelRow,
	BaseControl,
	TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

import AppearanceSettings from './AppearanceSettings.js';
import Settings from './Settings.js';

const onSelect = () => {};

const default_keys = {
	_sureforms_color1: '#000000',
	_sureforms_color2: '#dddddd',
	_sureforms_fontsize: 20,
	_sureforms_bg: '',
	_sureforms_thankyou_message: 'Form submitted successfully!',
	_sureforms_email: sfBlockData.admin_email,
	_sureforms_submit_type: 'message',
	_sureforms_submit_url: '',
};

const PluginDocumentSettingPanelDemo = () => {
	const [ hasCopied, setHasCopied ] = useState( false );
	const postId = wp.data.select( 'core/editor' ).getCurrentPostId();

	return (
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
							return (
								<AppearanceSettings
									default_keys={ default_keys }
								/>
							);
						case 'Settings':
							return <Settings default_keys={ default_keys } />;
						default:
							return <AppearanceSettings />;
					}
				} }
			</TabPanel>
			<PluginPostPublishPanel>
				<PanelRow>
					<BaseControl
						id="sureforms-form-shortcode"
						label={ __( 'Form Shortcode', 'sureforms' ) }
						help={ __(
							'Paste this shortcode on the page or post to render this form.',
							'sureforms'
						) }
					>
						<div className="sureforms-shortcode">
							<TextControl
								value={ `[sureforms id="${ postId }"]` }
								disabled
							/>
							<ClipboardButton
								onCopy={ () => setHasCopied( true ) }
								onFinishCopy={ () => setHasCopied( false ) }
								icon={ hasCopied ? 'yes' : 'admin-page' }
								text={ `[sureforms id="${ postId }"]` }
							/>
						</div>
					</BaseControl>
				</PanelRow>
			</PluginPostPublishPanel>
		</PluginDocumentSettingPanel>
	);
};

registerPlugin( 'plugin-document-setting-panel-demo', {
	render: PluginDocumentSettingPanelDemo,
	icon: 'palmtree',
} );
