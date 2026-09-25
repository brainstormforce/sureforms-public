import { __ } from '@wordpress/i18n';
import aiFormBuilder from '@Image/onboarding/welcome-ai-form-builder.svg';
import dragDropFields from '@Image/onboarding/welcome-drag-drop-fields.svg';
import paymentForms from '@Image/onboarding/welcome-payment-forms.svg';
import entries from '@Image/onboarding/welcome-entries.svg';
import conditionalLogic from '@Image/onboarding/welcome-conditional-logic.svg';
import conversationalForms from '@Image/onboarding/welcome-conversational-forms.svg';
import calculatorForms from '@Image/onboarding/welcome-calculator-forms.svg';
import pdfGeneration from '@Image/onboarding/welcome-pdf-generation.svg';
import connectYourApps from '@Image/onboarding/welcome-connect-your-apps.svg';

/*
 * Welcome-step carousel slides: the designed 460×360 mock-browser artwork.
 * Free installs see the four features the free plan ships; Pro installs see
 * the full set.
 */
const Slide = ( { src } ) => (
	<img
		src={ src }
		alt=""
		width={ 460 }
		height={ 360 }
		className="block h-auto w-[400px] max-w-full"
	/>
);

const slide = ( title, src, pro = false ) => ( {
	title,
	illustration: <Slide src={ src } />,
	pro,
} );

const ALL_SLIDES = [
	slide( __( 'AI Form Builder', 'sureforms' ), aiFormBuilder ),
	slide( __( 'Drag & Drop Fields', 'sureforms' ), dragDropFields ),
	slide( __( 'Payment Forms', 'sureforms' ), paymentForms ),
	slide( __( 'Manage Form Entries', 'sureforms' ), entries ),
	slide( __( 'Conditional Logic', 'sureforms' ), conditionalLogic, true ),
	slide(
		__( 'Conversational Forms', 'sureforms' ),
		conversationalForms,
		true
	),
	slide( __( 'Calculator Forms', 'sureforms' ), calculatorForms, true ),
	slide( __( 'PDF Generation', 'sureforms' ), pdfGeneration, true ),
	slide( __( 'Connect Your Apps', 'sureforms' ), connectYourApps, true ),
];

/**
 * Slides for the current install.
 *
 * @return {Array} Free slides, plus the Pro ones when SureForms Pro is active.
 */
export const getWelcomeSlides = () =>
	srfm_admin?.is_pro_active
		? ALL_SLIDES
		: ALL_SLIDES.filter( ( item ) => ! item.pro );
