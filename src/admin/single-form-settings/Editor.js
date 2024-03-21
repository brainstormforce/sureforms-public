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
import { useState, useEffect, createRoot, render } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { store as blockEditorStore } from '@wordpress/block-editor';

import GeneralSettings from './tabs/GeneralSettings.js';
import StyleSettings from './tabs/StyleSettings.js';
import AdvancedSettings from './tabs/AdvancedSettings.js';

import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import SRFMEditorHeader from './components/SRFMEditorHeader.js';
import {
	attachSidebar,
	toggleSidebar,
} from '../../../modules/quick-action-sidebar/index.js';
import { useDeviceType } from '@Controls/getPreviewType';

import ProPanel from './components/pro-panel/index.js';

const { select, dispatch } = wp.data;

const defaultKeys = {
	// General Tab
	_srfm_show_labels: true,
	_srfm_show_asterisk: true,
	_srfm_page_form_title: false,
	_srfm_single_page_form_title: false,
	_srfm_instant_form: false,
	// Submit Button
	_srfm_submit_button_text: 'SUBMIT',
	// Page Break
	_srfm_is_page_break: false,
	_srfm_first_page_label: 'Page break',
	_srfm_page_break_progress_indicator: 'connector',
	_srfm_page_break_toggle_label: false,
	_srfm_previous_button_text: 'Previous',
	_srfm_next_button_text: 'Next',
	// Style Tab
	// Form Container
	_srfm_form_container_width: 650,
	_srfm_color1: '#0e4372',
	_srfm_bg_type: 'image',
	_srfm_bg_image: '',
	_srfm_bg_color: '#ffffff',
	_srfm_fontsize: 20,
	_srfm_label_color: '#1f2937',
	_srfm_help_color: '#6b7280',
	// Input Fields
	_srfm_input_text_color: '#4B5563',
	_srfm_input_placeholder_color: '#9CA3AF',
	_srfm_input_bg_color: '#ffffff',
	_srfm_input_border_color: '#D0D5DD',
	_srfm_input_border_width: 1,
	_srfm_input_border_radius: 4,
	// Error
	_srfm_field_error_color: '#DC2626',
	_srfm_field_error_surface_color: '#EF4444',
	_srfm_field_error_bg_color: '#FEF2F2',
	// Submit Button
	_srfm_inherit_theme_button: false,
	_srfm_button_text_color: '#ffffff',
	_srfm_btn_bg_type: 'filled',
	_srfm_button_bg_color: '#0e4372',
	_srfm_button_border_color: '#ffffff',
	_srfm_button_border_width: 0,
	_srfm_button_border_radius: 6,
	_srfm_submit_alignment: 'left',
	_srfm_submit_width: '',
	_srfm_submit_alignment_backend: '100%',
	_srfm_submit_width_backend: 'max-content',
	_srfm_additional_classes: '',

	// Advanced Tab
	// Success Message
	_srfm_submit_type: 'message',
	_srfm_thankyou_message_title: 'Thank you',
	_srfm_thankyou_message: 'Form submitted successfully!',
	_srfm_submit_url: '',
	_srfm_form_recaptcha: 'none',
};

const SureformsFormSpecificSettings = ( props ) => {
	const [ hasCopied, setHasCopied ] = useState( false );
	const [ enableQuickActionSidebar, setEnableQuickActionSidebar ] =
		useState( 'enabled' );
	const postId = useSelect( () => {
		return select( 'core/editor' ).getCurrentPostId();
	}, [] );
	const sureformsKeys = useSelect( () =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);

	const blockCount = useSelect( () =>
		select( blockEditorStore ).getBlockCount()
	);
	const { editPost } = useDispatch( editorStore );
	const rootContainer = document.querySelector( '.is-root-container' );
	const blocks = wp.data.select( 'core/block-editor' ).getBlocks();
	const isPageBreak = blocks.some(
		( block ) => block.name === 'srfm/page-break'
	);
	const deviceType = useDeviceType();

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
		if ( sureformsKeys._srfm_is_page_break === undefined ) {
			return;
		}
		updateMeta( '_srfm_is_page_break', isPageBreak );
	}, [ blockCount ] );

	// Render the Components in the center of the Header
	const headerCenterContainer = document.querySelector(
		'.edit-post-header__center'
	);

	if ( headerCenterContainer ) {
		const root = createRoot( headerCenterContainer );
		root.render( <SRFMEditorHeader /> );
	}

	const submitBtnContainer = document.querySelector(
		'.srfm-submit-btn-container'
	);
	function addSubmitButton( elm ) {
		const inheritClass = 'wp-block-button__link';
		const customClass = 'srfm-btn-bg-color';
		const btnClass =
			sureformsKeys?._srfm_inherit_theme_button &&
			sureformsKeys._srfm_inherit_theme_button
				? inheritClass
				: customClass;
		const appendHtml = `<div class="srfm-submit-btn-container"><button class="srfm-button srfm-submit-button ${ btnClass }"></button></div>`;

		if ( elm ) {
			if (
				! elm
					.closest( 'body' )
					.querySelector( '.srfm-submit-btn-container' )
			) {
				elm.insertAdjacentHTML( 'afterend', appendHtml );
			}
		}
	}

	useEffect( () => {
		setTimeout( () => {
			const handleIframeStyle = ( iframeBody ) => {
				if ( iframeBody ) {
					const styleProperties = [
						// Form Container
						{
							property: '--srfm-primary-color',
							value: sureformsKeys._srfm_color1 || '#0e4372',
						},
						{
							property: '--srfm-bg-image',
							value: sureformsKeys._srfm_bg_image
								? `url(${ sureformsKeys._srfm_bg_image })`
								: '',
						},
						{
							property: '--srfm-bg-color',
							value: sureformsKeys._srfm_bg_color
								? sureformsKeys._srfm_bg_color
								: '',
						},
						{
							property: '--srfm-font-size',
							value: sureformsKeys._srfm_fontsize
								? `${ sureformsKeys._srfm_fontsize }px`
								: '20px',
						},
						{
							property: '--srfm-label-text-color',
							value: sureformsKeys._srfm_label_color || '#1f2937',
						},
						{
							property: '--srfm-help-color',
							value: sureformsKeys._srfm_help_color || '#6b7280',
						},
						// Input
						{
							property: '--srfm-body-input-color',
							value:
								sureformsKeys._srfm_input_text_color ||
								'#4B5563',
						},

						{
							property: '--srfm-placeholder-color',
							value:
								sureformsKeys._srfm_input_placeholder_color ||
								'#9CA3AF',
						},
						{
							property: '--srfm-base-background-color',
							value:
								sureformsKeys._srfm_input_bg_color || '#ffffff',
						},
						{
							property: '--srfm-border-color',
							value:
								sureformsKeys._srfm_input_border_color ||
								'#D0D5DD',
						},
						{
							property: '--srfm-border',
							value:
								sureformsKeys._srfm_input_border_width + 'px' ||
								'1px',
						},
						{
							property: '--srfm-border-radius',
							value:
								sureformsKeys._srfm_input_border_radius +
									'px' || '4px',
						},
						// Error
						{
							property: '--srfm-error-text-color',
							value: sureformsKeys._srfm_field_error_color
								? `${ sureformsKeys._srfm_field_error_color }`
								: '#DC2626',
						},
						{
							property: '--srfm-field-error-surface-color',
							value:
								sureformsKeys._srfm_field_error_surface_color ||
								'#EF4444',
						},
						{
							property: '--srfm-field-error-bg-color',
							value:
								sureformsKeys._srfm_field_error_bg_color ||
								'#FEF2F2',
						},
						// Submit Button
						{
							property: '--srfm-btn-text-color',
							value:
								sureformsKeys._srfm_button_text_color ||
								'#ffffff',
						},
						{
							property: '--srfm-btn-bg-color',
							value:
								sureformsKeys._srfm_button_bg_color ||
								'#0e4372',
						},
						{
							property: '--srfm-btn-border-color',
							value:
								sureformsKeys._srfm_button_border_color ||
								'#ffffff',
						},
						{
							property: '--srfm-btn-border-width',
							value:
								sureformsKeys._srfm_button_border_width +
									'px' || '0px',
						},
						{
							property: '--srfm-btn-border-radius',
							value:
								sureformsKeys._srfm_button_border_radius +
									'px' || '6px',
						},
						{
							property: '--srfm-submit-alignment-backend',
							value: sureformsKeys._srfm_submit_alignment_backend
								? `${ sureformsKeys._srfm_submit_alignment_backend }`
								: '100%',
						},
						{
							property: '--srfm-submit-width-backend',
							value: sureformsKeys._srfm_submit_width_backend
								? `${ sureformsKeys._srfm_submit_width_backend }`
								: 'max-content',
						},

						{
							property: '--srfm-submit-alignment',
							value: sureformsKeys._srfm_submit_alignment
								? `${ sureformsKeys._srfm_submit_alignment }`
								: 'left',
						},
						{
							property: '--srfm-submit-width',
							value: sureformsKeys._srfm_submit_width
								? `${ sureformsKeys._srfm_submit_width }`
								: '',
						},
						{
							property: '--srfm-submit-button-text',
							value: sureformsKeys._srfm_submit_button_text
								? `"${ sureformsKeys._srfm_submit_button_text }"`
								: 'SUBMIT',
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

					const submitBtnContainerIframe = iframeBody.querySelector(
						'.srfm-submit-btn-container'
					);

					if ( ! submitBtnContainerIframe ) {
						addSubmitButton( elm );
					}

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

				if ( ! submitBtnContainer ) {
					addSubmitButton( elm );
					const submitBtn = document.querySelectorAll(
						'.srfm-submit-btn-container'
					);
					if ( submitBtn.length > 1 ) {
						submitBtn[ 1 ].remove();
					}
				}
			}
		}, 200 );
	}, [ deviceType, sureformsKeys ] );

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
		} else if ( enableQuickActionSidebar !== undefined ) {
			// Attach the sidebar to the DOM.
			attachSidebar();
		} else {
			const container = document.querySelector( '.srfm-ee-quick-access' );
			if ( container ) {
				container.innerHTML = null;
			}
		}
	}, [ enableQuickActionSidebar ] );

	// Check if the user is a pro user and enable/disable the pro panel
	// eslint-disable-next-line no-unused-vars
	const [ isPro, setIsPro ] = useState( srfm_block_data.is_pro_active );

	// add pro panel to the block inserter
	useEffect( () => {
		const checkAndRenderCustomComponent = () => {
			const targetElement = document.querySelector(
				'.block-editor-inserter__block-list'
			);

			if ( targetElement && ! isPro ) {
				// Check if the custom component is already present
				const customComponent = targetElement.querySelector(
					'.upgrade-pro-container'
				);

				if ( ! customComponent ) {
					const newDiv = document.createElement( 'div' );
					newDiv.className = 'upgrade-pro-container';
					targetElement.appendChild( newDiv );
					render( <ProPanel />, newDiv );
				}
			}
		};

		// Set up MutationObserver to watch for changes in the DOM
		const MutationObserver =
			window.MutationObserver ||
			window.WebKitMutationObserver ||
			window.MozMutationObserver;
		const observer = new MutationObserver( checkAndRenderCustomComponent );

		// Set up the configuration of the MutationObserver
		const observerConfig = { childList: true, subtree: true };

		// Start observing the target element
		const targetNode = document.body;
		observer.observe( targetNode, observerConfig );

		// Cleanup the observer on component unmount
		return () => observer.disconnect();
	}, [] );

	return (
		<PluginDocumentSettingPanel
			className="srfm--panel"
			name="srfm-sidebar"
			title={ __( 'Form Options', 'sureforms' ) }
		>
			<InspectorTabs
				tabs={ [ 'general', 'style', 'advance' ] }
				defaultTab={ 'general' }
			>
				<InspectorTab { ...SRFMTabs.general }>
					<GeneralSettings
						defaultKeys={ defaultKeys }
						enableQuickActionSidebar={ enableQuickActionSidebar }
						setEnableQuickActionSidebar={
							setEnableQuickActionSidebar
						}
						isPageBreak={ isPageBreak }
					/>
				</InspectorTab>
				<InspectorTab { ...SRFMTabs.style }>
					<StyleSettings defaultKeys={ defaultKeys } />
				</InspectorTab>
				<InspectorTab { ...SRFMTabs.advance } parentProps={ props }>
					<AdvancedSettings defaultKeys={ defaultKeys } />
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
