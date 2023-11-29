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
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMEditorHeader from './SRFMEditorHeader.js';

const { select, dispatch } = wp.data;

const default_keys = {
	_srfm_color1: '',
	_srfm_textcolor1: '',
	_srfm_color2: '',
	_srfm_fontsize: 16,
	_srfm_bg: '',
	_srfm_thankyou_message: 'Form submitted successfully!',
	_srfm_email: sfBlockData.admin_email,
	_srfm_submit_type: 'message',
	_srfm_submit_url: '',
	_srfm_sender_notification: 'off',
	_srfm_form_recaptcha: 'none',
	_srfm_submit_alignment: 'left',
	_srfm_submit_width: '',
	_srfm_submit_styling_inherit_from_theme: false,
	_srfm_form_styling: 'classic',
	_srfm_form_container_width: 650,
	_srfm_thankyou_message_title: 'Thank you',
	_srfm_submit_button_text: 'Submit',
	_srfm_additional_classes: '',
	_srfm_page_form_title: false,
	_srfm_single_page_form_title: false,
};

const SureformsFormSpecificSettings = ( props ) => {
	const [ hasCopied, setHasCopied ] = useState( false );
	const postId = wp.data.select( 'core/editor' ).getCurrentPostId();

	const rootContainer = document.querySelector( '.is-root-container' );
	const customDiv = document.createElement( 'div' );
	customDiv.className = 'your-custom-div';

	const { deviceType } = useSelect( () => {
		return {
			deviceType:
				select(
					'core/edit-post'
				)?.__experimentalGetPreviewDeviceType() || 'Desktop',
		};
	}, [] );

	// Find the main Editor Container
	const rootContainerDiv = document.querySelector(
		'.edit-post-visual-editor__content-area'
	);

	// Add styling class to main Editor Container
	const addFormStylingClass = () => {
		if ( rootContainer && 'Desktop' === deviceType ) {
			rootContainer?.classList.add( 'srfm-form-style-classic' );
		} else if ( rootContainerDiv ) {
			rootContainerDiv?.classList.add( 'srfm-form-style-classic' );
		}
	};
	useEffect( addFormStylingClass, [ rootContainer, customDiv, deviceType ] );

	// Render the Components in the center of the Header
	const headerCenterContainer = document.querySelector(
		'.edit-post-header__center'
	);
	if ( headerCenterContainer ) {
		const root = createRoot( headerCenterContainer );
		root.render( <SRFMEditorHeader /> );
	}

	const sureforms_keys = useSelect( () =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);

	useEffect( () => {
		setTimeout( () => {
			const handleIframeStyle = ( iframeBody ) => {
				if ( iframeBody ) {
					const styleProperties = [
						{
							property: '--srfm_bg',
							value: sureforms_keys._srfm_bg
								? `url(${ sureforms_keys._srfm_bg })`
								: 'none',
						},
						{
							property: '--srfm-primary-color',
							value: sureforms_keys._srfm_color1 || '#0284C7',
						},
						{
							property: '--srfm-primary-text-color',
							value: sureforms_keys._srfm_textcolor1 || '#fff',
						},
						{
							property: '--srfm-secondary-color',
							value: sureforms_keys._srfm_color2 || 'none',
						},
						{
							property: '--srfm_fontsize',
							value: sureforms_keys._srfm_fontsize
								? `${ sureforms_keys._srfm_fontsize }px`
								: 'none',
						},
						{
							property: '--srfm_submit_alignment',
							value: sureforms_keys._srfm_submit_alignment
								? `${ sureforms_keys._srfm_submit_alignment }`
								: 'none',
						},
						{
							property: '--srfm_submit_width',
							value: sureforms_keys._srfm_submit_width
								? `${ sureforms_keys._srfm_submit_width }`
								: '',
						},
					];
					styleProperties.forEach( ( prop ) => {
						iframeBody.style.setProperty(
							prop.property,
							prop.value
						);
					} );

					// Add the styling class when the device type is changed
					const iframeRootContainer =
						iframeBody?.querySelector( '.is-root-container' );
					iframeRootContainer?.classList.add(
						'srfm-form-style-classic'
					);
					const customDivSelector =
						document.querySelector( '.your-custom-div' );
					if ( ! customDivSelector ) {
						customDiv.innerHTML = 'submit';
						iframeBody.insertAdjacentElement(
							'afterend',
							customDiv
						);
					}
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
	}, [ deviceType, sureforms_keys, customDiv ] );

	return (
		<PluginDocumentSettingPanel
			className="srfm--panel"
			name="srfm-sidebar"
			title={ __( 'Form Options', 'sureforms' ) }
		>
			<InspectorTabs
				tabs={ [ 'general', 'advance' ] }
				defaultTab={ 'general' }
			>
				<InspectorTab { ...SRFMTabs.general }>
					<AppearanceSettings default_keys={ default_keys } />
				</InspectorTab>
				<InspectorTab { ...SRFMTabs.advance } parentProps={ props }>
					<Settings default_keys={ default_keys } />
				</InspectorTab>
			</InspectorTabs>
			<PluginPostPublishPanel>
				<PanelRow>
					<BaseControl
						id="srfm-form-shortcode"
						label={ __( 'Form Shortcode', 'sureforms' ) }
						help={ __(
							'Paste this shortcode on the page or post to render this form.',
							'sureforms'
						) }
					>
						<div className="srfm-shortcode">
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

registerPlugin( 'srfm-form-specific-settings', {
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
			'srfm-form-specific-settings/srfm-sidebar'
		)
	) {
		dispatch( 'core/edit-post' ).toggleEditorPanelEnabled(
			'srfm-form-specific-settings/srfm-sidebar'
		);
	}
	if (
		! select( 'core/edit-post' ).isEditorPanelOpened(
			'srfm-form-specific-settings/srfm-sidebar'
		)
	) {
		dispatch( 'core/edit-post' ).toggleEditorPanelOpened(
			'srfm-form-specific-settings/srfm-sidebar'
		);
	}
};

wp.domReady( () => {
	forcePanel();
} );
