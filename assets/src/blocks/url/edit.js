/**
 * WordPress dependencies
 */
import { useBlockProps, RichText } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import Settings from './settings';
import { UrlThemeStyle } from './components/UrlThemeStyle';
import { UrlClassicStyle } from './components/UrlClassicStyle';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';

export default ( { attributes, setAttributes, clientId } ) => {
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
				className={ 'main-container sf-classic-inputs-holder' }
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				{ 'classic' === sureforms_keys?._sureforms_form_styling ? (
					<UrlClassicStyle
						blockID={ blockID }
						setAttributes={ setAttributes }
						attributes={ attributes }
					/>
				) : (
					<UrlThemeStyle
						blockID={ blockID }
						setAttributes={ setAttributes }
						attributes={ attributes }
					/>
				) }
				{ help !== '' && (
					<RichText
						tagName="label"
						value={ help }
						onChange={ ( value ) =>
							setAttributes( { help: value } )
						}
						className={
							'classic' ===
							sureforms_keys?._sureforms_form_styling
								? 'sforms-helper-txt'
								: 'sf-text-secondary'
						}
						multiline={ false }
						id={ blockID }
					/>
				) }
			</div>
		</>
	);
};
