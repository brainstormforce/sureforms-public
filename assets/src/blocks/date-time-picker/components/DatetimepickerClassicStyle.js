import { useState, useEffect } from '@wordpress/element';

export const DatetimepickerClassicStyle = ( { attributes, blockID } ) => {
	const { label, required, fieldType } = attributes;
	const [ dateTimeType, setDateTimeType ] = useState(
		'srfm-input-date-time'
	);

	function dateTimeSelected( type ) {
		if ( 'time' === type ) {
			setDateTimeType( 'srfm-input-time' );
			setTimeout( () => {
				/* eslint-disable no-undef */
				flatpickr( '.srfm-input-time', {
					enableTime: true,
					noCalendar: true,
					dateFormat: 'H:i',
				} );
				/* eslint-enable no-undef */
			}, 500 );
		}
		if ( 'date' === type ) {
			setDateTimeType( 'srfm-input-date' );
			setTimeout( () => {
				/* eslint-disable no-undef */
				flatpickr( '.srfm-input-date' );
				/* eslint-enable no-undef */
			}, 500 );
		}

		if ( 'dateTime' === type ) {
			setDateTimeType( 'srfm-input-date-time' );
			setTimeout( () => {
				/* eslint-disable no-undef */
				flatpickr( '.srfm-input-date-time', {
					enableTime: true,
					dateFormat: 'Y-m-d H:i',
				} );
				/* eslint-enable no-undef */
			}, 500 );
		}
	}

	useEffect( () => {
		dateTimeSelected( fieldType );
	}, [ fieldType ] );

	return (
		<>
			<label
				className="srfm-classic-label-text"
				htmlFor={ 'srfm-input-date-' + blockID }
			>
				{ label }
				{ required && label && (
					<span className="text-red-500"> *</span>
				) }
			</label>
			<div className="srfm-classic-date-time-picker relative mt-2 rounded-md shadow-sm datepicker-with-limits">
				<div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
					{ 'time' === fieldType ? (
						<i className="fa-solid fa-clock text-gray-400 text-[20px]"></i>
					) : (
						<i className="fa-regular fa-calendar text-gray-400 text-[20px]"></i>
					) }
				</div>
				<input
					id={ 'srfm-text-input-' + blockID }
					type="text"
					className={ `srfm-classic-email-element ${ dateTimeType }` }
					required={ required }
				/>
			</div>
		</>
	);
};
