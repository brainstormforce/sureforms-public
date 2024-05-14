import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { handleAddNewPost } from '@Utils/Helpers';

const TemplateScreen = () => {
	const [ message, setMessage ] = useState( '' );
	const [ errorMessage, setErrorMessage ] = useState( '' );
	const [ buttonLabel, setButtonLabel ] = useState(
		__(
			'Get Started with 1000 Free Monthly Credits',
			'ultimate-addons-for-gutenberg'
		)
	);

	const handleCreateAiForm = async (
		userCommand,
		previousMessages,
		useSystemMessage
	) => {
		if ( '1' !== srfm_admin.capability ) {
			console.error( 'User does not have permission to create posts' );
			return;
		}

		if ( ! userCommand ) {
			setErrorMessage(
				__( 'Please enter a valid prompt.', 'sureforms' )
			);
			return;
		}

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

		try {
			setMessage(
				__( 'Getting Fields Data from SureForms AI…', 'sureforms' )
			);
			const response = await apiFetch( {
				path: 'sureforms/v1/generate-form',
				method: 'POST',
				data: postData,
			} );

			if ( response ) {
				const data = JSON.parse( response.data );
				const formJsonData = data.choices[ 0 ].message.content;
				let sanitizedFormJsonData = formJsonData
					.replace( /```/g, '' )
					.replace( /json/g, '' );
				sanitizedFormJsonData = JSON.parse( sanitizedFormJsonData );

				setMessage( __( 'Creating Post Content…', 'sureforms' ) );

				const postContent = await apiFetch( {
					path: 'sureforms/v1/map-fields',
					method: 'POST',
					data: { form_data: sanitizedFormJsonData },
				} );

				if ( ! postContent ) {
					setErrorMessage(
						__(
							'Error creating post content. Please check your prompt',
							'sureforms'
						)
					);
					return;
				}

				setMessage( __( 'Creating Post…', 'sureforms' ) );
				handleAddNewPost(
					postContent,
					sanitizedFormJsonData?.formTitle,
					[]
				);
			} else {
				console.error(
					'Error creating sureforms_form:',
					response.message
				);
			}
		} catch ( error ) {
			console.log( error );
		}
	};

	const examplePrompts = [
		__( 'Create a user feedback form', 'sureforms' ),
		__( 'Create a GYM membership form', 'sureforms' ),
		__( 'Create a contact form for my website', 'sureforms' ),
		__( 'Create a form to submit support tickets', 'sureforms' ),
	];

	const handlePromptClick = ( prompt ) => {
		const userPrompt = document.querySelector( 'textarea' );
		userPrompt.value = prompt;
	};

	// Function: Authorize Zip AI.
	const authorizeZipAI = ( event ) => {
		event.preventDefault();
		// window.location.assign( uag_react.zip_ai_auth_middleware );

		// Get all the auth buttons and disable them.
		const authButtons = document.querySelectorAll(
			'.srfm-ai-features-authorization'
		);
		authButtons.forEach( ( authButton ) => {
			authButton.disabled = true;
		} );

		// Create the window positioning to be centered.
		const positioning = {
			left: ( window.screen.width - 480 ) / 2,
			top: ( window.screen.height - 720 ) / 2,
		};

		// Redirect to the Zip AI Authorization URL.
		const authWindow = window.open(
			srfm_admin.zip_ai_auth_middleware,
			'SureFormsAiFeaturesAuthorization',
			`width=480,height=720,top=${ positioning.top },left=${ positioning.left },scrollbars=0`
		);

		// Create an object with the security property.
		const data = {
			security: srfm_admin.zip_ai_verify_authenticity_nonce,
		};

		// Set a counter for timeout.
		let iterations = 0;

		// Update the texts.
		setButtonLabel(
			__(
				'Getting Started with 1000 Free Monthly Credits',
				'ultimate-addons-for-gutenberg'
			)
		);

		// Set an interval to check if the option was updated.
		const authVerificationInterval = setInterval( () => {
			// Clear the interval if the window was closed, or automatically after 5 minutes.
			if ( authWindow.closed || 300 === iterations ) {
				// Close the auth window if it wasn't closed.
				if ( ! authWindow.closed ) {
					authWindow.close();
				}
				// Reset the texts and enable the button.
				clearInterval( authVerificationInterval );
				setButtonLabel(
					__(
						'Get Started with 1000 Free Monthly Credits',
						'ultimate-addons-for-gutenberg'
					)
				);
				authButtons.forEach( ( authButton ) => {
					authButton.disabled = false;
				} );
			}

			// // Call the getApiData function with the required parameters.
			// const getApiFetchData = getApiData( {
			// 	url: srfm_admin.ajax_url,
			// 	action: 'uag_zip_ai_verify_authenticity',
			// 	data,
			// } );

			// // Make the AJAX request to check if the option was updated.
			// getApiFetchData.then( ( response ) => {
			// 	if ( response?.success && response?.data?.is_authorized ) {
			// 		authWindow.close();
			// 		localStorage.setItem( 'zipAiAuthorizationStatus', true );
			// 		clearInterval( authVerificationInterval );
			// 		window.location.reload();
			// 		setButtonLabel(
			// 			__(
			// 				'Get Started with 1000 Free Monthly Credits',
			// 				'ultimate-addons-for-gutenberg'
			// 			)
			// 		);
			// 	}
			// } );

			if ( srfm_admin.is_authorized ) {
				authWindow.close();
				localStorage.setItem( 'zipAiAuthorizationStatus', true );
				clearInterval( authVerificationInterval );
				window.location.reload();
				setButtonLabel(
					__(
						'Get Started with 1000 Free Monthly Credits',
						'ultimate-addons-for-gutenberg'
					)
				);
			}
			iterations++;
		}, 500 );
	};

	return (
		<div className="srfm-ai-form-builder-ctn">
			<Button
				onClick={ authorizeZipAI }
				className="srfm-ai-features-authorization srfm-example-ai-prompt-btn"
			>
				{ buttonLabel }
				<span>{ '→' }</span>
			</Button>
			<h2>{ __( 'AI Form Builder', 'sureforms' ) }</h2>
			<p>
				{ __(
					'Enter a prompt to generate a form. You can use the example prompts below to get started.',
					'sureforms'
				) }
			</p>
			<textarea maxLength={ 2000 } />
			<div className="srfm-example-ai-prompt-ctn">
				{ examplePrompts.map( ( prompt, index ) => (
					<Button
						key={ index }
						onClick={ () => handlePromptClick( prompt ) }
						className="srfm-example-ai-prompt-btn"
					>
						{ prompt }
					</Button>
				) ) }
			</div>
			{ message === '' ? (
				<Button
					onClick={ () => {
						const userPrompt = document.querySelector( 'textarea' );
						handleCreateAiForm( userPrompt.value, [], true );
					} }
					className="srfm-generate-ai-form-btn"
				>
					{ __( 'Generate Form', 'sureforms' ) }
				</Button>
			) : (
				<p className="srfm-ai-form-builder-message">{ message }</p>
			) }
			<pre>
				Credits Used { srfm_admin.zip_ai_credit_details?.used } /{ ' ' }
				{ srfm_admin.zip_ai_credit_details?.total }
			</pre>
			<pre style={ { color: 'red' } }>{ errorMessage }</pre>
		</div>
	);
};

export default TemplateScreen;
