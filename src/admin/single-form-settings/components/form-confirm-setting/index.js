
import { __ } from '@wordpress/i18n';
import Editor from '../email-settings/QuillEditor';
import Select from 'react-select';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const FormConfirmSetting = () => {
	const sureforms_keys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);

	const { editPost } = useDispatch( editorStore );
	const [ data, setData ] = useState( {} );
	const [ pageOptions, setPageOptions ] = useState( [] );
	const handleSaveChanges = () => {
		updateMeta( '_srfm_form_confirmation', [ data ] );
	};
	const handleEditorChange = ( newContent ) => {
		setData( { ...data, message: newContent } );
	};

	function updateMeta( option, value ) {
		const option_array = {};
		option_array[ option ] = value;
		editPost( {
			meta: option_array,
		} );
	}

	useEffect( () => {
		apiFetch( { path: '/wp/v2/pages' } )
			.then( ( pages ) => {
			   if ( pages ) {
				 const createFormat = pages.map( ( page ) => {
				   let label;
				   if ( page.title?.rendered ) {
					 label = page.title?.rendered;
				   } else {
					 label = page.id.toString();
				   }
				   const value = page.link;
				   return { label, value };
				 } );
				 setPageOptions( createFormat );
			   }
			} )
			.catch( ( error ) => console.error( 'Error:', error ) );
		const formConfirmationData = sureforms_keys._srfm_form_confirmation;
		if ( formConfirmationData ) {
			setData( formConfirmationData[ 0 ] );
		}
	}, [] );
	return (
		<div className="srfm-modal-content">
			<div className="srfm-modal-inner-content">
				<div className="srfm-modal-inner-heading">
					<div className="srfm-modal-inner-heading-text">
						<h4>{ __( 'Form Confirmation', 'sureforms' ) }</h4>
					</div>
					<button
						onClick={ handleSaveChanges }
						className="srfm-modal-inner-heading-button"
					>
						{ __( 'Save Changes', 'sureforms' ) }
					</button>
				</div>
				<div className="srfm-modal-inner-box">
					<div className="srfm-modal-inner-box-text">
						<h5>{ __( 'Confirmation', 'sureforms' ) }</h5>
					</div>
					<div className="srfm-modal-separator" />
					<div className="srfm-modal-inner-box-content">
						<div className="srfm-modal-option-box">
							<div className="srfm-modal-label">
								<label>
									{ __( 'Confirmation Type', 'sureforms' ) }
								</label>
							</div>
							<div className="srfm-options-wrapper">
								<div className="srfm-option">
									<input className="srfm-option-input" value="same page" checked={ data?.confirmation_type === 'same page' } onChange={ ( e ) => setData( { ...data, confirmation_type: e.target.value } ) } type="radio" id="confirm-type-1" name="confirm-type" />
									<label className="srfm-option-text" htmlFor="confirm-type-1">{ __( 'Same Page', 'sureforms' ) }</label>
								</div>
								<div className="srfm-option">
									<input className="srfm-option-input" value="different page" checked={ data?.confirmation_type === 'different page' } onChange={ ( e ) => setData( { ...data, confirmation_type: e.target.value } ) } type="radio" id="confirm-type-2" name="confirm-type" />
									<label className="srfm-option-text" htmlFor="confirm-type-2">{ __( 'Different Page', 'sureforms' ) }</label>
								</div>
								<div className="srfm-option">
									<input className="srfm-option-input" value="custom url" checked={ data?.confirmation_type === 'custom url' } onChange={ ( e ) => setData( { ...data, confirmation_type: e.target.value } ) } type="radio" id="confirm-type-3" name="confirm-type" />
									<label className="srfm-option-text" htmlFor="confirm-type-3">{ __( 'Custom URL', 'sureforms' ) }</label>
								</div>
							</div>
						</div>
						{ data?.confirmation_type === 'different page' && <div className="srfm-modal-option-box">
							<div className="srfm-modal-label">
								<label>
									{ __( 'Select Page', 'sureforms' ) }
								</label>
							</div>
							<div className="srfm-options-wrapper">
								<Select
									className="srfm-select-page"
									value={ pageOptions?.filter( function( option ) {
										return option.value === data?.page_url;
									  } ) }
									options={ pageOptions }
									isMulti={ false }
									onChange={ ( e ) => setData( { ...data, page_url: e.value } ) }
								/>
							</div>
						</div> }
						{ data?.confirmation_type === 'custom url' && <div className="srfm-modal-option-box">
							<div className="srfm-modal-label">
								<label>
									{ __( 'Custom URL', 'sureforms' ) }
								</label>
							</div>
							<input
								value={ data?.custom_url }
								className="srfm-modal-input"
								onChange={ ( e ) => setData( { ...data, custom_url: e.target.value } ) }
							/>
						</div> }
						<div className="srfm-modal-area-box">
							<div className="srfm-modal-area-header">
								<div className="srfm-modal-area-header-text">
									<p>{ __( 'Confirmation Message', 'sureforms' ) }</p>
								</div>
							</div>
							<div className="srfm-editor-wrap">
								<Editor
									handleContentChange={
										handleEditorChange
									}
									content={ data?.message }
									formData={ data }
									setFormData={ setData }
								/>
							</div>
						</div>
						{ data?.confirmation_type === 'same page' && <div className="srfm-modal-option-box">
							<div className="srfm-modal-label">
								<label>
									{ __( 'After Form Submission', 'sureforms' ) }
								</label>
							</div>
							<div className="srfm-options-wrapper">
								<div className="srfm-option">
									<input className="srfm-option-input" type="radio" value="hide form" checked={ data?.submission_action === 'hide form' } onChange={ ( e ) => setData( { ...data, submission_action: e.target.value } ) } id="submission-type-1" name="submission-type" />
									<label className="srfm-option-text" htmlFor="submission-type-1">{ __( 'Hide Form', 'sureforms' ) }</label>
								</div>
								<div className="srfm-option">
									<input className="srfm-option-input" type="radio" value="reset form" checked={ data?.submission_action === 'reset form' } onChange={ ( e ) => setData( { ...data, submission_action: e.target.value } ) } id="submission-type-2" name="submission-type" />
									<label className="srfm-option-text" htmlFor="submission-type-1">{ __( 'Reset Form', 'sureforms' ) }</label>
								</div>
							</div>
						</div> }
					</div>
				</div>
			</div>
		</div>
	);
};

export default FormConfirmSetting;
