import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useState,
	useEffect,
} from '@wordpress/element';
import { handleAddNewPost, initiateAuth } from '@Utils/Helpers';
import {
	MdArrowForward,
	MdKeyboardArrowDown,
	MdKeyboardArrowUp,
} from 'react-icons/md';
import aiFormBuilderPlaceholder from '@Image/ai-form-builder.svg';
import { CircularProgressBar } from '@tomickigrzegorz/react-circular-progress-bar';
import Header from './Header.js';
import LimitReachedPopup from './LimitReachedPopup.js';
import ErrorPopup from './ErrorPopup.js';
import { AuthErrorPopup } from './AuthErrorPopup.js';

const AiFormBuilder = () => {
	const [ message, setMessage ] = useState(
		__( 'Connecting with AI…', 'sureforms' )
	);
	const [ isBuildingForm, setIsBuildingForm ] = useState( false );
	const [ percentBuild, setPercentBuild ] = useState( 0 );
	const [ showEmptyError, setShowEmptyError ] = useState( false );
	const [ showLimitReachedPopup, setShowLimitReachedPopup ] =
		useState( false );
	const [ showFormCreationErr, setShowFormCreationErr ] = useState( false );
	const [ showFormIdeas, setShowFormIdeas ] = useState( false );
	const [ characterCount, setCharacterCount ] = useState( 0 );
	const [ showAuthErrorPopup, setShowAuthErrorPopup ] = useState( false );
	const urlParams = new URLSearchParams( window.location.search );
	const accessKey = urlParams.get( 'access_key' );
	const examplePrompts = [
		{
			title: 'Generate a user feedback form',
		},
		{
			title: 'Create a job application form',
		},
		{
			title: 'Create simple contact form',
		},
		{
			title: 'Make an event registration form',
		},
		{
			title: 'Create a gym membership form',
		},
	];

	const handleCreateAiForm = async (
		userCommand,
		previousMessages,
		useSystemMessage
	) => {
		setPercentBuild( 0 );
		// Check if the user has permission to create posts.
		if ( '1' !== srfm_admin.capability ) {
			console.error( 'User does not have permission to create posts' );
			return;
		}

		// Check if the user has entered a prompt in textarea.
		if ( ! userCommand ) {
			setShowEmptyError( true );
			return;
		}

		// Prepare the data to be sent to the API.
		const messageArray =
			previousMessages?.map( ( chat ) => ( {
				role: chat.role,
				content: chat.message,
			} ) ) || [];
		messageArray.push( { role: 'user', content: userCommand } );
		const postData = {
			message_array: messageArray,
			use_system_message: useSystemMessage,
		};

		// add a pause of 2 seconds and set percentBuild to 25 without using setTimeout
		setPercentBuild( 50 );
		setMessage( __( 'Generating Fields…', 'sureforms' ) );

		try {
			const response = await apiFetch( {
				path: 'sureforms/v1/generate-form',
				method: 'POST',
				data: postData,
			} );

			if ( response ) {
				setMessage(
					__( 'Just doing some final touches…', 'sureforms' )
				);
				setPercentBuild( 75 );

				if ( response?.success === false ) {
					setShowFormCreationErr( true );
					return;
				}

				const content = response?.data;

				if ( ! content ) {
					setShowFormCreationErr( true );
					return;
				}

				let sanitizedFormJsonData = content
					.replace( /```/g, '' )
					.replace( /json/g, '' );
				sanitizedFormJsonData = JSON.parse( sanitizedFormJsonData );

				const postContent = await apiFetch( {
					path: 'sureforms/v1/map-fields',
					method: 'POST',
					data: { form_data: sanitizedFormJsonData },
				} );

				if ( postContent ) {
					setMessage( __( 'Redirecting to Editor', 'sureforms' ) );
					setPercentBuild( 100 );

					handleAddNewPost(
						postContent,
						sanitizedFormJsonData?.formTitle,
						[]
					);
				} else {
					setShowFormCreationErr( true );
				}
			} else {
				setShowFormCreationErr( true );
				console.error(
					'Error creating sureforms form using AI: ',
					response.message
				);
				return;
			}
		} catch ( error ) {
			console.log( error );
		}
	};

	const handlePromptClick = ( prompt ) => {
		setShowEmptyError( false );
		const textArea = document.querySelector( 'textarea' );
		setCharacterCount(
			 prompt.length
		);
		textArea.value = prompt;
	};

	const handleAccessKey = async () => {
	  	// if access key is present, handle it by decrypting it and redirecting to form builder
		const response = await apiFetch( {
			path: '/sureforms/v1/handle-access-key',
			headers: {
				'Content-Type': 'application/json',
				'X-WP-Nonce': srfm_admin.template_picker_nonce,
			},
			method: 'POST',
			body: JSON.stringify( {
				accessKey,
			} ),
		} );

		if ( response?.success ) {
			const decryptedAccessKey = response?.data;

			if ( decryptedAccessKey ) {
				window.location.href = srfm_admin.site_url + `/wp-admin/admin.php?page=add-new-form&method=ai`;
			}
		} else {
			setShowAuthErrorPopup( true );
		}
	};

	// Handle access key on component mount
	useEffect( () => {
		if ( accessKey ) {
			handleAccessKey( );
		}
	}, [ accessKey ] );

	// shows while the form is being built
	if ( isBuildingForm ) {
		return (
			<>
				<div className="srfm-ts-main-container srfm-content-section">
					<div className="srfm-ai-builder-loading-container">
						<div className="srfm-loading-inner-container">
							<div className="srfm-ai-builder-header">
								<CircularProgressBar
									colorCircle="#eee3e1"
									colorSlice="#D54407"
									percent={ percentBuild }
									round
									speed={ 85 }
									fontColor="#0F172A"
									fontSize="18px"
									fontWeight={ 700 }
									size={ 72 }
								/>
								<div className="srfm-ai-builder-header-text">
									<h1 className="srfm-ai-builder-header-title">
										{ __(
											'We are building your Form…',
											'sureforms'
										) }
									</h1>
									<span className="srfm-ai-builder-header-subtitle">
										{ message }
									</span>
								</div>
							</div>
							<img
								src={ aiFormBuilderPlaceholder }
								alt={ __( 'AI Form Builder', 'sureforms' ) }
							/>
						</div>
					</div>
				</div>
				{ showFormCreationErr && <ErrorPopup /> }
			</>
		);
	}

	// show limit reached popup when free forms are consumed
	if ( showLimitReachedPopup
	 ) {
		return getLimitReachedPopup(
			showLimitReachedPopup,
			setShowLimitReachedPopup,
		);
	}

	// show auth error popup when access key is not present while authenticating
	if ( showAuthErrorPopup ) {
		return <AuthErrorPopup
			initiateAuth={ initiateAuth }
		/>;
	}

	return (
		<>
			<Header />
			<div className="srfm-ts-main-container srfm-content-section">
				<div className="srfm-ai-builder-container">
					<div className="srfm-ai-builder-inner-container">
						<h1 className="srfm-ai-builder-header-title">
							{ __(
								'Please describe the form you want to create',
								'sureforms'
							) }
						</h1>
						<div className="srfm-ai-builder-textarea-ctn">
							<textarea
								style={ {
									borderColor: showEmptyError
										? '#CD1A1A'
										: '#CBD5E1',
								} }
								placeholder={ __(
									'E.g. Form to gather feedback from our customer for our product functionality, usability , how much you will rate it and what you don’t like about it.',
									'sureforms'
								) }
								maxLength={ 2000 }
								onChange={ ( e ) => {
									setShowEmptyError( false );
									setCharacterCount( e.target.value.length );
								} }
							/>
							{ showEmptyError && (
								<span className="srfm-ai-builder-textarea-error">
									{ __(
										'Prompt cannot be empty.',
										'sureforms'
									) }
								</span>
							) }
							<Button
								onClick={ () =>
									setShowFormIdeas( ! showFormIdeas )
								}
								className="srfm-ai-form-ideas-toggle"
							>
								{ __( 'Some Form Ideas', 'sureforms' ) }
								{ showFormIdeas ? (
									<MdKeyboardArrowUp />
								) : (
									<MdKeyboardArrowDown />
								) }
							</Button>
							{ showFormIdeas && (
								<div className="srfm-ai-form-ideas-ctn">
									{ examplePrompts.map( ( prompt, index ) => (
										<Button
											key={ index }
											className="srfm-ai-builder-prompt-btn"
											onClick={ () =>
												handlePromptClick(
													prompt.title
												)
											}
										>
											<span className="srfm-ai-builder-prompt-title">
												{ prompt.title }
											</span>
										</Button>
									) ) }
								</div>
							) }
						</div>
						<hr className="srfm-ai-builder-separator" />
						<div
							style={ {
								display: 'flex',
								alignItems: 'center',
								justifyContent: 'flex-end',
								gap: '16px',
							} }>
							<span
								style={ {
									fontSize: '16px',
									fontWeight: '500',
									lineHeight: '24px',
									color: ' #64748B',
								} }
							>
								{ characterCount }/2000
							</span>
							<Button
								className="srfm-ai-builder-create-form-btn"
								onClick={ () => {
									const userPrompt =
									document.querySelector( 'textarea' );

									if ( ! userPrompt.value ) {
										setShowEmptyError( true );
										return;
									}

									if (
										srfm_admin?.srfm_ai_usage_details?.remaining === 0
									) {
										setShowLimitReachedPopup( true );
										return;
									}

									handleCreateAiForm(
										userPrompt.value,
										[],
										true
									);
									setIsBuildingForm( true );
								} }
							>
								<span className="srfm-ai-builder-create-form-btn-text">
									{ __( 'Generate Form', 'sureforms' ) }
								</span>
								<MdArrowForward color="white" size={ 20 } />
							</Button>
						</div>
					</div>
				</div>
			</div>
		</>
	);
};

export const getLimitReachedPopup = (
	showLimitReachedPopup,
	setShowLimitReachedPopup
) => {
	const isRegistered = srfm_admin?.srfm_ai_usage_details?.is_registered;
	const formCreationleft = srfm_admin?.srfm_ai_usage_details?.remaining ?? 0;
	// show upgrade plan popup if user is registered and form creation limit is reached
	if ( isRegistered === 'registered' && formCreationleft === 0 ) {
		return (
			<LimitReachedPopup
				setShowLimitReachedPopup={ setShowLimitReachedPopup }
				initiateAuth={ initiateAuth }
				paraOne={ 	 __(
					'You have reached the maximum number of form generations in your Free Plan.',
					'sureforms'
				) }
				paraTwo={ __(
					'Please upgrade your free plan to keep creating more forms with AI.',
					'sureforms'
				) }
				buttonText={ __( 'Upgrade Plan', 'sureforms' ) }
				onClick={ () => {
					window.open(
						srfm_admin?.pricing_page_url,
						'_blank'
					);
				} }
			/>
		);
	}

	return (
		<LimitReachedPopup
			onlClick={ initiateAuth }
			paraOne={ 	 __(
				'You have reached the maximum number of form generations.',
				'sureforms'
			) }
			paraTwo={ __(
				'Please connect your website with SureForms AI to create 20 more forms with AI.',
				'sureforms'
			) }
		/>
	);
};

export default AiFormBuilder;
