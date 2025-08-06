import { useState } from '@wordpress/element';
import { Select } from '@bsf/force-ui';

const TimePicker = ( {
	hours = '12',
	minutes = '00',
	meridiem = 'AM',
	onChange,
} ) => {
	const [ hour, setHour ] = useState( hours );
	const [ minute, setMinute ] = useState( minutes );
	const [ mer, setMeridiem ] = useState( meridiem );

	// create array of hours from 1 to 12
	const hourOptions = Array.from( { length: 12 }, ( _, i ) =>
		String( i + 1 ).padStart( 2, '0' )
	);

	// create array of minutes from 00 to 59
	const minuteOptions = Array.from( { length: 60 }, ( _, i ) =>
		String( i ).padStart( 2, '0' )
	);

	const meridiemOptions = [ 'AM', 'PM' ];

	const handleHourChange = ( val ) => {
		setHour( val );
		onChange( 'hours', val );
	};

	const handleMinuteChange = ( val ) => {
		setMinute( val );
		onChange( 'minutes', val );
	};

	const handleMeridiemChange = ( val ) => {
		setMeridiem( val );
		onChange( 'meridiem', val );
	};

	return (
		<div className="flex items-center gap-2 mt-[1.625rem]">
			<Select value={ hour } onChange={ handleHourChange }>
				<Select.Button className="w-20" />
				<Select.Options>
					{ hourOptions.map( ( h ) => (
						<Select.Option key={ h } value={ h }>
							{ h }
						</Select.Option>
					) ) }
				</Select.Options>
			</Select>

			<span>:</span>

			<Select value={ minute } onChange={ handleMinuteChange }>
				<Select.Button className="w-20" />
				<Select.Options>
					{ minuteOptions.map( ( m ) => (
						<Select.Option key={ m } value={ m }>
							{ m }
						</Select.Option>
					) ) }
				</Select.Options>
			</Select>

			<Select value={ mer } onChange={ handleMeridiemChange }>
				<Select.Button className="w-20" />
				<Select.Options>
					{ meridiemOptions.map( ( merVal ) => (
						<Select.Option key={ merVal } value={ merVal }>
							{ merVal }
						</Select.Option>
					) ) }
				</Select.Options>
			</Select>
		</div>
	);
};

export default TimePicker;
