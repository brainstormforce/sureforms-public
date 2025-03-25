import { __,
	sprintf,
} from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useState, useEffect, useRef } from '@wordpress/element';
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
import toast, { Toaster } from 'react-hot-toast';
import { applyFilters } from '@wordpress/hooks';
import PremiumBadge from '@Admin/components/PremiumBadge';

const AiFormBuilder = () => {
	const [ message, setMessage ] = useState(
		__( 'Connecting with AI…', 'sureforms' )
	);
	const [ isBuildingForm, setIsBuildingForm ] = useState( false );
	const [ percentBuild, setPercentBuild ] = useState( 0 );
	const [ showEmptyError, setShowEmptyError ] = useState( false );
	const [ showFormCreationErr, setShowFormCreationErr ] = useState( false );
	const [ showFormIdeas, setShowFormIdeas ] = useState( true );
	const [ characterCount, setCharacterCount ] = useState( 0 );
	const [ showAuthErrorPopup, setShowAuthErrorPopup ] = useState( false );
	const urlParams = new URLSearchParams( window.location.search );
	const accessKey = urlParams.get( 'access_key' );
	const [ isListening, setIsListening ] = useState( false ); // State to manage voice recording
	const recognitionRef = useRef( null ); // To store SpeechRecognition instance
	const [ formTypeObj, setFormTypeObj ] = useState( {} );
	const showAiConversationalFormToggle = false;
	const conversationalFormAiToggle = applyFilters(
		'srfm.aiFormScreen.conversational.toggle',
		showAiConversationalFormToggle,
		formTypeObj,
		setFormTypeObj
	);

	const examplePrompts = applyFilters(
		'srfm.aiFormScreen.examplePrompts',
		[
			{
				title: __( 'Create simple contact form', 'sureforms' ),
			},
			{
				title: __( 'Create a lead generation form', 'sureforms' ),
			},
			{
				title: __( 'Generate a user feedback form', 'sureforms' ),
			},
			{
				title: __( 'Create a job application form', 'sureforms' ),
			},
			{
				title: __( 'Make an event registration form', 'sureforms' ),
			},
		],
		formTypeObj
		 );

	const initSpeechRecognition = () => {
		const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
		if ( ! SpeechRecognition ) {
			return null;
		}
		const recognition = new SpeechRecognition();
		recognition.lang = 'en-US'; // Set language to English
		recognition.interimResults = false; // Only show final results
		recognition.maxAlternatives = 1; // One alternative result
		recognition.continuous = true; // Keep recording until stopped
		return recognition;
	};

	const toggleListening = () => {
		// initialize SpeechRecognition instance if not already initialized
		if ( ! recognitionRef.current ) {
			recognitionRef.current = initSpeechRecognition();
		}

		// if SpeechRecognition is not supported, show error message
		if ( ! recognitionRef.current ) {
			return;
		}

		const recognition = recognitionRef.current;

		if ( isListening ) {
			// Stop recording if already started
			recognition.stop();
			setIsListening( false );
		} else {
			// Start recording if not started
			recognition.start();
			setIsListening( true );
			recognition.onresult = ( event ) => {
				// keep on appending the result to the textarea
				const speechResult = event.results[ event.results.length - 1 ][ 0 ].transcript;
				const textArea = document.querySelector( 'textarea' );
				textArea.value += speechResult;
				setCharacterCount( textArea.value.length );
			};
			recognition.onerror = ( e ) => {
				recognition.stop();
				setIsListening( false );
				toast.dismiss();

				if ( e.error === 'not-allowed' ) {
					toast.error( __( 'Please allow microphone access to use voice input.', 'sureforms' ), {
						duration: 5000,
					} );
					return;
				}

				toast.error( __( 'Speech recognition is not supported in your current browser. Please use Google Chrome / Safari.', 'sureforms' ), {
					duration: 5000,
				} );
			};
		}
	};

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
		const formType = applyFilters(
			'srfm.aiFormScreen.formType',
			'',
			formTypeObj
		);
		const postData = {
			message_array: messageArray,
			use_system_message: useSystemMessage,
			is_conversional: formTypeObj?.isConversationalForm,
			form_type: formType,
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

				const postContent = await apiFetch( {
					path: 'sureforms/v1/map-fields',
					method: 'POST',
					data: { form_data: content,	is_conversional: formTypeObj?.isConversationalForm },
				} );

				if ( postContent ) {
					setMessage( __( 'Redirecting to Editor', 'sureforms' ) );
					setPercentBuild( 100 );
					const formTitle = content?.form?.formTitle;
					const metasToUpdate = applyFilters(
						'srfm.aiFormScreen.metasToUpdate',
						{},
						formTypeObj,
						content
					);
					handleAddNewPost( postContent, formTitle, metasToUpdate, formTypeObj?.isConversationalForm, formType );
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
		setCharacterCount( prompt.length );
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
			window.location.href =
				srfm_admin.site_url +
				`/wp-admin/admin.php?page=add-new-form&method=ai`;
		} else {
			setShowAuthErrorPopup( true );
			console.error( 'Error handling access key: ', response.message );
		}
	};

	// Stops voice input if typing begins
	const handleTyping = () => {
		if ( isListening && recognitionRef.current ) {
			recognitionRef.current.stop();
			setIsListening( false );
		}
		setShowEmptyError( false );
	};

	// Handle access key on component mount
	useEffect( () => {
		if ( accessKey ) {
			handleAccessKey();
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

	// show auth error popup when access key is not present while authenticating
	if ( showAuthErrorPopup ) {
		return <AuthErrorPopup initiateAuth={ initiateAuth } />;
	}

	return (
		<>
			<Toaster position="bottom-right" />
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
									'E.g. Form to gather feedback from our customers for our product functionality, usability, how much you will rate it, and what you don’t like about it.',
									'sureforms'
								) }
								maxLength={ 2000 }
								onChange={ ( e ) => {
									setShowEmptyError( false );
									setCharacterCount( e.target.value.length );
								} }
								onInput={ handleTyping }
							/>
							{ showEmptyError && (
								<span className="srfm-ai-builder-textarea-error">
									{ __(
										'Prompt cannot be empty.',
										'sureforms'
									) }
								</span>
							) }
							{ false === conversationalFormAiToggle
								? <div className="srfm-ai-conversational-form-toggle"
								>
									<div style={ {
										// Inline styles are used here to override the default styles
										backgroundColor: '#fff',
										border: '1px solid #949494',
										pointerEvents: 'none',
									} }
								 className="srfm-ai-conversational-form-toggle-btn">
										<div style={ {
											// Inline styles are used here to override the default styles
											backgroundColor: '#949494',
											left: '3px',
										} }
										className="srfm-ai-conversational-form-toggle-thumb"
										></div>
									</div>
									{
										__( 'Create Conversational Form', 'sureforms' )
									}
									<PremiumBadge
										tooltipHeading={ __( 'Unlock Conversational Forms', 'sureforms' ) }
										tooltipContent={ __( 'With the SureForms Pro Plan, you can transform your forms into engaging conversational layouts for a seamless user experience.', 'sureforms' ) }
										utmMedium="ai_builder"
									/>
								</div>
								: conversationalFormAiToggle }
							<div
								className="srfm-ai-voice-input-ctn"
							>
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
								<Button
									onClick={ toggleListening }
									className="srfm-ai-voice-input-toggle-btn"
									style={ {
										background: isListening ? '#F0FDF4' : '#D544071A',
										border: isListening ? '0.5px solid #BBF7D0' : '0.5px solid #D544074D',
									} }
								>
									{ isListening ? (
										<>
											<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path
													d="M6 1C5.60218 1 5.22064 1.15804 4.93934 1.43934C4.65804 1.72064 4.5 2.10218 4.5 2.5V6C4.5 6.39782 4.65804 6.77936 4.93934 7.06066C5.22064 7.34196 5.60218 7.5 6 7.5C6.39782 7.5 6.77936 7.34196 7.06066 7.06066C7.34196 6.77936 7.5 6.39782 7.5 6V2.5C7.5 2.10218 7.34196 1.72064 7.06066 1.43934C6.77936 1.15804 6.39782 1 6 1Z"
													stroke="#15803D"
													strokeLinecap="round"
													strokeLinejoin="round"
												/>
												<path d="M9.5 5V6C9.5 6.92826 9.13125 7.8185 8.47487 8.47487C7.8185 9.13125 6.92826 9.5 6 9.5C5.07174 9.5 4.1815 9.13125 3.52513 8.47487C2.86875 7.8185 2.5 6.92826 2.5 6V5" stroke="#15803D" strokeLinecap="round" strokeLinejoin="round" />
												<path d="M6 9.5V11" stroke="#15803D" strokeLinecap="round" strokeLinejoin="round" />
											</svg>
											<span className="srfm-ai-voice-input-text" style={ { color: '#15803D' } }>{ __( 'Listening', 'sureforms' ) }</span>
										</>
									) : (
										<>
											<svg width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
												<g clipPath="url(#clip0_15859_8426)">
													<path d="M1 1L11 11" stroke="#D54407" strokeLinecap="round" strokeLinejoin="round" />
													<path d="M9.44482 6.615C9.48093 6.41198 9.49934 6.20621 9.49982 6V5" stroke="#D54407" strokeLinecap="round" strokeLinejoin="round" />
													<path d="M2.49988 5V6C2.48971 6.69958 2.68944 7.38616 3.0733 7.97112C3.45716 8.55607 4.00752 9.01256 4.65336 9.28166C5.2992 9.55076 6.01086 9.62012 6.69651 9.48079C7.38215 9.34146 8.0103 8.99983 8.49988 8.5" stroke="#D54407" strokeLinecap="round" strokeLinejoin="round" />
													<path d="M7.50016 4.66878V2.49878C7.49813 2.16196 7.3828 1.83563 7.17274 1.57234C6.96268 1.30904 6.67012 1.12411 6.34217 1.04732C6.01422 0.970527 5.66996 1.00635 5.36485 1.14901C5.05973 1.29167 4.81151 1.53288 4.66016 1.83378" stroke="#D54407" strokeLinecap="round" strokeLinejoin="round" />
													<path d="M4.5 4.5V6C4.50026 6.29648 4.58838 6.58623 4.75322 6.83266C4.91806 7.0791 5.15223 7.27115 5.42615 7.38457C5.70008 7.498 6.00147 7.5277 6.29227 7.46993C6.58307 7.41216 6.85023 7.26951 7.06 7.06" stroke="#D54407" strokeLinecap="round" strokeLinejoin="round" />
													<path d="M6 9.5V11" stroke="#D54407" strokeLinecap="round" strokeLinejoin="round" />
												</g>
												<defs>
													<clipPath id="clip0_15859_8426">
														<rect width="12" height="12" fill="white" />
													</clipPath>
												</defs>
											</svg>
											<span className="srfm-ai-voice-input-text" style={ { color: '#D54407' } }>{ __( 'Voice Input', 'sureforms' ) }</span>
										</>
									) }
								</Button>

							</div>
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
							} }
						>
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

									if (
										! userPrompt.value ||
										! userPrompt.value.trim()
									) {
										setShowEmptyError( true );
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

export const getLimitReachedPopup = () => {
	const type = srfm_admin?.srfm_ai_usage_details?.type;
	const formCreationleft = srfm_admin?.srfm_ai_usage_details?.remaining ?? 0;
	const errorCode = srfm_admin?.srfm_ai_usage_details?.code;
	const resetAt = srfm_admin?.srfm_ai_usage_details?.resetAt;

	// shows when the user has encountered an error.
	if ( errorCode ) {
		return (
			<LimitReachedPopup
				title={ srfm_admin?.srfm_ai_usage_details?.title }
				paraOne={ srfm_admin?.srfm_ai_usage_details?.message }
				buttonText={ __( 'Try Again', 'sureforms' ) }
				onclick={ () => {
					window.location.href =
						srfm_admin.site_url +
						'/wp-admin/admin.php?page=add-new-form&method=ai';
				} }
			/>
		);
	}

	// When pro limit is consumed
	if (
		type === 'subscribed' &&
		srfm_admin?.is_pro_active &&
		srfm_admin?.is_pro_license_active &&
		formCreationleft === 0 &&
		resetAt && resetAt > Date.now() / 1000
	) {
		return (
			<LimitReachedPopup
				paraOne={ __(
					'You have reached the FUP limit of AI form generations for the day.',
					'sureforms'
				) }
				paraTwo={ sprintf(
					/* translators: %s: reset time */
					__( 'Please try again after %s.', 'sureforms' ),
					new Date(
						resetAt * 1000
					).toLocaleString()
				) }
				buttonText={ __( 'Try Again', 'sureforms' ) }
				onclick={ () => {
					window.location.href =
						srfm_admin.site_url +
						'/wp-admin/admin.php?page=add-new-form&method=ai';
				} }
			/>
		);
	}

	// When registered limit is consumed
	if ( type === 'registered' && formCreationleft === 0 ) {
		return (
			<LimitReachedPopup
				paraOne={ __(
					'You have reached the maximum number of form generations in your Free Plan.',
					'sureforms'
				) }
				paraTwo={ __(
					'Please upgrade your free plan to keep creating more forms with AI.',
					'sureforms'
				) }
				buttonText={ __( 'Upgrade Plan', 'sureforms' ) }
				onclick={ () => {
					window.open( srfm_admin?.pricing_page_url, '_blank' );
				} }
			/>
		);
	}

	// when initial 3 forms are consumed
	if ( type === 'non-registered' && formCreationleft === 0 ) {
		return (
			<LimitReachedPopup
				paraOne={ __(
					'You have reached the maximum number of form generations.',
					'sureforms'
				) }
				paraTwo={ __(
					'Please connect your website with SureForms AI to create 10 more forms with AI.',
					'sureforms'
				) }
				onclick={ initiateAuth }
			/>
		);
	}

	return <AiFormBuilder />;
};

export default AiFormBuilder;
