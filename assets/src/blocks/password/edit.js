/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';
import Settings from './settings';
import { PasswordClassicStyle } from './components/PasswordClassicStyle';
import { PasswordThemeStyle } from './components/PasswordThemeStyle';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';

const Edit = ( { attributes, setAttributes, clientId } ) => {
	const { help, block_id, formId } = attributes;
	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );

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
					<PasswordClassicStyle
						attributes={ attributes }
						blockID={ block_id }
					/>
				) : (
					<PasswordThemeStyle
						attributes={ attributes }
						blockID={ block_id }
					/>
				) }
				{ help !== '' && (
					<label
						htmlFor={ 'password-input-help-' + block_id }
						className={
							'classic' ===
							sureforms_keys?._sureforms_form_styling
								? 'sforms-helper-txt'
								: 'sf-text-secondary'
						}
					>
						{ help }
					</label>
				) }
			</div>
		</>
	);
};

export default compose( AddInitialAttr )( Edit );
