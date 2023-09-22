/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import Settings from './settings';

export default ( { className, attributes, setAttributes, isSelected } ) => {
	const {
		label,
		placeholder,
		help,
		required,
		id,
		defaultValue,
		isConfirmEmail,
		confirmLabel,
	} = attributes;
	const blockID = useBlockProps().id.split( '-' ).join( '' );

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
					className="text-primary"
					htmlFor={ 'email-input-' + blockID }
				>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				<input
					id={ 'email-input-' + blockID }
					type="email"
					value={ defaultValue }
					className={ className }
					placeholder={ placeholder }
					required={ required }
				/>
				{ isConfirmEmail && (
					<>
						<label htmlFor={ 'confirm-email-input-' + blockID }>
							{ confirmLabel }
							{ required && confirmLabel && (
								<span style={ { color: 'red' } }> *</span>
							) }
						</label>
						<input
							id={ 'confirm-email-input-' + blockID }
							type="email"
							value={ defaultValue }
							className={ className }
							placeholder={ placeholder }
							required={ required }
						/>
					</>
				) }
				{ help !== '' && (
					<label
						htmlFor={ 'email-input-help-' + blockID }
						className="text-secondary"
					>
						{ help }
					</label>
				) }
			</div>
		</>
	);
};
