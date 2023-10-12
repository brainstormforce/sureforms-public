export const DatetimepickerClassicStyle = ( { attributes, blockID } ) => {
	const { label, required, fieldType, min, max } = attributes;
	return (
		<>
			<label
				className="sf-classic-label-text"
				htmlFor={ 'ureforms-input-date-' + blockID }
			>
				{ label }
				{ required && label && (
					<span className="text-red-500"> *</span>
				) }
			</label>
			<input
				type="hidden"
				className="sf-min-max-holder"
				min={ min }
				max={ max }
			/>
			<input
				type="hidden"
				className="sf-classic-date-time-result"
				value=""
			/>
			<div
				className="sf-classic-date-time-picker relative mt-2 rounded-md shadow-sm datepicker-with-limits"
				data-te-input-wrapper-init
				{ ...( 'dateTime' === fieldType
					? 'data-te-date-timepicker-init'
					: fieldType === 'date'
						? 'data-te-datepicker-init'
						: fieldType === 'time'
							? 'data-te-timepicker-init'
							: 'data-te-date-timepicker-init' ) }
			>
				<input
					type="text"
					aria-required={ required ? 'true' : 'false' }
					className="sureforms-input-data-time sf-classic-datetime-picker"
					id={ `sureforms-input-time-${ blockID }` }
				/>
			</div>
		</>
	);
};
