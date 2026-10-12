import { __, sprintf } from '@wordpress/i18n';
import { useState, useRef, useEffect, useMemo, memo } from '@wordpress/element';
import {
	Button,
	Container,
	TextArea,
	toast,
	Tooltip,
} from '@bsf/force-ui';
import { ArrowRight, Sparkles, MicOff, Mic } from 'lucide-react';
import { applyFilters } from '@wordpress/hooks';
import { speak } from '@wordpress/a11y';
import { cn, srfmClassNames } from '@Utils/Helpers';
import FormTypeSelector from '../components/FormTypeSelector.js';
import CreditDetailsPopup from './CreditDetailsPopup.js';
import StarterPrompts from './StarterPrompts.js';
import {
	getStarterPrompts,
	isFromStarter,
	MAX_PROMPT_LENGTH,
} from '../utils/starter-prompts.js';
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
		formTypeObj,
		setFormTypeObj,
		setFormType,
		formType,
		type,
		showCreditDetailsPopup,
		setShowCreditDetailsPopup,
		features,
		prefilledPrompt,
	} = props;

	const [ isListening, setIsListening ] = useState( false ); // State to manage voice recording
	const [ characterCount, setCharacterCount ] = useState( 0 );
	const [ text, setText ] = useState( '' );
	const recognitionRef = useRef( null ); // To store SpeechRecognition instance
	const [ formLayout, setformLayout ] = useState( {} );
	const [ placeholderIndex, setPlaceholderIndex ] = useState( 0 );
	const [ displayedPlaceholder, setDisplayedPlaceholder ] = useState( '' );

	// 👇 added new state for banner visibility
	const [ isFocused, setIsFocused ] = useState( false );

	const starters = useMemo( () => getStarterPrompts(), [] );
	// Examples describe simple forms, so they are hidden for other form types
	// (Quiz, Conversational, Payment...) rather than sent with the wrong type.
	const startersAvailable =
		starters.length > 0 && ( ! formType || 'simple' === formType );
	const [ selectedStarterId, setSelectedStarterId ] = useState( null );
	// Prompt replaced by a starter card, kept so the user can undo.
	const [ replacedPrompt, setReplacedPrompt ] = useState( null );
	const [ showEmptyPromptHint, setShowEmptyPromptHint ] = useState( false );

	const canGenerate = Boolean( text.trim() );

	const setPrompt = ( prompt ) => {
		setText( prompt );
		setCharacterCount( prompt.length );
	};

	// Handles prompt edits made by typing or pasting.
	const handlePromptClick = ( prompt ) => {
		setPrompt( prompt );
		setReplacedPrompt( null );
		// Drop the card once the prompt no longer comes from it, so a prompt
		// the user wrote themselves isn't counted as that card.
		const selected = starters.find(
			( item ) => item.id === selectedStarterId
		);
		if (
			! prompt.trim() ||
			( selected && ! isFromStarter( prompt, selected.prompt ) )
		) {
			setSelectedStarterId( null );
		}
	};

	function useQuery() {
		return new URLSearchParams( useLocation().search );
	}

	const query = useQuery();

	const page = query.get( 'page' );
	const source = query.get( 'source' );
	const [ showLearnTip, setShowLearnTip ] = useState( false );
	const [ showGenerateTip, setShowGenerateTip ] = useState( false );

	// Starter examples are for getting started: collapse them once the site has
	// an AI form, except in the Learn flow which is about the first form.
	const [ showStarters, setShowStarters ] = useState(
		() => ! srfm_admin?.has_ai_generated_forms || source === 'learn'
	);
	const [ focusStarters, setFocusStarters ] = useState( false );

	// Leaving the simple form type drops the example selection; the prompt
	// text stays.
	useEffect( () => {
		if ( ! startersAvailable ) {
			setSelectedStarterId( null );
		}
	}, [ startersAvailable ] );

	// Move focus to the first example after "Show examples" is clicked.
	useEffect( () => {
		if ( focusStarters && showStarters ) {
			document
				.querySelector(
					'[aria-labelledby="srfm-starter-prompts-label"] button'
				)
				?.focus();
			setFocusStarters( false );
		}
	}, [ focusStarters, showStarters ] );

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

	// Stops voice input if typing begins. abort() drops a pending phrase that
	// stop() would still deliver on top of the new text.
	const handleTyping = () => {
		if ( isListening && recognitionRef.current ) {
			recognitionRef.current.abort();
			setIsListening( false );
		}
	};

	const toggleListening = () => {
		const textArea = document.getElementById( 'textarea' );
		if ( ! isFocused ) {
			setIsFocused( true );
			textArea.focus();
		}

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
				setReplacedPrompt( null );
				setText( ( prevText ) => {
					const separator =
						prevText && ! /\s$/.test( prevText ) ? ' ' : '';
					const updatedText = (
						prevText +
						separator +
						speechResult.trim()
					).slice( 0, MAX_PROMPT_LENGTH );
					setCharacterCount( updatedText.length );
					return updatedText;
				} );
			};
			recognition.onerror = ( e ) => {
				setIsListening( false );

				// abort() reports 'aborted' when typing or a card click stops voice
				// input on purpose; that isn't an error to show.
				if ( 'aborted' === e.error ) {
					return;
				}

				recognition.stop();
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
					'Job application form for "Marketing Manager" with a link to their resume or LinkedIn profile',
					'sureforms'
				),
			},
			{
				title: __(
					'Feedback form to ask customers: "How would you rate our product and what should we improve?"',
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

		let i = 0; // Start typing from first character

		const interval = setInterval( () => {
			if ( i < textAreaPlaceholder.length ) {
				// Typing one character at a time
				setDisplayedPlaceholder(
					( prev ) => prev + textAreaPlaceholder.charAt( i )
				);
				i++;
			} else {
				// Fully typed, stop interval
				clearInterval( interval );
			}
		}, 50 );

		// Clear previous placeholder before typing new one
		setDisplayedPlaceholder( '' );

		return () => clearInterval( interval );
	}, [ textAreaPlaceholder ] );

	// Show the first learn tip when redirected from Learn section.
	useEffect( () => {
		if ( source === 'learn' ) {
			setShowLearnTip( true );
		}
	}, [ source ] );

	// Move to the generate tip once the prompt has text.
	useEffect( () => {
		if ( showLearnTip && canGenerate ) {
			setShowLearnTip( false );
			setShowGenerateTip( true );
		}
	}, [ showLearnTip, canGenerate ] );

	useEffect( () => {
		if ( canGenerate ) {
			setShowEmptyPromptHint( false );
		}
	}, [ canGenerate ] );

	// Auto-dismiss generate tip after 5 seconds.
	useEffect( () => {
		if ( showGenerateTip ) {
			const timer = setTimeout( () => setShowGenerateTip( false ), 5000 );
			return () => clearTimeout( timer );
		}
	}, [ showGenerateTip ] );

	// Prefill prompt when coming from WordPress dashboard widget.
	useEffect( () => {
		if ( ! prefilledPrompt ) {
			return;
		}
		setText( prefilledPrompt );
		setCharacterCount( prefilledPrompt.length );
	}, [ prefilledPrompt ] );

	const formCreationleft = srfm_admin?.srfm_ai_usage_details?.remaining ?? 0;

	const isRegistered =
		srfm_admin?.srfm_ai_usage_details?.type === 'registered';
	const finalFormCreationCountRemaining =
		isRegistered && formCreationleft > 20 ? 20 : formCreationleft;

	const handleStarterToggle = ( starter ) => {
		// Stop voice input so it doesn't append to the starter prompt.
		handleTyping();

		if ( selectedStarterId === starter.id ) {
			setSelectedStarterId( null );
			// An untouched example gives back whatever it replaced; an edited
			// prompt belongs to the user now and is kept.
			const isUntouched = text === starter.prompt;
			if ( isUntouched ) {
				setPrompt( replacedPrompt ?? '' );
			}
			setReplacedPrompt( null );
			speak(
				isUntouched
					? __( 'Example removed.', 'sureforms' )
					: __( 'Example unselected. Your edits were kept.', 'sureforms' )
			);
			return;
		}

		// Switching from an untouched example keeps the text that example
		// replaced, so Undo still gives back what the user typed.
		let undoText = text.trim() ? text : null;
		if ( starters.some( ( item ) => item.prompt === text ) ) {
			undoText = replacedPrompt;
		}
		setReplacedPrompt( undoText );
		setPrompt( starter.prompt );
		setSelectedStarterId( starter.id );
		speak(
			null !== undoText
				? sprintf(
					/* translators: %s: starter example title, e.g. "Contact form". */
					__(
						'%s example replaced your prompt. Press Undo to get your text back.',
						'sureforms'
					),
					starter.title
				  )
				: sprintf(
					/* translators: %s: starter example title, e.g. "Contact form". */
					__(
						'%s example added to the prompt. Edit it or press Generate.',
						'sureforms'
					),
					starter.title
				  )
		);
	};

	const handleUndoReplace = () => {
		setPrompt( replacedPrompt ?? '' );
		setSelectedStarterId( null );
		setReplacedPrompt( null );
		document.getElementById( 'textarea' )?.focus();
	};

	// Whether the prompt is still exactly the selected starter's text (for tracking edits).
	const isUntouchedStarter =
		!! selectedStarterId &&
		starters.some(
			( item ) => item.id === selectedStarterId && item.prompt === text
		);

	let counterColorClass = 'text-field-helper';
	if ( characterCount >= MAX_PROMPT_LENGTH ) {
		counterColorClass = 'text-support-error';
	} else if ( characterCount >= MAX_PROMPT_LENGTH * 0.9 ) {
		counterColorClass = 'text-badge-color-yellow';
	}

	return (
		// The only scroller on this screen: Header.js sets body to overflow hidden,
		// and this fills the window below the 3.5rem fixed header.
		<Container
			className="gap-0 h-[calc(100vh-3.5rem)] overflow-y-auto"
			direction="column"
		>
			<Container.Item className="w-full">
				<Container
					className="p-4 sm:p-8 gap-6 mx-auto w-full min-h-[calc(100vh-3.5rem)] pb-16 bg-background-secondary"
					direction="column"
					justify="center"
					align="center"
				>
					<Container.Item className="w-full">
						<Container
							direction="column"
							align="center"
							justify="center"
							className="gap-6 w-full"
						>
							<Container.Item className="flex flex-col items-center gap-2 text-center">
								<h1 className="m-0 text-3xl font-semibold text-text-primary">
									{ __(
										'What form should we build?',
										'sureforms'
									) }
								</h1>
								<p className="m-0 text-base font-normal text-text-secondary">
									{ showStarters && startersAvailable
										? __(
											'Start with an example, or describe your own form below.',
											'sureforms'
										  )
										: __(
											'Describe the form you want to create.',
											'sureforms'
										  ) }
								</p>
							</Container.Item>
							<Container.Item className="w-full">
								<div className="w-full max-w-[750px] lg:max-w-[900px] mx-auto p-2 flex flex-col gap-4">
									{ showStarters && startersAvailable ? (
										<StarterPrompts
											starters={ starters }
											selectedStarterId={
												selectedStarterId
											}
											onToggle={ handleStarterToggle }
										/>
									) : (
										startersAvailable && (
											<Container
												align="center"
												className="gap-1 text-sm text-text-secondary"
											>
												<span>
													{ __(
														'Need ideas?',
														'sureforms'
													) }
												</span>
												<Button
													size="sm"
													variant="link"
													onClick={ () => {
														setShowStarters( true );
														setFocusStarters( true );
													} }
												>
													{ __(
														'Show examples',
														'sureforms'
													) }
												</Button>
											</Container>
										)
									) }
									<div className="relative">
										{ showLearnTip && (
											<div className="absolute top-4 right-4 z-[999999] pointer-events-none">
												<div className="bg-tooltip-background-dark text-text-on-color text-sm px-3 py-2 rounded-md shadow-lg whitespace-nowrap">
													{ startersAvailable
														? __(
															'Pick an example or describe your form',
															'sureforms'
														  )
														: __(
															'Describe your form',
															'sureforms'
														  ) }
												</div>
											</div>
										) }
										<div
											className="relative rounded-lg shadow-lg p-[2px]"
											style={ {
												background:
													'linear-gradient(180deg, #FF5811 0%, #8B2E16 5%, #000000 10%, #000000 100%)',
											} }
										>
											<div className="relative bg-white rounded-[calc(0.5rem-1px)]">
												<TextArea
													aria-label={ __(
														'Describe the form you want to create',
														'sureforms'
													) }
													aria-describedby="srfm-ai-prompt-counter"
													placeholder={
														displayedPlaceholder
													}
													id="textarea"
													value={ text }
													size="lg"
													className={ cn(
														'border-none focus:[box-shadow:none] focus:outline-none resize-none w-full min-h-[140px] max-h-[300px] text-field-placeholder pt-3 px-4 pb-14 rounded-[calc(0.5rem-1px)]',
														characterCount > 0 &&
															'text-text-primary'
													) }
													onChange={ ( e ) => {
														handlePromptClick( e );
													} }
													onInput={ handleTyping }
													onFocus={ () =>
														setIsFocused( true )
													}
													onBlur={ () =>
														setIsFocused( false )
													}
													maxLength={
														MAX_PROMPT_LENGTH
													}
												/>

												<Container
													className="flex-wrap gap-y-2 py-2 px-4"
													align="center"
													justify="between"
												>
													<Container.Item className="flex flex-row gap-4 items-center">
														<FormTypeSelector
															formTypeObj={
																formTypeObj
															}
															setFormTypeObj={
																setFormTypeObj
															}
															formType={ formType }
															setFormType={
																setFormType
															}
															setformLayout={
																setformLayout
															}
														/>
													</Container.Item>
													<Container.Item className="ml-auto gap-3 flex flex-row items-center">
														<span
															id="srfm-ai-prompt-counter"
															className={ cn(
																'text-xs font-normal tabular-nums',
																counterColorClass
															) }
														>
															{ sprintf(
																/* translators: 1: current prompt length, 2: maximum prompt length. */
																__(
																	'%1$d/%2$d',
																	'sureforms'
																),
																characterCount,
																MAX_PROMPT_LENGTH
															) }
														</span>
														<VoiceToggleButton
															isListening={
																isListening
															}
															toggleListening={
																toggleListening
															}
														/>
														<div className="relative">
															{ showGenerateTip && (
																<div className="absolute bottom-full right-0 mb-2 z-[999999] pointer-events-none">
																	<div className="absolute -bottom-1 right-6 w-2 h-2 bg-tooltip-background-dark rotate-45" />
																	<div className="bg-tooltip-background-dark text-text-on-color text-sm px-3 py-2 rounded-md shadow-lg whitespace-nowrap">
																		{ __(
																			'Click to generate the form',
																			'sureforms'
																		) }
																	</div>
																</div>
															) }
															<Button
																className={ cn(
																	'gap-1',
																	! canGenerate &&
																		'bg-button-disabled hover:bg-button-disabled outline-button-disabled hover:outline-button-disabled text-text-disabled shadow-none cursor-not-allowed'
																) }
																icon={
																	<Sparkles className="w-4 h-4" />
																}
																iconPosition="left"
																size="md"
																variant="primary"
																aria-disabled={
																	! canGenerate
																}
																aria-describedby={
																	showEmptyPromptHint
																		? 'srfm-ai-empty-prompt-hint'
																		: undefined
																}
																onClick={ () => {
																	if (
																		! canGenerate
																	) {
																		setShowEmptyPromptHint(
																			true
																		);
																		// The hint suggests picking an example, so make sure they're visible.
																		setShowStarters(
																			true
																		);
																		speak(
																			__(
																				'Describe your form or pick an example to continue.',
																				'sureforms'
																			)
																		);
																		document
																			.getElementById(
																				'textarea'
																			)
																			?.focus();
																		return;
																	}

																	handleCreateAiForm(
																		text,
																		[],
																		true,
																		{
																			starterId:
																				selectedStarterId,
																			starterEdited:
																				!! selectedStarterId &&
																				! isUntouchedStarter,
																		}
																	);
																} }
															>
																{ __(
																	'Generate',
																	'sureforms'
																) }
															</Button>
														</div>
													</Container.Item>
												</Container>
											</div>
										</div>
									</div>
									{ showEmptyPromptHint && ! canGenerate && (
										<p
											id="srfm-ai-empty-prompt-hint"
											className="m-0 text-sm text-text-secondary"
										>
											{ __(
												'Describe your form or pick an example to continue.',
												'sureforms'
											) }
										</p>
									) }
									{ replacedPrompt !== null && (
										<Container
											align="center"
											className="gap-2 text-sm text-text-secondary"
										>
											<span>
												{ __(
													'Your previous prompt was replaced.',
													'sureforms'
												) }
											</span>
											<Button
												size="xs"
												variant="link"
												onClick={ handleUndoReplace }
											>
												{ __( 'Undo', 'sureforms' ) }
											</Button>
										</Container>
									) }
								</div>
							</Container.Item>
						</Container>
					</Container.Item>

					<Container.Item className="flex p-2 gap-6 justify-center">
						<Button
							className="text-text-tertiary hover:cursor-pointer hover:text-text-secondary"
							icon={ <ArrowRight size={ 16 } /> }
							iconPosition="right"
							size="md"
							variant="ghost"
							onClick={ () => {
								// If coming from the Learn section, set a relay flag so the
								// editor can store the new post ID for Lesson 2's smart redirect.
								if ( source === 'learn' ) {
									localStorage.setItem(
										'srfmLearnFlow',
										'build-scratch'
									);
								}
								window.location.href = `${ srfm_admin.site_url }/wp-admin/post-new.php?post_type=sureforms_form`;
							} }
						>
							{ __( 'Or create it yourself', 'sureforms' ) }
						</Button>
					</Container.Item>

					{ page === 'add-new-form' &&
						! srfm_admin?.is_pro_active && (
						<CreditDetailsPopup
							finalFormCreationCountRemaining={
								finalFormCreationCountRemaining
							}
							setShowCreditDetailsPopup={
								setShowCreditDetailsPopup
							}
							showCreditDetailsPopup={
								showCreditDetailsPopup
							}
							type={ type }
							features={ features }
						/>
					) }
				</Container>
			</Container.Item>
		</Container>
	);
};
