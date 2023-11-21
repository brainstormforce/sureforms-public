/**
 * WordPress dependencies
 */
import { RichText } from '@wordpress/block-editor';
import { useEffect } from '@wordpress/element';
import Settings from './setting';
import { SliderClassicStyle } from './components/sliderClassicStyle';
import { SliderThemeStyle } from './components/sliderThemeStyle';
import { useGetCurrentFormId } from '../../blocks-attributes/getFormId';
import { useGetSureFormsKeys } from '../../blocks-attributes/getMetakeys';
import AddInitialAttr from '@Controls/addInitialAttr';
import { compose } from '@wordpress/compose';
import { FieldsPreview } from '../FieldsPreview.jsx';

const Edit = ( { clientId, attributes, setAttributes } ) => {
	const { help, block_id, formId, fieldWidth, preview } = attributes;
	const currentFormId = useGetCurrentFormId( clientId );
	const sureforms_keys = useGetSureFormsKeys( formId );

	useEffect( () => {
		if ( formId !== currentFormId ) {
			setAttributes( { formId: currentFormId } );
		}
	}, [ formId, setAttributes, currentFormId ] );

	useEffect( () => {
		const width_req_element = document.getElementById(
			'srfm-numberslider-fieldwidth' + block_id
		);
		const parent_to_width_element = width_req_element.parentElement;
		if ( window.innerWidth < 630 ) {
			parent_to_width_element.style.width = '100%';
		} else {
			parent_to_width_element.style.width =
				'calc( ' + fieldWidth + '% - 20px)';
		}
	}, [ fieldWidth ] );
	// show the block preview on hover.
	if ( preview ) {
		const fieldName = fieldsPreview.number_slider_preview;
		return <FieldsPreview fieldName={ fieldName } />;
	}

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
				id={ 'srfm-numberslider-fieldwidth' + block_id }
			>
				{ 'classic' === sureforms_keys?._srfm_form_styling ? (
					<SliderClassicStyle
						attributes={ attributes }
						blockID={ block_id }
						sureforms_keys={ sureforms_keys }
						setAttributes={ setAttributes }
					/>
				) : (
					<SliderThemeStyle
						attributes={ attributes }
						blockID={ block_id }
						setAttributes={ setAttributes }
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
							'classic' === sureforms_keys?._srfm_form_styling
								? 'srfm-helper-txt'
								: 'srfm-text-secondary'
						}
						multiline={ false }
						id={ block_id }
					/>
				) }
			</div>
		</>
	);
};

export default compose( AddInitialAttr )( Edit );
