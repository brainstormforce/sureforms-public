import Editor from '../QuillEditor';
import { __ } from '@wordpress/i18n';
import Select from 'react-select';
import { useEffect, useState } from '@wordpress/element';

const DefaultConfirmationTypes = ( { data, setData, pageOptions, setErrorMessage, errorMessage, keyValueComponent } ) => {
	const [ canDisplayError, setCanDisplayError ] = useState( false );
	const handleEditorChange = ( newContent ) => {
		setData( { ...data, message: newContent } );
	};
	useEffect( () => {
		// Do not display pre-validation message right after changing tabs or confirmation type.
		setCanDisplayError( false );
	}, [ data?.confirmation_type ] );
	return (
		<>
			{ data?.confirmation_type === 'same page' && (
				<>
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
								handleContentChange={
									handleEditorChange
								}
								content={ data?.message }
							/>
						</div>
					</div>
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
							<label
								className="srfm-option-label"
								htmlFor="submission-type-1"
							>
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
									{ __( 'Hide Form', 'sureforms' ) }
								</div>
							</label>
							<label
								className="srfm-option-label"
								htmlFor="submission-type-2"
							>
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
									{ __( 'Reset Form', 'sureforms' ) }
								</div>
							</label>
						</div>
					</div>
				</>
			) }

			{ data?.confirmation_type === 'different page' && (
				<>
					<div className="srfm-modal-option-box">
						<div className="srfm-modal-label">
							<label>
								{ __( 'Select Page', 'sureforms' ) }
								<span className="srfm-validation-error">
									{ ' ' }
												*
								</span>
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
									setCanDisplayError( true );
									setErrorMessage( null );
									setData( {
										...data,
										page_url: e.value,
									} );
								} }
								classNamePrefix={ 'srfm-select' }
								menuPlacement="auto"
								styles={ {
									control: (
										baseStyles,
										state
									) => ( {
										...baseStyles,
										boxShadow: state.isFocused
											? '0 0 0 1px #D54406'
											: '0 1px 2px 0 rgba(13, 19, 30, .1)', // Primary color for option when focused
										borderColor: state.isFocused
											? '#D54406'
											: '#dce0e6', // Primary color for focus
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
					{ keyValueComponent() }
				</>
			) }
			{ data?.confirmation_type === 'custom url' && (
				<>
					<div className="srfm-modal-option-box">
						<div className="srfm-modal-label">
							<label>
								{ __( 'Custom URL', 'sureforms' ) }
								<span className="srfm-validation-error">
									{ ' ' }
								*
								</span>
							</label>
						</div>
						<input
							value={ data?.custom_url }
							className="srfm-modal-input"
							onChange={ ( e ) => {
								setCanDisplayError( true );
								setData( {
									...data,
									custom_url: e.target.value,
								} );
							} }
						/>
					</div>
					{ keyValueComponent() }
				</>
			) }
			{ canDisplayError && errorMessage && (
				<div className="srfm-validation-error">
					{ errorMessage }
				</div>
			) }
		</>
	);
};

export default DefaultConfirmationTypes;
