import { __ } from '@wordpress/i18n';
import { useState, useRef, useEffect, memo } from '@wordpress/element';
import { Button, Container, TextArea, toast, Tooltip } from '@bsf/force-ui';
import { ArrowRight, Sparkles, MicOff, Mic } from 'lucide-react';
import { applyFilters } from '@wordpress/hooks';
import { cn, srfmClassNames } from '@Utils/Helpers';
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
		type,
		showCreditDetailsPopup,
		setShowCreditDetailsPopup,
		features,
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

	const formCreationleft = srfm_admin?.srfm_ai_usage_details?.remaining ?? 0;

	const isRegistered =
		srfm_admin?.srfm_ai_usage_details?.type === 'registered';
	const finalFormCreationCountRemaining =
		isRegistered && formCreationleft > 20 ? 20 : formCreationleft;

	const is_pro_active =
		srfm_admin?.is_pro_active && srfm_admin?.is_pro_license_active;

	return (
		<Container
			className={ cn(
				'gap-0',
				showCreditDetailsPopup &&
					! is_pro_active &&
					'h-screen overflow-y-auto'
			) }
			direction="column"
		>
			<Container.Item className="w-full">
				<Container
					className={ cn(
						'p-8 gap-6 mx-auto w-full bg-background-secondary',
						! is_pro_active ? 'min-h-screen pb-16' : 'h-screen'
					) }
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
								<span className="text-3xl font-semibold text-text-primary">
									{ __(
										'Describe the form that you want',
										'sureforms'
									) }
								</span>
							</Container.Item>
							<Container.Item>
								<div className="w-full min-w-[750px] mx-auto p-2 relative">
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
												maxLength={ 2000 }
											/>

											<Container
												className="flex-wrap py-2 px-4"
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
												<Container.Item className="gap-4 flex flex-row">
													<VoiceToggleButton
														isListening={
															isListening
														}
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
										</div>
									</div>
								</div>
							</Container.Item>
						</Container>
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

					<Container.Item className="flex p-2 gap-6 justify-center">
						<Button
							className="text-text-tertiary hover:cursor-pointer hover:text-text-secondary"
							icon={ <ArrowRight size={ 16 } /> }
							iconPosition="right"
							size="md"
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
