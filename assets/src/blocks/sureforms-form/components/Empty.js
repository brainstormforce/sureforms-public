/** @jsx jsx */
/* eslint-disable react/no-unknown-property */
import { jsx } from '@emotion/react';

/**
 * WordPress dependencies
 */
import { useBlockProps } from '@wordpress/block-editor';
import { createBlock, serialize } from '@wordpress/blocks';
import { Placeholder, Button, Icon, TextControl } from '@wordpress/components';
import { dispatch } from '@wordpress/data';
import { useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

import icon from '../../../../../images/Logo.js';

/**
 * Components
 */
import PlaceholderTemplate from '../../form/components/PlaceholderTemplate';
import SelectForm from './SelectForm';

export default ( { attributes, setAttributes } ) => {
	const { title, step } = attributes;
	const [ form, setForm ] = useState( {} );

	// save the form block.
	const saveFormBlock = async () => {
		setAttributes( { loading: true } );

		try {
			const updatedRecord = await dispatch( 'core' ).saveEntityRecord(
				'postType',
				'sureforms_form',
				{
					title: title || __( 'Untitled Form', 'sureforms' ),
					content: serialize(
						createBlock(
							'sureforms/form', // name
							{},
							[]
						)
					),
					status: 'publish',
				}
			);
			const formStatusData = {
				post_id: updatedRecord.id,
				_sureforms_color1: '',
				_sureforms_textcolor1: '',
				_sureforms_color2: '',
				_sureforms_fontsize: 16,
				_sureforms_bg: '',
				_sureforms_thankyou_message: 'Form submitted successfully!',
				_sureforms_email: sfBlockData.admin_email,
				_sureforms_submit_type: 'message',
				_sureforms_submit_url: '',
				_sureforms_sender_notification: 'off',
				_sureforms_form_recaptcha: 'none',
				_sureforms_submit_alignment: 'left',
				_sureforms_submit_width: '',
				_sureforms_submit_styling_inherit_from_theme: false,
			};

			try {
				await apiFetch( {
					path: '/sureforms/v1/load-form-defaults',
					method: 'POST',
					data: formStatusData,
				} );
			} catch ( error ) {
				console.error( error );
			}

			setAttributes( { id: updatedRecord.id } );
		} catch ( e ) {
			// TODO: Add notice here.
			console.error( e );
		} finally {
			setAttributes( { loading: false } );
		}
	};

	if ( step === 'new' ) {
		return (
			<div { ...useBlockProps() }>
				<PlaceholderTemplate
					header={ __( 'Create a SureForms Form', 'sureforms' ) }
				>
					<div
						style={ {
							display: 'grid',
							gap: '0.5em',
							width: '100%',
							padding: '0.5em',
						} }
					>
						<div>{ __( 'Form Title', 'sureforms' ) }</div>
						<TextControl
							value={ title }
							placeholder={ __(
								'Enter a title for your form',
								'sureforms'
							) }
							onChange={ ( value ) =>
								setAttributes( { title: value } )
							}
						/>
						<div style={ { display: 'flex', gap: '0.5em' } }>
							<Button
								variant="primary"
								onClick={ () => {
									saveFormBlock();
								} }
							>
								{ __( 'Next', 'sureforms' ) }
								<Icon icon={ 'arrow-right' }></Icon>
							</Button>
							<Button
								variant="secondary"
								onClick={ () =>
									setAttributes( { step: null } )
								}
							>
								{ __( 'Cancel', 'sureforms' ) }
							</Button>
						</div>
					</div>
				</PlaceholderTemplate>
			</div>
		);
	}

	if ( step === 'select' ) {
		return (
			<div { ...useBlockProps() }>
				<PlaceholderTemplate
					header={ __( 'Select a SureForms form', 'sureforms' ) }
				>
					<div
						style={ {
							display: 'grid',
							gap: '0.5em',
							width: '100%',
							margin: '0.5em',
						} }
					>
						<SelectForm form={ form } setForm={ setForm } />
						<div style={ { display: 'flex', gap: '0.5em' } }>
							<Button
								variant="primary"
								onClick={ () => {
									setAttributes( { id: form?.id } );
								} }
							>
								{ __( 'Choose', 'sureforms' ) }
								<Icon icon={ 'arrow-right' }></Icon>
							</Button>
							<Button
								variant="secondary"
								onClick={ () =>
									setAttributes( { step: null } )
								}
							>
								{ __( 'Cancel', 'sureforms' ) }
							</Button>
						</div>
					</div>
				</PlaceholderTemplate>
			</div>
		);
	}

	return (
		<div { ...useBlockProps() }>
			<Placeholder
				icon={ icon }
				instructions={ __(
					'Get started by selecting a form or start build a new form.',
					'sureforms'
				) }
				label={ __( 'Add a SureForms form', 'sureforms' ) }
			>
				<div
					style={ {
						display: 'flex',
						gap: '0.5em',
						flexWrap: 'wrap',
					} }
				>
					<Button
						variant="primary"
						onClick={ () => setAttributes( { step: 'new' } ) }
					>
						{ __( 'New Form', 'sureforms' ) }
					</Button>
					<Button
						variant="primary"
						onClick={ () => setAttributes( { step: 'select' } ) }
					>
						{ __( 'Select Form', 'sureforms' ) }
					</Button>
				</div>
			</Placeholder>
		</div>
	);
};
