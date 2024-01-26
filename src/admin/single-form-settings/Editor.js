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
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';

import AppearanceSettings from './AppearanceSettings.js';
import Settings from './Settings.js';

import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMEditorHeader from './SRFMEditorHeader.js';
import { attachSidebar, toggleSidebar } from '../../../modules/quick-action-sidebar/index.js';

const { select, dispatch } = wp.data;

const default_keys = {
	_srfm_color1: '',
	_srfm_textcolor1: '',
	_srfm_color2: '',
	_srfm_fontsize: 20,
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
	_srfm_submit_button_text: 'SUBMIT',
	_srfm_additional_classes: '',
	_srfm_page_form_title: false,
	_srfm_single_page_form_title: false,
	_srfm_submit_alignment_backend: '100%',
	_srfm_submit_width_backend: 'max-content',
	

};

const SureformsFormSpecificSettings = ( props ) => {
	const [ hasCopied, setHasCopied ] = useState( false );
	const [ enableQuickActionSidebar, setEnableQuickActionSidebar ] = useState();
	const postId = wp.data.select( 'core/editor' ).getCurrentPostId();
	const { editPost } = useDispatch( editorStore );

	const rootContainer = document.querySelector( '.is-root-container' );
	const blocks = wp.data.select( 'core/block-editor' ).getBlocks();
	const isPageBreak = blocks.some( ( block ) => block.name === 'sureforms/page-break' );

	const { deviceType } = useSelect( () => {
		return {
			deviceType:
				select(
					'core/edit-post'
				)?.__experimentalGetPreviewDeviceType() || 'Desktop',
		};
	}, [] );
	function updateMeta( option, value ) {
		const option_array = {};
		option_array[ option ] = value;
		editPost( {
			meta: option_array,
		} );
	}

	// Find the main Editor Container
	const rootContainerDiv = document.querySelector(
		'.edit-post-visual-editor__content-area'
	);

	// Add styling class to main Editor Container
	const addFormStylingClass = () => {
		if ( rootContainer && 'Desktop' === deviceType ) {
			rootContainer?.classList.add( 'srfm-form-container' );
			rootContainer.setAttribute( 'id', 'srfm-form-container' );
		} else if ( rootContainerDiv ) {
			rootContainerDiv?.classList.add( 'srfm-form-container' );
			rootContainerDiv.setAttribute( 'id', 'srfm-form-container' );
		}
	};
	useEffect( addFormStylingClass, [ rootContainer, deviceType ] );
	useEffect( () => {
		updateMeta( '_srfm_is_page_break', isPageBreak );
	}, [ isPageBreak ] );

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

	function addSubmitButton( elm ) {
		const appendHtml = `<div class="srfm-submit-btn-container wp-block-button"><button class="srfm-button srfm-submit-button wp-block-button__link"></button></div>`;

		if ( elm ) {
			if ( ! elm.closest( 'body' ).querySelector( '.srfm-submit-btn-container' ) ) {
				elm.insertAdjacentHTML( 'afterend', appendHtml );
			}
		}
	}

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
							property: '--srfm-font-size',
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
						{
							property: '--srfm_submit_button_text',
							value: sureforms_keys._srfm_submit_button_text
								? `"${ sureforms_keys._srfm_submit_button_text }"`
								: '',
						},
						{
							property: '--srfm_submit_alignment',
							value: sureforms_keys._srfm_submit_alignment
								? `${ sureforms_keys._srfm_submit_alignment }`
								: '',
						},
						{
							property: '--srfm_submit_alignment_backend',
							value: sureforms_keys._srfm_submit_alignment_backend
								? `${ sureforms_keys._srfm_submit_alignment_backend }`
								: '',
						},
						{
							property: '--srfm_submit_width_backend',
							value: sureforms_keys._srfm_submit_width_backend
								? `${ sureforms_keys._srfm_submit_width_backend }`
								: '',
						},
					];

					styleProperties.forEach( ( prop ) => {
						iframeBody.style.setProperty(
							prop.property,
							prop.value
						);
					} );
					const elm = iframeBody.querySelector(
						'.block-editor-block-list__layout'
					);

					addSubmitButton( elm );

					// Add the styling class when the device type is changed
					const iframeRootContainer =
						iframeBody?.querySelector( '.is-root-container' );
					iframeRootContainer?.classList.add( 'srfm-form-container' );
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
			} else {
				const elm = document.querySelector(
					'.block-editor-block-list__layout'
				);
				addSubmitButton( elm );
			}
		}, 100 );
	}, [ deviceType, sureforms_keys ] );

	useEffect( () => {
		//quick action sidebar
		// If not FSE editor, attach the sidebar to the DOM.
		const currentUrl = new URL( window.location.href );
		if ( '/wp-admin/site-editor.php' === currentUrl.pathname ) {
			toggleSidebar( window.location.href );

			// For FSE we are adding eventlistener to remove the sidebar when the user canvas is not editable.
			window.navigation.addEventListener( 'navigate', ( e ) => {
				toggleSidebar( e.destination.url );
			} );
		} else if ( enableQuickActionSidebar !== undefined && 'enabled' === enableQuickActionSidebar ) {
			// Attach the sidebar to the DOM.
			attachSidebar();
		} else {
			const container = document.querySelector( '.srfm-ee-quick-access' );
			if ( container ) {
				container.innerHTML = null;
			}
		}
	}, [ enableQuickActionSidebar ] );

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
					<AppearanceSettings default_keys={ default_keys } enableQuickActionSidebar={ enableQuickActionSidebar } setEnableQuickActionSidebar={ setEnableQuickActionSidebar } />
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
