import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { Mail, FileText, CalendarCheck, MessageSquare } from 'lucide-react';

// Maximum prompt length accepted by the AI form builder.
export const MAX_PROMPT_LENGTH = 2000;

// Maximum number of starter cards rendered in the grid.
const MAX_STARTERS = 4;

/**
 * Get the starter prompts shown above the AI prompt box.
 *
 * Default prompts only ask for fields available in the free plugin.
 *
 * @since x.x.x
 * @return {Array<{id: string, title: string, description: string, icon: Function, prompt: string}>} Starter prompts.
 */
export const getStarterPrompts = () => {
	const starters = applyFilters( 'srfm.aiFormScreen.starterPrompts', [
		{
			id: 'contact',
			title: __( 'Contact form', 'sureforms' ),
			description: __( 'Name, email and message', 'sureforms' ),
			icon: Mail,
			prompt: __(
				'Contact form with full name, email address, phone number (optional), subject and message. Add a consent checkbox asking permission to reply by email.',
				'sureforms'
			),
		},
		{
			id: 'quote-request',
			title: __( 'Quote request', 'sureforms' ),
			description: __( 'Enquiries with project details', 'sureforms' ),
			icon: FileText,
			prompt: __(
				'Quote request form for a service business with full name, email, phone number, company name (optional), a dropdown to choose the service needed, a dropdown for budget range (Under $500, $500–$2,000, $2,000–$5,000, Over $5,000), a dropdown for timeline (As soon as possible, Within a month, 1–3 months, Just exploring), a field describing the project, and preferred contact method (Email, Phone).',
				'sureforms'
			),
		},
		{
			id: 'event-registration',
			title: __( 'Event registration', 'sureforms' ),
			description: __( 'Sign-ups with attendee details', 'sureforms' ),
			icon: CalendarCheck,
			prompt: __(
				'Event registration form with full name, email address, phone number, number of attendees, a dropdown to choose a session (Morning, Afternoon, Evening), a multiple-choice question for dietary needs that allows selecting more than one option (Vegetarian, Vegan, Gluten-free, None), and a field for questions or special requests.',
				'sureforms'
			),
		},
		{
			id: 'feedback',
			title: __( 'Feedback form', 'sureforms' ),
			description: __( 'Ratings and suggestions', 'sureforms' ),
			icon: MessageSquare,
			prompt: __(
				'Customer feedback form with name (optional), email (optional), a multiple-choice question "How satisfied are you with our service?" from Very satisfied to Very dissatisfied, a field for what we did well, a field for what we could improve, and a Yes/No question "Would you recommend us to a friend?"',
				'sureforms'
			),
		},
	] );

	if ( ! Array.isArray( starters ) ) {
		return [];
	}

	const seenIds = new Set();

	return starters
		.filter( ( starter ) => {
			if (
				! starter?.id ||
				! starter?.title ||
				! starter?.prompt ||
				seenIds.has( starter.id )
			) {
				return false;
			}
			seenIds.add( starter.id );
			return true;
		} )
		.slice( 0, MAX_STARTERS )
		.map( ( starter ) => ( {
			...starter,
			prompt: starter.prompt.slice( 0, MAX_PROMPT_LENGTH ),
		} ) );
};
