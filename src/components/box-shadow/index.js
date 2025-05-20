/**
 * Box-Shadow reusable component.
 *
 */
import { __ } from '@wordpress/i18n';
import Range from '@Components/range/Range.js';
import AdvancedPopColorControl from '../color-control/advanced-pop-color-control';
import { Button, Dashicon } from '@wordpress/components';
import MultiButtonsControl from '../multi-buttons-control/index';
import {
	useLayoutEffect,
	useEffect,
	useState,
	useRef,
} from '@wordpress/element';
import { select } from '@wordpress/data';
import getSRFMEditorStateLocalStorage from '@Controls/getSRFMEditorStateLocalStorage';
import { blocksAttributes } from '@Attributes/getBlocksDefaultAttributes';
import { getIdFromString, getPanelIdFromRef } from '@Utils/Helpers';
import SRFMHelpText from '@Components/help-text';
import { applyFilters } from '@wordpress/hooks';

const BoxShadowControl = ( props ) => {
	const [ panelNameForHook, setPanelNameForHook ] = useState( null );
	const panelRef = useRef( null );
	const [ showAdvancedControls, toggleAdvancedControls ] = useState( false );

	const allBlocksAttributes = applyFilters(
		'srfm.blocksAttributes',
		blocksAttributes
	); // eslint-disable-line @wordpress/no-unused-vars-before-return

	const { getSelectedBlock } = select( 'core/block-editor' );

	useLayoutEffect( () => {
		window.addEventListener( 'click', function ( e ) {
			const popupButton = document.querySelector(
				`.active.popup-${ blockId } .srfm-control-popup__options--action-button`
			);
			const popupWrap = document.querySelector(
				`.active.popup-${ blockId } .srfm-control-popup`
			);

			if (
				popupButton &&
				! popupButton?.contains( e.target ) &&
				popupWrap &&
				! popupWrap?.contains( e.target ) &&
				! e.target?.classList?.contains(
					'srfm-advanced-color-indicate'
				) &&
				! e.target?.parentElement?.closest( '.srfm-popover-color' ) &&
				! e.target?.parentElement?.closest( '.srfm-reset' )
			) {
				toggleAdvancedControls( false );
				const blockName = getSelectedBlock()?.name;
				const srfmSettingState =
					getSRFMEditorStateLocalStorage( 'srfmSettingState' );

				const data = {
					...srfmSettingState,
					[ blockName ]: {
						...srfmSettingState?.[ blockName ],
						selectedSetting: false,
					},
				};

				const srfmLocalStorage = getSRFMEditorStateLocalStorage();
				if ( srfmLocalStorage ) {
					srfmLocalStorage.setItem(
						'srfmSettingState',
						JSON.stringify( data )
					);
				}
			}
		} );
	}, [] );

	const blockNameForHook = getSelectedBlock()?.name.split( '/' ).pop(); // eslint-disable-line @wordpress/no-unused-vars-before-return
	useEffect( () => {
		setPanelNameForHook( getPanelIdFromRef( panelRef ) );
	}, [ blockNameForHook ] );

	const {
		setAttributes,
		boxShadowColor,
		boxShadowHOffset,
		boxShadowVOffset,
		boxShadowBlur,
		boxShadowSpread,
		boxShadowPosition,
		label = __( 'Box Shadow', 'sureforms' ),
		popup = false,
		blockId,
		help = false,
		isFormSpecific = false,
	} = props;

	let advancedControls;
	const activeClass = showAdvancedControls ? 'active' : '';

	// Array of all the current Typography Control's Labels.
	const attributeNames = [
		boxShadowColor.label,
		boxShadowHOffset.label,
		boxShadowVOffset.label,
		boxShadowBlur.label,
		boxShadowSpread.label,
		boxShadowPosition.label,
	];

	// Function to get the Block's default Box Shadow Values.
	const getBlockBoxShadowValue = () => {
		const selectedBlockName = getSelectedBlock()?.name.split( '/' ).pop();

		let defaultValues = false;
		if ( 'undefined' !== typeof allBlocksAttributes[ selectedBlockName ] ) {
			attributeNames.forEach( ( attributeName ) => {
				if ( attributeName ) {
					const blockDefaultAttributeValue =
						'undefined' !==
						typeof allBlocksAttributes[ selectedBlockName ][
							attributeName
						]?.default
							? allBlocksAttributes[ selectedBlockName ][
								attributeName
							  ]?.default
							: '';
					defaultValues = {
						...defaultValues,
						[ attributeName ]: blockDefaultAttributeValue,
					};
				}
			} );
		}
		return defaultValues;
	};

	// Function to check if any Box Shadow Setting has changed.
	const getUpdateState = () => {
		const defaultValues = getBlockBoxShadowValue();
		const selectedBlockAttributes = getSelectedBlock()?.attributes;
		let isBoxShadowUpdated = false;
		attributeNames.forEach( ( attributeName ) => {
			if (
				selectedBlockAttributes?.[ attributeName ] &&
				selectedBlockAttributes?.[ attributeName ] !==
					defaultValues?.[ attributeName ]
			) {
				isBoxShadowUpdated = true;
			}
		} );
		return isBoxShadowUpdated;
	};

	// Flag to check if this control has been updated or not.
	const isBoxShadowUpdated = popup && getUpdateState();

	const overallControls = (
		<>
			{ /* Shadow Color */ }
			<AdvancedPopColorControl
				label={ boxShadowColor.title }
				colorValue={ boxShadowColor.value }
				data={ {
					value: boxShadowColor.value,
					label: boxShadowColor.label,
				} }
				setAttributes={ setAttributes }
				isFormSpecific={ isFormSpecific }
				value={ boxShadowColor.value }
			/>
			{ /* Horizontal Positioning */ }
			<Range
				label={ boxShadowHOffset.title }
				value={ boxShadowHOffset.value }
				min={ -30 }
				max={ 30 }
				displayUnit={ false }
				setAttributes={ setAttributes }
				data={ {
					value: boxShadowHOffset.value,
					label: boxShadowHOffset.label,
				} }
				isFormSpecific={ isFormSpecific }
			/>
			{ /* Vertical Positioning */ }
			<Range
				label={ boxShadowVOffset.title }
				value={ boxShadowVOffset.value }
				min={ -30 }
				max={ 30 }
				displayUnit={ false }
				setAttributes={ setAttributes }
				data={ {
					value: boxShadowVOffset.value,
					label: boxShadowVOffset.label,
				} }
				isFormSpecific={ isFormSpecific }
			/>
			{ /* Blur */ }
			<Range
				label={ boxShadowBlur.title }
				value={ boxShadowBlur.value }
				min={ 0 }
				max={ 100 }
				displayUnit={ false }
				setAttributes={ setAttributes }
				data={ {
					value: boxShadowBlur.value,
					label: boxShadowBlur.label,
				} }
				isFormSpecific={ isFormSpecific }
			/>
			{ /* Spread */ }
			<Range
				label={ boxShadowSpread.title }
				value={ boxShadowSpread.value }
				min={ -30 }
				max={ 30 }
				displayUnit={ false }
				setAttributes={ setAttributes }
				data={ {
					value: boxShadowSpread.value,
					label: boxShadowSpread.label,
				} }
				isFormSpecific={ isFormSpecific }
			/>
			{ /* Shadow Position */ }
			<MultiButtonsControl
				setAttributes={ setAttributes }
				label={ boxShadowPosition.title }
				data={ {
					value: boxShadowPosition.value,
					label: boxShadowPosition.label,
				} }
				options={ [
					{
						value: 'outset',
						label: __( 'Outset', 'sureforms' ),
						tooltip: __( 'Outset', 'sureforms' ),
					},
					{
						value: 'inset',
						label: __( 'Inset', 'sureforms' ),
						tooltip: __( 'Inset (10px)', 'sureforms' ),
					},
				] }
				showIcons={ false }
			/>
		</>
	);

	if ( showAdvancedControls ) {
		advancedControls = (
			<div className="srfm-box-shadow-advanced srfm-control-popup">
				{ overallControls }
			</div>
		);
	}

	const boxShadowAdvancedControls = (
		<div className="srfm-control-popup__options--action-wrapper">
			<span className="srfm-control-label">
				{ label }
				{ isBoxShadowUpdated && (
					<div className="srfm__change-indicator--dot-right" />
				) }
			</span>
			<Button
				className="srfm-box-shadow-button srfm-control-popup__options--action-button"
				aria-pressed={ showAdvancedControls }
				onClick={ () => {
					const allPopups = document.querySelectorAll(
						'.srfm-control-popup__options'
					);
					if ( allPopups && 0 < allPopups.length ) {
						for ( let i = 0; i < allPopups.length; i++ ) {
							const popupButton = allPopups[ i ]?.querySelector(
								'.srfm-control-popup__options.active .srfm-control-popup__options--action-button'
							);
							popupButton?.click();
						}
					}
					toggleAdvancedControls( ! showAdvancedControls );

					const blockName = getSelectedBlock()?.name;
					const srfmSettingState =
						getSRFMEditorStateLocalStorage( 'srfmSettingState' );
					let data = {
						...srfmSettingState,
						[ blockName ]: {
							...srfmSettingState?.[ blockName ],
							selectedSetting: '.srfm-box-shadow-options',
						},
					};

					if ( showAdvancedControls ) {
						data = {
							...srfmSettingState,
							[ blockName ]: {
								...srfmSettingState?.[ blockName ],
								selectedSetting: false,
							},
						};
					}
					const srfmLocalStorage = getSRFMEditorStateLocalStorage();
					if ( srfmLocalStorage ) {
						srfmLocalStorage.setItem(
							'srfmSettingState',
							JSON.stringify( data )
						);
					}
				} }
			>
				<Dashicon icon="edit" />
			</Button>
		</div>
	);

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
		<div ref={ panelRef }>
			{ controlBeforeDomElement }
			{ popup ? (
				<div
					className={ ` components-base-control srfm-box-shadow-options srfm-control-popup__options popup-${ blockId } ${ activeClass }` }
				>
					{ boxShadowAdvancedControls }
					{ showAdvancedControls && advancedControls }
					<SRFMHelpText text={ help } />
				</div>
			) : (
				<>{ overallControls }</>
			) }
			{ controlAfterDomElement }
		</div>
	);
};

export default BoxShadowControl;
