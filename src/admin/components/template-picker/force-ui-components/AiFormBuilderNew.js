import { __ } from '@wordpress/i18n';
import { useState, useRef } from '@wordpress/element';
import { Button, Container, Label, TextArea, Title, Switch } from '@bsf/force-ui';
import { ArrowRight, ChevronDown, ChevronUp, MicOff, Mic } from 'lucide-react';
import { applyFilters } from '@wordpress/hooks';
import toast from 'react-hot-toast';
import PremiumNew from '@Admin/dashboard/PremiumNew';

export default ( props ) => {
	const { handleCreateAiForm, setIsBuildingForm, formTypeObj, setFormTypeObj, showEmptyError, setShowEmptyError } = props;

	const [ isListening, setIsListening ] = useState( false ); // State to manage voice recording
	const [ showFormIdeas, setShowFormIdeas ] = useState( true );
	const [ characterCount, setCharacterCount ] = useState( 0 );
	const [ text, setText ] = useState( '' );
	const recognitionRef = useRef( null ); // To store SpeechRecognition instance
	const showAiConversationalFormToggle = true;
	const conversationalFormAiToggle = applyFilters(
		'srfm.aiFormScreen.conversational.toggle',
		showAiConversationalFormToggle,
		formTypeObj,
		setFormTypeObj
	);

	const handlePromptClick = ( prompt ) => {
		setShowEmptyError( false );
		setText( prompt );
		setCharacterCount( prompt.length );
	};

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

	// Stops voice input if typing begins
	const handleTyping = () => {
		if ( isListening && recognitionRef.current ) {
			recognitionRef.current.stop();
			setIsListening( false );
		}
		setShowEmptyError( false );
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
				setText( text + speechResult );
				setCharacterCount( text );
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

	return (
		<Container
			className="mt-[90px] p-4 gap-1.5 bg-background-primary border-0.5 border-solid border-border-subtle shadow-sm-blur-2 rounded-xl w-full max-w-[680px] mx-auto"
			containerType="flex"
			direction="column"
		>
			<Container.Item className="p-2 gap-6">
				<Title
					tag="h4"
					size="md"
					className="text-text-primary font-semibold"
					title={ __( 'Please describe the form you want to create', 'sureforms' ) }
				/>
			</Container.Item>
			<Container.Item className="p-2 gap-6">
				<TextArea
					aria-label={ __( 'Describe the form you want to create', 'sureforms' ) }
					placeholder={ __( "E.g. Form to gather feedback from our customer for our product functionality, usability , how much you will rate it and what you don't like about it.", 'sureforms' ) }
					id="textarea"
					value={ text }
					size="lg"
					className="gap-1.5 w-full h-[124px] font-normal text-md ${characterCount > 0 ? 'text-field-placeholder' : 'text-text-primary'}"
					onChange={ ( e ) => {
						handlePromptClick( e );
					} }
					onInput={ handleTyping }
				/>
				{ showEmptyError && <Label
					size="sm"
					variant="error"
					className="font-semibold text-sm"
				>
					{ __( 'Prompt cannot be empty.', 'sureforms' ) }
				</Label> }
			</Container.Item>
			{ false === conversationalFormAiToggle ? <Container.Item className="p-2 flex flex-wrap items-center gap-3">
				<Switch
					aria-label={ __( 'Create Conversational Form', 'sureforms' ) }
					id="switch-element"
					onChange={ function Ki() { } }
					size="sm"
					className="border border-toggle-off-border shadow-sm-blur-2"
					disabled={ true }
				/>
				<Label
					variant="neutral"
					className="font-medium text-field-label text-sm"
				>
					{ __( 'Create Conversational Form', 'sureforms' ) }
				</Label>
				<PremiumNew
					title={ __( 'Unlock Conversational Forms', 'sureforms' ) }
					description={ __( 'With the SureForms Pro Plan, you can transform your forms into engaging conversational layouts for a seamless user experience.', 'sureforms' ) }
					btnText={ __( 'Upgrade', 'sureforms' ) }
					portalId="srfm-add-new-form-container"
					utmMedium="ai_builder"
				/>
			</Container.Item>
				: null
			}
			<Container.Item className="p-2 gap-6">
				<Container
					containerType="flex"
					direction="column"
				>
					<Container.Item>
						<Container className="flex flex-wrap flex-row justify-between items-center">
							<Container.Item className="p-1 gap-1 flex flex-row items-center">
								<Label
									variant="neutral"
									className="font-medium text-text-primary text-sm gap-1 flex items-center cursor-pointer"
									onClick={ () =>
										setShowFormIdeas( ! showFormIdeas )
									}
								>
									{ __( 'Some Form Ideas', 'sureforms' ) }

									{ showFormIdeas ? (
										<ChevronUp size={ 20 } className="!text-icon-secondary" />
									) : (
										<ChevronDown size={ 20 } className="!text-icon-secondary" />
									) }
								</Label>
							</Container.Item>
							<Container.Item className="py-2 px-4 gap-2">
								{ isListening
									? <Button
										icon={ <Mic size={ 12 } /> }
										iconPosition="left"
										variant="outline"
										size="xs"
										className="rounded-full bg-badge-background-green border-0.5 border-solid border-badge-border-green text-badge-color-green font-medium text-xs hover:bg-badge-background-green hover:cursor-pointer"
										onClick={ toggleListening }
									>
										{ __( 'Listening', 'sureforms' ) }
									</Button>
									: <Button
										icon={ <MicOff size={ 12 } /> }
										iconPosition="left"
										variant="outline"
										size="xs"
										className="rounded-full bg-badge-background-orange-10 border-0.5 border-solid border-badge-background-orange-30 text-brand-800 font-medium text-xs hover:bg-badge-background-orange-10 hover:cursor-pointer"
										onClick={ toggleListening }
									>
										{ __( 'Voice Input', 'sureforms' ) }
									</Button>

								}
							</Container.Item>
						</Container>
					</Container.Item>
					{ showFormIdeas && <Container.Item>
						<Container className="gap-2 flex-wrap"
							containerType="flex"
							direction="row"
						>
							{ examplePrompts.map( ( prompt, index ) => (
								<Label
									key={ index }
									size="md"
									type="pill"
									variant="neutral"
									className="rounded-full border-0.5 border-solid border-badge-border-gray bg-badge-background-gray hover:bg-badge-background-gray hover:cursor-pointer text-badge-color-gray font-medium text-sm py-1 px-1.5 gap-1 flex"
									onClick={ () =>
										handlePromptClick(
											prompt.title
										)
									}
								>
									{ prompt.title }
								</Label>
							) ) }
						</Container>
					</Container.Item> }
				</Container>
			</Container.Item>
			<Container.Item className="py-1 px-2 gap-3 flex flex-col sm:flex-row justify-end">
				<Label
					variant="neutral"
					className="font-semibold text-text-tertiary text-sm cursor-pointer"
				>
					{ characterCount }/2000
				</Label>
				<Button
					className="bg-button-primary hover:bg-button-primary gap-1 border border-solid border-button-primary text-text-on-color hover:border-button-primary shadow-sm-blur-2"
					icon={ <ArrowRight size={ 20 } strokeWidth={ 1.25 } /> }
					iconPosition="right"
					size="md"
					tag="button"
					type="button"
					variant="outline"
				>
					<Label
						variant="neutral"
						className="font-semibold text-text-on-color text-sm cursor-pointer"
						onClick={ () => {
							if (
								! text ||
								! text.trim()
							) {
								setShowEmptyError( true );
								const textArea = document.getElementById( 'textarea' );
								textArea.focus();
								return;
							}

							handleCreateAiForm(
								text,
								[],
								true
							);
							setIsBuildingForm( true );
						} }
					>
						{ __( 'Generate Form', 'sureforms' ) }
					</Label>
				</Button>
			</Container.Item>
		</Container >
	);
};
