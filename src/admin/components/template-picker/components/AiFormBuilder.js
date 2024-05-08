import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { handleAddNewPost } from '@Utils/Helpers';

const TemplateScreen = () => {
	const [ message, setMessage ] = useState( '' );
	const [ errorMessage, setErrorMessage ] = useState( '' );

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
				handleAddNewPost( postContent, 'AI Form', [] );
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

	return (
		<div className="srfm-ai-form-builder-ctn">
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
