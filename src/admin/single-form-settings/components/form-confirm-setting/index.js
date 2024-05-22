import { __ } from '@wordpress/i18n';
import Editor from '../QuillEditor';
import Select from 'react-select';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import {
	Spinner,
} from '@wordpress/components';

const FormConfirmSetting = () => {
	const sureforms_keys = useSelect( ( select ) =>
		select( editorStore ).getEditedPostAttribute( 'meta' )
	);

	const { editPost } = useDispatch( editorStore );
	const [ data, setData ] = useState( {} );
	const [ pageOptions, setPageOptions ] = useState( [] );
	const [ errorMessage, setErrorMessage ] = useState( null );
	const [ isProcessing, setIsProcessing ] = useState( false );
	const [ showSuccess, setShowSuccess ] = useState( null );
	const handleSaveChanges = () => {
		setIsProcessing( true );
		const validationStatus = validateForm();
		setTimeout( () => {
			setErrorMessage( validationStatus );
			setIsProcessing( false );
			if ( '' !== validationStatus ) {
				return;
			}
			updateMeta( '_srfm_form_confirmation', [ data ] );
			setShowSuccess( true );
		}, 500 );
	};
	useEffect( () => {
		if ( null !== errorMessage ) {
			setErrorMessage( validateForm() );
		}
	}, [ data ] );
	useEffect( () => {
		if ( true === showSuccess ) {
			setTimeout( () => {
				setShowSuccess( false );
			}, 500 );
		}
	}, [ showSuccess ] );

	const validateForm = () => {
		let validation = '';
		if ( 'different page' === data?.confirmation_type && ! data?.page_url ) {
			validation = __( 'Please select a page.', 'sureforms' );
		}
		if ( 'custom url' === data?.confirmation_type ) {
			if ( ! data?.custom_url ) {
				validation = __( 'This field is required', 'sureforms' );
			} else {
				try {
					const newURL = new URL( data?.custom_url );
					if ( newURL.protocol !== 'https:' ) {
						validation = __( 'URL should use HTTPS', 'sureforms' );
					} else if ( ! (
						'localhost' !== newURL.hostname &&
						newURL.hostname.includes( '.' ) &&
						newURL.hostname.split( '.' ).pop().length > 1 ) ) {
						validation = __( 'URL is missing Top Level Domain (TLD)', 'sureforms' );
					} else {
						validation = '';
					}
				} catch ( error ) {
					validation = __( 'Please enter a valid URL', 'sureforms' );
				}
			}
		}
		return validation;
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
					<div className="srfm-flex srfm-flex-row srfm-gap-xs srfm-items-center">
						{
							isProcessing && <Spinner
								style={ {
									marginTop: '0',
									color: '#D54407',
								} }
							/>
						}
						{
							showSuccess && <div className="srfm-success srfm-tick"> &#10004;</div>
						}
						<button
							onClick={ handleSaveChanges }
							className="srfm-modal-inner-heading-button"
						>
							{ __( 'Save Changes', 'sureforms' ) }
						</button>
					</div>
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
								<div
									className={ `srfm-option ${ data?.confirmation_type === 'same page'
										? 'srfm-active-conf-type'
										: ''
									}` }
								>
									<input
										className="srfm-option-input"
										value="same page"
										checked={
											data?.confirmation_type ===
											'same page'
										}
										onChange={ ( e ) =>
											setData( {
												...data,
												confirmation_type:
													e.target.value,
											} )
										}
										type="radio"
										id="confirm-type-1"
										name="confirm-type"
									/>
									<label
										className="srfm-option-text"
										htmlFor="confirm-type-1"
									>
										{ __( 'Success Message', 'sureforms' ) }
									</label>
								</div>
								<div
									className={ `srfm-option ${ data?.confirmation_type ===
										'different page'
										? 'srfm-active-conf-type'
										: ''
									}` }
								>
									<input
										className="srfm-option-input"
										value="different page"
										checked={
											data?.confirmation_type ===
											'different page'
										}
										onChange={ ( e ) => {
											setErrorMessage( null );
											setData( {
												...data,
												confirmation_type:
													e.target.value,
											} );
										}
										}
										type="radio"
										id="confirm-type-2"
										name="confirm-type"
									/>
									<label
										className="srfm-option-text"
										htmlFor="confirm-type-2"
									>
										{ __(
											'Redirect to Page',
											'sureforms'
										) }
									</label>
								</div>
								<div
									className={ `srfm-option ${ data?.confirmation_type === 'custom url'
										? 'srfm-active-conf-type'
										: ''
									}` }
								>
									<input
										className="srfm-option-input"
										value="custom url"
										checked={
											data?.confirmation_type ===
											'custom url'
										}
										onChange={ ( e ) => {
											setErrorMessage( null );
											setData( {
												...data,
												confirmation_type:
													e.target.value,
											} );
										}
										}
										type="radio"
										id="confirm-type-3"
										name="confirm-type"
									/>
									<label
										className="srfm-option-text"
										htmlFor="confirm-type-3"
									>
										{ __(
											'Redirect to Custom URL',
											'sureforms'
										) }
									</label>
								</div>
							</div>
						</div>
						{ data?.confirmation_type === 'different page' && (
							<div className="srfm-modal-option-box">
								<div className="srfm-modal-label">
									<label>
										{ __( 'Select Page', 'sureforms' ) }
										<span className="srfm-validation-error"> *</span>
									</label>
								</div>
								<div className="srfm-options-wrapper">
									<Select
										className="srfm-select-page"
										value={ pageOptions?.filter(
											( option ) =>
												option.value === data?.page_url
										) }
										options={ pageOptions }
										isMulti={ false }
										onChange={ ( e ) => {
											setErrorMessage( null );
											setData( {
												...data,
												page_url: e.value,
											} );
										}
										}
										classNamePrefix={ 'srfm-select' }
										styles={ {
											control: (
												baseStyles,
												state
											) => ( {
												...baseStyles,
												boxShadow: state.isFocused
													? '0 0 0 1px #D54406'
													: 'none', // Primary color for option when focused
												borderColor: state.isFocused
													? '#D54406'
													: 'grey', // Primary color for focus
												'&:hover': {
													borderColor: '#D54406', // Primary color for hover
												},
												'&:active': {
													borderColor: '#D54406', // Primary color for active
												},
												'&:focus-within': {
													borderColor: '#D54406', // Primary color for focus within
												},
											} ),
											option: ( baseStyles, state ) => ( {
												...baseStyles,
												backgroundColor: state.isFocused
													? '#FFEFE8'
													: state.isSelected
														? '#D54406'
														: 'white', // Background color for option when focused or selected
												color: state.isFocused
													? 'black'
													: state.isSelected
														? 'white'
														: 'black', // Text color for option when focused or selected
											} ),
										} }
										menuPosition="auto"
										theme={ ( theme ) => ( {
											...theme,
											colors: {
												...theme.colors,
												primary50: '#FFEFE8',
												primary: '#D54406',
											},
										} ) }
									/>
								</div>
							</div>
						) }
						{ data?.confirmation_type === 'custom url' && (
							<div className="srfm-modal-option-box">
								<div className="srfm-modal-label">
									<label>
										{ __( 'Custom URL', 'sureforms' ) }
										<span className="srfm-validation-error"> *</span>
									</label>
								</div>
								<input
									value={ data?.custom_url }
									className="srfm-modal-input"
									onChange={ ( e ) =>
										setData( {
											...data,
											custom_url: e.target.value,
										} )
									}
								/>
							</div>
						) }
						{ errorMessage && (
							<div className="srfm-validation-error">{ errorMessage }</div>
						) }
						<div className="srfm-modal-area-box">
							<div className="srfm-modal-area-header">
								<div className="srfm-modal-area-header-text">
									<p>
										{ __(
											'Confirmation Message',
											'sureforms'
										) }
									</p>
								</div>
							</div>
							<div className="srfm-editor-wrap">
								<Editor
									handleContentChange={ handleEditorChange }
									content={ data?.message }
								/>
							</div>
						</div>
						{ data?.confirmation_type === 'same page' && (
							<div className="srfm-modal-option-box">
								<div className="srfm-modal-label">
									<label>
										{ __(
											'After Form Submission',
											'sureforms'
										) }
									</label>
								</div>
								<div className="srfm-options-wrapper">
									<div
										className={ `srfm-option ${ data?.submission_action ===
											'hide form'
											? 'srfm-active-after-submit'
											: ''
										}` }
									>
										<input
											className="srfm-option-input"
											type="radio"
											value="hide form"
											checked={
												data?.submission_action ===
												'hide form'
											}
											onChange={ ( e ) =>
												setData( {
													...data,
													submission_action:
														e.target.value,
												} )
											}
											id="submission-type-1"
											name="submission-type"
										/>
										<label
											className="srfm-option-text"
											htmlFor="submission-type-1"
										>
											{ __( 'Hide Form', 'sureforms' ) }
										</label>
									</div>
									<div
										className={ `srfm-option ${ data?.submission_action ===
											'reset form'
											? 'srfm-active-after-submit'
											: ''
										}` }
									>
										<input
											className="srfm-option-input"
											type="radio"
											value="reset form"
											checked={
												data?.submission_action ===
												'reset form'
											}
											onChange={ ( e ) =>
												setData( {
													...data,
													submission_action:
														e.target.value,
												} )
											}
											id="submission-type-2"
											name="submission-type"
										/>
										<label
											className="srfm-option-text"
											htmlFor="submission-type-1"
										>
											{ __( 'Reset Form', 'sureforms' ) }
										</label>
									</div>
								</div>
							</div>
						) }
					</div>
				</div>
			</div>
		</div>
	);
};

export default FormConfirmSetting;
