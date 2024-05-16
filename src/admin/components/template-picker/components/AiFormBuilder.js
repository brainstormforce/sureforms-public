import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { handleAddNewPost, getRemaingCredits } from '@Utils/Helpers';
import { MdArrowForward } from 'react-icons/md';
import aiFormBuilderPlaceholder from '@Image/ai-form-builder.svg';
import { CircularProgressBar } from '@tomickigrzegorz/react-circular-progress-bar';
import Header from './Header.js';
import LimitReachedPopup from './LimitReachedPopup.js';
import ErrorPopup from './ErrorPopup.js';

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
	const examplePrompts = [
		{
			title: 'Generate User Survey Form',
			description:
				'Collects data on user satisfaction, ease of use, and overall experience with a product or service.',
		},
		{
			title: 'Request for Quote Form',
			description:
				'Allows users to request quotes for products or services, providing details such as quantity, specifications, and contact information.',
		},
		{
			title: 'Event Registration Form',
			description:
				'Enables users to register for an event, providing details such as name, contact information, and any additional requirements or preferences.',
		},
	];

	const handleCreateAiForm = async (
		userCommand,
		previousMessages,
		useSystemMessage
	) => {
		setPercentBuild( 0 );
		if ( '1' !== srfm_admin.capability ) {
			console.error( 'User does not have permission to create posts' );
			return;
		}

		if ( ! userCommand ) {
			setShowEmptyError( true );
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
				const data = JSON.parse( response.data );
				const formJsonData = data.choices[ 0 ].message.content;

				let sanitizedFormJsonData = formJsonData
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
				console.error(
					'Error creating sureforms form using AI: ',
					response.message
				);
			}
		} catch ( error ) {
			console.log( error );
		}
	};

	const handlePromptClick = ( prompt ) => {
		setShowEmptyError( false );
		const userPrompt = document.querySelector( 'textarea' );
		userPrompt.value = prompt;
	};

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

	if ( showLimitReachedPopup ) {
		return (
			<LimitReachedPopup
				setShowLimitReachedPopup={ setShowLimitReachedPopup }
			/>
		);
	}

	return (
		<>
			<Header />
			<div className="srfm-ts-main-container srfm-content-section">
				<div className="srfm-ai-builder-container">
					<div className="srfm-ai-builder-inner-container">
						<div className="srfm-ai-builder-header">
							<h1 className="srfm-ai-builder-header-title">
								{ __(
									'What Type of Form Do You Want to Create?',
									'sureforms'
								) }
							</h1>
							<p className="srfm-ai-builder-header-subtitle">
								{ __(
									'The best way to describe the form you want is by providing as much details, mention the audience you are creating the form for and how you want your form to look like.',
									'sureforms'
								) }
							</p>
						</div>
						<div className="srfm-ai-builder-textarea-ctn">
							<p className="srfm-ai-builder-textarea-title">
								{ __( 'Create a Form', 'sureforms' ) }
							</p>
							<textarea
								style={ {
									borderColor: showEmptyError
										? '#CD1A1A'
										: '#CBD5E1',
								} }
								placeholder={ __(
									'E.g. Form to gather feedback from our customer for our product functionality, usability, how much you will rate it and what you don’t like about it.',
									'sureforms'
								) }
								maxLength={ 2000 }
								onChange={ () => setShowEmptyError( false ) }
							/>
							{ showEmptyError && (
								<span className="srfm-ai-builder-textarea-error">
									{ __(
										'Prompt cannot be empty.',
										'sureforms'
									) }
								</span>
							) }
							<div className="srfm-ai-builder-prompt-btn-ctn">
								{ examplePrompts.map( ( prompt, index ) => (
									<Button
										key={ index }
										className="srfm-ai-builder-prompt-btn"
										onClick={ () =>
											handlePromptClick( prompt.title )
										}
									>
										<span className="srfm-ai-builder-prompt-title">
											{ prompt.title }
										</span>
										<span className="srfm-ai-builder-prompt-description">
											{ prompt.description }
										</span>
									</Button>
								) ) }
							</div>
						</div>
						<hr className="srfm-ai-builder-separator" />
						<Button
							className="srfm-ai-builder-create-form-btn"
							onClick={ () => {
								const userPrompt =
									document.querySelector( 'textarea' );

								if ( ! userPrompt.value ) {
									setShowEmptyError( true );
									return;
								}

								if ( getRemaingCredits() <= 0 ) {
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
								{ __( 'Create Form', 'sureforms' ) }
							</span>
							<MdArrowForward color="white" size={ 20 } />
						</Button>
					</div>
				</div>
			</div>
		</>
	);
};

export default AiFormBuilder;
