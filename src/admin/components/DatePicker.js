import {
	useEffect,
	useLayoutEffect,
	useRef,
	useState,
} from '@wordpress/element';
import { DatePicker as FUIDatePicker } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';

/**
 * DatePicker component.
 *
 * @typedef {Object} TriggerArgs
 * @property {boolean}                                            show            Whether the picker is visible.
 * @property {(value:boolean|((prev:boolean)=>boolean))=>void}    setShow         Setter for show (accepts boolean or updater).
 *
 * @param    {Object}                                             props           Component props.
 * @param    {'bottom-start'|'bottom-end'|'top-start'|'top-end'}  props.placement Placement of the date picker relative to the trigger. Options: 'bottom-start', 'bottom-end', 'top-start', 'top-end'. Default is 'bottom-end'.
 * @param    {JSX.Element|((args:TriggerArgs)=>JSX.Element)|null} props.trigger   The element or function that triggers the date picker. If a function is provided, it receives an object with `show` and `setShow` properties.
 * @param    {Function}                                           props.onApply   Callback function when the date range is applied. Receives the selected date range as an argument.
 * @param    {{from: Date|null, to: Date|null}}                   props.value     The currently selected date range. Should have `from` and `to` properties.
 * @return {JSX.Element} DatePicker component.
 */
const DatePicker = ( props ) => {
	const {
		placement = 'bottom-end',
		trigger = null,
		onApply,
		value = { from: null, to: null },
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

	const handleApply = () => {
		if ( typeof onApply === 'function' ) {
			onApply( selectedDate );
		}
		setShow( false );
	};

	// Update selected date when Datepicker opens.
	useLayoutEffect( () => {
		if ( show ) {
			setSelectedDate( value );
		}
	}, [ show ] );

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

	return (
		<div className="relative" ref={ container }>
			{ typeof trigger === 'function'
				? trigger( { show, setShow } )
				: trigger }
			{ show && (
				<div className={ cn( 'absolute z-10', placementClassName ) }>
					<FUIDatePicker
						{ ...props }
						onCancel={ handleCancel }
						variant="dualdate"
						selectionType="range"
						onDateSelect={ handleSelect }
						selected={ selectedDate }
						onApply={ handleApply }
					/>
				</div>
			) }
		</div>
	);
};

export default DatePicker;
