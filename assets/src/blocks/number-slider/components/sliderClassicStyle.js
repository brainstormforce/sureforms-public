import { useState, useEffect } from '@wordpress/element';

export const SliderClassicStyle = ( {
	attributes,
	blockID,
	sureforms_keys,
} ) => {
	const { label, placeholder, min, max, step } = attributes;
	const [ slideValue, setSlideValue ] = useState( 0 );
	const [ bgValue, setBgValue ] = useState( 0 );
	let color = sureforms_keys._sureforms_color1;
	if ( color === '' ) {
		color = '#0284C7';
	}
	const handleChange = ( e ) => {
		const currentValue = Number( e.target.value );
		const valBg = ( ( currentValue - min ) / ( max - min ) ) * 100;
		setBgValue( valBg );
		setSlideValue( e.target.value );
	};

	useEffect( () => {
		setSlideValue( min );
	}, [ min ] );

	return (
		<div
			className={ `sureforms-number-slider-container sf-classic-number-slider sf-classic-inputs-holder` }
		>
			<div className="range-slider-container">
				<div className="range-slider-block">
					<div id="range-sliders" className="range-sliders w-full">
						<div className="range-slider-group range-slider-group-sf">
							<label
								htmlFor="range-slider-sf"
								className="sf-classic-label-text"
							>
								{ label }
							</label>
							<div className="flex justify-between items-center">
								<input
									id={ 'number-slider-input-' + blockID }
									type="range"
									min={ min }
									max={ max }
									step={ step }
									value={ slideValue }
									style={ {
										background: `linear-gradient(to right, ${ color } 0%, ${ color } ${ bgValue }%, rgb(255, 255, 255) ${ bgValue }%, white 100%)`,
									} }
									onChange={ handleChange }
									placeholder={ placeholder }
									className="range-slider range-slider-sf !border-solid !border !border-[#d1d5db]"
								/>
								<input
									type="number"
									min={ parseInt( min ) }
									max={ parseInt( max ) }
									value={ slideValue }
									onChange={ handleChange }
									className="input-slider number-input-slider-sf !w-[60px] !border-solid !border-[1px] !border-[#D1D5DB] !rounded-md !px-2 !py-1 !text-center !bg-white focus:!border-sf_primary_color focus:!ring-sf_primary_color focus:!outline-0 focus:!bg-white sm:text-sm sm:leading-6 !shadow-none"
								/>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};
