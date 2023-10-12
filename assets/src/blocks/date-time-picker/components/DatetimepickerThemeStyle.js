export const DatetimepickerThemeStyle = ( { attributes, blockID } ) => {
	const { label, required, fieldType, min, max, className } = attributes;
	return (
		<>
			<label
				className="sf-text-primary"
				htmlFor={ 'date-picker-' + blockID }
			>
				{ label }
				{ required && label && (
					<span style={ { color: 'red' } }> *</span>
				) }
			</label>
			<div
				style={ {
					display: 'flex',
					gap: '.5rem',
				} }
			>
				{ ( () => {
					switch ( fieldType ) {
						case 'dateTime':
							return (
								<>
									<input
										id={ 'date-picker-' + blockID }
										type="date"
										className={ className }
										required={ required }
										min={ min }
										max={ max }
									/>
									<input
										id={ 'time-picker-' + blockID }
										type="time"
									/>
								</>
							);
						case 'date':
							return (
								<input
									id={ 'date-picker-' + blockID }
									type="date"
									className={ className }
									required={ required }
									min={ min }
									max={ max }
								/>
							);
						case 'time':
							return (
								<input
									id={ 'time-picker-' + blockID }
									type="time"
								/>
							);
						default:
							return (
								<>
									<input
										id={ 'date-picker-' + blockID }
										type="date"
										className={ className }
										required={ required }
									/>
									<input
										id={ 'time-picker-' + blockID }
										type="time"
									/>
								</>
							);
					}
				} )() }
			</div>
		</>
	);
};
