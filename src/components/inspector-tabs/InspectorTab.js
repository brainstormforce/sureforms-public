import { applyFilters } from '@wordpress/hooks';
import { useRef, useEffect } from '@wordpress/element';
import getSRFMEditorStateLocalStorage from '@Controls/getSRFMEditorStateLocalStorage';
import { select } from '@wordpress/data';

const InspectorTab = ( props ) => {
	const { children, isActive, type } = props;
	const tabRef = useRef( null );
	const { getSelectedBlock } = select( 'core/block-editor' );
	const blockName = getSelectedBlock()?.name;

	const tabContent = function () {
		return applyFilters(
			`srfm_${ type }_tab_content`,
			'',
			props.parentProps
		);
	};

	useEffect( () => {
		const srfmSettingState =
			getSRFMEditorStateLocalStorage( 'srfmSettingState' );

		if ( srfmSettingState ) {
			const inspectorTabName = srfmSettingState[ blockName ]?.selectedTab;
			const panelBodyClass = srfmSettingState[ blockName ]?.selectedPanel;
			const settingsPopup =
				srfmSettingState[ blockName ]?.selectedSetting;
			const selectedInnerTab =
				srfmSettingState[ blockName ]?.selectedInnerTab;

			// This code is to fix the side-effect of the editor responsive click settings panel refresh issue AND aldo for preserving state for better block editor experinence.
			if ( inspectorTabName && type === inspectorTabName ) {
				let panelToActivate = false;
				if ( panelBodyClass ) {
					panelToActivate = tabRef.current.querySelector(
						`.${ panelBodyClass }`
					);
				} else {
					panelToActivate =
						tabRef.current.querySelector( '.is-opened' );
				}

				if ( panelToActivate ) {
					if ( ! panelToActivate.classList.contains( 'is-opened' ) ) {
						panelToActivate
							.querySelector( '.components-button' )
							.click();
					}
					if ( selectedInnerTab ) {
						// Need a delay to open the popup as the makup load just after the above click function called.
						setTimeout( function () {
							const selectedInnerTabToActivate =
								panelToActivate.querySelector(
									selectedInnerTab
								);
							if (
								selectedInnerTabToActivate &&
								! selectedInnerTabToActivate.classList.contains(
									'active-tab'
								)
							) {
								selectedInnerTabToActivate.click();
							}
						}, 100 );
					}
					if ( settingsPopup ) {
						// Need a delay to open the popup as the makup load just after the above click function called.
						setTimeout( function () {
							const settingsPopupToActivate =
								panelToActivate.querySelector( settingsPopup );

							if (
								settingsPopupToActivate &&
								! settingsPopupToActivate.classList.contains(
									'active'
								)
							) {
								settingsPopupToActivate
									.querySelector( '.components-button' )
									.click();
							}
						}, 100 );
					}
				}
			}
		}
	}, [] );

	const blockNameForHook = blockName?.split( '/' )?.pop();
	const inspectorTabBefore = applyFilters(
		`srfm.${ blockNameForHook }.tab_${ type }.before`,
		'',
		blockName
	);
	const inspectorTabAfter = applyFilters(
		`srfm.${ blockNameForHook }.tab_${ type }`,
		'',
		blockName
	);

	return (
		<div
			style={ {
				display: isActive ? 'block' : 'none',
			} }
			className={ `srfm-inspector-tab srfm-tab-content-${ type }` }
			ref={ tabRef }
		>
			{ inspectorTabBefore }
			{ Array.isArray( children )
				? children.map( ( item ) => item )
				: children }
			{ tabContent() }
			{ inspectorTabAfter }
		</div>
	);
};

export default InspectorTab;

export const SRFMTabs = {
	general: {
		key: 'general',
		type: 'general',
	},
	style: {
		key: 'style',
		type: 'style',
	},
	advance: {
		key: 'advance',
		type: 'advance',
	},
};
