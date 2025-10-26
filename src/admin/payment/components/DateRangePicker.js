import { useState, useRef } from '@wordpress/element';
import { Input, DatePicker } from '@bsf/force-ui';
import { Calendar } from 'lucide-react';
import { __ } from '@wordpress/i18n';
import { getSelectedDateRange } from './utils';

/**
 * DateRangePicker component for selecting date ranges.
 *
 * @param {Object}   props               - Component props.
 * @param {Object}   props.selectedDates - Selected date range object with 'from' and 'to'.
 * @param {Function} props.onApply       - Callback when date range is applied.
 * @param {Function} props.onCancel      - Optional callback when date selection is canceled.
 * @return {JSX.Element} The DateRangePicker component.
 */
const DateRangePicker = ( { selectedDates, onApply, onCancel } ) => {
	const [ isDatePickerOpen, setIsDatePickerOpen ] = useState( false );
	const datePickerContainerRef = useRef( null );

	const handleApply = ( dates ) => {
		setIsDatePickerOpen( false );
		onApply( dates );
	};

	const handleCancel = () => {
		setIsDatePickerOpen( false );
		if ( onCancel ) {
			onCancel();
		}
	};

	return (
		<div className="relative" ref={ datePickerContainerRef }>
			<Input
				type="text"
				size="sm"
				value={ getSelectedDateRange( selectedDates ) }
				suffix={ <Calendar className="text-icon-secondary" /> }
				onClick={ () => setIsDatePickerOpen( ! isDatePickerOpen ) }
				placeholder={ __( 'mm/dd/yyyy - mm/dd/yyyy', 'sureforms' ) }
				className="cursor-pointer w-52"
				readOnly
			/>
			{ isDatePickerOpen && (
				<div className="absolute right-0 z-10 mt-2 rounded-lg shadow-lg">
					<DatePicker
						applyButtonText={ __( 'Apply', 'sureforms' ) }
						cancelButtonText={ __( 'Cancel', 'sureforms' ) }
						selectionType="range"
						showOutsideDays={ false }
						variant="presets"
						onApply={ handleApply }
						onCancel={ handleCancel }
						selected={ selectedDates }
					/>
				</div>
			) }
		</div>
	);
};

export default DateRangePicker;
