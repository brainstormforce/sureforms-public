import { __ } from '@wordpress/i18n';
import { useState, useRef } from '@wordpress/element';
import {
	Button,
	Container,
	Label,
	TextArea,
	Title,
	toast,
	Switch,
} from '@bsf/force-ui';
import { ArrowRight, ChevronDown, ChevronUp, MicOff, Mic } from 'lucide-react';
import { applyFilters } from '@wordpress/hooks';
import PremiumBadge from '@Admin/components/PremiumBadge';
import { cn, srfmClassNames } from '@Utils/Helpers';
import ConnectWithAIBanner from '../ai-form-builder-components/ConnectWithAIBanner.js';

export default ( props ) => {
	const {
		handleCreateAiForm,
		setIsBuildingForm,
		formTypeObj,
		setFormTypeObj,
		showEmptyError,
		setShowEmptyError,
	} = props;

	const [ isListening, setIsListening ] = useState( false ); // State to manage voice recording
	const [ showFormIdeas, setShowFormIdeas ] = useState( true );
	const [ characterCount, setCharacterCount ] = useState( 0 );
	const [ text, setText ] = useState( '' );
	const recognitionRef = useRef( null ); // To store SpeechRecognition instance
	const showAiConversationalFormToggle = false;
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
		const SpeechRecognition =
			window.SpeechRecognition || window.webkitSpeechRecognition;
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
				const speechResult =
					event.results[ event.results.length - 1 ][ 0 ].transcript;
				setText( text + speechResult );
				setCharacterCount( text.length + speechResult.length );
			};
			recognition.onerror = ( e ) => {
				recognition.stop();
				setIsListening( false );
				toast.dismiss();

				if ( e.error === 'not-allowed' ) {
					toast.error(
						__(
							'Please allow microphone access to use voice input.',
							'sureforms'
						),
						{
							duration: 5000,
						}
					);
					return;
				}

				toast.error(
					__(
						'Speech recognition is not supported in your current browser. Please use Google Chrome / Safari.',
						'sureforms'
					),
					{
						duration: 5000,
					}
				);
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

	// conversational form toggle
	const conversationalAiToggle = (
		<Container.Item className="py-2 flex flex-wrap items-center gap-2">
			<Switch
				aria-label={ __( 'Create Conversational Form', 'sureforms' ) }
				id="switch-element"
				size="sm"
				className="shadow-sm-blur-2"
				disabled={ true }
			/>
			<Label variant="neutral" size="sm">
				{ __( 'Create Conversational Form', 'sureforms' ) }
			</Label>
			<PremiumBadge
				tooltipHeading={ __(
					'Unlock Conversational Forms',
					'sureforms'
				) }
				tooltipContent={ __(
					'With the SureForms Pro Plan, you can transform your forms into engaging conversational layouts for a seamless user experience.',
					'sureforms'
				) }
				utmMedium="ai_builder"
			/>
		</Container.Item>
	);

	const is_pro_active =
		srfm_admin?.is_pro_active && srfm_admin?.is_pro_license_active;

	const VoiceToggleButton = () => {
		const buttonProps = isListening
			? {
				icon: <Mic size={ 12 } />,
				required_className: [
					'bg-badge-background-green',
					'border-badge-border-green',
					'text-badge-color-green',
					'hover:bg-badge-background-green',
				],
				label: __( 'Listening', 'sureforms' ),
			  }
			: {
				icon: <MicOff size={ 12 } />,
				required_className: [
					'bg-badge-background-orange-10',
					'border-badge-background-orange-30',
					'text-brand-800',
					'hover:bg-badge-background-orange-10',
				],
				label: __( 'Voice Input', 'sureforms' ),
			  };

		return (
			<Button
				icon={ buttonProps.icon }
				iconPosition="left"
				variant="outline"
				size="xs"
				className={ `rounded-full border-0.5 border-solid font-medium hover:cursor-pointer ${ srfmClassNames(
					buttonProps.required_className
				) }` }
				onClick={ toggleListening }
			>
				{ buttonProps.label }
			</Button>
		);
	};

	return (
		<Container
			className={ cn(
				'w-full h-full mx-auto pb-8 gap-8',
				is_pro_active && 'mt-16'
			) }
			containerType="flex"
			direction="column"
		>
			<Container.Item>
				{ ! is_pro_active && <ConnectWithAIBanner /> }
			</Container.Item>
			<Container.Item>
				<Container
					className="p-4 gap-1.5 bg-background-primary border-0.5 border-solid border-border-subtle shadow-sm-blur-2 rounded-xl w-full h-full max-w-[680px] mx-auto"
					containerType="flex"
					direction="column"
				>
					<Container.Item className="flex p-2 gap-6">
						<Title
							tag="h4"
							size="md"
							title={ __(
								'Please describe the form you want to create',
								'sureforms'
							) }
						/>
					</Container.Item>
					<Container.Item className="p-2 gap-6">
						<TextArea
							aria-label={ __(
								'Describe the form you want to create',
								'sureforms'
							) }
							placeholder={ __(
								"E.g. Form to gather feedback from our customer for our product functionality, usability , how much you will rate it and what you don't like about it.",
								'sureforms'
							) }
							id="textarea"
							value={ text }
							size="lg"
							className={ cn(
								'gap-2 w-full h-[124px] text-field-placeholder py-2 px-4',
								characterCount > 0 && 'text-text-primary'
							) }
							onChange={ ( e ) => {
								handlePromptClick( e );
							} }
							onInput={ handleTyping }
							maxLength={ 2000 }
						/>
						{ showEmptyError && (
							<Label
								size="sm"
								variant="error"
								className="font-semibold"
							>
								{ __( 'Prompt cannot be empty.', 'sureforms' ) }
							</Label>
						) }
						<Container className="flex-wrap" containerType="flex" direction="row" align="center" justify="between">
							{ false === conversationalFormAiToggle
								? conversationalAiToggle
								: conversationalFormAiToggle }
							<Container.Item className="py-2 gap-2 ml-auto">
								<VoiceToggleButton />
							</Container.Item>
						</Container>
					</Container.Item>
					<Container.Item className="p-2 gap-3">
						<Container containerType="flex" direction="column">
							<Container.Item>
								<Container containerType="flex" direction="row" align="center" justify="between" className="flex-wrap">
									<Container.Item className="p-1 gap-1 flex flex-row items-center">
										<Label
											variant="neutral"
											size="sm"
											className="gap-1 flex items-center cursor-pointer"
											onClick={ () =>
												setShowFormIdeas(
													! showFormIdeas
												)
											}
										>
											{ __(
												'Some Form Ideas',
												'sureforms'
											) }

											{ showFormIdeas ? (
												<ChevronUp className="!text-icon-secondary !size-5" />
											) : (
												<ChevronDown className="!text-icon-secondary !size-5" />
											) }
										</Label>
									</Container.Item>
								</Container>
							</Container.Item>
							{ showFormIdeas && (
								<Container.Item>
									<Container
										className="gap-2 flex-wrap"
										containerType="flex"
										direction="row"
									>
										{ examplePrompts.map(
											( prompt, index ) => (
												<Label
													key={ index }
													size="md"
													type="pill"
													variant="neutral"
													className="rounded-full border-0.5 border-solid border-badge-border-gray bg-badge-background-gray hover:bg-badge-background-gray hover:cursor-pointer font-medium text-sm py-1 px-1.5 gap-0.5 flex"
													onClick={ () =>
														handlePromptClick(
															prompt.title
														)
													}
												>
													{ prompt.title }
												</Label>
											)
										) }
									</Container>
								</Container.Item>
							) }
						</Container>
					</Container.Item>
					<Container.Item className="py-1 px-2 gap-3 flex flex-col sm:flex-row justify-end">
						<Label
							variant="help"
							size="sm"
							className="font-semibold cursor-pointer"
						>
							{ characterCount }/2000
						</Label>
						<Button
							className="gap-1"
							icon={
								<ArrowRight size={ 20 } strokeWidth={ 1.25 } />
							}
							iconPosition="right"
							size="md"
							variant="primary"
							onClick={ () => {
								if ( ! text || ! text.trim() ) {
									setShowEmptyError( true );
									const textArea =
										document.getElementById( 'textarea' );
									textArea.focus();
									return;
								}

								handleCreateAiForm( text, [], true );
								setIsBuildingForm( true );
							} }
						>
							{ __( 'Generate Form', 'sureforms' ) }
						</Button>
					</Container.Item>
				</Container>
			</Container.Item>
		</Container>
	);
};
