/* eslint-disable no-mixed-operators */

/**
 * Advanced Color Control.
 *
 */
import cIcons from './uagb-color-icons';
import maybeGetColorForVariable from '@Controls/maybeGetColorForVariable';
import { __ } from '@wordpress/i18n';
import {
	Button,
	Popover,
	ColorIndicator,
	Tooltip,
	Dashicon,
	ColorPicker,
	ColorPalette,
} from '@wordpress/components';
import { useSelect, select } from '@wordpress/data';
import styles from './editor.lazy.scss';
import {
	useLayoutEffect,
	useEffect,
	useState,
	useRef,
} from '@wordpress/element';
import { getIdFromString, getPanelIdFromRef } from '@Utils/Helpers';
import SRFMReset from '../reset';
import { applyFilters } from '@wordpress/hooks';

const AdvancedPopColorControl = ( props ) => {
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

	// eslint-disable-next-line no-shadow
	const { colors } = useSelect( ( select ) => {
		const settings = select( 'core/block-editor' ).getSettings();
		return { colors: settings.colors };
	} );

	const {
		alpha,
		colorValue,
		opacityValue,
		backgroundVideoOpacity,
		onOpacityChange,
		data,
		setAttributes,
		onColorChange,
		label,
		help,
		isFormSpecific = false,
	} = props;

	const [ value, setValue ] = useState( {
		alpha: false === alpha ? false : true,
		colors: [],
		classSat: 'first',
		currentColor: colorValue,
		inherit: false,
		currentOpacity: opacityValue !== undefined ? opacityValue : 1,
		isPalette:
			colorValue && colorValue.startsWith( 'palette' ) ? true : false,
		refresh: false,
	} );
	const [ visible, setVisible ] = useState( { isVisible: false } );

	useEffect( () => {
		onChangeComplete( colorValue, '' );
	}, [ colorValue ] );

	const onChangeComplete = ( color, palette ) => {
		let opacity = backgroundVideoOpacity?.value;
		let newColor;
		if ( palette ) {
			newColor = color;
		} else if ( color?.rgb && color?.rgb?.a && 1 !== color?.rgb?.a ) {
			if ( onOpacityChange ) {
				opacity = color?.rgb?.a;
			}

			newColor =
				'rgba(' +
				color?.rgb?.r +
				',' +
				color?.rgb?.g +
				',' +
				color?.rgb?.b +
				',' +
				color?.rgb?.a +
				')';
		} else if ( color?.hex ) {
			newColor = color?.hex;
		} else {
			newColor = color;
		}

		setValue( {
			currentColor: newColor,
			currentOpacity: opacity,
			isPalette: palette ? true : false,
		} );

		if ( true === palette ) {
			setValue( {
				refresh: ! value?.refresh,
			} );
		}

		if ( data && setAttributes ) {
			setAttributes( { [ data?.label ]: newColor } );
		}
		if ( onColorChange ) {
			onColorChange( newColor );
		}

		if ( onOpacityChange ) {
			onOpacityChange( opacity );
		}
	};

	const toggleVisible = () => {
		setVisible( { isVisible: true } );
	};

	const toggleClose = () => {
		if ( visible.isVisible === true ) {
			setVisible( { isVisible: false } );
		}
	};

	const resetValues = ( resetValue ) => {
		if ( isFormSpecific ) {
			if ( props?.onColorChange ) {
				props?.onColorChange( resetValue[ props?.data?.label ] );
			}
		}
		setValue( {
			...value,
			currentColor: resetValue[ data?.label ],
		} );
	};

	const colorVal = value.currentColor ? value.currentColor : colorValue;

	const pickIconColorBasedOnBgColorAdvanced = ( color ) => {
		const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(
			color
		);
		const parsedColor = result
			? {
					r: parseInt( result[ 1 ], 16 ),
					g: parseInt( result[ 2 ], 16 ),
					b: parseInt( result[ 3 ], 16 ),
			  }
			: null;
		if ( parsedColor ) {
			const brightness = Math.round(
				( parsedColor.r * 299 +
					parsedColor.g * 587 +
					parsedColor.b * 114 ) /
					1000
			);
			const textColour = brightness > 125 ? 'black' : 'white';
			return textColour;
		}
		return 'white';
	};
	const globalIconColor = pickIconColorBasedOnBgColorAdvanced(
		maybeGetColorForVariable( colorVal )
	);

	const globalIndicator =
		colorVal && colorVal.includes( 'var' )
			? `srfm-global-indicator srfm-global-icon-${ globalIconColor }`
			: '';

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
		<div ref={ panelRef } className="components-base-control">
			{ controlBeforeDomElement }
			<div className="srfm-color-popover-container new-srfm-advanced-colors">
				<div className="srfm-advanced-color-settings-container">
					{ label && (
						<span className="srfm-beside-color-label srfm-control-label">
							{ label }
						</span>
					) }
					<SRFMReset
						onReset={ resetValues }
						attributeNames={ [ data?.label ] }
						setAttributes={ setAttributes }
						isFormSpecific={ isFormSpecific }
						value={ props?.value }
					/>
					<div className="srfm-beside-color-click">
						{ visible.isVisible && (
							<Popover
								position="top left"
								className="srfm-popover-color new-srfm-advanced-colors-pop"
								onClose={ toggleClose }
							>
								{ value.refresh && (
									<>
										<ColorPicker
											color={ maybeGetColorForVariable(
												colorVal
											) }
											onChangeComplete={ ( color ) =>
												onChangeComplete( color, '' )
											}
										/>
									</>
								) }
								{ ! value.refresh && (
									<>
										<ColorPicker
											color={ maybeGetColorForVariable(
												colorVal
											) }
											onChangeComplete={ ( color ) =>
												onChangeComplete( color, '' )
											}
										/>
									</>
								) }
								{ colors && (
									<ColorPalette
										color={ colorVal }
										colors={ colors }
										onChange={ ( color ) =>
											onChangeComplete( color, true )
										}
										clearable={ false }
										disableCustomColors={ true }
									/>
								) }
								<button
									type="button"
									onClick={ () => {
										onChangeComplete( '', true );
									} }
									className="srfm-clear-btn-inside-picker components-button components-circular-option-picker__clear is-secondary is-small"
								>
									{ __( 'Clear', 'sureforms' ) }
								</button>
							</Popover>
						) }
						{ visible.isVisible && (
							<Tooltip text={ __( 'Select Color', 'sureforms' ) }>
								<Button
									className={ `srfm-color-icon-indicate srfm-has-alpha` }
									onClick={ toggleClose }
								>
									<ColorIndicator
										className={ `srfm-advanced-color-indicate ${ globalIndicator }` }
										colorValue={ colorVal }
									/>
									{ '' === colorVal && value.inherit && (
										<span className="color-indicator-icon">
											{ cIcons.inherit }
										</span>
									) }
									{ colorValue &&
										colorValue.startsWith( 'palette' ) && (
											<span className="color-indicator-icon">
												{
													<Dashicon icon="admin-site" />
												}
											</span>
										) }
								</Button>
							</Tooltip>
						) }
						{ ! visible.isVisible && (
							<Tooltip text={ __( 'Select Color', 'sureforms' ) }>
								<Button
									className={ `srfm-color-icon-indicate srfm-has-alpha` }
									onClick={ toggleVisible }
								>
									<ColorIndicator
										className={ `srfm-advanced-color-indicate ${ globalIndicator }` }
										colorValue={ colorVal }
									/>
									{ '' === colorVal && value.inherit && (
										<span className="color-indicator-icon">
											{ cIcons.inherit }
										</span>
									) }
									{ colorValue &&
										colorValue.startsWith( 'palette' ) && (
											<span className="color-indicator-icon">
												{
													<Dashicon icon="admin-site" />
												}
											</span>
										) }
								</Button>
							</Tooltip>
						) }
					</div>
				</div>
				{ help && (
					<p className="components-base-control__help">{ help }</p>
				) }
			</div>
			{ controlAfterDomElement }
		</div>
	);
};

export default AdvancedPopColorControl;
