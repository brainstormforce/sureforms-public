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
import {
	store as editorStore,
	PluginDocumentSettingPanel,
	PluginPostPublishPanel,
} from '@wordpress/editor';
import { store as blockEditorStore } from '@wordpress/block-editor';
import { store as preferencesStore } from '@wordpress/preferences';

import GeneralSettings from './tabs/GeneralSettings.js';
import StyleSettings from './tabs/StyleSettings.js';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	SRFMTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
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

// Capture URL source param at module-load time, before Gutenberg's BrowserURL
// component can call history.replaceState and strip unknown query parameters.
const _learnUrlSource = new URLSearchParams( window.location.search ).get(
	'source'
);

const SureformsFormSpecificSettings = () => {
	const [ hasCopied, setHasCopied ] = useState( false );
	const [ enableQuickActionSidebar, setEnableQuickActionSidebar ] =
		useState( 'enabled' );

	const { postId, sureformsKeys, blockCount, blocks, editorMode } = useSelect(
		( select ) => {
			const { get } = select( preferencesStore );
			return {
				editorMode: get( 'core', 'editorMode' ) ?? 'visual',
				postId: select( 'core/editor' ).getCurrentPostId(),
				sureformsKeys:
					select( editorStore ).getEditedPostAttribute( 'meta' ),
				blockCount: select( blockEditorStore ).getBlockCount(),
				blocks: select( 'core/block-editor' ).getBlocks(),
			};
		}
	);

	const { editPost } = useDispatch( editorStore );
	const { selectBlock } = useDispatch( blockEditorStore );
	const [ rootContainer, setRootContainer ] = useState( null );
	const [ rootContainerDiv, setRootContainerDiv ] = useState( null );

	useEffect( () => {
		setRootContainer( document.querySelector( '.is-root-container' ) );
		setRootContainerDiv(
			document.querySelector( '.edit-post-visual-editor__content-area' )
		);
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

	useEffect( addFormStylingClass, [
		rootContainer,
		rootContainerDiv,
		deviceType,
	] );

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
		if (
			typeof sureformsKeys?._srfm_page_break_settings?.is_page_break ===
			'boolean'
		) {
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

			if ( targetElement ) {
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

	// Learn: store new post ID when user chose "Build from scratch" from Lesson 1.
	useEffect( () => {
		if ( ! postId ) {
			return;
		}
		const learnFlow = localStorage.getItem( 'srfmLearnFlow' );
		if ( learnFlow === 'build-scratch' ) {
			localStorage.setItem( 'srfmLearnFormId', String( postId ) );
			localStorage.removeItem( 'srfmLearnFlow' );
		}
	}, [ postId ] );

	// Learn: auto-select first block and show tooltip for Lesson 2 (Set Up Your Form Fields).
	const [ showSetupFieldsTip, setShowSetupFieldsTip ] = useState( false );
	const [ showAttrTip, setShowAttrTip ] = useState( false );

	useEffect( () => {
		if ( _learnUrlSource === 'learn-setup-fields' ) {
			setShowSetupFieldsTip( true );
			const timer = setTimeout( () => {
				setShowSetupFieldsTip( false );
				setShowAttrTip( true );
			}, 4000 );
			return () => clearTimeout( timer );
		}
	}, [] );

	useEffect( () => {
		const existing = document.getElementById(
			'srfm-setup-fields-learn-tip'
		);
		if ( existing ) {
			existing.remove();
		}
		if ( ! showSetupFieldsTip ) {
			return;
		}

		const interval = setInterval( () => {
			// Find the first block — check main document first, then the
			// iframe canvas used by Gutenberg in WordPress 6.3+.
			let blockEl = document.querySelector(
				'.block-editor-block-list__block[data-block]'
			);
			let iframeEl = null;

			if ( ! blockEl ) {
				iframeEl = document.querySelector(
					'iframe[name="editor-canvas"], .editor-canvas__iframe'
				);
				if ( iframeEl?.contentDocument ) {
					blockEl = iframeEl.contentDocument.querySelector(
						'.block-editor-block-list__block[data-block]'
					);
				}
			}

			if ( ! blockEl ) {
				return;
			}
			clearInterval( interval );

			// Auto-select the first block.
			const clientId = blockEl.getAttribute( 'data-block' );
			selectBlock( clientId );

			// Compute tooltip position, adjusting for iframe offset when needed.
			const blockRect = blockEl.getBoundingClientRect();
			let tipTop, tipLeft;
			if ( iframeEl ) {
				const iframeRect = iframeEl.getBoundingClientRect();
				tipTop = iframeRect.top + blockRect.top + ( blockRect.height / 2 );
				tipLeft = iframeRect.left + blockRect.right + 10;
			} else {
				tipTop = blockRect.top + ( blockRect.height / 2 );
				tipLeft = blockRect.right + 10;
			}

			const tip = document.createElement( 'div' );
			tip.id = 'srfm-setup-fields-learn-tip';
			tip.style.cssText = `position:fixed;top:${ tipTop }px;left:${ tipLeft }px;transform:translateY(-50%);z-index:2147483647;pointer-events:none;`;
			tip.innerHTML = `
				<div style="position:absolute;top:50%;left:-4px;transform:translateY(-50%) rotate(45deg);width:8px;height:8px;background:#1e1e1e;"></div>
				<div style="background:#1e1e1e;color:#fff;font-size:13px;padding:6px 12px;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,0.15);white-space:nowrap;">
					Edit the Field Name If Required
				</div>
			`;
			document.body.appendChild( tip );
		}, 100 );

		return () => {
			clearInterval( interval );
			const tip = document.getElementById(
				'srfm-setup-fields-learn-tip'
			);
			if ( tip ) {
				tip.remove();
			}
		};
	}, [ showSetupFieldsTip ] );

	// Learn: second tooltip for Lesson 2 — beside the block inspector sidebar.
	useEffect( () => {
		if ( showAttrTip ) {
			const timer = setTimeout( () => setShowAttrTip( false ), 4000 );
			return () => clearTimeout( timer );
		}
	}, [ showAttrTip ] );

	useEffect( () => {
		const existing = document.getElementById( 'srfm-attr-learn-tip' );
		if ( existing ) {
			existing.remove();
		}
		if ( ! showAttrTip ) {
			return;
		}

		const interval = setInterval( () => {
			// Target the block inspector / attributes sidebar.
			const sidebar = document.querySelector(
				'.interface-interface-skeleton__sidebar'
			);
			if ( ! sidebar ) {
				return;
			}
			clearInterval( interval );

			const rect = sidebar.getBoundingClientRect();
			const tip = document.createElement( 'div' );
			tip.id = 'srfm-attr-learn-tip';
			tip.style.cssText = `position:fixed;top:${
				rect.top + ( rect.height / 4 )
			}px;left:${
				rect.left - 10
			}px;transform:translateX(-100%);z-index:2147483647;pointer-events:none;`;
			tip.innerHTML = `
				<div style="position:absolute;top:50%;right:-4px;transform:translateY(-50%) rotate(45deg);width:8px;height:8px;background:#1e1e1e;"></div>
				<div style="background:#1e1e1e;color:#fff;font-size:13px;padding:6px 12px;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,0.15);white-space:nowrap;">
					Update Your Field Settings From Here
				</div>
			`;
			document.body.appendChild( tip );
		}, 100 );

		return () => {
			clearInterval( interval );
			const tip = document.getElementById( 'srfm-attr-learn-tip' );
			if ( tip ) {
				tip.remove();
			}
		};
	}, [ showAttrTip ] );

	// Learn tooltip for Style tab (Module 1, Lesson 3 — Style Your Forms).
	const [ showStyleLearnTip, setShowStyleLearnTip ] = useState( false );

	useEffect( () => {
		if ( _learnUrlSource === 'learn-style-form' ) {
			setShowStyleLearnTip( true );
			const timer = setTimeout(
				() => setShowStyleLearnTip( false ),
				4000
			);
			return () => clearTimeout( timer );
		}
	}, [] );

	useEffect( () => {
		const existing = document.getElementById( 'srfm-style-learn-tip' );
		if ( existing ) {
			existing.remove();
		}
		if ( ! showStyleLearnTip ) {
			return;
		}

		let waitForPanel = null;

		const interval = setInterval( () => {
			// The Style tab is the second tab in .srfm-inspector-tabs.
			const styleTab = document.querySelector(
				'.srfm-inspector-tabs div:nth-child(2)'
			);
			if ( ! styleTab ) {
				return;
			}
			clearInterval( interval );

			// Auto-select the Style tab.
			styleTab.click();

			// Poll until the style panel is rendered with non-zero dimensions.
			// A fixed timeout is unreliable — React re-renders + DOM rearrangement
			// can take varying amounts of time, especially in free mode.
			let attempts = 0;
			waitForPanel = setInterval( () => {
				attempts++;
				if ( attempts > 40 ) {
					// Give up after ~2 seconds
					clearInterval( waitForPanel );
					return;
				}
				const formPanel =
					document.querySelector( '.srfm-advance-panel-body-form' ) ||
					document.querySelector(
						'.srfm-advance-panel-body-form-theme'
					);
				if ( ! formPanel ) {
					return;
				}
				const rect = formPanel.getBoundingClientRect();
				if ( rect.height === 0 || rect.width === 0 ) {
					// Panel not yet visible — keep polling
					return;
				}
				clearInterval( waitForPanel );

				if ( ! formPanel.classList.contains( 'is-opened' ) ) {
					formPanel.querySelector( '.components-button' )?.click();
				}
				// Scroll the panel into view in case it's below the sidebar fold,
				// then measure after the browser has committed the layout.
				formPanel.scrollIntoView( {
					behavior: 'instant',
					block: 'nearest',
				} );
				window.requestAnimationFrame( () => {
					const finalRect = formPanel.getBoundingClientRect();
					const tip = document.createElement( 'div' );
					tip.id = 'srfm-style-learn-tip';
					tip.style.cssText = `position:fixed;top:${
						finalRect.top + ( finalRect.height / 2 )
					}px;left:${
						finalRect.left - 10
					}px;transform:translateY(-50%) translateX(-100%);z-index:2147483647;pointer-events:none;`;
					tip.innerHTML = `
						<div style="position:absolute;top:50%;right:-4px;transform:translateY(-50%) rotate(45deg);width:8px;height:8px;background:#1e1e1e;"></div>
						<div style="background:#1e1e1e;color:#fff;font-size:13px;padding:6px 12px;border-radius:6px;box-shadow:0 4px 12px rgba(0,0,0,0.15);white-space:nowrap;">
							Customize Your Form Style Here
						</div>
					`;
					document.body.appendChild( tip );
				} );
			}, 50 );
		}, 100 );

		return () => {
			clearInterval( interval );
			if ( waitForPanel ) {
				clearInterval( waitForPanel );
			}
			const tip = document.getElementById( 'srfm-style-learn-tip' );
			if ( tip ) {
				tip.remove();
			}
		};
	}, [ showStyleLearnTip ] );

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
					tabs={ [ 'general', 'style' ] }
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
