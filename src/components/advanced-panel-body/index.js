import { PanelBody } from '@wordpress/components';
import { useRef, memo, useState, useEffect } from '@wordpress/element';
import getSRFMEditorStateLocalStorage from '@Controls/getSRFMEditorStateLocalStorage';
import { select } from '@wordpress/data';
import { applyFilters } from '@wordpress/hooks';

const SRFMAdvancedPanelBody = ( props ) => {
	const { children } = props;

	const panelRef = useRef( null );
	// Below code is to set the setting state of Tab for each block.
	const { getSelectedBlock } = select( 'core/block-editor' );
	const blockName = getSelectedBlock()?.name;
	const srfmSettingState =
		getSRFMEditorStateLocalStorage( 'srfmSettingState' );
	const [ panelNameForHook, setPanelNameForHook ] = useState( 'style' );

	const getInspectorTabName = () => {
		let inspectorTabName = 'style';
		if (
			panelRef?.current?.parentElement?.classList.contains(
				'srfm-tab-content-general'
			)
		) {
			inspectorTabName = 'general';
		}
		if (
			panelRef?.current?.parentElement?.classList.contains(
				'srfm-tab-content-advance'
			)
		) {
			inspectorTabName = 'advance';
		}

		return inspectorTabName;
	};

	useEffect( () => {
		setPanelNameForHook( getInspectorTabName() );
	}, [ panelRef ] );

	const onPanelToggle = () => {
		if ( 'enabled' === srfm_blocks_info.collapse_panels ) {
			const siblings = getSiblings( panelRef.current );

			siblings.forEach( ( element ) => {
				element.querySelector( '.components-button' ).click();
			} );
		}

		let match = false;
		panelRef?.current?.classList.forEach( function ( value ) {
			if ( value.includes( 'srfm-advance-panel-body' ) ) {
				match = value;
			}
		} );
		let inspectorTabName = 'style';
		if (
			panelRef?.current?.parentElement?.classList.contains(
				'srfm-tab-content-general'
			)
		) {
			inspectorTabName = 'general';
		}
		if (
			panelRef?.current?.parentElement?.classList.contains(
				'srfm-tab-content-advance'
			)
		) {
			inspectorTabName = 'advance';
		}

		const data = {
			...srfmSettingState,
			[ blockName ]: {
				...srfmSettingState?.[ blockName ],
				selectedPanel: match,
				selectedTab: inspectorTabName,
			},
		};

		const srfmLocalStorage = getSRFMEditorStateLocalStorage();
		if ( srfmLocalStorage ) {
			srfmLocalStorage.setItem(
				'srfmSettingState',
				JSON.stringify( data )
			);
		}
	};

	const getSiblings = function ( elem ) {
		const siblings = [];
		let sibling = elem.parentNode.firstChild;

		while ( sibling ) {
			if (
				sibling.nodeType === 1 &&
				sibling !== elem &&
				sibling.classList.contains( 'is-opened' )
			) {
				siblings.push( sibling );
			}
			sibling = sibling.nextSibling;
		}

		return siblings;
	};

	const panelTitle = props?.title
		? props?.title
			.toLowerCase()
			.replace( /[^a-zA-Z ]/g, '' )
			.replace( /\s+/g, '-' )
		: '';

	const blockNameForHook = blockName?.split( '/' )?.pop();
	const tabBodyBefore = applyFilters(
		`srfm.${ blockNameForHook }.${ panelNameForHook }.${ panelTitle }.before`,
		'',
		blockName
	);
	const tabBodyAfter = applyFilters(
		`srfm.${ blockNameForHook }.${ panelNameForHook }.${ panelTitle }`,
		'',
		blockName
	);

	return (
		<PanelBody
			{ ...props }
			onToggle={ onPanelToggle }
			ref={ panelRef }
			className={ `srfm-advance-panel-body-${ panelTitle }` }
		>
			{ tabBodyBefore }
			{ children }
			{ tabBodyAfter }
		</PanelBody>
	);
};
export default memo( SRFMAdvancedPanelBody );
