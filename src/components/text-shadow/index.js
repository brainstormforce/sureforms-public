/**
 * Text-Shadow reusable component.
 *
 */
import { __ } from '@wordpress/i18n';
import Range from '@Components/range/Range.js';
import AdvancedPopColorControl from '../color-control/advanced-pop-color-control';
import { Button, Dashicon } from '@wordpress/components';
import {
	useLayoutEffect,
	useEffect,
	useState,
	useRef,
} from '@wordpress/element';
import { select } from '@wordpress/data';
import getSRFMEditorStateLocalStorage from '@Controls/getSRFMEditorStateLocalStorage';
import { getIdFromString, getPanelIdFromRef } from '@Utils/Helpers';
import { blocksAttributes } from '@Attributes/getBlocksDefaultAttributes';
import SRFMHelpText from '@Components/help-text';
import { applyFilters } from '@wordpress/hooks';

const TextShadowControl = ( props ) => {
	const [ showAdvancedControls, toggleAdvancedControls ] = useState( false );
	const [ panelNameForHook, setPanelNameForHook ] = useState( null );
	const panelRef = useRef( null );

	const {
		setAttributes,
		textShadowColor,
		textShadowHOffset,
		textShadowVOffset,
		textShadowBlur,
		label = __( 'Text Shadow', 'sureforms' ),
		popup = false,
		blockId,
		help = false,
	} = props;

	let advancedControls;
	const activeClass = showAdvancedControls ? 'active' : '';

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
				! e.target?.classList?.contains(
					'srfm-advanced-color-indicate'
				) &&
				! e.target?.parentElement?.closest( '.srfm-popover-color' ) &&
				popupWrap &&
				! popupWrap?.contains( e.target ) &&
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

	// Array of all the current Typography Control's Labels.
	const attributeNames = [
		textShadowColor.label,
		textShadowHOffset.label,
		textShadowVOffset.label,
		textShadowBlur.label,
	];

	const { getSelectedBlock } = select( 'core/block-editor' );
	const blockNameForHook = getSelectedBlock()?.name.split( '/' ).pop(); // eslint-disable-line @wordpress/no-unused-vars-before-return
	useEffect( () => {
		setPanelNameForHook( getPanelIdFromRef( panelRef ) );
	}, [ blockNameForHook ] );

	// Function to get the Block's default Text Shadow Values.
	const getBlockTextShadowValue = () => {
		const selectedBlockName = getSelectedBlock()?.name.split( '/' ).pop();
		let defaultValues = false;
		if ( 'undefined' !== typeof blocksAttributes[ selectedBlockName ] ) {
			attributeNames.forEach( ( attributeName ) => {
				if ( attributeName ) {
					const blockDefaultAttributeValue =
						'undefined' !==
						typeof blocksAttributes[ selectedBlockName ][
							attributeName
						]?.default
							? blocksAttributes[ selectedBlockName ][
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

	// Function to check if any Text Shadow Setting has changed.
	const getUpdateState = () => {
		const defaultValues = getBlockTextShadowValue();
		const selectedBlockAttributes = getSelectedBlock()?.attributes;
		let isTextShadowUpdated = false;
		attributeNames.forEach( ( attributeName ) => {
			if (
				selectedBlockAttributes?.[ attributeName ] &&
				selectedBlockAttributes?.[ attributeName ] !==
					defaultValues?.[ attributeName ]
			) {
				isTextShadowUpdated = true;
			}
		} );
		return isTextShadowUpdated;
	};

	// Flag to check if this control has been updated or not.
	const isTextShadowUpdated = popup && getUpdateState();

	const overallControls = (
		<>
			{ /* Shadow Color */ }
			<AdvancedPopColorControl
				label={ textShadowColor.title }
				colorValue={ textShadowColor.value }
				data={ {
					value: textShadowColor.value,
					label: textShadowColor.label,
				} }
				setAttributes={ setAttributes }
			/>
			{ /* Horizontal */ }
			<Range
				label={ textShadowHOffset.title }
				value={ textShadowHOffset.value }
				min={ -100 }
				max={ 100 }
				displayUnit={ false }
				setAttributes={ setAttributes }
				data={ {
					value: textShadowHOffset.value,
					label: textShadowHOffset.label,
				} }
			/>
			{ /* Vertical */ }
			<Range
				label={ textShadowVOffset.title }
				value={ textShadowVOffset.value }
				min={ -100 }
				max={ 100 }
				displayUnit={ false }
				setAttributes={ setAttributes }
				data={ {
					value: textShadowVOffset.value,
					label: textShadowVOffset.label,
				} }
			/>
			{ /* Blur */ }
			<Range
				label={ textShadowBlur.title }
				value={ textShadowBlur.value }
				min={ 0 }
				max={ 100 }
				displayUnit={ false }
				setAttributes={ setAttributes }
				data={ {
					value: textShadowBlur.value,
					label: textShadowBlur.label,
				} }
			/>
		</>
	);

	if ( showAdvancedControls ) {
		advancedControls = (
			<div className="srfm-text-shadow-advanced srfm-control-popup">
				{ overallControls }
			</div>
		);
	}

	const textShadowAdvancedControls = (
		<div className="srfm-control-popup__options--action-wrapper">
			<span className="srfm-control-label">
				{ label }
				{ isTextShadowUpdated && (
					<div className="srfm__change-indicator--dot-right" />
				) }
			</span>
			<Button
				className="srfm-text-shadow-button srfm-control-popup__options--action-button"
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
							selectedSetting: '.srfm-text-shadow-options',
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
		<div
			ref={ panelRef }
			className={ popup ? 'components-base-control' : '' }
		>
			{ controlBeforeDomElement }
			{ popup ? (
				<div
					className={ ` srfm-text-shadow-options srfm-control-popup__options popup-${ blockId } ${ activeClass }` }
				>
					{ textShadowAdvancedControls }
					{ showAdvancedControls && advancedControls }
				</div>
			) : (
				<>{ overallControls }</>
			) }
			<SRFMHelpText text={ help } />
			{ controlAfterDomElement }
		</div>
	);
};

export default TextShadowControl;
