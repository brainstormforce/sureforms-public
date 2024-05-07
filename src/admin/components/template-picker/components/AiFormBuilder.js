import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { handleAddNewPost } from '@Utils/Helpers';

const TemplateScreen = () => {
	const [ message, setMessage ] = useState( '' );
	const [ errorMessage, setErrorMessage ] = useState();

	const createAiForm = async (
		userCommand,
		previousMessages,
		useSystemMessage
	) => {
		if ( '1' !== srfm_admin.capability ) {
			console.error( 'User does not have permission to create posts' );
			return;
		}

		// check if userCommand is empty then setErrorMessage as 'Please enter a valid prompt.'
		if ( ! userCommand ) {
			setErrorMessage( 'Please enter a valid prompt.' );
			return;
		}

		const messageArray = [];

		// Add the previous messages to the message array.
		if ( previousMessages?.length ) {
			previousMessages.forEach( ( chat ) => {
				messageArray.push( { role: chat.role, content: chat.message } );
			} );
		}

		// Add the user message to the message array.
		messageArray.push( { role: 'user', content: userCommand } );

		const postData = {
			message_array: messageArray,
			use_system_message: useSystemMessage,
		};

		console.log( postData );

		// Send the user message to the sureforms/v1/generate-form endpoint.
		// This is the endpoint that will generate the form based on the user's input.

		// return apiFetch( {
		// 	path: 'sureforms/v1/generate-form',
		// 	method: 'POST',
		// 	data: postData,
		// } );

		// handleAddNewPost using the postData

		try {
			setMessage( 'Getting field labels from SureForms AI!' );
			const response = await apiFetch( {
				path: 'sureforms/v1/generate-form',

				method: 'POST',
				data: postData,
			} );

			if ( response ) {
				// setMessage( 'Form markup generated...' );
				// console.log( 'got response' );

				// console.log( response.data );

				// get eh choice message from open ai response

				// convert json to object

				// response = JSON.parse( response );

				// const choiceMessage =
				// 	response.data.choices[ 0 ].message.content;

				setMessage( 'Mapping data to field markup...' );

				const data = JSON.parse( response.data );

				const formJsonData = data.choices[ 0 ].message.content;
				// parse the response and send it to the sureforms/v1/map-fields endpoint

				// remove ``` from the response.data

				let sanitizedFormJsonData = formJsonData.replace( /```/g, '' );

				// remove word "json" from the response.data

				sanitizedFormJsonData = sanitizedFormJsonData.replace(
					/json/g,
					''
				);

				sanitizedFormJsonData = JSON.parse( sanitizedFormJsonData );

				// data = JSON.parse( data );

				setMessage( 'Creating Post Content...' );

				console.log( sanitizedFormJsonData );

				const postContent = await apiFetch( {
					path: 'sureforms/v1/map-fields',

					method: 'POST',
					data: {
						form_data: sanitizedFormJsonData,
					},
				} );

				console.log( postContent );

				// if the postContent is empty, setErrorMessage as 'Error creating post content. Please check your prompt'

				if ( ! postContent ) {
					setErrorMessage(
						'Error creating post content. Please check your prompt'
					);
					return;
				}

				setMessage( 'Creating Post...' );

				// check if postContent have srfm/page-break then create array [ 'srfm_is_page_break' = true]
				// else create empty array []

				// const pageBreak = postContent.includes( 'srfm/page-break' )
				// 	? [
				// 			{
				// 				_srfm_is_page_break: [ true ],
				// 			},
				// 	  ]
				// 	: [];

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

	function QueryScreen() {
		return (
			<div
				style={ {
					display: 'flex',
					flexDirection: 'column',
					justifyContent: 'center',
					alignItems: 'center',
					height: '100vh',
				} }
			>
				<textarea
					style={ {
						width: '80%',
						height: '300px',
						padding: '10px',
						fontSize: '16px',
						fontFamily: 'monospace',
						borderRadius: '5px',
						marginBottom: '20px',
					} }
					// value={ textAreaValue }
					// onChange={ ( e ) => setTextAreaValue( e.target.value ) }
				></textarea>
				{ message === '' ? (
					<Button
						onClick={ () => {
							const userPrompt =
								document.querySelector( 'textarea' );

							// send userPrompt.value to the sureforms/v1/generate-form endpoint
							createAiForm( userPrompt.value, [], true );
						} }
					>
						{ __( 'Generate Form', 'sureforms' ) }
					</Button>
				) : (
					message
				) }
				<pre>Total requests = { srfm_admin.srfm_ai_request_count }</pre>
				<pre
					style={ {
						color: 'red',
					} }
				>
					{ errorMessage }
				</pre>
			</div>
		);
	}

	return <QueryScreen />;
};

export default TemplateScreen;
