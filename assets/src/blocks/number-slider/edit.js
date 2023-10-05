/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { useEffect, useState } from '@wordpress/element';
import Settings from './setting';

export default ( { className, attributes, setAttributes, isSelected } ) => {
	const {
		label,
		placeholder,
		help,
		required,
		id,
		valueDisplayText,
		min,
		max,
		step,
	} = attributes;
	const blockID = useBlockProps().id.split( '-' ).join( '' );
	const [ slideValue, setSlideValue ] = useState( 0 );
	useEffect( () => {
		if ( id !== '' ) {
			return;
		}
		setAttributes( { id: blockID } );
	}, [ blockID, id, setAttributes ] );

	return (
		<>
			<Settings
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>
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
					htmlFor={ 'number-slider-input-' + blockID }
				>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				<input
					id={ 'number-slider-input-' + blockID }
					type="range"
					min={ min }
					max={ max }
					step={ step }
					value={ slideValue }
					onChange={ ( e ) => setSlideValue( e.target.value ) }
					className={ className }
					placeholder={ placeholder }
					required={ required }
				/>
				<div
					style={ {
						fontSize: '14px',
						fontWeight: 600,
						color: 'black',
					} }
				>
					{ valueDisplayText + slideValue }
				</div>
				{ help !== '' && (
					<label
						htmlFor={ 'number-slider-input-help-' + blockID }
						className="sf-text-secondary"
					>
						{ help }
					</label>
				) }
			</div>
		</>
	);
};
