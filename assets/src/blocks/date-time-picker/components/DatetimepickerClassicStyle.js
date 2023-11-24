import { useState, useEffect } from '@wordpress/element';
import { RichText } from '@wordpress/block-editor';

export const DatetimepickerClassicStyle = ( {
	attributes,
	setAttributes,
	blockID,
} ) => {
	const { label, required, fieldType } = attributes;
	const [ dateTimeType, setDateTimeType ] = useState(
		'srfm-input-date-time'
	);

	const isRequired = required ? 'srfm-required' : '';

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
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-classic-label-text ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
			<div className="srfm-classic-date-time-picker srfm-relative srfm-mt-2 srfm-rounded-md srfm-shadow-sm datepicker-with-limits">
				<div className="srfm-pointer-events-none srfm-absolute srfm-inset-y-0 srfm-right-0 srfm-flex srfm-items-center srfm-pr-3">
					{ 'time' === fieldType ? (
						<i className="fa-solid fa-clock srfm-text-gray-400 srfm-text-[20px]"></i>
					) : (
						<i className="fa-regular fa-calendar srfm-text-gray-400 srfm-text-[20px]"></i>
					) }
				</div>
				<input
					id={ 'srfm-text-input-' + blockID }
					type="text"
					className={ `srfm-classic-email-element ${ dateTimeType }` }
					required={ required }
					placeholder="Choose ..."
				/>
			</div>
		</>
	);
};
