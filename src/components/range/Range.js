import {
	ButtonGroup,
	Button,
	Tooltip,
	RangeControl,
	__experimentalNumberControl as NumberControl,
} from '@wordpress/components';
import ResponsiveToggle from '../responsive-toggle';
import { __, sprintf } from '@wordpress/i18n';
import styles from './editor.lazy.scss';
import {
	useLayoutEffect,
	useEffect,
	useState,
	useRef,
} from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { select } from '@wordpress/data';
import { limitMax, limitMin } from '@Controls/unitWiseMinMaxOption';
import { getIdFromString, getPanelIdFromRef } from '@Utils/Helpers';
import SRFMReset from '../reset';
import SRFMHelpText from '@Components/help-text';

const isNumberControlSupported = !! NumberControl;

const Range = ( props ) => {
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

	const { withInputField, isShiftStepEnabled } = props;

	let max = limitMax( props.unit?.value, props );
	let min = limitMin( props.unit?.value, props );
	const inputValue = isNaN( props?.value ) ? '' : props?.value;

	let unitSizes = [
		{
			name: __( 'Pixel', 'sureforms' ),
			unitValue: 'px',
		},
		{
			name: __( 'Em', 'sureforms' ),
			unitValue: 'em',
		},
	];

	if ( props.units ) {
		unitSizes = props.units;
	}

	const handleOnChange = ( newValue ) => {
		const parsedValue = parseFloat( newValue );
		if ( props.setAttributes ) {
			props.setAttributes( {
				[ props.data.label ]: parsedValue,
			} );
		}
		if ( props?.onChange ) {
			props.onChange( parsedValue );
		}
	};

	const resetValues = ( defaultValues ) => {
		const newAttributes = {
			[ props.data.label ]: defaultValues[ props?.data?.label ],
		}
		if ( props?.onChange ) {
			props?.onChange( defaultValues[ props?.data?.label ] );
		}
		if ( props.displayUnit ) {
			newAttributes[ props.unit.label ] = defaultValues[ props?.unit?.label ];
		}
		if ( props?.setAttributes ) {
			props.setAttributes( newAttributes );
		}
	};

	const onChangeUnits = ( newValue ) => {
		if ( props.setAttributes ) {
			props.setAttributes( { [ props.unit.label ]: newValue } );
		}

		max = limitMax( newValue, props );
		min = limitMin( newValue, props );

		if ( props.value > max ) {
			handleOnChange( max );
		}
		if ( props.value < min ) {
			handleOnChange( min );
		}
	};

	const onUnitSizeClick = ( uSizes ) => {
		const items = [];
		uSizes.map( ( key ) =>
			items.push(
				<Tooltip
					text={ sprintf(
						/* translators: abbreviation for units */
						__( '%s units', 'sureforms' ),
						key.name
					) }
					key={ key.name }
				>
					<Button
						key={ key.unitValue }
						className={ 'srfm-range-control__units--' + key.name }
						isSmall
						isPrimary={ props.unit.value === key.unitValue }
						isSecondary={ props.unit.value !== key.unitValue }
						aria-pressed={ props.unit.value === key.unitValue }
						aria-label={ sprintf(
							/* translators: abbreviation for units */
							__( '%s units', 'sureforms' ),
							key.name
						) }
						onClick={ () => onChangeUnits( key.unitValue ) }
					>
						{ key.unitValue }
					</Button>
				</Tooltip>
			)
		);

		return items;
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
		<div ref={ panelRef } className="components-base-control">
			{ controlBeforeDomElement }
			<div className="srfm-range-control srfm-size-type-field-tabs">
				<div className="srfm-control__header">
					<ResponsiveToggle
						label={ props.label }
						responsive={ props.responsive }
					/>
					<div className="srfm-range-control__actions srfm-control__actions">
						{ props?.allowReset && (
							<SRFMReset
								onReset={ resetValues }
								attributeNames={ [
									props.data.label,
									props.displayUnit
										? props.unit.label
										: false,
								] }
								setAttributes={ props?.setAttributes }
								isFormSpecific={ props?.isFormSpecific }
								isValueArray={ true }
								value={ [
									props?.value,
									props.displayUnit ? props.unit.value : '',
								] }
							/>
						) }
						{ props.displayUnit && (
							<ButtonGroup
								className="srfm-control__units"
								aria-label={ __( 'Select Units', 'sureforms' ) }
							>
								{ onUnitSizeClick( unitSizes ) }
							</ButtonGroup>
						) }
					</div>
				</div>
				<div className="srfm-range-control__mobile-controls">
					<RangeControl
						value={ inputValue }
						onChange={ handleOnChange }
						withInputField={ false }
						allowReset={ false }
						max={ max }
						min={ min }
						step={ props?.step || 1 }
						initialPosition={ inputValue }
						marks={ props?.marks || false }
					/>
					{ withInputField && isNumberControlSupported && (
						<NumberControl
							disabled={ props.disabled }
							isShiftStepEnabled={ isShiftStepEnabled }
							max={ max }
							min={ min }
							onChange={ handleOnChange }
							value={ inputValue }
							step={ props?.step || 1 }
						/>
					) }
				</div>
				<SRFMHelpText text={ props.help } />
			</div>
			{ controlAfterDomElement }
		</div>
	);
};

Range.defaultProps = {
	label: __( 'Margin', 'sureforms' ),
	className: '',
	allowReset: true,
	withInputField: true,
	isShiftStepEnabled: true,
	max: Infinity,
	min: -Infinity,
	resetFallbackValue: '',
	placeholder: null,
	unit: [ 'px', 'em' ],
	displayUnit: true,
	responsive: false,
	help: false,
	marks: false,
	isFormSpecific: false,
};

export default Range;
