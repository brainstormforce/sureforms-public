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
						className="srfm-range-sliders srfm-w-full"
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
							<div className="srfm-flex srfm-justify-between srfm-items-center">
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
									className="range-slider srfm-range-slider !srfm-border-solid !srfm-border !srfm-border-[#d1d5db]"
								/>
								<input
									type="number"
									min={ parseInt( min ) }
									max={ parseInt( max ) }
									value={ slideValue }
									onChange={ handleChange }
									className="input-slider srfm-number-input-slider !srfm-w-[60px] !srfm-border-solid !srfm-border-[1px] !srfm-border-[#D1D5DB] !srfm-rounded-md !srfm-px-2 !srfm-py-1 !srfm-text-center !srfm-bg-white focus:!srfm-border-srfm_primary_color focus:!srfm-ring-srfm_primary_color focus:!srfm-outline-0 focus:!srfm-bg-white sm:srfm-text-sm sm:srfm-leading-6 !srfm-shadow-none"
								/>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	);
};
