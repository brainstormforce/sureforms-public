import { __ } from '@wordpress/i18n';
import { useEffect } from '@wordpress/element';
import { useNavigate, useLocation } from 'react-router-dom';
import { Badge, Button, Container, Skeleton, Text, Title } from '@bsf/force-ui';
import { ArrowRight, CreditCard, ListChecks, PencilLine, Sparkles } from 'lucide-react';
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

// Static bars for the illustrations, not a loading state.
const Bar = ( { className } ) => (
	<Skeleton className={ cn( 'animate-none h-2.5 w-full', className ) } />
);

const AiArt = () => (
	<Container
		direction="column"
		className="w-[84%] max-w-[300px] min-h-32 gap-3.5 p-3.5 bg-background-primary border border-solid border-border-subtle rounded-lg shadow-md"
	>
		<Container direction="column" className="gap-2 pt-0.5">
			<Bar />
			<Bar className="w-[70%]" />
		</Container>
		<Container align="center" className="mt-auto gap-1.5">
			<Badge
				size="xs"
				type="rounded"
				variant="neutral"
				icon={ <CreditCard /> }
				label={ __( 'Payment', 'sureforms' ) }
			/>
			<Badge
				size="xs"
				type="rounded"
				variant="neutral"
				icon={ <ListChecks /> }
				label={ __( 'Quiz', 'sureforms' ) }
			/>
			<Button
				tag="span"
				size="xs"
				variant="primary"
				className="ml-auto pointer-events-none"
				icon={ <Sparkles /> }
				tabIndex={ -1 }
			>
				{ __( 'Generate', 'sureforms' ) }
			</Button>
		</Container>
	</Container>
);

const MiniForm = ( { className, rows = 2 } ) => (
	<Container
		direction="column"
		className={ cn(
			'absolute gap-1 p-2.5 pb-3 bg-background-primary rounded-lg shadow-md',
			className
		) }
	>
		<Bar className="h-2 w-[58%] mb-1 bg-border-strong" />
		{ Array.from( { length: rows }, ( _, i ) => (
			<Container key={ i } direction="column" className="gap-1">
				<Bar className="h-1 w-[30%] bg-border-strong" />
				<div className="h-4 mb-0.5 rounded border border-solid border-field-border" />
			</Container>
		) ) }
		<div className="mt-auto h-4 w-[46%] rounded bg-button-primary" />
	</Container>
);

const TemplateArt = () => (
	<div className="relative w-[82%] max-w-[300px] h-40">
		<MiniForm className="left-0 top-4 w-[44%] h-32 -rotate-[4deg]" />
		<MiniForm className="right-0 top-0.5 w-[44%] h-32 rotate-[3deg]" />
		<MiniForm className="left-1/4 top-5 w-1/2 h-36 z-[2]" rows={ 3 } />
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
		tint: 'bg-background-secondary',
	},
	template: {
		title: __( 'Choose a template', 'sureforms' ),
		text: __(
			'Pick a ready made form for contact, bookings, payments, quizzes and more.',
			'sureforms'
		),
		cta: __( 'Browse templates', 'sureforms' ),
		art: TemplateArt,
		tint: 'bg-brand-background-50',
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
			<Container
				direction="column"
				align="center"
				className="mt-14 max-w-[1120px] mx-auto px-5 sm:px-10 gap-0"
			>
				<Container
					direction="column"
					align="center"
					className="gap-2.5 pt-10 pb-7 lg:pt-16 lg:pb-10 text-center"
				>
					<Title
						tag="h1"
						size="lg"
						title={ __( 'How do you want to start?', 'sureforms' ) }
						className="[&>*]:text-[26px] lg:[&>*]:text-[32px] [&>*]:font-bold"
					/>
					<Text size={ 16 } color="secondary" className="max-w-[520px]">
						{ __(
							'Pick the way that suits you best. You can edit everything later.',
							'sureforms'
						) }
					</Text>
				</Container>

				<div className="grid grid-cols-1 lg:grid-cols-2 gap-5 lg:gap-6 w-full max-w-[480px] lg:max-w-[720px] pb-7">
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
								className="group flex flex-col bg-background-primary border border-solid border-border-subtle rounded-xl overflow-hidden cursor-pointer transition-[border-color,box-shadow] duration-200 hover:border-brand-border-300 hover:shadow-lg motion-reduce:transition-none"
							>
								<div
									aria-hidden="true"
									className={ cn(
										'h-[200px] flex items-center justify-center overflow-hidden',
										card.tint
									) }
								>
									<Art />
								</div>
								<Container
									direction="column"
									className="flex-1 gap-2 px-5 pt-5 pb-5"
								>
									<Title
										tag="h2"
										size="md"
										title={ card.title }
										className="[&>*]:font-bold"
									/>
									<Text
										size={ 14 }
										color="secondary"
										className="mb-3"
									>
										{ card.text }
									</Text>
									<Button
										variant="outline"
										size="md"
										icon={ <ArrowRight /> }
										iconPosition="right"
										className="mt-auto self-start group-hover:bg-button-primary group-hover:text-text-on-color group-hover:outline-button-primary"
										onClick={ ( event ) => {
											event.stopPropagation();
											open();
										} }
									>
										{ card.cta }
									</Button>
								</Container>
							</div>
						);
					} ) }
				</div>

				<Container
					align="center"
					justify="center"
					className="gap-1 pt-1 pb-[72px]"
				>
					<Text size={ 14 } color="secondary">
						{ __( 'Prefer to build it yourself?', 'sureforms' ) }
					</Text>
					<Button
						tag="a"
						href={ getScratchUrl( 'scratch' ) }
						variant="link"
						size="md"
						icon={ <PencilLine /> }
						onClick={ () => choose( 'scratch', 'link' ) }
					>
						{ __( 'Start from scratch', 'sureforms' ) }
					</Button>
				</Container>
			</Container>
		</div>
	);
};

export default StartScreen;
