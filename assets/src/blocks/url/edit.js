/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import Settings from './settings';

export default ( { className, attributes, setAttributes } ) => {
	const { label, placeholder, help, required, id } = attributes;
	const blockID = useBlockProps().id.split( '-' ).join( '' );

	useEffect( () => {
		if ( id !== '' ) {
			return;
		}
		setAttributes( { id: blockID } );
	}, [ blockID, id, setAttributes ] );

	return (
		<div { ...useBlockProps() }>
			<Settings
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>
			<div
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				<label htmlFor={ 'url-input-' + blockID }>
					{ label }
					{ required && label && (
						<span style={ { color: 'red' } }> *</span>
					) }
				</label>
				<input
					id={ 'url-input-' + blockID }
					type="url"
					className={ className }
					placeholder={ placeholder }
					required={ required }
				/>
				{ help !== '' && (
					<label
						htmlFor={ 'url-input-help-' + blockID }
						className="text-secondary"
					>
						{ help }
					</label>
				) }
			</div>
		</div>
	);
};
