import { __ } from '@wordpress/i18n';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';
import { handleAddNewPost } from '@Utils/Helpers';
import { MdArrowForward } from 'react-icons/md';
import aiFormBuilderPlaceholder from '@Image/ai-form-builder.svg';
import { CircularProgressBar } from '@tomickigrzegorz/react-circular-progress-bar';
import Header from './Header.js';

const TemplateScreen = () => {
	const [ message, setMessage ] = useState(
		__( 'Connecting with AI...', 'sureforms' )
	);
	const [ errorMessage, setErrorMessage ] = useState( '' );
	const [ isBuildingForm, setIsBuildingForm ] = useState( false );
	const [ percentBuild, setPercentBuild ] = useState( 0 );

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

		// setMessage(  );

		// after 1 second, set the message to "Creating Form..."
		// setTimeout( () => {
		// setPercentBuild( 25 );
		// setMessage( __( 'Generating Fields...', 'sureforms' ) );
		// }, 2000 );

		// add a pause of 2 seconds and set percentBuild to 25 without using setTimeout
		setPercentBuild( 25 );
		setMessage( __( 'Generating Fields...', 'sureforms' ) );
		// setTimeout( () => {
		// 	setPercentBuild( 40 );
		// 	setMessage( __( 'Generating Fields Labels...', 'sureforms' ) );
		// }, 4500 );

		// setTimeout( () => {
		// 	setMessage(
		// 		__( 'Optimizing and Sorting the Form...', 'sureforms' )
		// 	);
		// }, 6000 );

		// setTimeout( () => {
		// 	setMessage(
		// 		__( 'Optimizing and Sorting the Form...', 'sureforms' )
		// 	);
		// }, 6000 );

		// setTimeout( () => {
		// 	setMessage(
		// 		__(
		// 			"It's taking a bit more than usual. Please bear with us....",
		// 			'sureforms'
		// 		)
		// 	);
		// }, 20000 );

		try {
			const response = await apiFetch( {
				path: 'sureforms/v1/generate-form',
				method: 'POST',
				data: postData,
			} );

			if ( response ) {
				setPercentBuild( 50 );
				const data = JSON.parse( response.data );
				const formJsonData = data.choices[ 0 ].message.content;
				let sanitizedFormJsonData = formJsonData
					.replace( /```/g, '' )
					.replace( /json/g, '' );
				sanitizedFormJsonData = JSON.parse( sanitizedFormJsonData );

				setMessage(
					__( 'Just doing some final touches...', 'sureforms' )
				);
				setPercentBuild( 75 );

				const postContent = await apiFetch( {
					path: 'sureforms/v1/map-fields',
					method: 'POST',
					data: { form_data: sanitizedFormJsonData },
				} );

				if ( postContent ) {
					setMessage( __( 'Redirecting to Form', 'sureforms' ) );
					setPercentBuild( 100 );

					handleAddNewPost(
						postContent,
						sanitizedFormJsonData?.formTitle,
						[]
					);
				}
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

	if ( isBuildingForm ) {
		return (
			<div className="srfm-ts-main-container srfm-content-section">
				<div
					style={ {
						display: 'flex',
						flexWrap: 'wrap',
						alignItems: 'center',
						justifyContent: 'center',
						padding: '50px 1.25em',
						position: 'absolute',
						top: '50%',
						left: '50%',
						transform: 'translate(-50%, -50%)',
						width: '100%',
						height: '100%',
					} }
				>
					<div
						style={ {
							display: 'flex',
							flexDirection: 'column',
							alignItems: 'center',
							justifyContent: 'center',
							width: '480px',
							height: '440px',
							gap: '24px',
						} }
					>
						<div
							style={ {
								display: 'flex',
								flexDirection: 'row',
								alignItems: 'center',
								justifyContent: 'center',
								width: '400px',
								height: '72px',
								gap: '24px',
							} }
						>
							<CircularProgressBar
								colorCircle="#3d45921a"
								colorSlice={ '#3D4592' }
								percent={ percentBuild }
								round
								speed={ 45 }
								fontColor="#0F172A"
								fontSize="18px"
								fontWeight={ 700 }
								size={ 72 }
							/>
							<div
								style={ {
									display: 'flex',
									flexDirection: 'column',
									justifyContent: 'center',
									width: '304px',
									height: '50px',
									gap: '2px',
								} }
							>
								<h1
									style={ {
										width: '273px',
										height: '28px',
										fontSize: '20px',
										fontWeight: '700',
										lineHeight: '28px',
										margin: 0,
										padding: 0,
										textAlign: 'start',
									} }
								>
									{ __(
										'We are building your Form...',
										'sureforms'
									) }
								</h1>
								<span
									style={ {
										width: '301px',
										height: '20px',
										fontSize: '14px',
										fontWeight: '400',
										textAlign: 'start',
									} }
								>
									{ message }
								</span>
							</div>
						</div>
						<img
							src={ aiFormBuilderPlaceholder }
							alt="AI Form Builder"
						/>
					</div>
				</div>
			</div>
		);
	}

	return (
		<>
			<Header />
			<div className="srfm-ts-main-container srfm-content-section">
				<div
					style={ {
						display: 'flex',
						flexWrap: 'wrap',
						alignItems: 'center',
						justifyContent: 'center',
						padding: '50px 1.25em',
						position: 'absolute',
						top: '50%',
						left: '50%',
						transform: 'translate(-50%, -50%)',
						width: '100%',
						height: '100%',
					} }
				>
					<div
						style={ {
							display: 'flex',
							flexDirection: 'column',
							gap: '32px',
							width: '768px',
							height: '514px',
						} }
					>
						<div
							style={ {
								display: 'flex',
								flexDirection: 'column',
								gap: '12px',
							} }
						>
							<h1
								style={ {
									fontSize: '32px',
									fontWeight: '600',
									color: '#0F172A',
									lineHeight: '41.6px',
									margin: '0',
									padding: '0',
								} }
							>
								{ __(
									'What Type of Form Do You Want to Create?',
									'sureforms'
								) }
							</h1>
							<p
								style={ {
									fontSize: '16px',
									fontWeight: '400',
									color: '#64748B',
									lineHeight: '24px',
									margin: '0',
									padding: '0',
								} }
							>
								{ __(
									'The best way to describe the form you want is by providing as much details, mention the audience you are creating the form for and how you want your form to look like.',
									'sureforms'
								) }
							</p>
						</div>
						<div
							style={ {
								display: 'flex',
								flexDirection: 'column',
								gap: '20px',
							} }
						>
							<p
								style={ {
									fontSize: '14px',
									fontWeight: '600',
									lineHeight: '20px',
									color: '#0F172A',
									margin: '0',
									padding: '0',
								} }
							>
								{ __( 'Create a Form', 'sureforms' ) }
							</p>
							<textarea
								style={ {
									width: '768px',
									height: '144px',
									borderRadius: '4px',
									border: '1px solid #CBD5E1',
									outline: 'none',
									boxShadow: 'none',
									resize: 'none',
								} }
								placeholder="E.g. Form to gather feedback from our customer for our product functionality, usability, how much you will rate it and what you don’t like about it."
								maxLength={ 2000 }
							/>
							<div
								style={ {
									display: 'flex',
									width: '768px',
									height: '62px',
									gap: '12px',
								} }
							>
								<Button
									style={ {
										display: 'flex',
										flexDirection: 'column',
										alignItems: 'flex-start',
										justifyContent: 'left',
										width: '248px',
										height: '62px',
										background: '#ffffff',
										border: '1px solid #CBD5E1',
										borderRadius: '4px',
										padding: '12px',
										gap: '4px',
										cursor: 'pointer',
									} }
									onClick={ () =>
										handlePromptClick(
											'Generate User Survey Form'
										)
									}
								>
									<span
										style={ {
											width: '191px',
											height: '18px',
											fontSize: '14px',
											fontWeight: '500',
											lineHeight: '18px',
											textAlign: 'start',
										} }
									>
										{ __(
											'Generate User Survey Form',
											'sureforms'
										) }
									</span>
									<span
										style={ {
											width: '224px',
											height: '16px',
											fontSize: '13px',
											fontWeight: '400',
											lineHeight: '16px',
											color: '#94A3B8',
											textAlign: 'start',
											overflow: 'hidden',
											whiteSpace: 'nowrap',
											textOverflow: 'ellipsis',
										} }
									>
										{ __(
											'Collects data on user satisfaction, ease of use, and overall experience with a product or service.',
											'sureforms'
										) }
									</span>
								</Button>
								<Button
									style={ {
										display: 'flex',
										flexDirection: 'column',
										alignItems: 'flex-start',
										justifyContent: 'left',
										width: '248px',
										height: '62px',
										background: '#ffffff',
										border: '1px solid #CBD5E1',
										borderRadius: '4px',
										padding: '12px',
										gap: '4px',
										cursor: 'pointer',
									} }
									onClick={ () =>
										handlePromptClick(
											'Request for Quote Form'
										)
									}
								>
									<span
										style={ {
											width: '191px',
											height: '18px',
											fontSize: '14px',
											fontWeight: '500',
											lineHeight: '18px',
											textAlign: 'start',
										} }
									>
										{ __(
											'Request for Quote Form',
											'sureforms'
										) }
									</span>
									<span
										style={ {
											width: '224px',
											height: '16px',
											fontSize: '13px',
											fontWeight: '400',
											lineHeight: '16px',
											color: '#94A3B8',
											textAlign: 'start',
											overflow: 'hidden',
											whiteSpace: 'nowrap',
											textOverflow: 'ellipsis',
										} }
									>
										{ __(
											'Allows users to request quotes for products or services, providing details such as quantity, specifications, and contact information.',
											'sureforms'
										) }
									</span>
								</Button>
								<Button
									style={ {
										display: 'flex',
										flexDirection: 'column',
										alignItems: 'flex-start',
										justifyContent: 'left',
										width: '248px',
										height: '62px',
										background: '#ffffff',
										border: '1px solid #CBD5E1',
										borderRadius: '4px',
										padding: '12px',
										gap: '4px',
										cursor: 'pointer',
									} }
									onClick={ () =>
										handlePromptClick(
											'Event Registration Form'
										)
									}
								>
									<span
										style={ {
											width: '191px',
											height: '18px',
											fontSize: '14px',
											fontWeight: '500',
											lineHeight: '18px',
											textAlign: 'start',
										} }
									>
										{ __(
											'Event Registration Form',
											'sureforms'
										) }
									</span>
									<span
										style={ {
											width: '224px',
											height: '16px',
											fontSize: '13px',
											fontWeight: '400',
											lineHeight: '16px',
											color: '#94A3B8',
											textAlign: 'start',
											overflow: 'hidden',
											whiteSpace: 'nowrap',
											textOverflow: 'ellipsis',
										} }
									>
										{ __(
											'Enables users to register for an event, providing details such as name, contact information, and any additional requirements or preferences.',
											'sureforms'
										) }
									</span>
								</Button>
							</div>
						</div>
						<hr
							style={ {
								width: '768px',
								height: '1px',
								background: '#CBD5E1',
							} }
						/>
						<Button
							style={ {
								display: 'flex',
								alignItems: 'center',
								justifyContent: 'space-between',
								backgroundColor: '#D54407',
								color: 'white',
								width: '164px',
								height: '48px',
								padding: '12px 16px 12px 24px',
								gap: '8px',
								borderRadius: '6px',
								border: 'none',
								cursor: 'pointer',
							} }
							onClick={ () => {
								const userPrompt =
									document.querySelector( 'textarea' );
								handleCreateAiForm(
									userPrompt.value,
									[],
									true
								);
								setIsBuildingForm( true );
							} }
						>
							<span
								style={ {
									fontSize: '16px',
									fontWeight: '600',
									lineHeight: '24px',
								} }
							>
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

export default TemplateScreen;
