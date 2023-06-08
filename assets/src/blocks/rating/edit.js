/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { useState, useEffect } from '@wordpress/element';
import {
	PanelBody,
	PanelRow,
	SelectControl,
	ToggleControl,
	TextControl,
	BaseControl,
	ButtonGroup,
	Button,
	ColorPalette,
} from '@wordpress/components';

/**
 * Component Dependencies
 */
import RatingIcon from './components/RatingIcon.jsx';

export default function Edit( { attributes, setAttributes } ) {
	const [ rating, setRating ] = useState( null );
	// eslint-disable-next-line no-unused-vars
	const [ hover, setHover ] = useState( null );

	const {
		id,
		required,
		label,
		ratingBoxHelpText,
		width,
		iconColor,
		showNumbers,
		iconShape,
		maxValue,
	} = attributes;

	const arrayRating = [];

	for ( let i = 1; i <= maxValue; i++ ) {
		arrayRating.push( i );
	}

	const blockID = useBlockProps().id.split( '-' ).join( '' );

	useEffect( () => {
		if ( id !== '' ) {
			return;
		}
		setAttributes( { id: blockID } );
	}, [ blockID, id, setAttributes ] );
	return (
		<div { ...useBlockProps() }>
			<InspectorControls>
				<PanelBody title={ __( 'Attributes', 'sureforms' ) }>
					<PanelRow>
						<ToggleControl
							label={ __( 'Required', 'sureforms' ) }
							checked={ required }
							onChange={ ( checked ) =>
								setAttributes( { required: checked } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={ __( 'Label', 'sureforms' ) }
							value={ label }
							onChange={ ( value ) =>
								setAttributes( { label: value } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<TextControl
							label={ __( 'Help', 'sureforms' ) }
							value={ ratingBoxHelpText }
							onChange={ ( value ) =>
								setAttributes( { ratingBoxHelpText: value } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<BaseControl
							id="for-width-control"
							label={ __( 'Width', 'sureforms' ) }
						>
							<ButtonGroup>
								<Button
									isPressed={ width === 'halfWidth' }
									onClick={ () =>
										setAttributes( { width: 'halfWidth' } )
									}
								>
									{ __( 'Half Width', 'sureforms' ) }
								</Button>
								<Button
									isPressed={ width === 'fullWidth' }
									onClick={ () =>
										setAttributes( { width: 'fullWidth' } )
									}
								>
									{ __( 'Full Width', 'sureforms' ) }
								</Button>
							</ButtonGroup>
						</BaseControl>
					</PanelRow>
					<PanelRow>
						<div>
							<BaseControl
								id="for-icon-color"
								label={ __( 'Icon Color', 'sureforms' ) }
							/>
						</div>
						<ColorPalette
							value={ iconColor }
							onChange={ ( color ) =>
								setAttributes( { iconColor: color } )
							}
							clearable={ false }
						/>
					</PanelRow>
					<PanelRow>
						<ToggleControl
							label={ __( 'Show Numbers', 'sureforms' ) }
							checked={ showNumbers }
							onChange={ ( checked ) =>
								setAttributes( { showNumbers: checked } )
							}
						/>
					</PanelRow>
					<PanelRow>
						<div
							style={ {
								display: 'flex',
								gap: '10px',
								alignItems: 'center',
							} }
						>
							<div>
								<SelectControl
									value={ iconShape }
									label={ __( 'Icon', 'sureforms' ) }
									onChange={ ( value ) =>
										setAttributes( { iconShape: value } )
									}
								>
									<option value="star">
										{ __( 'Star', 'sureforms' ) }
									</option>
									<option value="heart">
										{ __( 'Heart', 'sureforms' ) }
									</option>
									<option value="smiley">
										{ __( 'Smiley', 'sureforms' ) }
									</option>
								</SelectControl>
							</div>
							<div style={ { width: '10rem' } }>
								<TextControl
									label={ __(
										'Number of Icons',
										'sureforms'
									) }
									type="number"
									step={ 1 }
									min={ 1 }
									max={ 10 }
									value={ maxValue }
									onChange={ ( value ) =>
										setAttributes( {
											maxValue: value,
										} )
									}
								/>
							</div>
						</div>
					</PanelRow>
				</PanelBody>
			</InspectorControls>
			<div
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				<label htmlFor={ 'rating-block-' + blockID }>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>

				<div
					id={ 'rating-block-' + blockID }
					style={ {
						justifyContent:
							width === 'fullWidth'
								? 'space-between'
								: 'space-evenly',
						display: 'flex',
						alignItems: 'center',
					} }
				>
					{ arrayRating.map( ( index ) => {
						const ratingValue = index;
						const iconProps = {
							color:
								ratingValue <= ( hover || rating )
									? iconColor
									: '#ddd',
							fontSize: ratingValue === rating ? '30px' : null,
							// Might be used later.
							// onMouseEnter: () => setHover( ratingValue ),
							// onMouseLeave: () => setHover( null ),
						};

						return (
							<div key={ index }>
								<div
									style={ {
										display: 'flex',
										flexDirection: 'column',
										alignItems: 'center',
									} }
								>
									<label
										style={ { fontSize: '25px' } }
										htmlFor={ blockID + 'i-' + index }
									>
										<RatingIcon
											iconShape={ iconShape }
											iconProps={ iconProps }
										/>
									</label>
									<div style={ { color: 'black' } }>
										{ showNumbers && index }
									</div>

									<input
										required={ index === 1 && required }
										type="radio"
										id={ blockID + 'i-' + index }
										style={ { display: 'none' } }
										value={ ratingValue }
										onClick={ () => {
											setRating( ratingValue );
										} }
									/>
								</div>
							</div>
						);
					} ) }
				</div>
				{ ratingBoxHelpText !== '' && (
					<div className="text-secondary">{ ratingBoxHelpText }</div>
				) }
			</div>
		</div>
	);
}
