import { __ } from '@wordpress/i18n';
import { useState, useRef, useEffect } from '@wordpress/element';
import {
	Button,
	Container,
	TextArea,
	Title,
	toast,
	Label,
} from '@bsf/force-ui';
import { ArrowRight, SendHorizontal, MicOff, Mic } from 'lucide-react';
import { applyFilters } from '@wordpress/hooks';
import { cn, srfmClassNames } from '@Utils/Helpers';
import ConnectWithAIBanner from '../ai-form-builder-components/ConnectWithAIBanner.js';
import LimitReachedBanner from '../ai-form-builder-components/LimitReachedBanner.js';
import FormTypeSelector from '../components/FormTypeSelector.js';

export default ( props ) => {
	const {
		handleCreateAiForm,
		setIsBuildingForm,
		formTypeObj,
		setFormTypeObj,
		setFormType,
		formType,
	} = props;

	const [ isListening, setIsListening ] = useState( false ); // State to manage voice recording
	const [ characterCount, setCharacterCount ] = useState( 0 );
	const [ text, setText ] = useState( '' );
	const recognitionRef = useRef( null ); // To store SpeechRecognition instance
	const [ formLayout, setformLayout ] = useState( {} );
	const [ placeholderIndex, setPlaceholderIndex ] = useState( 0 );
	const [ aiPlaceholderIndex, setAiPlaceholderIndex ] = useState( 0 );

	const [ displayedPlaceholder, setDisplayedPlaceholder ] = useState( '' );

	const handlePromptClick = ( prompt ) => {
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
				setText( ( prevText ) => {
					const updatedText = prevText + speechResult;
					setCharacterCount( updatedText.length );
					return updatedText;
				} );
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
		formTypeObj,
		formType
	);

	const rotatingPlaceholders = examplePrompts
		.map( ( prompt ) =>
			typeof prompt === 'string' ? prompt : prompt.title
		)
		.filter( Boolean ); // remove empty values

	useEffect( () => {
		if (
			( formType === 'simple' || formType === 'conversational' ) &&
			rotatingPlaceholders.length > 1
		) {
			const interval = setInterval( () => {
				setPlaceholderIndex(
					( prevIndex ) =>
						( prevIndex + 1 ) % rotatingPlaceholders.length
				);
			}, 2000 ); // rotate every 4 seconds

			return () => clearInterval( interval );
		}
	}, [ formType, rotatingPlaceholders ] );

	useEffect( () => {
		if ( aiPromptPlaceholders.length > 1 ) {
			const interval = setInterval( () => {
				setAiPlaceholderIndex(
					( prev ) => ( prev + 1 ) % aiPromptPlaceholders.length
				);
			}, 4000 );
			return () => clearInterval( interval );
		}
	}, [ formType, formLayout, aiPromptPlaceholders ] );

	const aiPromptPlaceholderRaw = applyFilters(
		'srfm.aiFormScreen.aiPromptPlaceholder',
		'',
		formLayout,
		formType
	);

	const aiPromptPlaceholders = Array.isArray( aiPromptPlaceholderRaw )
		? aiPromptPlaceholderRaw
		: [ aiPromptPlaceholderRaw ];

	const textAreaPlaceholder =
		formType === 'simple' || formType === 'conversational'
			? rotatingPlaceholders[ placeholderIndex ]
			: aiPromptPlaceholders[ aiPlaceholderIndex ];

	useEffect( () => {
		if ( ! textAreaPlaceholder ) {
			return;
		}

		let i = displayedPlaceholder.length;
		let typingPhase = false;

		const interval = setInterval( () => {
			if ( ! typingPhase ) {
				// Backspacing faster
				if ( i > 0 ) {
					setDisplayedPlaceholder( ( prev ) => prev.slice( 0, -1 ) );
					i--;
				} else {
					typingPhase = true;
					i = 0;
				}
			} else if ( i < textAreaPlaceholder.length ) {
				// Typing new text
				setDisplayedPlaceholder(
					( prev ) => prev + textAreaPlaceholder.charAt( i )
				);
				i++;
			} else {
				clearInterval( interval );
			}
		}, 50 );

		return () => clearInterval( interval );
	}, [ textAreaPlaceholder ] );

	const handlePlaceholderFocus = () => {
		if ( ! text.trim() ) {
			setText( textAreaPlaceholder );
			setCharacterCount( textAreaPlaceholder.length );
		}
	};

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
			  }
			: {
				icon: <MicOff size={ 12 } />,
				required_className: [
					'border-badge-border-gray',
					'bg-badge-background-gray',
				],
			  };

		return (
			<Button
				icon={ buttonProps.icon }
				iconPosition="left"
				variant="outline"
				size="xs"
				className={ `p-3 rounded-full border-0.5 border-solid font-medium hover:cursor-pointer ${ srfmClassNames(
					buttonProps.required_className
				) }` }
				onClick={ toggleListening }
			></Button>
		);
	};

	const type = srfm_admin?.srfm_ai_usage_details?.type;
	const formCreationleft = srfm_admin?.srfm_ai_usage_details?.remaining ?? 0;

	const banner =
		type === 'registered' && formCreationleft === 0 ? (
			<LimitReachedBanner />
		) : (
			<ConnectWithAIBanner />
		);

	return (
		<Container
			className={ cn( 'gap-0', is_pro_active && 'mt-12' ) }
			direction="column"
		>
			<Container.Item>{ ! is_pro_active && banner }</Container.Item>
			<Container.Item>
				<Container
					className="p-8 gap-8 mx-auto w-full h-screen bg-background-secondary"
					direction="column"
					justify="center"
					align="center"
				>
					<Container.Item>
						<Container
							direction="column"
							align="center"
							justify="center"
							className="gap-2 p-2"
						>
							<Container.Item>
								<Title
									tag="h4"
									size="md"
									title={ __(
										'Start with AI. Just describe your form…',
										'sureforms'
									) }
								/>
							</Container.Item>
							<Container.Item>
								<Container
									className="gap-1.5 w-full h-full min-w-[750px] mx-auto"
									direction="column"
								>
									<Container.Item className="flex flex-col gap-2 shadow-md-blur-32 border border-solid border-field-border rounded-lg bg-background-primary">
										<TextArea
											aria-label={ __(
												'Describe the form you want to create',
												'sureforms'
											) }
											placeholder={ displayedPlaceholder }
											id="textarea"
											value={ text }
											size="lg"
											className={ cn(
												'focus:[box-shadow:none] focus:outline-none resize-none gap-2 w-full min-h-[120px] max-h-[180px] text-field-placeholder py-2 px-4 border-0',
												characterCount > 0 &&
													'text-text-primary'
											) }
											onChange={ ( e ) => {
												handlePromptClick( e );
											} }
											onInput={ handleTyping }
											maxLength={ 2000 }
											onFocus={ handlePlaceholderFocus }
										/>
										<Container
											className="flex-wrap py-2 px-4"
											align="center"
											justify="between"
										>
											<Container.Item className="flex flex-row gap-4 items-center">
												<FormTypeSelector
													formTypeObj={ formTypeObj }
													setFormTypeObj={
														setFormTypeObj
													}
													formType={ formType }
													setFormType={ setFormType }
													setformLayout={
														setformLayout
													}
												/>
												<Label
													variant="help"
													size="sm"
													className="font-semibold"
												>
													{ characterCount }/2000
												</Label>
											</Container.Item>
											<Container.Item className="gap-4 flex flex-row">
												<VoiceToggleButton />
												<Button
													className="gap-1"
													icon={
														<SendHorizontal
															size={ 20 }
														/>
													}
													iconPosition="right"
													size="md"
													variant="primary"
													disabled={
														characterCount <= 0
													}
													onClick={ () => {
														if (
															! text ||
															! text.trim()
														) {
															const textArea =
																document.getElementById(
																	'textarea'
																);
															textArea.focus();
															return;
														}

														handleCreateAiForm(
															text,
															[],
															true
														);
														setIsBuildingForm(
															true
														);
													} }
												>
													{ '' }
												</Button>
											</Container.Item>
										</Container>
									</Container.Item>
								</Container>
							</Container.Item>
						</Container>
					</Container.Item>

					<div className="w-full max-w-[616px] border border-solid border-border-subtle"></div>

					<Container.Item className="flex p-2 gap-6 justify-center">
						<Button
							className="text-text-tertiary hover:cursor-pointer"
							icon={ <ArrowRight size={ 16 } /> }
							iconPosition="right"
							size="md"
							variant="ghost"
							onClick={ () => {
								window.location.href = `${ srfm_admin.site_url }/wp-admin/post-new.php?post_type=sureforms_form`;
							} }
						>
							{ __( 'Build from Scratch', 'sureforms' ) }
						</Button>
					</Container.Item>
				</Container>
			</Container.Item>
		</Container>
	);
};
