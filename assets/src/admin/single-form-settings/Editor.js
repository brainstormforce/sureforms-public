import { registerPlugin } from '@wordpress/plugins';
import {
	PluginDocumentSettingPanel,
	PluginPostPublishPanel,
} from '@wordpress/edit-post';
import {
	ClipboardButton,
	PanelRow,
	BaseControl,
	TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect, createRoot } from '@wordpress/element';
import { useSelect } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

import AppearanceSettings from './AppearanceSettings.js';
import Settings from './Settings.js';

import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import HeaderTitle from './HeaderTitle.js';

const { select, dispatch } = wp.data;

const default_keys = {
	_sureforms_color1: '',
	_sureforms_textcolor1: '',
	_sureforms_color2: '',
	_sureforms_fontsize: 16,
	_sureforms_bg: '',
	_sureforms_thankyou_message: 'Form submitted successfully!',
	_sureforms_email: sfBlockData.admin_email,
	_sureforms_submit_type: 'message',
	_sureforms_submit_url: '',
	_sureforms_sender_notification: 'off',
	_sureforms_form_recaptcha: 'none',
	_sureforms_submit_alignment: 'left',
	_sureforms_submit_width: '',
	_sureforms_submit_styling_inherit_from_theme: false,
	_sureforms_form_styling: 'classic',
	_sureforms_form_container_width: 650,
};

const SureformsFormSpecificSettings = ( props ) => {
	const [ hasCopied, setHasCopied ] = useState( false );
	const postId = wp.data.select( 'core/editor' ).getCurrentPostId();

	const headerCenter = document.querySelector( '.edit-post-header__center' );

	if ( headerCenter ) {
		const root = createRoot( headerCenter );
		root.render( <HeaderTitle /> );
	}

	const { deviceType } = useSelect( () => {
		return {
			deviceType:
				select(
					'core/edit-post'
				)?.__experimentalGetPreviewDeviceType() || 'Desktop',
		};
	}, [] );

	const sureforms_keys = useSelect( () =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);

	useEffect( () => {
		setTimeout( () => {
			const handleIframeStyle = ( iframeBody ) => {
				if ( iframeBody ) {
					const styleProperties = [
						{
							property: '--sureforms_bg',
							value: sureforms_keys._sureforms_bg
								? `url(${ sureforms_keys._sureforms_bg })`
								: 'none',
						},
						{
							property: '--sf-primary-color',
							value:
								sureforms_keys._sureforms_color1 || '#0284C7',
						},
						{
							property: '--sf-primary-text-color',
							value:
								sureforms_keys._sureforms_textcolor1 || '#fff',
						},
						{
							property: '--sf-secondary-color',
							value: sureforms_keys._sureforms_color2 || 'none',
						},
						{
							property: '--sureforms_fontsize',
							value: sureforms_keys._sureforms_fontsize
								? `${ sureforms_keys._sureforms_fontsize }px`
								: 'none',
						},
						{
							property: '--sureforms_submit_alignment',
							value: sureforms_keys._sureforms_submit_alignment
								? `${ sureforms_keys._sureforms_submit_alignment }`
								: 'none',
						},
						{
							property: '--sureforms_submit_width',
							value: sureforms_keys._sureforms_submit_width
								? `${ sureforms_keys._sureforms_submit_width }`
								: '',
						},
					];
					styleProperties.forEach( ( prop ) => {
						iframeBody.style.setProperty(
							prop.property,
							prop.value
						);
					} );
				}
			};

			const tabletPreview =
				document.getElementsByClassName( 'is-tablet-preview' );
			const mobilePreview =
				document.getElementsByClassName( 'is-mobile-preview' );
			if ( tabletPreview.length !== 0 || mobilePreview.length !== 0 ) {
				const preview = tabletPreview[ 0 ] || mobilePreview[ 0 ];
				if ( preview ) {
					const iframe = preview.querySelector( 'iframe' );
					const iframeDocument =
						iframe?.contentWindow.document ||
						iframe?.contentDocument;
					const iframeBody = iframeDocument
						?.querySelector( 'html' )
						?.querySelector( 'body' );

					handleIframeStyle( iframeBody );
				}
			}
		}, 100 );
	}, [ deviceType, sureforms_keys ] );

	return (
		<PluginDocumentSettingPanel
			className="sureforms--panel"
			name="sureforms-sidebar"
			title={ __( 'Form Options', 'sureforms' ) }
		>
			<InspectorTabs
				tabs={ [ 'general', 'advance' ] }
				defaultTab={ 'general' }
			>
				<InspectorTab { ...UAGTabs.general }>
					<AppearanceSettings default_keys={ default_keys } />
				</InspectorTab>
				<InspectorTab { ...UAGTabs.advance } parentProps={ props }>
					<Settings default_keys={ default_keys } />
				</InspectorTab>
			</InspectorTabs>
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

registerPlugin( 'sureforms-form-specific-settings', {
	render: SureformsFormSpecificSettings,
	icon: 'palmtree',
} );

//force panel open
const forcePanel = () => {
	//force sidebar open
	if ( ! select( 'core/edit-post' ).isEditorSidebarOpened() ) {
		dispatch( 'core/edit-post' ).openGeneralSidebar( 'edit-post/document' );
	}
	//force panel open
	if (
		! select( 'core/edit-post' ).isEditorPanelEnabled(
			'sureforms-form-specific-settings/sureforms-sidebar'
		)
	) {
		dispatch( 'core/edit-post' ).toggleEditorPanelEnabled(
			'sureforms-form-specific-settings/sureforms-sidebar'
		);
	}
	if (
		! select( 'core/edit-post' ).isEditorPanelOpened(
			'sureforms-form-specific-settings/sureforms-sidebar'
		)
	) {
		dispatch( 'core/edit-post' ).toggleEditorPanelOpened(
			'sureforms-form-specific-settings/sureforms-sidebar'
		);
	}
};

wp.domReady( () => {
	forcePanel();
} );
