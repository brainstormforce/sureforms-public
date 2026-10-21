import { __ } from '@wordpress/i18n';
import { createInterpolateElement, useEffect } from '@wordpress/element';
import { useNavigate, useLocation } from 'react-router-dom';
import { Button, Container, Text } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';
import {
	getScratchUrl,
	setStartChoice,
	trackStartStep,
} from '@Utils/form-start';
import Header from './Header.js';
import ICONS from './icons';
import { AiArt, TemplateArt } from './StartScreenArt.js';

/**
 * Cards in the order they are shown. Each choice is counted with its card's
 * position, so the share of each path can be read apart from where it sat.
 */
const CARD_ORDER = [ 'ai', 'template' ];

// History entries whose view is already counted: Back to the start screen
// shows the same entry again, not a new visit.
const countedViews = new Set();

// How long the scratch link waits for its count before leaving the page.
const LEAVE_DELAY = 600;

const CARDS = {
	ai: {
		title: __( 'Create with AI', 'sureforms' ),
		text: __(
			'Describe the form you want and we will build the fields and layout for you.',
			'sureforms'
		),
		cta: __( 'Describe your form', 'sureforms' ),
		art: AiArt,
		tint: 'bg-background-illustration-blue',
	},
	template: {
		title: __( 'Choose a template', 'sureforms' ),
		text: __(
			'Pick a ready made form for contact, bookings, payments, quizzes and more.',
			'sureforms'
		),
		cta: __( 'Browse templates', 'sureforms' ),
		art: TemplateArt,
		tint: 'bg-brand-background-hover-100',
	},
};

/**
 * First step of Add New Form: create with AI, choose a template, or build
 * from scratch. Every view and choice is counted, so the funnel shows which
 * way people start and how far each path gets.
 *
 * @since x.x.x
 */
const StartScreen = () => {
	const navigate = useNavigate();
	const { search, key } = useLocation();

	// The choice is not cleared here: Back then Forward returns to the screen
	// that choice opened. The next choice replaces it anyway.
	useEffect( () => {
		if ( countedViews.has( key ) ) {
			return;
		}
		countedViews.add( key );
		trackStartStep( 'view' );
	}, [ key ] );

	const choose = ( choice, position ) => {
		// A blank form carries its choice in the URL instead.
		if ( 'scratch' !== choice ) {
			setStartChoice( choice );
		} else if ( 'learn' === new URLSearchParams( search ).get( 'source' ) ) {
			// As the AI screen's scratch button does: the editor stores the new
			// form for the Learn section's Lesson 2 redirect.
			localStorage.setItem( 'srfmLearnFlow', 'build-scratch' );
		}
		return trackStartStep( 'choose', {
			choice,
			position: String( position ),
		} );
	};

	const startFromScratch = ( event ) => {
		// A new tab or window keeps the page, so its count goes out anyway.
		if (
			event.metaKey ||
			event.ctrlKey ||
			event.shiftKey ||
			event.altKey ||
			0 !== event.button
		) {
			choose( 'scratch', 'link' );
			return;
		}
		// Leaving at once can cancel the count before it is sent.
		event.preventDefault();
		const href = event.currentTarget.href;
		Promise.race( [
			choose( 'scratch', 'link' ),
			new Promise( ( resolve ) => setTimeout( resolve, LEAVE_DELAY ) ),
		] ).then( () => window.location.assign( href ) );
	};

	const openPath = ( choice, position ) => {
		choose( choice, position );
		// Keep the other query args (e.g. source=learn) for the next screen.
		const params = new URLSearchParams( search );
		params.set( 'view', 'ai' === choice ? 'ai' : 'templates' );
		navigate( { search: `?${ params.toString() }` } );
	};

	return (
		<div className="h-screen overflow-y-auto bg-background-secondary">
			<Header />
			<main className="mt-14 max-w-[1120px] mx-auto px-10 max-[560px]:px-5">
				<Container
					direction="column"
					align="center"
					className="gap-0 text-center pt-10 pb-7 min-[981px]:pt-16 min-[981px]:pb-10"
				>
					<Text
						as="h1"
						className="w-full m-0 text-[26px] min-[981px]:text-[32px] leading-[1.2] font-bold tracking-[-0.02em] text-text-primary"
					>
						{ __( 'How do you want to start?', 'sureforms' ) }
					</Text>
					<Text
						as="p"
						className="mt-2.5 mb-0 mx-auto max-w-[520px] text-base leading-[1.45] font-normal text-text-secondary"
					>
						{ __(
							'Pick the way that suits you best. You can edit everything later.',
							'sureforms'
						) }
					</Text>
				</Container>

				<div className="grid grid-cols-1 gap-5 max-w-[480px] mx-auto pb-7 min-[981px]:grid-cols-2 min-[981px]:gap-[26px] min-[981px]:max-w-[720px]">
					{ CARD_ORDER.map( ( choice, index ) => {
						const card = CARDS[ choice ];
						const Art = card.art;
						const open = () => openPath( choice, index + 1 );
						return (
							// The button is the keyboard target; the card is a bigger mouse target.
							// eslint-disable-next-line jsx-a11y/click-events-have-key-events, jsx-a11y/no-static-element-interactions
							<div
								key={ choice }
								onClick={ open }
								className="group relative flex flex-col bg-background-primary border border-solid border-border-subtle rounded-xl overflow-hidden cursor-pointer transition-[border-color,box-shadow,transform] duration-[180ms] ease-in-out hover:border-border-interactive/45 hover:shadow-md-blur-32 hover:-translate-y-0.5 motion-reduce:transition-none"
							>
								<div
									aria-hidden="true"
									className={ cn(
										'relative h-[200px] flex items-center justify-center overflow-hidden',
										card.tint
									) }
								>
									<Art />
								</div>
								<div className="flex flex-col flex-auto pt-5 px-[22px] pb-[22px] text-start">
									<Text
										as="h2"
										className="m-0 text-xl leading-[1.25] font-bold tracking-[-0.01em] text-text-primary"
									>
										{ card.title }
									</Text>
									<Text
										as="p"
										className="mt-2 mb-5 text-[15px] leading-[1.5] font-normal text-text-secondary"
									>
										{ card.text }
									</Text>
									<div className="mt-auto flex items-center justify-between gap-3">
										<Button
											variant="outline"
											size="md"
											icon={
												<span className="inline-flex shrink-0 transition-transform duration-[180ms] ease-in-out group-hover:translate-x-[3px] rtl:rotate-180 rtl:group-hover:-translate-x-[3px] motion-reduce:transition-none">
													{ ICONS.startArrow }
												</span>
											}
											iconPosition="right"
											className="h-11 px-4 py-0 gap-2 [&>span]:px-0 [&>svg]:size-4 rounded-lg outline-0 border border-solid border-field-color-disabled bg-background-primary text-text-secondary text-sm font-semibold whitespace-nowrap shadow-none transition-colors duration-150 group-hover:bg-button-primary group-hover:border-button-primary group-hover:text-text-on-color hover:bg-button-primary hover:border-button-primary hover:text-text-on-color hover:outline-0 focus:shadow-none"
											onClick={ ( event ) => {
												event.stopPropagation();
												open();
											} }
										>
											{ card.cta }
										</Button>
									</div>
								</div>
							</div>
						);
					} ) }
				</div>

				<Text
					as="p"
					className="m-0 text-center pt-1 pb-[72px] text-[15px] leading-[1.5] font-normal text-text-secondary"
				>
					{ createInterpolateElement(
						/* translators: <link> wraps the link that opens a blank form. */
						__(
							'Prefer to build it yourself? <link>Start from scratch</link>',
							'sureforms'
						),
						{
							link: (
								<Button
									tag="a"
									variant="link"
									size="md"
									href={ getScratchUrl( 'scratch' ) }
									onClick={ startFromScratch }
									className="inline p-0 h-auto [&>span]:px-0 text-[15px] leading-[1.5] font-semibold text-text-primary no-underline hover:no-underline border-0 border-b border-solid border-field-color-disabled pb-px rounded-none transition-colors duration-150 hover:text-button-primary hover:border-button-primary focus:shadow-none"
								/>
							),
						}
					) }
				</Text>
			</main>
		</div>
	);
};

export default StartScreen;
