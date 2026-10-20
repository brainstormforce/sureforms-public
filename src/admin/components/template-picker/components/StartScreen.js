import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { useNavigate, useLocation } from 'react-router-dom';
import { Badge, Button, Container, Skeleton, Text } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';
import {
	clearStartChoice,
	getScratchUrl,
	setStartChoice,
	trackStartStep,
} from '@Utils/form-start';
import Header from './Header.js';

/**
 * Cards in the order they are shown. The order is recorded with every choice,
 * so the share of each path can be read apart from where its card sat.
 */
const CARD_ORDER = [ 'ai', 'template' ];

// Icons drawn to match the design exactly.
const PaymentIcon = () => (
	<svg
		width="11"
		height="11"
		viewBox="0 0 24 24"
		fill="none"
		stroke="currentColor"
		strokeWidth="2"
		strokeLinecap="round"
		strokeLinejoin="round"
		aria-hidden="true"
	>
		<rect x="2.5" y="5" width="19" height="14" rx="2" />
		<path d="M2.5 10h19" />
	</svg>
);

const QuizIcon = () => (
	<svg
		width="11"
		height="11"
		viewBox="0 0 24 24"
		fill="none"
		stroke="currentColor"
		strokeWidth="2"
		strokeLinecap="round"
		strokeLinejoin="round"
		aria-hidden="true"
	>
		<path d="M4 7l2 2 3-3M4 17l2 2 3-3M13 8h7M13 18h7" />
	</svg>
);

const SparkleIcon = () => (
	<svg
		width="12"
		height="12"
		viewBox="0 0 24 24"
		fill="currentColor"
		aria-hidden="true"
	>
		<path d="M12 2l1.9 6.1L20 10l-6.1 1.9L12 18l-1.9-6.1L4 10l6.1-1.9zM19 15l.9 2.6 2.6.9-2.6.9L19 22l-.9-2.6-2.6-.9 2.6-.9z" />
	</svg>
);

const ArrowIcon = () => (
	<svg
		width="16"
		height="16"
		viewBox="0 0 24 24"
		fill="none"
		stroke="currentColor"
		strokeWidth="2"
		strokeLinecap="round"
		strokeLinejoin="round"
		aria-hidden="true"
		className="shrink-0 transition-transform duration-[180ms] ease-in-out group-hover:translate-x-[3px] motion-reduce:transition-none"
	>
		<path d="M5 12h14M13 6l6 6-6 6" />
	</svg>
);

// Static bar for the illustrations, not a loading state.
const Bar = ( { className } ) => (
	<Skeleton className={ cn( 'animate-none w-auto', className ) } />
);

const Chip = ( { icon, label } ) => (
	<Badge
		size="xs"
		type="rounded"
		variant="neutral"
		icon={ icon }
		label={ label }
		className="h-[22px] px-2 py-0 gap-1 rounded-[5px] border border-solid border-field-color-disabled bg-transparent hover:bg-transparent text-[11px] leading-none font-medium text-text-secondary whitespace-nowrap [&_svg]:size-[11px] [&_svg]:text-[#6B7280] [&>span:last-child]:px-0"
	/>
);

const AiArt = () => (
	<div className="w-[84%] max-w-[300px]">
		<Container
			direction="column"
			className="min-h-[128px] gap-3.5 pt-3.5 px-3.5 pb-3 bg-background-primary border border-solid border-border-subtle rounded-[10px] shadow-[0_6px_18px_rgba(17,24,39,0.08)]"
		>
			<Container direction="column" className="gap-[9px] pt-0.5">
				<Bar className="h-2.5 rounded bg-border-subtle" />
				<Bar className="h-2.5 w-[70%] rounded bg-border-subtle" />
			</Container>
			<Container align="center" className="mt-auto gap-1.5">
				<Chip
					icon={ <PaymentIcon /> }
					label={ __( 'Payment', 'sureforms' ) }
				/>
				<Chip icon={ <QuizIcon /> } label={ __( 'Quiz', 'sureforms' ) } />
				<span className="ml-auto h-[26px] px-2.5 inline-flex items-center gap-[5px] rounded-[5px] bg-[rgba(213,68,7,0.16)] text-[rgba(213,68,7,0.9)] text-[11.5px] font-semibold whitespace-nowrap">
					<SparkleIcon />
					{ __( 'Generate', 'sureforms' ) }
				</span>
			</Container>
		</Container>
	</div>
);

// Parts of the mini forms in the template illustration.
const MiniTitle = () => (
	<div className="h-[7px] w-[58%] mb-[3px] rounded-[3px] bg-field-color-disabled" />
);
const MiniLabel = ( { width = '30%' } ) => (
	<div
		className="h-1 rounded-sm bg-field-color-disabled"
		style={ { width } }
	/>
);
const MiniInput = ( { height = 15 } ) => (
	<div
		className="mb-[3px] rounded border border-solid border-[#D9DCE1] bg-background-primary"
		style={ { height } }
	/>
);
const MiniSubmit = () => (
	<div className="relative mt-auto h-[15px] w-[46%] rounded bg-[rgba(213,68,7,0.26)] after:content-[''] after:absolute after:left-[14%] after:right-[14%] after:top-1/2 after:h-[3px] after:-translate-y-1/2 after:rounded-sm after:bg-[rgba(255,255,255,0.85)]" />
);

const MiniForm = ( { className, children } ) => (
	<div
		className={ cn(
			'absolute flex flex-col gap-[5px] pt-[11px] px-[11px] pb-3 bg-background-primary rounded-lg shadow-[0_6px_16px_rgba(17,24,39,0.10)]',
			className
		) }
	>
		<MiniTitle />
		{ children }
		<MiniSubmit />
	</div>
);

const TemplateArt = () => (
	<div className="relative w-[82%] max-w-[300px] h-40">
		<MiniForm className="left-0 top-4 w-[44%] h-[132px] -rotate-[4deg]">
			<MiniLabel />
			<MiniInput />
			<MiniLabel width="38%" />
			<MiniInput />
			<MiniLabel />
			<MiniInput />
		</MiniForm>
		<MiniForm className="right-0 top-0.5 w-[44%] h-[132px] rotate-[3deg]">
			<MiniLabel />
			<MiniInput />
			<MiniLabel width="42%" />
			<MiniInput height={ 26 } />
		</MiniForm>
		<MiniForm className="left-1/4 top-[22px] w-1/2 h-[134px] z-[2]">
			<div className="flex gap-1.5">
				{ [ 0, 1 ].map( ( i ) => (
					<div key={ i } className="flex flex-1 flex-col gap-[5px]">
						<MiniLabel />
						<MiniInput />
					</div>
				) ) }
			</div>
			<MiniLabel width="26%" />
			<MiniInput />
			<MiniLabel width="34%" />
			<MiniInput height={ 20 } />
		</MiniForm>
	</div>
);

const CARDS = {
	ai: {
		title: __( 'Create with AI', 'sureforms' ),
		text: __(
			'Describe the form you want and we will build the fields and layout for you.',
			'sureforms'
		),
		cta: __( 'Describe your form', 'sureforms' ),
		art: AiArt,
		tint: 'bg-[#E6EEFB]',
	},
	template: {
		title: __( 'Choose a template', 'sureforms' ),
		text: __(
			'Pick a ready made form for contact, bookings, payments, quizzes and more.',
			'sureforms'
		),
		cta: __( 'Browse templates', 'sureforms' ),
		art: TemplateArt,
		tint: 'bg-[#FFE9DC]',
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
	const { search } = useLocation();

	useEffect( () => {
		// A fresh visit starts a fresh attempt.
		clearStartChoice();
		trackStartStep( 'view', { order: CARD_ORDER.join( ',' ) } );
	}, [] );

	const choose = ( choice, position ) => {
		// A blank form carries its choice in the URL instead.
		if ( 'scratch' !== choice ) {
			setStartChoice( choice );
		}
		trackStartStep( 'choose', { choice, position: String( position ) } );
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
						className="mt-2.5 mb-0 mx-auto max-w-[520px] text-base leading-[1.45] font-normal text-[#6B7280]"
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
								className="group relative flex flex-col bg-background-primary border border-solid border-border-subtle rounded-xl overflow-hidden cursor-pointer transition-[border-color,box-shadow,transform] duration-[180ms] ease-in-out hover:border-[rgba(213,68,7,0.45)] hover:shadow-[0_10px_28px_rgba(17,24,39,0.08)] hover:-translate-y-0.5 motion-reduce:transition-none"
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
								<div className="flex flex-col flex-auto pt-5 px-[22px] pb-[22px] text-left">
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
											icon={ <ArrowIcon /> }
											iconPosition="right"
											className="h-11 px-4 py-0 gap-2 [&>span]:px-0 [&>svg]:size-4 rounded-lg outline-0 border border-solid border-field-color-disabled bg-background-primary text-[#374151] text-sm font-semibold whitespace-nowrap shadow-none transition-colors duration-150 group-hover:bg-button-primary group-hover:border-button-primary group-hover:text-text-on-color hover:bg-button-primary hover:border-button-primary hover:text-text-on-color hover:outline-0 focus:shadow-none"
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
					className="m-0 text-center pt-1 pb-[72px] text-[15px] leading-[1.5] font-normal text-[#6B7280]"
				>
					{ __( 'Prefer to build it yourself?', 'sureforms' ) }{ ' ' }
					<Button
						tag="a"
						variant="link"
						size="md"
						href={ getScratchUrl( 'scratch' ) }
						onClick={ () => choose( 'scratch', 'link' ) }
						className="inline p-0 h-auto [&>span]:px-0 text-[15px] leading-[1.5] font-semibold text-text-primary no-underline hover:no-underline border-0 border-b border-solid border-field-color-disabled pb-px rounded-none transition-colors duration-150 hover:text-button-primary hover:border-button-primary focus:shadow-none"
					>
						{ __( 'Start from scratch', 'sureforms' ) }
					</Button>
				</Text>
			</main>
		</div>
	);
};

export default StartScreen;
