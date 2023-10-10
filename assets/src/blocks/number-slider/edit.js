/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import Settings from './setting';
import { SliderClassicStyle } from './components/sliderClassicStyle';
import { SliderThemeStyle } from './components/sliderThemeStyle';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';

export default ( { clientId, attributes, setAttributes } ) => {
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
				sureforms_keys={ sureforms_keys }
			/>
			<div
				className={ 'main-container sf-classic-inputs-holder frontend-inputs-holder' }
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				{ 'classic' === sureforms_keys?._sureforms_form_styling ? (
					<SliderClassicStyle
						attributes={ attributes }
						blockID={ blockID }
						sureforms_keys={ sureforms_keys }
					/>
				) : (
					<SliderThemeStyle
						attributes={ attributes }
						blockID={ blockID }
					/>
				) }
				{ help !== '' && (
					<label
						htmlFor={ 'number-slider-input-help-' + blockID }
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
