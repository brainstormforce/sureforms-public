import { useState, useEffect } from '@wordpress/element';

/**
 * RangeSlider Component
 * A customizable range slider that supports both numeric and non-numeric values
 *
 * @param {Object}   props                - Component props
 * @param {Array}    props.values         - Array of values (can be numbers, strings, or objects)
 * @param {*}        props.value          - Current selected value from the values array
 * @param {Function} props.onChange       - Callback function when value changes
 * @param {string}   props.label          - Label text for the slider
 * @param {boolean}  props.showValue      - Whether to display the current value
 * @param {Function} props.formatValue    - Optional function to format the displayed value
 * @param {string}   props.className      - Additional CSS classes
 * @param {boolean}  props.disabled       - Whether the slider is disabled
 * @param {Object}   props.labelExtractor - Optional object with 'key' property to extract label and optional 'idKey' to match by ID
 * @return {JSX.Element} RangeSlider component
 */
const RangeSlider = ( {
	values = [],
	value,
	onChange,
	label = '',
	showValue = true,
	formatValue,
	className = '',
	disabled = false,
	labelExtractor = null,
} ) => {
	// Find the index of the current value in the values array
	const getIndexFromValue = ( val ) => {
		const index = values.findIndex( ( v ) => {
			if ( typeof v === 'object' && v !== null ) {
				// If labelExtractor has an idKey, match by ID
				if ( labelExtractor?.idKey ) {
					return v[ labelExtractor.idKey ] === val;
				}
				// Otherwise match by the entire object or key
				if ( labelExtractor?.key ) {
					return v[ labelExtractor.key ] === val;
				}
				// If val is also an object, do deep comparison
				if ( typeof val === 'object' && val !== null ) {
					return JSON.stringify( v ) === JSON.stringify( val );
				}
			}
			return v === val;
		} );
		return index >= 0 ? index : 0;
	};

	const [ currentIndex, setCurrentIndex ] = useState(
		getIndexFromValue( value )
	);

	// Update index when value prop changes
	useEffect( () => {
		const newIndex = getIndexFromValue( value );
		if ( newIndex !== currentIndex ) {
			setCurrentIndex( newIndex );
		}
	}, [ value, values ] );

	// Handle slider change
	const handleChange = ( event ) => {
		const newIndex = parseInt( event.target.value, 10 );
		setCurrentIndex( newIndex );

		if ( onChange ) {
			const newValue = values[ newIndex ];
			onChange( newValue, newIndex );
		}
	};

	// Get display value
	const getDisplayValue = () => {
		const currentValue = values[ currentIndex ];

		if ( formatValue ) {
			return formatValue( currentValue, currentIndex );
		}

		if (
			typeof currentValue === 'object' &&
			currentValue !== null &&
			labelExtractor
		) {
			return currentValue[ labelExtractor.key ];
		}

		return currentValue;
	};

	// Calculate percentage for the slider fill
	const percentage =
		values.length > 1
			? ( currentIndex / ( values.length - 1 ) ) * 100
			: 0;

	return (
		<div className={ `srfm-range-slider ${ className }` }>
			{ label && (
				<div className="flex items-center justify-between mb-2">
					<label className="text-sm font-medium text-text-primary">
						{ label }
					</label>
					{ showValue && (
						<span className="text-sm font-medium text-text-secondary">
							{ getDisplayValue() }
						</span>
					) }
				</div>
			) }
			<div className="relative pb-8 overflow-visible">
				<input
					type="range"
					min={ 0 }
					max={ values.length - 1 }
					step={ 1 }
					value={ currentIndex }
					onChange={ handleChange }
					disabled={ disabled }
					className="w-full h-2 mx-0 bg-background-secondary rounded-lg appearance-none cursor-pointer disabled:cursor-not-allowed disabled:opacity-50 [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:w-4 [&::-webkit-slider-thumb]:h-4 [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:bg-brand-primary-600 [&::-webkit-slider-thumb]:cursor-pointer [&::-webkit-slider-thumb]:border [&::-webkit-slider-thumb]:border-solid [&::-webkit-slider-thumb]:border-white [&::-webkit-slider-thumb]:shadow-sm [&::-moz-range-thumb]:w-4 [&::-moz-range-thumb]:h-4 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:bg-brand-primary-600 [&::-moz-range-thumb]:cursor-pointer [&::-moz-range-thumb]:border [&::-moz-range-thumb]:border-solid [&::-moz-range-thumb]:border-white [&::-moz-range-thumb]:shadow-sm"
					style={ {
						background: `linear-gradient(to right, #DC4809 0%, #DC4809 ${ percentage }%, #E5E7EB ${ percentage }%, #E5E7EB 100%)`,
					} }
				/>
				{ /* Tick marks */ }
				<div className="flex justify-between mt-2">
					{ values.map( ( val, index ) => {
						const displayLabel =
							typeof val === 'object' &&
							val !== null &&
							labelExtractor
								? val[ labelExtractor.key ]
								: val;

						const isFirst = index === 0;
						const isLast = index === values.length - 1;

						return (
							<div
								key={ index }
								className="relative flex flex-col items-center"
							>
								<div
									className={ `w-1 h-1 rounded-full ${
										index === currentIndex
											? 'bg-brand-primary-600'
											: 'bg-border-subtle'
									}` }
								/>
								<span
									className={ `absolute top-3 text-xs mt-1 max-w-[5rem] truncate whitespace-nowrap ${
										isFirst ? 'left-0 ml-px translate-x-0 text-left' : isLast ? 'mr-px right-0 translate-x-0 text-right' : 'left-1/2 -translate-x-1/2 text-center'
									} ${
										index === currentIndex
											? 'text-text-primary font-medium'
											: 'text-text-tertiary'
									}` }
								>
									{ displayLabel }
								</span>
							</div>
						);
					} ) }
				</div>
			</div>
		</div>
	);
};

export default RangeSlider;
