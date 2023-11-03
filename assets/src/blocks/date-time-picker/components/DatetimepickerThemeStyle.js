import { RichText } from '@wordpress/block-editor';

export const DatetimepickerThemeStyle = ( {
	attributes,
	blockID,
	setAttributes,
} ) => {
	const { label, required, fieldType, min, max, className } = attributes;
	const isRequired = required ? 'required' : '';

	return (
		<>
			<RichText
				tagName="label"
				value={ label }
				onChange={ ( value ) => setAttributes( { label: value } ) }
				className={ `srfm-text-primary ${ isRequired }` }
				multiline={ false }
				id={ blockID }
			/>
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
