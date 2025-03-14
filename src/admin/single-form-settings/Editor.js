import { registerPlugin } from '@wordpress/plugins';

import {
	ClipboardButton,
	PanelRow,
	BaseControl,
	TextControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState, useEffect, render } from '@wordpress/element';

import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore, PluginDocumentSettingPanel, PluginPostPublishPanel } from '@wordpress/editor';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as preferencesStore } from '@wordpress/preferences';

import GeneralSettings from './tabs/GeneralSettings.js';
import StyleSettings from './tabs/StyleSettings.js';
import AdvancedSettings from './tabs/AdvancedSettings.js';

import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, { SRFMTabs } from '@Components/inspector-tabs/InspectorTab.js';
import { addHeaderCenterContainer } from './components/SRFMEditorHeader.js';
import {
	attachSidebar,
	toggleSidebar,
} from '../../../modules/quick-action-sidebar/index.js';
import { useDeviceType } from '@Controls/getPreviewType';

import ProPanel from './components/pro-panel/index.js';
import useSubmitButton from './components/useSubmitButton.js';
import SureFormsDescription from './components/SureFormsDescription.js';
import { defaultKeys, forcePanel } from './utils.js';
import InstantForm from './InstantForm.js';
import useContainerDynamicClass from './components/useContainerDynamicClass.js';

const SureformsFormSpecificSettings = ( props ) => {
	const [ hasCopied, setHasCopied ] = useState( false );
	const [ enableQuickActionSidebar, setEnableQuickActionSidebar ] =
		useState( 'enabled' );

	const {
		postId,
		sureformsKeys,
		blockCount,
		blocks,
		editorMode,
	} = useSelect( ( select ) => {
		const { get } = select( preferencesStore );
		return {
			editorMode: get( 'core', 'editorMode' ) ?? 'visual',
			postId: select( 'core/editor' ).getCurrentPostId(),
			sureformsKeys: select( editorStore ).getEditedPostAttribute( 'meta' ),
			blockCount: select( blockEditorStore ).getBlockCount(),
			blocks: select( 'core/block-editor' ).getBlocks(),
		};
	} );

	const { editPost } = useDispatch( editorStore );
	const [ rootContainer, setRootContainer ] = useState( null );
	const [ rootContainerDiv, setRootContainerDiv ] = useState( null );

	useEffect( () => {
		setRootContainer( document.querySelector( '.is-root-container' ) );
		setRootContainerDiv( document.querySelector( '.edit-post-visual-editor__content-area' ) );
	}, [] );

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

	// Add styling class to main Editor Container
	const addFormStylingClass = () => {
		if ( rootContainer && 'Desktop' === deviceType ) {
			rootContainer?.classList?.add( 'srfm-form-container' );
			rootContainer.setAttribute( 'id', 'srfm-form-container' );
		} else if ( rootContainerDiv ) {
			rootContainerDiv?.classList?.add( 'srfm-form-container' );
			rootContainerDiv.setAttribute( 'id', 'srfm-form-container' );
		}
	};

	useEffect( addFormStylingClass, [ rootContainer, rootContainerDiv, deviceType ] );

	useContainerDynamicClass( sureformsKeys );

	// Update the custom CSS when the formCustomCssData prop changes. This will apply the custom CSS to the editor.
	const formCustomCssData = sureformsKeys?._srfm_form_custom_css || '';

	useEffect( () => {
		if ( ! formCustomCssData ) {
			return;
		}

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

		if ( typeof sureformsKeys?._srfm_is_inline_button === 'boolean' ) {
			updateMeta( '_srfm_is_inline_button', isInlineButtonBlockPresent );
		}
	}, [ blockCount ] );

	useSubmitButton( {
		isInlineButtonBlockPresent,
		updateMeta,
		editorMode,
	} );

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
				<SureFormsDescription />
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

wp.domReady( () => {
	forcePanel();

	// Add the header center container.
	addHeaderCenterContainer();

	InstantForm();
} );
