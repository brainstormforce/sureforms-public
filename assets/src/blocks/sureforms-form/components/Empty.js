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

import { receipt as icon } from '@wordpress/icons';

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
							margin: '0.5em',
						} }
					>
						<div>{ __( 'Form Title', 'sureforms' ) }</div>
						<TextControl
							style={ { maxWidth: '400px' } }
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
				label={ __( 'Add a sureforms form', 'sureforms' ) }
			>
				<div style={ { display: 'flex', gap: '0.5em' } }>
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
