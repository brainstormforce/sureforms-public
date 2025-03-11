/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import { useState } from '@wordpress/element';
import SRFMHelpText from '@Components/help-text';
import Range from '@Components/range/Range';

const TextStylesControl = ( props ) => {
	const {
		textSize,
		textSizeUnit,
		lineHeight,
		lineHeightUnit,
		setAttributes,
		help = false,
		disableFontSize = false,
		disableLineHeight = false,
	} = props;
	const [ isPopUpActive, setIsPopUpActive ] = useState( false );
	const activeClass = isPopUpActive ? 'srfm-active' : '';
	const defaultUnits = [
		{
			name: __( 'Pixel', 'sureforms' ),
			unitValue: 'px',
		},
		{
			name: __( 'EM', 'sureforms' ),
			unitValue: 'em',
		},
	];
	const popUpActionWrapper = () => {
		return (
			<div className="srfm-control-popup__options--action-wrapper">
				<span className="srfm-control-label">{ props?.label }</span>
				<Button
					onClick={ () => setIsPopUpActive( ( prev ) => ! prev ) }
					variant="secondary"
					className="srfm-control-popup__edit-button components-button srfm-control-popup__options--action-button"
					style={ { 'box-shadow': 'none' } }
				>
					<svg
						width="28"
						height="11"
						viewBox="0 0 10 11"
						fill="none"
						xmlns="http://www.w3.org/2000/svg"
					>
						<path
							d="M0.605578 6.89308L0.0147844 9.44812C-0.00559594 9.54152 -0.00489729 9.6383 0.0168293 9.7314C0.0385559 9.82449 0.0807616 9.91155 0.140363 9.98621C0.199965 10.0609 0.275457 10.1212 0.361324 10.1629C0.447191 10.2046 0.541265 10.2265 0.636672 10.2271C0.681128 10.2316 0.725923 10.2316 0.770378 10.2271L3.33567 9.63507L8.26102 4.71818L5.51227 1.96995L0.605578 6.89308Z"
							fill="#555D66"
						/>
						<path
							d="M9.80952 2.25661L7.97495 0.418229C7.85433 0.297975 7.69111 0.230469 7.52097 0.230469C7.35083 0.230469 7.18761 0.297975 7.06699 0.418229L6.0471 1.44025L8.79273 4.19159L9.81263 3.16957C9.87231 3.10946 9.91958 3.03814 9.95173 2.9597C9.98388 2.88127 10.0003 2.79724 10 2.71245C9.99971 2.62765 9.98274 2.54374 9.95005 2.46552C9.91737 2.38731 9.86961 2.31631 9.80952 2.25661Z"
							fill="#555D66"
						/>
					</svg>
				</Button>
			</div>
		);
	};
	const popUpContent = () => {
		return (
			<div className="srfm-control-popup srfm-control-popup__content">
				{ ! disableFontSize && (
					<div>
						<Range
							label={ __( 'Font Size', 'sureforms' ) }
							value={ textSize?.value }
							data={ {
								value: textSize?.value,
								label: textSize?.label,
							} }
							min={ 0 }
							max={ 200 }
							step={ textSizeUnit?.value === 'em' ? 0.1 : 1 }
							unit={ {
								value: textSizeUnit?.value,
								label: textSizeUnit?.label,
							} }
							units={ props?.units ? props.units : defaultUnits }
							displayUnit={
								props?.displayUnit ? props.displayUnit : true
							}
							isFormSpecific={
								props?.isFormSpecific ? props.isFormSpecific : true
							}
							setAttributes={ setAttributes }
						/>
					</div>
				) }
				{ ! disableLineHeight && (
					<div>
						<Range
							label={ __( 'Line Height', 'sureforms' ) }
							value={ lineHeight?.value }
							data={ {
								value: lineHeight?.value,
								label: lineHeight?.label,
							} }
							min={ 0 }
							max={ 200 }
							step={ lineHeight?.value === 'em' ? 0.1 : 1 }
							unit={ {
								value: lineHeightUnit?.value,
								label: lineHeightUnit?.label,
							} }
							units={ props?.units ? props.units : defaultUnits }
							displayUnit={
								props?.displayUnit ? props.displayUnit : true
							}
							isFormSpecific={
								props?.isFormSpecific ? props.isFormSpecific : true
							}
							setAttributes={ setAttributes }
						/>
					</div>
				) }
			</div>
		);
	};

	return (
		<div className="components-base-control">
			<div
				className={ `srfm-text-style-options srfm-control-popup__options ${ activeClass }` }
			>
				{ popUpActionWrapper() }
				{ isPopUpActive && popUpContent() }
				<SRFMHelpText text={ help } />
			</div>
		</div>
	);
};

export default TextStylesControl;
