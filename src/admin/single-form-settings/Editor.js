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
import { store as blockEditorStore, RichText } from '@wordpress/block-editor';

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
import { BlockInserterWrapper } from './Inserter.js';

const { select, dispatch } = wp.data;

const defaultKeys = {
	// General Tab
	_srfm_use_label_as_placeholder: false,
	_srfm_single_page_form_title: true,
	_srfm_instant_form: false,
	_srfm_is_inline_button: false,
	// Submit Button
	_srfm_submit_button_text: 'Submit',
	// Style Tab
	// Form Container
	_srfm_form_container_width: 650,
	_srfm_bg_type: 'image',
	_srfm_bg_image: '',
	_srfm_cover_image: '',
	_srfm_bg_color: '#ffffff',
	// Submit Button
	_srfm_inherit_theme_button: false,
	_srfm_submit_alignment: 'left',
	_srfm_submit_width: '',
	_srfm_submit_alignment_backend: '100%',
	_srfm_submit_width_backend: 'max-content',
	_srfm_additional_classes: '',

	// Advanced Tab
	// Success Message
	_srfm_submit_type: 'message',
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
	const isInlineButtonBlockPresent = blocks.some(
		( block ) => block.name === 'srfm/inline-button'
	);
	const deviceType = useDeviceType();

	function updateMeta( option, value ) {
		const option_array = {};
		option_array[ option ] = value;
		editPost( {
			meta: option_array,
		} );
	}

	// find if code editor is open/close then trigger adding button again
	const codeEditor = document.querySelector( '.editor-post-text-editor' );

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

	// Find the root container of the form
	const formRootContainer = document.querySelector(
		'.editor-styles-wrapper'
	);

	const addRootClass = () => {
		if ( formRootContainer && sureformsKeys._srfm_additional_classes ) {
			// Split the classes string by spaces
			const classesArray =
				sureformsKeys._srfm_additional_classes.split( ' ' );

			// Add classes individually
			classesArray.forEach( ( classname ) => {
				formRootContainer?.classList.add( classname );
			} );
		}
	};

	useEffect( addRootClass, [ formRootContainer ] );

	// Update the custom CSS when the formCustomCssData prop changes. This will apply the custom CSS to the editor.
	const formCustomCssData = sureformsKeys._srfm_form_custom_css || [];
	useEffect( () => {
		const isExistStyle = document.getElementById(
			'srfm-blocks-editor-custom-css'
		);
		if ( ! isExistStyle ) {
			const node = document.createElement( 'style' );
			node.setAttribute( 'id', 'srfm-blocks-editor-custom-css' );
			node.textContent =
				'.edit-post-visual-editor{' + formCustomCssData + '}';
			document.head.appendChild( node );
		} else {
			isExistStyle.textContent =
				'.edit-post-visual-editor{' + formCustomCssData + '}';
		}
	}, [ formCustomCssData ] );

	useEffect( () => {
		if ( typeof sureformsKeys?._srfm_page_break_settings?.is_page_break === 'boolean' ) {
			const updatedPageBreakSettings = {
				...sureformsKeys._srfm_page_break_settings,
				is_page_break: isPageBreak,
			};
			updateMeta( '_srfm_page_break_settings', updatedPageBreakSettings );
		}

		if ( typeof sureformsKeys._srfm_is_inline_button === 'boolean' ) {
			updateMeta( '_srfm_is_inline_button', isInlineButtonBlockPresent );
		}
	}, [ blockCount ] );

	// Render the Components in the center of the Header
	const headerCenterContainer =
		document.querySelector( '.edit-post-header__center' ) ||
		// added support for WP 6.6.
		document.querySelector( '.editor-header__center' );

	if ( headerCenterContainer ) {
		// remove the command bar and add our custom header title editor
		const header = document.querySelector( '.editor-post-title__block' );
		if ( header ) {
			header.remove();
		}
		const root = createRoot( headerCenterContainer );
		root.render( <SRFMEditorHeader /> );
	}

	const submitBtnContainer = document.querySelector(
		'.srfm-submit-btn-container'
	);
	function addSubmitButton( elm ) {
		const inheritClass = 'srfm-btn-alignment wp-block-button__link';
		const customClass =
			'srfm-button srfm-submit-button srfm-btn-alignment srfm-btn-bg-color';
		const btnClass =
			sureformsKeys?._srfm_inherit_theme_button &&
			sureformsKeys._srfm_inherit_theme_button
				? inheritClass
				: customClass;
		const btnCtnClass =
			sureformsKeys?._srfm_inherit_theme_button &&
			sureformsKeys._srfm_inherit_theme_button
				? 'wp-block-button'
				: 'srfm-submit-btn-font-size';

		const appendHtml = `<div class="srfm-custom-block-inserter"></div><div class="srfm-submit-btn-container ${ btnCtnClass }"><button class="srfm-submit-richtext ${ btnClass }"></button></div>`;

		if ( elm ) {
			if (
				! elm
					.closest( 'body' )
					.querySelector( '.srfm-submit-btn-container' )
			) {
				elm.insertAdjacentHTML( 'afterend', appendHtml );

				// If the normal button is present, add RichText to the button.
				const elementParent = elm.parentElement;

				const buttonContainer = elementParent.querySelector(
					'.srfm-submit-btn-container'
				);

				const button = buttonContainer.querySelector(
					'.srfm-submit-richtext'
				);

				const submitBtnText = sureformsKeys._srfm_submit_button_text;

				// Add block inserter in the srfm-custom-block-inserter div.
				const getBlockInserterDiv = elementParent.querySelector(
					'.srfm-custom-block-inserter'
				);

				if ( getBlockInserterDiv ) {
					createRoot( getBlockInserterDiv ).render( <BlockInserterWrapper /> );
				}

				createRoot( button ).render(
					<RichText
						tagName="label"
						value={
							submitBtnText
								? submitBtnText
								: __( 'Submit', 'sureforms' )
						}
						onChange={ ( value ) =>
							updateMeta( '_srfm_submit_button_text', value )
						}
						placeholder={ __( 'Submit', 'sureforms' ) }
					/>
				);

				button.addEventListener( 'click', () => {
					// need multiple timeouts for DOM elements to find.
					// click on form section
					setTimeout( () => {
						const editPostTab = document.getElementById( 'tabs-0-edit-post/document' );

						editPostTab?.click();
					}, 100 );

					// click on style tab
					setTimeout( () => {
						// elements for submit button event listener
						const styleTabElement = document.querySelectorAll( '.srfm-inspector-tabs div' )[ 1 ]; // Style Tab
						styleTabElement?.click();
					}, 150 );

					// then click on submit accordion
					setTimeout( () => {
						// elements for submit button event listener
						const submitBtnStyleContainer = document.querySelector( '.srfm-advance-panel-body-submit-button' );
						const submitBtnElement = submitBtnStyleContainer?.querySelector( 'button' );

						if ( ! submitBtnStyleContainer?.classList?.contains( 'is-opened' ) ) {
							submitBtnElement?.click();
						}
					}, 200 );
				} );
			}
		}
	}

	useEffect( () => {
		setTimeout( () => {
			const elm = document.querySelector(
				'.block-editor-block-list__layout'
			);

			// If Custom Button is present, remove the default button.
			if ( isInlineButtonBlockPresent ) {
				const submitBtn = document.querySelectorAll(
					'.srfm-submit-btn-container'
				);
				if ( submitBtn.length > 0 ) {
					submitBtn[ 0 ].remove();
				}
			}

			// If Custom Button is not present, add the default button. Remove the default button if there are more than one.
			if ( ! submitBtnContainer && ! isInlineButtonBlockPresent ) {
				addSubmitButton( elm );

				// remove duplicated submit button from the view after inline button is removed
				const submitBtn = document.querySelectorAll(
					'.srfm-submit-btn-container'
				);
				if ( submitBtn.length > 1 ) {
					submitBtn[ 1 ].remove();
				}

				// remove duplicated inserter from the view after inline button is removed
				const appender = document.querySelectorAll(
					'.srfm-custom-block-inserter'
				);
				if ( appender.length > 1 ) {
					appender[ 1 ].remove();
				}
			}
		}, 200 );
	}, [
		sureformsKeys,
		codeEditor,
		blockCount,
		isInlineButtonBlockPresent,
	] );

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
		/**
		 * For the tablist occurred with the WP-6.6.
		 * We will replace this with better solution
		 * in the future, when WordPress provides something built-in.
		 */
		const removeUnnecessaryTablist = () => {
			const tablist = document.querySelector(
				'.block-editor-inserter__tabs .block-editor-inserter__tablist-and-close-button'
			);

			if ( tablist ) {
				tablist.remove();
			}
		};

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
		const observer = new MutationObserver( () => {
			removeUnnecessaryTablist();
			checkAndRenderCustomComponent();
		} );

		// Set up the configuration of the MutationObserver
		const observerConfig = { childList: true, subtree: true };

		// Start observing the target element
		const targetNode = document.body;
		observer.observe( targetNode, observerConfig );

		// Cleanup the observer on component unmount
		return () => observer.disconnect();
	}, [] );

	return (
		<>
			<PluginDocumentSettingPanel
				name="srfm-description"
				className="srfm-single-form-settings-description"
				title={ __( 'SureForms Description', 'sureforms' ) }
			>
				<div className="block-editor-block-card">
					<span className="block-editor-block-icon has-colors">
						<svg
							width="24"
							height="24"
							viewBox="0 0 24 24"
							fill="none"
							xmlns="http://www.w3.org/2000/svg"
						>
							<path
								fillRule="evenodd"
								clipRule="evenodd"
								d="M12.0078 24C18.6352 24 24.0078 18.6274 24.0078 12C24.0078 5.37259 18.6352 0 12.0078 0C5.3804 0 0.0078125 5.37259 0.0078125 12C0.0078125 18.6274 5.3804 24 12.0078 24ZM12.0595 6C11.0959 6 9.76255 6.55103 9.08115 7.23077L7.2307 9.07692H16.4543L19.5384 6H12.0595ZM14.9189 16.7692C14.2376 17.449 12.9041 18 11.9406 18H4.46169L7.54585 14.9231H16.7694L14.9189 16.7692ZM17.9166 10.6154H5.69197L5.11453 11.1923C3.74722 12.4231 4.15274 13.3846 6.0676 13.3846H18.3253L18.903 12.8077C20.257 11.5841 19.8315 10.6154 17.9166 10.6154Z"
								fill="#D54407"
							/>
						</svg>
					</span>
					<div className="block-editor-block-card__content">
						<h2 className="block-editor-block-card__title">
							{ __( 'SureForms', 'sureforms' ) }
						</h2>
						<span className="block-editor-block-card__description">
							{ __( 'Customize with SureForms', 'sureforms' ) }
						</span>
					</div>
				</div>
			</PluginDocumentSettingPanel>
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
							enableQuickActionSidebar={
								enableQuickActionSidebar
							}
							setEnableQuickActionSidebar={
								setEnableQuickActionSidebar
							}
							isPageBreak={ isPageBreak }
						/>
					</InspectorTab>
					<InspectorTab { ...SRFMTabs.style }>
						<StyleSettings
							defaultKeys={ defaultKeys }
							isInlineButtonBlockPresent={
								isInlineButtonBlockPresent
							}
							isPageBreak={ isPageBreak }
						/>
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
									style={ {
										marginTop: '5px',
									} }
								/>
							</div>
						</BaseControl>
					</PanelRow>
				</PluginPostPublishPanel>
			</PluginDocumentSettingPanel>
		</>
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
