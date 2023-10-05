/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import Settings from './settings';
import { PasswordClassicStyle } from './components/PasswordClassicStyle';
import { PasswordThemeStyle } from './components/PasswordThemeStyle';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';

export default ( { className, attributes, setAttributes, clientId } ) => {
	const { help, id, formId } = attributes;
	const blockID = useBlockProps().id.split( '-' ).join( '' );
	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );

	useEffect( () => {
		if ( id !== '' ) {
			return;
		}
		setAttributes( { id: blockID } );
	}, [ blockID, id, setAttributes ] );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	return (
		<>
			<Settings
				attributes={ attributes }
				setAttributes={ setAttributes }
			/>
			<div
				className={ 'main-container' + className }
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				{ 'classic' === sureforms_keys?._sureforms_form_styling ? (
					<PasswordClassicStyle
						attributes={ attributes }
						blockID={ blockID }
					/>
				) : (
					<PasswordThemeStyle
						attributes={ attributes }
						blockID={ blockID }
					/>
				) }
				{ help !== '' && (
					<label
						htmlFor={ 'password-input-help-' + blockID }
						className="text-secondary"
					>
						{ help }
					</label>
				) }
			</div>
		</>
	);
};
