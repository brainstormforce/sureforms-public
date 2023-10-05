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
		isConfirmPassword,
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
					className="sf-text-primary"
					htmlFor={ 'password-input-' + blockID }
				>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				<input
					id={ 'password-input-' + blockID }
					type="password"
					className={ className }
					placeholder={ placeholder }
					required={ required }
				/>
				{ isConfirmPassword && (
					<>
						<label
							className="sf-text-primary"
							htmlFor={ 'confirm-email-input-' + blockID }
						>
							{ confirmLabel }
							{ required && confirmLabel && (
								<span style={ { color: 'red' } }> *</span>
							) }
						</label>
						<input
							id={ 'confirm-password-input-' + blockID }
							type="password"
							className={ className }
							placeholder={ placeholder }
							required={ required }
						/>
					</>
				) }
				{ help !== '' && (
					<label
						htmlFor={ 'password-input-help-' + blockID }
						className="sf-text-secondary"
					>
						{ help }
					</label>
				) }
			</div>
		</>
	);
};
