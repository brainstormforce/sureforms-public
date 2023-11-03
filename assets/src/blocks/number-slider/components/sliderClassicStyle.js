import { useState, useEffect } from '@wordpress/element';
import { RichText } from '@wordpress/block-editor';

export const SliderClassicStyle = ( {
	attributes,
	blockID,
	sureforms_keys,
	setAttributes,
} ) => {
	const { label, placeholder, min, max, step } = attributes;
	const [ slideValue, setSlideValue ] = useState( 0 );
	const [ bgValue, setBgValue ] = useState( 0 );
	let color = sureforms_keys._srfm_color1;
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
			className={ `srfm-number-slider-container srfm-classic-number-slider srfm-classic-inputs-holder` }
		>
			<div className="range-slider-container">
				<div className="range-slider-block">
					<div
						id="srfm-range-sliders"
						className="srfm-range-sliders w-full"
					>
						<div className="range-slider-group range-slider-group-sf">
							<RichText
								tagName="label"
								value={ label }
								onChange={ ( value ) =>
									setAttributes( { label: value } )
								}
								className={ `srfm-classic-label-text` }
								multiline={ false }
								id={ blockID }
							/>
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
									className="range-slider srfm-range-slider !border-solid !border !border-[#d1d5db]"
								/>
								<input
									type="number"
									min={ parseInt( min ) }
									max={ parseInt( max ) }
									value={ slideValue }
									onChange={ handleChange }
									className="input-slider srfm-number-input-slider !w-[60px] !border-solid !border-[1px] !border-[#D1D5DB] !rounded-md !px-2 !py-1 !text-center !bg-white focus:!border-srfm_primary_color focus:!ring-srfm_primary_color focus:!outline-0 focus:!bg-white sm:text-sm sm:leading-6 !shadow-none"
								/>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};
