import { __ } from '@wordpress/i18n';
import { useState, useRef, useEffect, memo } from '@wordpress/element';
import {
	Button,
	Container,
	TextArea,
	Title,
	toast,
	Tooltip,
} from '@bsf/force-ui';
import {
	ArrowRight,
	Sparkles,
	MicOff,
	Mic,
} from 'lucide-react';
import { applyFilters } from '@wordpress/hooks';
import { cn, srfmClassNames } from '@Utils/Helpers';
import ConnectWithAIBanner from '../ai-form-builder-components/ConnectWithAIBanner.js';
import LimitReachedBanner from '../ai-form-builder-components/LimitReachedBanner.js';
import FormTypeSelector from '../components/FormTypeSelector.js';
import CreditDetailsPopup from './CreditDetailsPopup.js';
import { useLocation } from 'react-router-dom';

const VoiceToggleButton = memo( ( { isListening, toggleListening } ) => {
	const buttonProps = isListening
		? {
			icon: <Mic className="w-3 h-3" />,
			required_className: [
				'bg-badge-background-green',
				'border-badge-border-green',
				'text-badge-color-green',
				'hover:bg-badge-background-green',
			],
		  }
		: {
			icon: <MicOff className="w-3 h-3" />,
			required_className: [
				'border-badge-border-gray',
				'bg-badge-background-gray',
			],
		  };

	return (
		<Tooltip
			tooltipPortalId="srfm-add-new-form-container"
			arrow
			className="z-999999"
			title={ __( 'Voice Input', 'sureforms' ) }
			placement="bottom"
			triggers={ [ 'hover', 'focus' ] }
		>
			<Button
				icon={ buttonProps.icon }
				iconPosition="left"
				variant="outline"
				size="xs"
				className={ `p-3 rounded-full border-0.5 border-solid font-medium hover:cursor-pointer ${ srfmClassNames(
					buttonProps.required_className
				) }` }
				onClick={ toggleListening }
			/>
		</Tooltip>
	);
} );

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
	const [ displayedPlaceholder, setDisplayedPlaceholder ] = useState( '' );

	// 👇 added new state for banner visibility
	const [ showBanner, setShowBanner ] = useState( false );

	const handlePromptClick = ( prompt ) => {
		setText( prompt );
		setCharacterCount( prompt.length );
	};

	function useQuery() {
		return new URLSearchParams( useLocation().search );
	}

	const query = useQuery();

	const page = query.get( 'page' );

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
				title: __(
					'Contact form to collect name, email, and message from visitors',
					'sureforms'
				),
			},
			{
				title: __(
					'Job application form for "Marketing Manager" with resume upload',
					'sureforms'
				),
			},
			{
				title: __(
					'Feedback form to ask customers: "How would you rate our product and what should we improve?',
					'sureforms'
				),
			},
			{
				title: __(
					'Event registration form for "Photography Workshop" with date and seat selection',
					'sureforms'
				),
			},
			{
				title: __(
					'Newsletter signup form with name and email to join mailing list',
					'sureforms'
				),
			},
			{
				title: __(
					'Order form for "Custom T-Shirt" with size, color, and quantity options',
					'sureforms'
				),
			},
			{
				title: __(
					'Survey form: "How satisfied are you with our service? (1–5 stars)"',
					'sureforms'
				),
			},
			{
				title: __(
					'Appointment booking form for "Consultation Call" with preferred time',
					'sureforms'
				),
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
		if ( rotatingPlaceholders.length > 1 ) {
			const interval = setInterval( () => {
				setPlaceholderIndex(
					( prevIndex ) =>
						( prevIndex + 1 ) % rotatingPlaceholders.length
				);
			}, 2000 );

			return () => clearInterval( interval );
		}
	}, [ formType, rotatingPlaceholders, formLayout ] );

	const textAreaPlaceholder = rotatingPlaceholders[ placeholderIndex ];

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

	const is_pro_active =
		srfm_admin?.is_pro_active && srfm_admin?.is_pro_license_active;

	const type = srfm_admin?.srfm_ai_1usage_details?.type;
	const formCreationleft = srfm_admin?.srfm_ai_usage_details?.remaining ?? 0;

	const isRegistered =
		srfm_admin?.srfm_ai_usage_details?.type === 'registered';
	const finalFormCreationCountRemaining =
		isRegistered && formCreationleft > 20 ? 20 : formCreationleft;

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
					className={ cn( 'p-8 gap-6 mx-auto w-full h-screen bg-background-secondary', showBanner && 'mt-8' ) }
					direction="column"
					justify="center"
					align="center"
				>
					<Container.Item>
						<Container
							direction="column"
							align="center"
							justify="center"
							className="gap-6"
						>
							<Container.Item>
								<Title
									size="md"
									title={ __(
										'Describe the form that you want',
										'sureforms'
									) }
									className="text-3xl font-semibold text-text-primary"
								/>
							</Container.Item>
							<Container.Item>
								<Container
									className="gap-2 p-2 w-full h-full min-w-[750px] mx-auto"
									direction="column"
								>
									<Container.Item className="focus-within:border-brand-primary-600 focus-within:ring-1 focus-within:ring-brand-primary-600 flex flex-col gap-2 shadow-md-blur-32 border-[0.5 border-solid border-t-background-brand rounded-lg bg-background-primary">
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
												'focus:[box-shadow:none] focus:outline-none resize-none gap-2 w-full min-h-[140px] text-field-placeholder py-2 px-4 border-0',
												characterCount > 0 &&
													'text-text-primary'
											) }
											onChange={ ( e ) => {
												handlePromptClick( e );
											} }
											onInput={ handleTyping }
											maxLength={ 2000 }
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
											</Container.Item>
											<Container.Item className="gap-4 flex flex-row">
												<VoiceToggleButton
													isListening={ isListening }
													toggleListening={
														toggleListening
													}
												/>
												<Button
													className="gap-1"
													icon={
														<Sparkles className="w-4 h-4" />
													}
													iconPosition="left"
													size="md"
													variant="primary"
													onClick={ () => {
														setShowBanner( true );
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
													{ __(
														'Generate',
														'sureforms'
													) }
												</Button>
											</Container.Item>
										</Container>
									</Container.Item>
								</Container>
							</Container.Item>
						</Container>
					</Container.Item>

					{ page === 'add-new-form' &&
						( ! srfm_admin?.is_pro_active ||
							! srfm_admin?.is_pro_license_active ) && (
						<CreditDetailsPopup
							finalFormCreationCountRemaining={
								finalFormCreationCountRemaining
							}
							showBanner={ showBanner }
							setShowBanner={ setShowBanner }
						/>
					) }

					<Container.Item className="flex p-2 gap-6 justify-center">
						<Button
							className="text-text-tertiary hover:cursor-pointer hover:text-text-secondary"
							icon={ <ArrowRight size={ 16 } /> }
							iconPosition="right"
							size="sm"
							variant="ghost"
							onClick={ () => {
								window.location.href = `${ srfm_admin.site_url }/wp-admin/post-new.php?post_type=sureforms_form`;
							} }
						>
							{ __( 'Or Build It Yourself', 'sureforms' ) }
						</Button>
					</Container.Item>
				</Container>
			</Container.Item>
		</Container>
	);
};
