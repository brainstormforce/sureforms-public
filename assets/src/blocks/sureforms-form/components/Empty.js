/** @jsx jsx */
/* eslint-disable react/no-unknown-property */
import { jsx } from '@emotion/react';

/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { Placeholder, Button, Icon } from '@wordpress/components';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import icon from '../../../../../images/Logo.js';

/**
 * Components
 */
// import PlaceholderTemplate from '../../form/components/PlaceholderTemplate';
import SelectForm from './SelectForm';

export default ( { attributes, setAttributes } ) => {
	const { title, step } = attributes;
	const [ form, setForm ] = useState( {} );

	// const postType = useSelect( ( select ) =>
	// 	select( 'core/editor' ).getCurrentPostType()
	// );

	// console.log( postType );
	// useEffect( () => {
	// 	const saveFormBlock = async () => {
	// 		setAttributes( { loading: true } );

	// 		try {
	// 			const updatedRecord = await dispatch( 'core' ).saveEntityRecord(
	// 				'postType',
	// 				'sureforms_form',
	// 				{
	// 					title: title || __( 'Untitled Form', 'sureforms' ),
	// 					content: serialize(
	// 						createBlock(
	// 							'sureforms/form', // name
	// 							{},
	// 							[]
	// 						)
	// 					),
	// 					status: 'publish',
	// 				}
	// 			);
	// 			// const formStatusData = {
	// 			// 	post_id: updatedRecord.id,
	// 			// 	_srfm_color1: '#046bd2',
	// 			// 	_srfm_textcolor1: '#fff',
	// 			// 	_srfm_color2: '',
	// 			// 	_srfm_fontsize: 16,
	// 			// 	_srfm_bg: '',
	// 			// 	_srfm_thankyou_message: 'Form submitted successfully!',
	// 			// 	_srfm_email: sfBlockData.admin_email,
	// 			// 	_srfm_submit_type: 'message',
	// 			// 	_srfm_submit_url: '',
	// 			// 	_srfm_sender_notification: 'off',
	// 			// 	_srfm_form_recaptcha: 'none',
	// 			// 	_srfm_submit_alignment: 'left',
	// 			// 	_srfm_submit_width: '',
	// 			// 	_srfm_submit_styling_inherit_from_theme: false,
	// 			// 	_srfm_form_container_width: 650,
	// 			// 	_srfm_form_styling: 'classic',
	// 			// };

	// 			// try {
	// 			// 	await apiFetch( {
	// 			// 		path: '/sureforms/v1/load-form-defaults',
	// 			// 		method: 'POST',
	// 			// 		data: formStatusData,
	// 			// 	} );
	// 			// } catch ( error ) {
	// 			// 	console.error( error );
	// 			// }

	// 			setAttributes( { id: updatedRecord.id } );
	// 		} catch ( e ) {
	// 			// TODO: Add notice here.
	// 			console.error( e );
	// 		} finally {
	// 			setAttributes( { loading: false } );
	// 		}
	// 	};

	// 	if ( 'sureforms_form' === postType ) {
	// 		saveFormBlock();
	// 		return;
	// 	}
	// }, [ postType ] );

	// Not required.
	// save the form block.

	// Not required.
	// if ( step === 'new' ) {
	// 	return (
	// 		<div { ...useBlockProps() }>
	// 			<PlaceholderTemplate
	// 				header={ __( 'Create a SureForms Form', 'sureforms' ) }
	// 			>
	// 				<div
	// 					style={ {
	// 						display: 'grid',
	// 						gap: '0.5em',
	// 						width: '100%',
	// 						padding: '0.5em',
	// 					} }
	// 				>
	// 					<div>{ __( 'Form Title', 'sureforms' ) }</div>
	// 					<TextControl
	// 						value={ title }
	// 						placeholder={ __(
	// 							'Enter a title for your form',
	// 							'sureforms'
	// 						) }
	// 						onChange={ ( value ) =>
	// 							setAttributes( { title: value } )
	// 						}
	// 						style={ {
	// 							width: '95%',
	// 						} }
	// 					/>
	// 					<div style={ { display: 'flex', gap: '0.5em' } }>
	// 						<Button
	// 							variant="primary"
	// 							onClick={ () => {
	// 								saveFormBlock();
	// 							} }
	// 						>
	// 							{ __( 'Next', 'sureforms' ) }
	// 							<Icon icon={ 'arrow-right' }></Icon>
	// 						</Button>
	// 						<Button
	// 							variant="secondary"
	// 							onClick={ () =>
	// 								setAttributes( { step: null } )
	// 							}
	// 						>
	// 							{ __( 'Cancel', 'sureforms' ) }
	// 						</Button>
	// 					</div>
	// 				</div>
	// 			</PlaceholderTemplate>
	// 		</div>
	// 	);
	// }
	// Added the same code as default step.
	// if ( step === 'select' ) {
	// 	return (
	// 		<div { ...useBlockProps() }>
	// 			<Placeholder
	// 				icon={ icon }
	// 				label={ __(
	// 					'Get started by selecting a SureForm.',
	// 					'sureforms'
	// 				) }
	// 				style={ {
	// 					display: 'flex',
	// 					alignItems: 'center',
	// 				} }
	// 			>
	// 				<div
	// 					style={ {
	// 						display: 'flex',
	// 						margin: 'auto',
	// 						flexDirection: 'column',
	// 						gap: '16px',
	// 					} }
	// 				>
	// 					<SelectForm form={ form } setForm={ setForm } />
	// 					<div style={ { textAlign: 'center' } }>
	// 						<Button
	// 							variant="primary"
	// 							onClick={ () => {
	// 								setAttributes( { id: form?.id } );
	// 							} }
	// 						>
	// 							{ __( 'Choose', 'sureforms' ) }
	// 							<Icon icon={ 'arrow-right' }></Icon>
	// 						</Button>
	// 						<Button
	// 							variant="secondary"
	// 							onClick={ () =>
	// 								setAttributes( { step: null } )
	// 							}
	// 						>
	// 							{ __( 'Cancel', 'sureforms' ) }
	// 						</Button>
	// 					</div>
	// 				</div>
	// 			</Placeholder>
	// 		</div>
	// 	);
	// }

	return (
		// Removed might be used later.
		// <div { ...useBlockProps() }>
		// 	<Placeholder
		// 		icon={ icon }
		// 		label={ __( 'Add a SureForms form', 'sureforms' ) }
		// 		style={ {
		// 			display: 'flex',
		// 			alignItems: 'center',
		// 		} }
		// 	>
		// 		<Button
		// 			variant="primary"
		// 			onClick={ () => setAttributes( { step: 'select' } ) }
		// 			style={ {
		// 				margin: 'auto',
		// 			} }
		// 		>
		// 			{ __( 'Select Form', 'sureforms' ) }
		// 		</Button>
		// 	</Placeholder>
		// </div>
		<div { ...useBlockProps() }>
			<Placeholder
				icon={ icon }
				label={ __(
					'Get started by selecting a SureForm.',
					'sureforms'
				) }
				style={ {
					display: 'flex',
					alignItems: 'center',
				} }
			>
				<div
					style={ {
						display: 'flex',
						margin: 'auto',
						flexDirection: 'column',
						gap: '16px',
					} }
				>
					<SelectForm form={ form } setForm={ setForm } />
					<div style={ { textAlign: 'center' } }>
						<Button
							variant="primary"
							onClick={ () => {
								setAttributes( { id: form?.id } );
							} }
						>
							{ __( 'Choose', 'sureforms' ) }
							<Icon icon={ 'arrow-right' }></Icon>
						</Button>
						{ /* <Button
							variant="secondary"
							onClick={ () => setAttributes( { step: null } ) }
						>
							{ __( 'Cancel', 'sureforms' ) }
						</Button> */ }
					</div>
				</div>
			</Placeholder>
		</div>
	);
};
