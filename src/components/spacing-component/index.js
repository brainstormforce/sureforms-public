/**
 * External dependencies
 */
import styles from './editor.lazy.scss';
import { useLayoutEffect } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { ButtonGroup, Button, Tooltip } from '@wordpress/components';
import ResponsiveToggle from '../responsive-toggle';
import SRFMReset from '../reset';
import SRFMHelpText from '@Components/help-text';

const SpacingComponent = ( props ) => {
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
		disableUnits,
		valueBottom,
		valueLeft,
		valueRight,
		valueTop,
		link,
		setAttributes,
		help = false,
		min = -50,
	} = props;

	const onChangeUnits = ( value ) => {
		setAttributes( { [ unit.label ]: value.unitValue } );
	};
	const changeLinkedValues = ( newValue ) => {
		setAttributes( { [ valueTop.label ]: newValue } );
		setAttributes( { [ valueRight.label ]: newValue } );
		setAttributes( { [ valueBottom.label ]: newValue } );
		setAttributes( { [ valueLeft.label ]: newValue } );
	};
	const changedUnLinkedValues = () => {
		setAttributes( {
			[ valueTop.label ]:
				'' === valueTop.value || undefined === valueTop.value
					? 0
					: valueTop.value,
		} );
		setAttributes( {
			[ valueRight.label ]:
				'' === valueRight.value ||
				undefined === valueRight.value
					? 0
					: valueRight.value,
		} );
		setAttributes( {
			[ valueBottom.label ]:
				'' === valueBottom.value ||
				undefined === valueBottom.value
					? 0
					: valueBottom.value,
		} );
		setAttributes( {
			[ valueLeft.label ]:
				'' === valueLeft.value || undefined === valueLeft.value
					? 0
					: valueLeft.value,
		} );
	};
	const onChangeTopValue = (
		event,
		value = '',
		resetLink = false
	) => {
		let newValue = value;
		if ( '' === value && '' !== event ) {
			newValue =
				event.target.value === '' ? 0 : Number( event.target.value );
		}

		if ( ! resetLink ) {
			if ( link.value ) {
				changeLinkedValues( newValue );
			} else {
				changedUnLinkedValues();
			}
		}
		setAttributes( { [ valueTop.label ]: newValue } );
	};
	const onChangeRightValue = (
		event,
		value = '',
		resetLink = false
	) => {
		let newValue = value;

		if ( '' === value && '' !== event ) {
			newValue =
				event.target.value === '' ? 0 : Number( event.target.value );
		}
		if ( ! resetLink ) {
			if ( link.value ) {
				changeLinkedValues( newValue );
			} else {
				changedUnLinkedValues();
			}
		}
		setAttributes( { [ valueRight.label ]: newValue } );
	};

	const onChangeBottomValue = (
		event,
		value = '',
		resetLink = false
	) => {
		let newValue = value;

		if ( '' === value && '' !== event ) {
			newValue =
				event.target.value === '' ? 0 : Number( event.target.value );
		}
		if ( ! resetLink ) {
			if ( link.value ) {
				changeLinkedValues( newValue );
			} else {
				changedUnLinkedValues();
			}
		}
		setAttributes( { [ valueBottom.label ]: newValue } );
	};

	const onChangeLeftValue = (
		event,
		value = '',
		resetLink = false
	) => {
		let newValue = value;

		if ( '' === value && '' !== event ) {
			newValue =
				event.target.value === '' ? 0 : Number( event.target.value );
		}
		if ( ! resetLink ) {
			if ( link.value && ! resetLink ) {
				changeLinkedValues( newValue );
			} else {
				changedUnLinkedValues();
			}
		}
		setAttributes( { [ valueLeft.label ]: newValue } );
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

	let linkHtml = '';

	if ( link ) {
		linkHtml = (
			<span // eslint-disable-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions
				className="srfm-spacing-control__link srfm-spacing-control-connected dashicons dashicons-admin-links "
				onClick={ () => {
					changedUnLinkedValues();
					setAttributes( { [ link.label ]: false } );
				} }
			></span>
		);

		if ( ! link.value ) {
			linkHtml = (
				<span // eslint-disable-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions
					className="srfm-spacing-control__link srfm-spacing-control-disconnected dashicons dashicons-editor-unlink"
					onClick={ () => {
						onLinkClickHandler();
						setAttributes( { [ link.label ]: true } );
					} }
				></span>
			);
		}
	}
	const onLinkClickHandler = () => {
		let linkValue = valueTop.value;
		changeLinkedValues( linkValue );
	};

	const resetValues = ( defaultValues ) => {
		onChangeTopValue(
			'',
			defaultValues[ valueTop.label ],
			true
		);
		onChangeRightValue(
			'',
			defaultValues[ valueRight.label ],
			true
		);
		onChangeBottomValue(
			'',
			defaultValues[ valueBottom.label ],
			true
		);
		onChangeLeftValue(
			'',
			defaultValues[ valueLeft.label ],
			true
		);
		setAttributes( {
			[ unit?.label ]: defaultValues[ unit?.label ],
		} );
	};

	return (
		<div className="components-base-control">
			<div className="srfm-spacing-control">
				<div className="srfm-size-type-field-tabs">
					<div className="srfm-control__header">
						<ResponsiveToggle
							label={ label }
							responsive={ false }
						/>
						<div className="srfm-control__actions">
							<SRFMReset
								onReset={ resetValues }
								attributeNames={ [
									valueTop?.label,
									valueRight?.label,
									valueBottom?.label,
									valueLeft?.label,
									unit?.label,
								] }
								isFormSpecific={ true }
								setAttributes={ setAttributes }
								// value={ props?.value }
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
								<input
									className="srfm-spacing-control__number"
									type="number"
									min={ min }
									onChange={ ( e ) => onChangeTopValue( e ) }
									value={ undefined !== valueTop.value ? valueTop.value : '' }
								/>
								<input
									className="srfm-spacing-control__number"
									type="number"
									min={ min }
									onChange={ ( e ) => onChangeRightValue( e ) }
									value={
										undefined !== valueRight.value ? valueRight.value : ''
									}
								/>
								<input
									className="srfm-spacing-control__number"
									type="number"
									min={ min }
									onChange={ ( e ) => onChangeBottomValue( e ) }
									value={
										undefined !== valueBottom.value ? valueBottom.value : ''
									}
								/>
								<input
									className="srfm-spacing-control__number"
									type="number"
									min={ min }
									onChange={ ( e ) => onChangeLeftValue( e ) }
									value={
										undefined !== valueLeft.value ? valueLeft.value : ''
									}
								/>
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

export default SpacingComponent;
