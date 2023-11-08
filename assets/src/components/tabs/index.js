import { TabPanel } from '@wordpress/components';
import styles from './editor.lazy.scss';
import {
	useLayoutEffect,
	useEffect,
	useState,
	useRef,
} from '@wordpress/element';
import { getPanelIdFromRef } from '@Utils/Helpers';
import Separator from '@Components/separator';
import { select } from '@wordpress/data';
import getSRFMEditorStateLocalStorage from '@Controls/getUAGEditorStateLocalStorage';
import SRFMHelpText from '@Components/help-text';
import { applyFilters } from '@wordpress/hooks';

const SRFMTabsControl = ( props ) => {
	const [ panelNameForHook, setPanelNameForHook ] = useState( null );
	const panelRef = useRef( null );
	// Add and remove the CSS on the drop and remove of the component.
	useLayoutEffect( () => {
		styles.use();
		return () => {
			styles.unuse();
		};
	}, [] );

	const { getSelectedBlock } = select( 'core/block-editor' );

	const blockNameForHook = getSelectedBlock()?.name.split( '/' ).pop(); // eslint-disable-line @wordpress/no-unused-vars-before-return
	useEffect( () => {
		setPanelNameForHook( getPanelIdFromRef( panelRef ) );
	}, [ blockNameForHook ] );

	const tabRef = useRef( null );

	const tabsCountClass =
		3 === props.tabs.length ? 'srfm-control-tabs-three-tabs ' : '';

	const tabs = props.tabs.map( ( tab, index ) => {
		return {
			...tab,
			className: `srfm-tab-${ index + 1 } ${ tab?.name }`,
		};
	} );

	const controlName = 'tabs'; // there is no label props that's why keep hard coded label
	const controlBeforeDomElement = applyFilters(
		`spectra.${ blockNameForHook }.${ panelNameForHook }.${ controlName }.before`,
		'',
		blockNameForHook
	);
	const controlAfterDomElement = applyFilters(
		`spectra.${ blockNameForHook }.${ panelNameForHook }.${ controlName }`,
		'',
		blockNameForHook
	);

	return (
		<div ref={ panelRef }>
			{ controlBeforeDomElement }
			<TabPanel
				className={ `srfm-control-tabs ${ tabsCountClass }` }
				activeClass="active-tab"
				tabs={ tabs }
				ref={ tabRef }
				onSelect={ ( tabName ) => {
					const selectedTab = document
						.getElementsByClassName( 'srfm-control-tabs' )[ 0 ]
						?.querySelector( `.${ tabName }` );
					let selectedTabClass = false;
					if ( selectedTab && selectedTab?.classList ) {
						selectedTab?.classList.forEach( ( className ) => {
							if ( className.includes( 'srfm-tab' ) ) {
								selectedTabClass = `.${ className }`;
							}
						} );
					}

					const blockName = getSelectedBlock()?.name;
					const srfmSettingState =
						getSRFMEditorStateLocalStorage( 'srfmSettingState' );
					const data = {
						...srfmSettingState,
						[ blockName ]: {
							...srfmSettingState?.[ blockName ],
							selectedInnerTab: selectedTabClass,
						},
					};

					const srfmLocalStorage = getSRFMEditorStateLocalStorage();
					if ( srfmLocalStorage ) {
						srfmLocalStorage.setItem(
							'srfmSettingState',
							JSON.stringify( data )
						);
					}
				} }
			>
				{ ( tabName ) => {
					return (
						<div className="srfm-control-tabs-output">
							{ props[ tabName.name ] }
						</div>
					);
				} }
			</TabPanel>
			{ ! props?.disableBottomSeparator && <Separator /> }
			<SRFMHelpText text={ props.help } />
			{ controlAfterDomElement }
		</div>
	);
};
export default SRFMTabsControl;
