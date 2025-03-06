/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { Button, Dashicon } from '@wordpress/components';
import { useState } from '@wordpress/element';
import SRFMHelpText from '@Components/help-text';
import Range from '@Components/range/Range';

const TextStylesControl = ( props ) => {
	const { textSize, textSizeUnit, lineHeight, lineHeightUnit, setAttributes, help = false } = props;
	const [ isPopUpActive, setIsPopUpActive ] = useState( false );
	const activeClass = isPopUpActive ? 'srfm-active' : '';
	const defaultUnits = [
		{
			name: __( 'Pixel', 'sureforms-pro' ),
			unitValue: 'px',
		},
		{
			name: __( 'EM', 'sureforms-pro' ),
			unitValue: 'em',
		},
	];
	const popUpActionWrapper = () => {
		return (
			<div className="srfm-control-popup__options--action-wrapper">
				<span className="srfm-control-label">
					{ props?.label }
				</span>
				<Button
					onClick={ () => setIsPopUpActive( ( prev ) => ! prev ) }
					variant="secondary"
					className="srfm-control-popup__edit-button components-button srfm-control-popup__options--action-button"
				>
					<Dashicon icon="edit" />
				</Button>
			</div>
		);
	};
	const popUpContent = () => {
		return (
			<div className="srfm-control-popup srfm-control-popup__content">
				<div>
					<Range
						label={ __( 'Font Size', 'sureforms-pro' ) }
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
						displayUnit={ props?.displayUnit ? props.displayUnit : true }
						isFormSpecific={ props?.isFormSpecific ? props.isFormSpecific : true }
						setAttributes={ setAttributes }
						onChange={ ( value ) => setAttributes( { [ textSize?.label ]: value } ) }
					/>
				</div>
				<div>
					<Range
						label={ __( 'Line Height', 'sureforms-pro' ) }
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
						displayUnit={ props?.displayUnit ? props.displayUnit : true }
						isFormSpecific={ props?.isFormSpecific ? props.isFormSpecific : true }
						setAttributes={ setAttributes }
						onChange={ ( value ) => setAttributes( { [ lineHeight?.label ]: value } ) }
					/>
				</div>
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
