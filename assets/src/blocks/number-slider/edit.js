/**
 * WordPress dependencies
 */
import { useEffect } from '@wordpress/element';
import Settings from './setting';
import { SliderClassicStyle } from './components/sliderClassicStyle';
import { SliderThemeStyle } from './components/sliderThemeStyle';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';

const Edit = ( { clientId, attributes, setAttributes } ) => {
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
				sureforms_keys={ sureforms_keys }
			/>
			<div
				className={
					'srfm-main-container srfm-classic-inputs-holder srfm-frontend-inputs-holder'
				}
				style={ {
					display: 'flex',
					flexDirection: 'column',
					gap: '.5rem',
				} }
			>
				{ 'classic' === sureforms_keys?._srfm_form_styling ? (
					<SliderClassicStyle
						attributes={ attributes }
						blockID={ block_id }
						sureforms_keys={ sureforms_keys }
					/>
				) : (
					<SliderThemeStyle
						attributes={ attributes }
						blockID={ block_id }
					/>
				) }
				{ help !== '' && (
					<label
						htmlFor={ 'number-slider-input-help-' + block_id }
						className={
							'classic' === sureforms_keys?._srfm_form_styling
								? 'srfm-helper-txt'
								: 'srfm-text-secondary'
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
