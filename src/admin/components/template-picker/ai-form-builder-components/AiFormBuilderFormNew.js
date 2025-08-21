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
	DropdownMenu,
	Badge,
} from '@bsf/force-ui';
import { ArrowRight, ChevronDown, SendHorizontal, MicOff, Mic, ArrowLeft } from 'lucide-react';
import { applyFilters } from '@wordpress/hooks';
import PremiumBadge from '@Admin/components/PremiumBadge';
import { cn, srfmClassNames } from '@Utils/Helpers';
import ConnectWithAIBanner from '../ai-form-builder-components/ConnectWithAIBanner.js';
// import FormTypeSelector from '../components/FormTypeSelector.js';
import FormTypeSelectorNew from '../components/FormTypeSelectorNew.js';

export default (props) => {
	const {
		handleCreateAiForm,
		setIsBuildingForm,
		formTypeObj,
		setFormTypeObj,
		showEmptyError,
		setShowEmptyError,
		setFormType,
		formType,
	} = props;

	const [isListening, setIsListening] = useState(false); // State to manage voice recording
	const [showFormIdeas, setShowFormIdeas] = useState(true);
	const [characterCount, setCharacterCount] = useState(0);
	const [text, setText] = useState('');
	const recognitionRef = useRef(null); // To store SpeechRecognition instance
	const [formLayout, setformLayout] = useState({});
	const showAiConversationalFormToggle = false;
	const conversationalFormAiToggle = applyFilters(
		'srfm.aiFormScreen.conversational.toggle',
		showAiConversationalFormToggle,
		formTypeObj,
		setFormTypeObj,
		formLayout,
		setformLayout
	);

	const handlePromptClick = (prompt) => {
		setShowEmptyError(false);
		setText(prompt);
		setCharacterCount(prompt.length);
	};

	const initSpeechRecognition = () => {
		const SpeechRecognition =
			window.SpeechRecognition || window.webkitSpeechRecognition;
		if (!SpeechRecognition) {
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
		if (isListening && recognitionRef.current) {
			recognitionRef.current.stop();
			setIsListening(false);
		}
		setShowEmptyError(false);
	};

	const toggleListening = () => {
		// initialize SpeechRecognition instance if not already initialized
		if (!recognitionRef.current) {
			recognitionRef.current = initSpeechRecognition();
		}

		// if SpeechRecognition is not supported, show error message
		if (!recognitionRef.current) {
			return;
		}

		const recognition = recognitionRef.current;

		if (isListening) {
			// Stop recording if already started
			recognition.stop();
			setIsListening(false);
		} else {
			// Start recording if not started
			recognition.start();
			setIsListening(true);
			recognition.onresult = (event) => {
				// keep on appending the result to the textarea
				const speechResult =
					event.results[event.results.length - 1][0].transcript;
				setText((prevText) => {
					const updatedText = prevText + speechResult;
					setCharacterCount(updatedText.length);
					return updatedText;
				});
			};
			recognition.onerror = (e) => {
				recognition.stop();
				setIsListening(false);
				toast.dismiss();

				if (e.error === 'not-allowed') {
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
				title: __('Create simple contact form', 'sureforms'),
			},
			{
				title: __('Create a lead generation form', 'sureforms'),
			},
			{
				title: __('Generate a user feedback form', 'sureforms'),
			},
			{
				title: __('Create a job application form', 'sureforms'),
			},
			{
				title: __('Make an event registration form', 'sureforms'),
			},
		],
		formTypeObj,
		formType
	);

	const aiPromptPlaceholder = applyFilters(
		'srfm.aiFormScreen.aiPromptPlaceholder',
		'',
		formLayout,
		formType
	);

	// conversational form toggle
	const conversationalAiToggle = (
		<Container.Item className="py-2 flex flex-wrap items-center gap-2">
			<Switch
				aria-label={__('Create Conversational Form', 'sureforms')}
				id="switch-element"
				size="sm"
				className="shadow-sm-blur-2 bg-toggle-off border border-solid border-toggle-off-border"
				disabled={true}
			/>
			<Label variant="neutral" size="sm">
				{__('Create Conversational Form', 'sureforms')}
			</Label>
			<PremiumBadge
				tooltipHeading={__(
					'Unlock Conversational Forms',
					'sureforms'
				)}
				tooltipContent={__(
					'With the SureForms Pro Plan, you can transform your forms into engaging conversational layouts for a seamless user experience.',
					'sureforms'
				)}
				utmMedium="ai_builder"
			/>
		</Container.Item>
	);

	const is_pro_active =
		srfm_admin?.is_pro_active && srfm_admin?.is_pro_license_active;

	const VoiceToggleButton = () => {
		return (
			<Button
				icon={isListening ? <Mic className="!size-4" /> : <MicOff className="!size-4" />}
				iconPosition="left"
				variant="outline"
				size="sm"
				className="p-2.5 rounded-full border-0.5 border-solid font-medium hover:cursor-pointer bg-badge-background-gray border-badge-border-gray"
				onClick={toggleListening}
			>
			</Button>
		);
	};

	const textAreaPlaceholder =
		formType === 'simple' || formType === 'conversational'
			? __(
				"E.g. Form to gather feedback from our customer for our product functionality, usability , how much you will rate it and what you don't like about it.",
				'sureforms'
			)
			: aiPromptPlaceholder;

	return (
		<Container
			className="p-8 gap-8 mx-auto w-full h-screen mt-16 bg-background-secondary"
			direction="column"
			justify="center"
			align="center"
		>
			{/* header */}
			<Container.Item>
				<Container direction="column" align="center" justify="center" className="gap-2 p-2">
					<Container.Item>
						<Title
							tag="h4"
							size="md"
							title={__(
								'Start with AI. Just describe your form...',
								'sureforms'
							)}
						/>
					</Container.Item>
					<Container.Item>
						<Container
							className="gap-1.5 w-full h-full min-w-[664px] mx-auto"
							direction="column"
						>
							<Container.Item className="flex flex-col gap-2 shadow-md-blur-32 border border-solid border-field-border rounded-lg bg-background-primary">
								<TextArea
									aria-label={__(
										'Describe the form you want to create',
										'sureforms'
									)}
									placeholder={textAreaPlaceholder}
									id="textarea"
									value={text}
									size="lg"
									className={cn(
										'focus:[box-shadow:none] focus:outline-none resize-none gap-2 w-full h-[112px] text-field-placeholder py-2 px-4 border-0',
										characterCount > 0 && 'text-text-primary'
									)}
									onChange={(e) => {
										handlePromptClick(e);
									}}
									onInput={handleTyping}
									maxLength={2000}
								/>
								{showEmptyError && (
									<Label
										size="sm"
										variant="error"
										className="font-semibold"
									>
										{__('Prompt cannot be empty.', 'sureforms')}
									</Label>
								)}
								<Container
									className="flex-wrap py-2 px-4"
									align="center"
									justify="between"
								>
									<Container.Item>
										<FormTypeSelectorNew
											formTypeObj={formTypeObj}
											setFormTypeObj={setFormTypeObj}
											formType={formType}
											setFormType={setFormType}
											setformLayout={setformLayout}
										/>
										{/* {false === conversationalFormAiToggle
											? conversationalAiToggle
											: conversationalFormAiToggle} */}
									</Container.Item>
									<Container.Item className="gap-4 flex flex-row">

										<VoiceToggleButton />
										<Button
											className="gap-1"
											icon={<SendHorizontal size={20} />}
											iconPosition="right"
											size="md"
											variant="primary"
											disabled={!characterCount > 0}
											onClick={() => {
												if (!text || !text.trim()) {
													setShowEmptyError(true);
													const textArea =
														document.getElementById('textarea');
													textArea.focus();
													return;
												}

												handleCreateAiForm(text, [], true);
												setIsBuildingForm(true);
											}}
										>
											{''}
										</Button>
									</Container.Item>
								</Container>
							</Container.Item>
						</Container>
					</Container.Item>
				</Container>
			</Container.Item>

			{/* divider */}
			<div className="w-full max-w-[616px] border border-solid border-border-subtle"></div>

			{/* Button */}
			<Container.Item className="flex p-2 gap-6 justify-center">
				<Button
					className="text-text-tertiary hover:cursor-pointer"
					icon={<ArrowRight size={16} />}
					iconPosition="right"
					size="xs"
					variant="ghost"
					onClick={() => {
						window.location.href = `${srfm_admin.site_url}/wp-admin/post-new.php?post_type=sureforms_form`;
					}}
				>
					{__('Build from Scratch', 'sureforms')}
				</Button>
			</Container.Item>
		</Container>
	);
};
