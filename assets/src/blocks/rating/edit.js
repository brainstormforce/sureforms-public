/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { useState, useEffect } from '@wordpress/element';
import { SelectControl, ToggleControl } from '@wordpress/components';
import UAGTextControl from '@Components/text-control';
import UAGNumberControl from '@Components/number-control';
import UAGAdvancedPanelBody from '@Components/advanced-panel-body';
import MultiButtonsControl from '@Components/multi-buttons-control';
import InspectorTabs from '@Components/inspector-tabs/InspectorTabs.js';
import InspectorTab, {
	UAGTabs,
} from '@Components/inspector-tabs/InspectorTab.js';
import AdvancedPopColorControl from '@Components/color-control/advanced-pop-color-control.js';

/**
 * Component Dependencies
 */
import RatingIcon from './components/RatingIcon.jsx';

export default function Edit( { attributes, setAttributes, isSelected } ) {
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
		errorMsg,
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
		<>
			<InspectorControls>
				<InspectorTabs>
					<InspectorTab { ...UAGTabs.general }>
						<UAGAdvancedPanelBody
							title={ __( 'Attributes', 'sureforms' ) }
							initialOpen={ true }
						>
							<UAGTextControl
								label={ __( 'Label', 'sureforms' ) }
								data={ {
									value: label,
									label: 'label',
								} }
								value={ label }
								onChange={ ( value ) => {
									setAttributes( { label: value } );
								} }
							/>
							<ToggleControl
								label={ __( 'Required', 'sureforms' ) }
								checked={ required }
								onChange={ ( checked ) =>
									setAttributes( { required: checked } )
								}
							/>
							{ required && (
								<UAGTextControl
									label={ __( 'Error message', 'sureforms' ) }
									data={ {
										value: errorMsg,
										label: 'errorMsg',
									} }
									value={ errorMsg }
									onChange={ ( value ) =>
										setAttributes( { errorMsg: value } )
									}
								/>
							) }
							<UAGNumberControl
								label={ __( 'Number of Icons', 'sureforms' ) }
								displayUnit={ false }
								step={ 1 }
								min={ 1 }
								max={ 10 }
								data={ {
									value: maxValue,
									label: 'maxValue',
								} }
								value={ maxValue }
								onChange={ ( value ) => {
									if ( value <= 10 ) {
										setAttributes( {
											maxValue: value,
										} );
									}
								} }
							/>
							<UAGTextControl
								label={ __( 'Help', 'sureforms' ) }
								value={ ratingBoxHelpText }
								data={ {
									value: ratingBoxHelpText,
									label: 'ratingBoxHelpText',
								} }
								onChange={ ( value ) =>
									setAttributes( {
										ratingBoxHelpText: value,
									} )
								}
							/>
						</UAGAdvancedPanelBody>
					</InspectorTab>
					<InspectorTab { ...UAGTabs.style }>
						<UAGAdvancedPanelBody
							title={ __( 'Icon Styles', 'sureforms' ) }
							initialOpen={ true }
						>
							<MultiButtonsControl
								label={ __( 'Width', 'sureforms' ) }
								data={ {
									value: width,
									label: 'width',
								} }
								options={ [
									{
										value: 'halfWidth',
										icon: 'Half Width',
									},
									{
										value: 'fullWidth',
										icon: 'Full Width',
									},
								] }
								showIcons={ true }
								onChange={ ( value ) => {
									if ( width !== value ) {
										setAttributes( {
											width: value,
										} );
									} else {
										setAttributes( {
											width: 'fullWidth',
										} );
									}
								} }
							/>
							<AdvancedPopColorControl
								label={ __( 'Icon Color', 'sureforms' ) }
								setAttributes={ setAttributes }
								colorValue={ iconColor }
								data={ {
									value: iconColor,
									label: 'iconColor',
								} }
								onColorChange={ ( value ) =>
									setAttributes( { iconColor: value } )
								}
							/>
							<ToggleControl
								label={ __( 'Show Numbers', 'sureforms' ) }
								checked={ showNumbers }
								onChange={ ( checked ) =>
									setAttributes( { showNumbers: checked } )
								}
							/>

							<SelectControl
								value={ iconShape }
								label={ __( 'Icon', 'sureforms' ) }
								onChange={ ( value ) =>
									setAttributes( {
										iconShape: value,
									} )
								}
								options={ [
									{ label: 'Star', value: 'star' },
									{ label: 'Heart', value: 'heart' },
									{
										label: 'Smiley',
										value: 'smiley',
									},
								] }
							/>
						</UAGAdvancedPanelBody>
					</InspectorTab>
				</InspectorTabs>
			</InspectorControls>
			<div
				className={
					'main-container' + ( isSelected ? ' sf--focus' : '' )
				}
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				<label
					className="sf-text-primary"
					htmlFor={ 'rating-block-' + blockID }
				>
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
					<div className="sf-text-secondary">{ ratingBoxHelpText }</div>
				) }
			</div>
		</>
	);
}
