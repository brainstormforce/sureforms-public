/**
 * Internal & External dependencies.
 */
import { ButtonGroup, Button, Tooltip } from '@wordpress/components';
import { useDeviceType } from '@Controls/getPreviewType';
import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useState, useRef, useCallback } from '@wordpress/element';
import { dispatch, select } from '@wordpress/data';
import getSRFMEditorStateLocalStorage from '@Controls/getSRFMEditorStateLocalStorage';
import { getIdFromString, getPanelIdFromRef } from '@Utils/Helpers';
import SRFMHelpText from '@Components/help-text';
import { applyFilters } from '@wordpress/hooks';

const ResponsiveToggle = ( props ) => {
	const { label, responsive, help = false } = props;
	const deviceType = useDeviceType();
	const [ displayResponsive, toggleResponsive ] = useState( false );
	const [ panelNameForHook, setPanelNameForHook ] = useState( null );
	const panelRef = useRef( null );

	const { getSelectedBlock } = select( 'core/block-editor' );
	const blockNameForHook = getSelectedBlock()?.name.split( '/' ).pop(); // eslint-disable-line @wordpress/no-unused-vars-before-return

	useEffect( () => {
		setPanelNameForHook( getPanelIdFromRef( panelRef ) );
	}, [ blockNameForHook ] );

	const customSetPreviewDeviceType = useCallback( ( device ) => {
		if ( null !== dispatch( 'core/edit-post' ) ) {
			const { __experimentalSetPreviewDeviceType: setPreviewDeviceType } =
				dispatch( 'core/edit-post' );
			setPreviewDeviceType( device );
		}
		toggleResponsive( displayResponsive );
	}, [] );

	const devicesSvgs = {
		desktop: (
			<svg
				width="8"
				height="7"
				viewBox="0 0 8 7"
				xmlns="http://www.w3.org/2000/svg"
			>
				<path d="M7.33333 0H0.666667C0.298611 0 0 0.293945 0 0.65625V5.03125C0 5.39355 0.298611 5.6875 0.666667 5.6875H3.33333L3.11111 6.34375H2.11111C1.92639 6.34375 1.77778 6.49004 1.77778 6.67188C1.77778 6.85371 1.92639 7 2.11111 7H5.88889C6.07361 7 6.22222 6.85371 6.22222 6.67188C6.22222 6.49004 6.07361 6.34375 5.88889 6.34375H4.88889L4.66667 5.6875H7.33333C7.70139 5.6875 8 5.39355 8 5.03125V0.65625C8 0.293945 7.70139 0 7.33333 0ZM7.11111 4.8125H0.888889V0.875H7.11111V4.8125Z" />
			</svg>
		),
		tablet: (
			<svg
				width="6"
				height="7"
				viewBox="0 0 6 7"
				xmlns="http://www.w3.org/2000/svg"
			>
				<path d="M5 0H1C0.446667 0 0 0.390833 0 0.875V6.125C0 6.60917 0.446667 7 1 7H5C5.55333 7 6 6.60917 6 6.125V0.875C6 0.390833 5.55333 0 5 0ZM3.66667 6.41667H2.33333V6.125H3.66667V6.41667ZM5.41667 5.54167H0.583333V0.875H5.41667V5.54167Z" />
			</svg>
		),
		mobile: (
			<svg
				width="4"
				height="7"
				viewBox="0 0 4 7"
				xmlns="http://www.w3.org/2000/svg"
			>
				<path d="M3.33333 0H0.666667C0.297778 0 0 0.390833 0 0.875V6.125C0 6.60917 0.297778 7 0.666667 7H3.33333C3.70222 7 4 6.60917 4 6.125V0.875C4 0.390833 3.70222 0 3.33333 0ZM2.44444 6.41667H1.55556V6.125H2.44444V6.41667ZM3.61111 5.54167H0.388889V0.875H3.61111V5.54167Z" />
			</svg>
		),
	};
	const devices = [
		{
			name: __( 'Desktop', 'sureforms' ),
			staticName: 'Desktop',
			title: devicesSvgs.desktop,
			itemClass: 'srfm-desktop-tab srfm-responsive-tabs',
		},
		{
			name: __( 'Tablet', 'sureforms' ),
			staticName: 'Tablet',
			title: devicesSvgs.tablet,
			itemClass: 'srfm-tablet-tab srfm-responsive-tabs',
		},
		{
			name: __( 'Mobile', 'sureforms' ),
			staticName: 'Mobile',
			key: 'mobile',
			title: devicesSvgs.mobile,
			itemClass: 'srfm-mobile-tab srfm-responsive-tabs',
		},
	];

	// In the Widget editor, the device type is always set to desktop.
	if ( ! deviceType ) {
		return null;
	}

	const commonResponsiveHandler = ( e ) => {
		// This code is to fix the side-effect of the editor responsive click settings panel refresh issue.
		let eventTriggerElement = e.target;
		let settingsPopup = null;

		if ( 'svg' === eventTriggerElement.tagName ) {
			eventTriggerElement = eventTriggerElement.closest(
				'.srfm-responsive-common-button'
			);
		}
		if (
			eventTriggerElement.closest( '.srfm-typography-options.active' )
		) {
			settingsPopup = '.srfm-typography-options';
		}
		if (
			eventTriggerElement.closest( '.srfm-box-shadow-options.active' )
		) {
			settingsPopup = '.srfm-box-shadow-options';
		}

		const blockName = getSelectedBlock()?.name;
		const srfmSettingState =
			getSRFMEditorStateLocalStorage( 'srfmSettingState' );

		const inspectorTab = eventTriggerElement.closest(
			'.srfm-inspector-tab'
		);
		const panelBody = eventTriggerElement.closest(
			'.components-panel__body.is-opened'
		);
		let panelBodyClass = '';

		if ( panelBody.classList && 0 !== panelBody.classList ) {
			panelBody.classList.forEach( ( className ) => {
				if ( className.includes( 'srfm-advance-panel-body' ) ) {
					panelBodyClass = className;
				}
			} );
		}

		let inspectorTabName = 'style';
		if ( inspectorTab.classList.contains( 'srfm-tab-content-general' ) ) {
			inspectorTabName = 'general';
		}
		if ( inspectorTab.classList.contains( 'srfm-tab-content-advance' ) ) {
			inspectorTabName = 'advance';
		}

		const data = {
			...srfmSettingState,
			[ blockName ]: {
				selectedTab: inspectorTabName,
				selectedPanel: panelBodyClass,
				selectedSetting: settingsPopup,
			},
		};

		const srfmLocalStorage = getSRFMEditorStateLocalStorage();
		if ( srfmLocalStorage ) {
			srfmLocalStorage.setItem(
				'srfmSettingState',
				JSON.stringify( data )
			);
		}

		// Above Section Ends.
		toggleResponsive( ! displayResponsive );
	};

	const controlName = getIdFromString( props.label );

	const controlBeforeDomElement = applyFilters(
		`srfm.${ blockNameForHook }.${ panelNameForHook }.${ controlName }.before`,
		'',
		blockNameForHook
	);
	const controlAfterDomElement = applyFilters(
		`srfm.${ blockNameForHook }.${ panelNameForHook }.${ controlName }`,
		'',
		blockNameForHook
	);

	return (
		<div ref={ panelRef } className="srfm-responsive-label-wrap">
			{ controlBeforeDomElement }

			{ label && <span className="srfm-control-label">{ label }</span> }
			{ ! displayResponsive && responsive && (
				<Button
					key="srfm-responsive-common-button"
					className="srfm-responsive-common-button"
					onClick={ commonResponsiveHandler }
				>
					{ devicesSvgs[ deviceType.toLowerCase() ] }
				</Button>
			) }
			{ displayResponsive && responsive && (
				<ButtonGroup
					className="srfm-range-control-responsive components-tab-panel__tabs"
					aria-label={ __( 'Device', 'sureforms' ) }
				>
					{ devices.map(
						( { name, staticName, key, title, itemClass } ) => (
							<Tooltip
								text={ sprintf(
									/* translators: abbreviation for units */
									'%s',
									name
								) }
								key={ key }
							>
								<Button
									key={ key }
									className={ `components-button components-tab-panel__tabs-item ${ itemClass }${
										staticName === deviceType
											? ' active-tab'
											: ''
									}` }
									aria-pressed={ deviceType === staticName }
									onClick={ () =>
										customSetPreviewDeviceType( staticName )
									}
								>
									{ title }
								</Button>
							</Tooltip>
						)
					) }
				</ButtonGroup>
			) }
			<SRFMHelpText text={ help } />
			{ controlAfterDomElement }
		</div>
	);
};

export default ResponsiveToggle;
