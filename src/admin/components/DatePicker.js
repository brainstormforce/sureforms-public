import { useEffect, useRef, useState } from '@wordpress/element';
import { DatePicker as FUIDatePicker, Input } from '@bsf/force-ui';
import { Calendar } from 'lucide-react';
import { __ } from '@wordpress/i18n';
import { cn, getSelectedDate } from '@Utils/Helpers';

/**
 * DatePicker component.
 *
 * @typedef {Object} TriggerArgs
 * @property {boolean}                                            show                     Whether the picker is visible.
 * @property {(value:boolean|((prev:boolean)=>boolean))=>void}    setShow                  Setter for show (accepts boolean or updater).
 *
 * @param    {Object}                                             props                    Component props.
 * @param    {'bottom-start'|'bottom-end'|'top-start'|'top-end'}  props.placement          Placement of the date picker relative to the trigger. Options: 'bottom-start', 'bottom-end', 'top-start', 'top-end'. Default is 'bottom-end'.
 * @param    {JSX.Element|((args:TriggerArgs)=>JSX.Element)|null} props.trigger            The element or function that triggers the date picker. If null, uses default Input trigger.
 * @param    {Function}                                           props.onApply            Callback function when the date range is applied. Receives the selected date range as an argument.
 * @param    {{from: Date|null, to: Date|null}}                   props.value              The currently selected date range. Should have `from` and `to` properties.
 * @param    {string}                                             props.triggerPlaceholder Placeholder text for default Input trigger.
 * @return {JSX.Element} DatePicker component.
 */
const DatePicker = ( props ) => {
	const {
		placement = 'bottom-end',
		trigger = null,
		onApply,
		value = { from: null, to: null },
		triggerPlaceholder = __( 'mm/dd/yyyy - mm/dd/yyyy', 'sureforms' ),
		...restProps
	} = props;

	const placementClassName =
		{
			'bottom-start': 'top-full -left-0.5 mt-2',
			'bottom-end': 'top-full -right-0.5 mt-2',
			'top-start': 'bottom-full -left-0.5 mb-2',
			'top-end': 'bottom-full -right-0.5 mb-2',
		}[ placement ] ?? 'bottom-end';

	// States
	const [ show, setShow ] = useState( false );
	const [ selectedDate, setSelectedDate ] = useState( value );
	const container = useRef();

	const handleSelect = ( date ) => {
		setSelectedDate( { from: date.from, to: date.to } );
	};

	const handleCancel = () => {
		setShow( false );
	};

	const handleApply = ( newValue ) => {
		if ( ! newValue.from || ! newValue.to ) {
			return;
		}
		if ( typeof onApply === 'function' ) {
			onApply( newValue );
		}
		setShow( false );
	};

	// Update selected date when Datepicker opens.
	useEffect( () => {
		if ( ! show ) {
			setSelectedDate( value || { from: null, to: null } );
		}
	}, [ show, value ] );

	// Handle click outside to close the date picker.
	useEffect( () => {
		const handleClickOutside = ( event ) => {
			const containerElem = container.current;
			if ( containerElem && ! containerElem.contains( event.target ) ) {
				setShow( false );
			}
		};
		document.addEventListener( 'mousedown', handleClickOutside );
		return () => {
			document.removeEventListener( 'mousedown', handleClickOutside );
		};
	}, [ show ] );

	// Default trigger component
	const defaultTrigger = ( { setShow: toggleShow } ) => (
		<Input
			type="text"
			size="sm"
			value={ getSelectedDate( value ) }
			suffix={ <Calendar className="text-icon-secondary" /> }
			onClick={ () => toggleShow( ( prev ) => ! prev ) }
			placeholder={ triggerPlaceholder }
			readOnly
			aria-label={ __( 'Select Date Range', 'sureforms' ) }
			{ ...restProps }
		/>
	);

	return (
		<div className="relative" ref={ container }>
			{ trigger === null
				? defaultTrigger( { show, setShow } )
				: typeof trigger === 'function'
					? trigger( { show, setShow } )
					: trigger }
			{ show && (
				<div
					className={ cn(
						'absolute z-10 rounded-md shadow-soft-shadow-md overflow-clip',
						placementClassName
					) }
				>
					<FUIDatePicker
						onCancel={ handleCancel }
						variant="presets"
						selectionType="range"
						onDateSelect={ handleSelect }
						selected={ selectedDate }
						onApply={ handleApply }
						disabled={ {
							after: new Date(),
						} }
					/>
				</div>
			) }
		</div>
	);
};

export default DatePicker;
