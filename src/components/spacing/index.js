/**
 * External dependencies
 */
import styles from './editor.lazy.scss';
import { useLayoutEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { ButtonGroup, Button, Tooltip } from '@wordpress/components';
import SRFMReset from '../reset';
import SRFMHelpText from '@Components/help-text';

const Spacing = ( props ) => {
	// Add and remove the CSS on the drop and remove of the component.
	useLayoutEffect( () => {
		styles.use();
		return () => {
			styles.unuse();
		};
	}, [] );

	const {
		label,
		unit,
		valueBottom,
		valueLeft,
		valueRight,
		valueTop,
		link,
		setAttributes,
		help = false,
		min = 0,
		disableUnits = false,
	} = props;
	const inputs = [ valueTop, valueRight, valueBottom, valueLeft ];

	const onChangeUnits = ( value ) => {
		setAttributes( { [ unit.label ]: value.unitValue } );
	};

	const onChangeValue = ( event, value = '', valueLabel ) => {
		let newValue = value;
		if ( '' === value && '' !== event ) {
			newValue =
				event.target.value === '' ? 0 : Number( event.target.value );
		}
		if ( link.value ) {
			setAttributes( {
				[ valueTop.label ]: newValue,
				[ valueRight.label ]: newValue,
				[ valueBottom.label ]: newValue,
				[ valueLeft.label ]: newValue,
			} );
		} else {
			setAttributes( { [ valueLabel ]: newValue } );
		}
	};

	let unitSizes = [
		{
			name: __( 'Pixel', 'sureforms' ),
			unitValue: 'px',
		},
		{
			name: __( 'Em', 'sureforms' ),
			unitValue: 'em',
		},
		{
			name: __( '%', 'sureforms' ),
			unitValue: '%',
		},
	];
	if ( props.units ) {
		unitSizes = props.units;
	}

	const onUnitSizeClick = ( uSizes ) => {
		const items = [];
		uSizes.map( ( key ) =>
			items.push(
				<Tooltip
					text={ sprintf(
						/* translators: abbreviation for units */
						__( '%s units', 'sureforms' ),
						key.name
					) }
				>
					<Button
						key={ key.unitValue }
						className={ 'srfm-range-control__units--' + key.name }
						isSmall
						isPrimary={ unit.value === key.unitValue }
						isSecondary={ unit.value !== key.unitValue }
						aria-pressed={ unit.value === key.unitValue }
						aria-label={ sprintf(
							/* translators: abbreviation for units */
							__( '%s units', 'sureforms' ),
							key.name
						) }
						onClick={ () => onChangeUnits( key ) }
					>
						{ key.unitValue }
					</Button>
				</Tooltip>
			)
		);

		return items;
	};

	let linkHtml = null;

	if ( link && link.value ) {
		linkHtml = (
			<span // eslint-disable-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions
				className="srfm-spacing-control__link srfm-spacing-control-connected dashicons dashicons-admin-links "
				onClick={ () => {
					setAttributes( {
						[ valueTop.label ]: valueTop.value || 0,
						[ valueRight.label ]: valueRight.value || 0,
						[ valueBottom.label ]: valueBottom.value || 0,
						[ valueLeft.label ]: valueLeft.value || 0,
						[ link.label ]: false,
					} );
				} }
			></span>
		);
	} else if ( link ) {
		linkHtml = (
			<span // eslint-disable-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions
				className="srfm-spacing-control__link srfm-spacing-control-disconnected dashicons dashicons-editor-unlink"
				onClick={ () => {
					setAttributes( {
						[ valueTop.label ]: valueTop.value,
						[ valueRight.label ]: valueTop.value,
						[ valueBottom.label ]: valueTop.value,
						[ valueLeft.label ]: valueTop.value,
						[ link.label ]: true,
					} );
				} }
			></span>
		);
	}

	const resetValues = ( defaultValues ) => {
		const updatedAttributes = {};

		inputs.forEach( ( input ) => {
			updatedAttributes[ input.label ] = defaultValues[ input.label ];
		} );

		updatedAttributes[ unit?.label ] = defaultValues[ unit?.label ];
		updatedAttributes[ link?.label ] = defaultValues[ link?.label ];

		setAttributes( updatedAttributes );
	};

	return (
		<div className="components-base-control">
			<div className="srfm-spacing-control">
				<div className="srfm-size-type-field-tabs">
					<div className="srfm-control__header">
						<span className='srfm-control-label'>
							{ label }
						</span>
						<div className="srfm-control__actions">
							<SRFMReset
								onReset={ resetValues }
								attributeNames={ [
									valueTop?.label,
									valueRight?.label,
									valueBottom?.label,
									valueLeft?.label,
									unit?.label,
									link?.label,
								] }
								isFormSpecific={ true }
								setAttributes={ setAttributes }
								isValueArray={ true } // New: Added to compare array values.
								value={ [
									valueTop?.value,
									valueRight?.value,
									valueBottom?.value,
									valueLeft?.value,
									unit?.value,
									link?.value,
								] }
							/>
							<ButtonGroup
								className="srfm-control__units"
								aria-label={ __( 'Select Units', 'sureforms' ) }
							>
								{ ! disableUnits &&
									onUnitSizeClick( unitSizes ) }
							</ButtonGroup>
						</div>
					</div>
					{
						<>
							<div className="srfm-spacing-control__inputs">
								{ inputs.map( ( input, index ) => (
									<input
										key={ index }
										className="srfm-spacing-control__number"
										type="number"
										min={ min }
										onChange={ ( e ) =>
											onChangeValue( e, '', input.label )
										}
										value={
											undefined !== input.value
												? input.value
												: ''
										}
									/>
								) ) }
								{ linkHtml }
							</div>
						</>
					}
					<div className="srfm-spacing-control__input-labels">
						<span className="srfm-spacing-control__number-label">
							{ __( 'Top', 'sureforms' ) }
						</span>
						<span className="srfm-spacing-control__number-label">
							{ __( 'Right', 'sureforms' ) }
						</span>
						<span className="srfm-spacing-control__number-label">
							{ __( 'Bottom', 'sureforms' ) }
						</span>
						<span className="srfm-spacing-control__number-label">
							{ __( 'Left', 'sureforms' ) }
						</span>
						<span className="srfm-spacing-control__number-label srfm-spacing-control__link-label"></span>
					</div>
				</div>
				<SRFMHelpText text={ help } />
			</div>
		</div>
	);
};

export default Spacing;
