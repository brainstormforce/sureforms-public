import { __, sprintf } from '@wordpress/i18n';
import {
	createInterpolateElement,
	useState,
	useEffect,
	useRef,
} from '@wordpress/element';
import { Text, Badge, Button, Tabs } from '@bsf/force-ui';
import { ExternalLink } from 'lucide-react';
import { useOnboardingNavigation } from '../hooks';
import { useOnboardingState } from '../onboarding-state';
import NavigationButtons from '../components/navigation-buttons';
import { Header, Divider, BeforeAfterSlider } from '../components';
import { addQueryParam } from '@Utils/Helpers';
import ADDON_COMPARISONS from '../illustrations/addon-comparisons';

const COUPON_CODE = 'ONB10';
const TAB_ROTATE_MS = 3000;

// One tab per add-on; the free/pro mock pair for each lives in
// ../illustrations/addon-comparisons.js keyed by slug.
const TABS = [
	{
		slug: 'multistep',
		tab: __( 'Multistep', 'sureforms' ),
		title: __( 'Multistep Forms', 'sureforms' ),
		description: __(
			'Break long forms into short steps. Fewer drop-offs, more completions.',
			'sureforms'
		),
	},
	{
		slug: 'conditional',
		tab: __( 'Conditional', 'sureforms' ),
		title: __( 'Conditional Fields', 'sureforms' ),
		description: __(
			"Show fields only when they're relevant. Cleaner forms, better answers.",
			'sureforms'
		),
	},
	{
		slug: 'calculation',
		tab: __( 'Calculation', 'sureforms' ),
		title: __( 'Calculation Forms', 'sureforms' ),
		description: __(
			'Give instant estimates, quotes, and totals as users fill things in.',
			'sureforms'
		),
	},
	{
		slug: 'conversational',
		tab: __( 'Conversational', 'sureforms' ),
		title: __( 'Conversational Forms', 'sureforms' ),
		description: __(
			'One question at a time. Feels like a chat — completes like one too.',
			'sureforms'
		),
	},
];

const copyText = async ( text ) => {
	try {
		await navigator.clipboard.writeText( text );
		return true;
	} catch ( error ) {
		// Clipboard API unavailable (insecure context / permissions): fall
		// back to a hidden textarea + execCommand.
		try {
			const textArea = document.createElement( 'textarea' );
			textArea.value = text;
			textArea.style.position = 'fixed';
			textArea.style.opacity = '0';
			document.body.appendChild( textArea );
			textArea.focus();
			textArea.select();
			document.execCommand( 'copy' );
			document.body.removeChild( textArea );
			return true;
		} catch ( fallbackError ) {
			return false;
		}
	}
};

const PremiumFeatures = () => {
	const [ , actions ] = useOnboardingState();
	const { navigateToPreviousRoute, navigateToNextRoute } =
		useOnboardingNavigation();
	const [ activeSlug, setActiveSlug ] = useState( TABS[ 0 ].slug );
	const [ isCopied, setIsCopied ] = useState( false );
	const [ isPaused, setIsPaused ] = useState( false );
	const [ hasChosenTab, setHasChosenTab ] = useState( false );

	// Rotate through the tabs while nobody is using the showcase.
	//
	// Stopping matters more than rotating. Each tick remounts BeforeAfterSlider
	// (it is keyed by slug), and that slider's range input is the only focusable
	// control in the step -- so a rotation while it has focus drops focus onto
	// <body>. Pausing on pointer alone left a keyboard user unable to hold it for
	// more than one tick, which is WCAG 2.2.2 as well as simply unusable.
	//
	// Three things stop it: the pointer or focus being inside (isPaused), the
	// visitor having picked a tab (hasChosenTab), and prefers-reduced-motion,
	// which is exactly the setting for content that moves on its own.
	useEffect( () => {
		const prefersReducedMotion = window.matchMedia?.(
			'(prefers-reduced-motion: reduce)'
		)?.matches;

		if ( isPaused || hasChosenTab || prefersReducedMotion ) {
			return;
		}
		const timer = setInterval( () => {
			setActiveSlug( ( current ) => {
				const index = TABS.findIndex(
					( item ) => item.slug === current
				);
				return TABS[ ( index + 1 ) % TABS.length ].slug;
			} );
		}, TAB_ROTATE_MS );
		return () => clearInterval( timer );
	}, [ isPaused, hasChosenTab ] );

	const activeTab =
		TABS.find( ( item ) => item.slug === activeSlug ) || TABS[ 0 ];

	// TABS and ADDON_COMPARISONS are two hand-maintained maps over the same four
	// slugs. Adding a tab without its illustration should show an empty panel,
	// not throw and take the whole step down.
	const comparison = ADDON_COMPARISONS[ activeTab.slug ] ?? {};

	// Only the first tab is "viewed" without a click; every later entry comes
	// from handleTabChange. Recording the rotation as well would turn
	// viewed_premium_tabs into "sat on the step for 12 seconds".
	useEffect( () => {
		actions.markPremiumTabViewed( TABS[ 0 ].slug );
		// Moves upgradeClicked off null, which is what tells analytics the step
		// was reached at all. Pro installs skip it entirely, and without this they
		// would report premium_upgrade_clicked='no' -- indistinguishable from a
		// free user who saw the tabs and declined.
		actions.setPremiumUpgradeClicked( false );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	const pricingUrl = addQueryParam(
		srfm_admin?.sureforms_pricing_page,
		'onboarding_premium_features'
	);

	const handleUpgrade = () => {
		actions.setPremiumUpgradeClicked( true );
		window.open( pricingUrl, '_blank', 'noopener' );
	};

	const handleUpgradeAndContinue = () => {
		handleUpgrade();
		navigateToNextRoute();
	};

	const handleSkip = () => {
		actions.markStepSkipped( 'premiumFeatures' );
		navigateToNextRoute();
	};

	// Held in a ref so unmounting mid-countdown (e.g. clicking Upgrade) clears it.
	const copyTimer = useRef( null );
	useEffect( () => () => clearTimeout( copyTimer.current ), [] );

	const handleCopy = async () => {
		if ( await copyText( COUPON_CODE ) ) {
			setIsCopied( true );
			clearTimeout( copyTimer.current );
			copyTimer.current = setTimeout( () => setIsCopied( false ), 1500 );
		}
	};

	// A deliberate choice should stick: rotation stops for good once someone
	// picks a tab, rather than sliding off it a few seconds later.
	const handleTabChange = ( slug ) => {
		setActiveSlug( slug );
		setHasChosenTab( true );
		actions.markPremiumTabViewed( slug );
	};

	return (
		<div className="space-y-7">
			<Header
				tag="h2"
				align="center"
				title={ __( 'Add more to your forms', 'sureforms' ) }
				description={ __(
					'Small additions that make a big difference',
					'sureforms'
				) }
			/>

			<div
				className="flex flex-col items-center gap-8"
				onMouseEnter={ () => setIsPaused( true ) }
				onMouseLeave={ () => setIsPaused( false ) }
				// Capture phase: focus and blur do not bubble, so the listener has
				// to see them on the way down or a keyboard user never pauses.
				onFocusCapture={ () => setIsPaused( true ) }
				onBlurCapture={ () => setIsPaused( false ) }
			>
				<Tabs.Group
					activeItem={ activeSlug }
					onChange={ ( { value } ) =>
						handleTabChange(
							typeof value === 'string' ? value : value?.slug
						)
					}
					size="sm"
					variant="rounded"
					width="auto"
					// force-ui marks the active tab with bg-background-primary;
					// recolour that to the brand fill from the group so the
					// highlight always follows the component's own active state.
					className="[&_.bg-background-primary]:bg-button-primary [&_.bg-background-primary]:text-text-on-color [&_.bg-background-primary:hover]:text-text-on-color"
				>
					{ TABS.map( ( item ) => (
						<Tabs.Tab
							key={ item.slug }
							slug={ item.slug }
							text={ item.tab }
						/>
					) ) }
				</Tabs.Group>

				<div className="flex items-center justify-center gap-7">
					{ /* Keyed by slug so the divider resets when the tab changes. */ }
					<BeforeAfterSlider
						key={ activeTab.slug }
						before={ comparison.free }
						after={ comparison.pro }
						initial={ 20 }
						label={ sprintf(
							/* translators: %s: add-on name, e.g. Multistep Forms. */
							__( 'Compare %s: free versus premium', 'sureforms' ),
							activeTab.title
						) }
					/>
					<div className="w-[304px] space-y-1.5 rounded-lg bg-background-primary p-2">
						<div className="flex items-center gap-2">
							<Text size={ 16 } weight={ 500 } color="primary">
								{ activeTab.title }
							</Text>
							<Badge
								label={ __( 'Premium', 'sureforms' ) }
								size="xs"
								variant="inverse"
							/>
						</div>
						<Text size={ 14 } weight={ 400 } color="tertiary">
							{ activeTab.description }
						</Text>
						<a
							href={ pricingUrl }
							target="_blank"
							rel="noopener noreferrer"
							onClick={ () =>
								actions.setPremiumUpgradeClicked( true )
							}
							className="inline-block text-xs font-semibold text-button-primary no-underline hover:text-button-primary-hover hover:underline"
						>
							{ __( 'Upgrade Now', 'sureforms' ) }
						</a>
					</div>
				</div>
			</div>

			<Divider />

			<div className="flex items-center justify-between gap-2 rounded-lg p-3 ring-1 ring-alert-border-neutral bg-[#F9FAFB]">
				<Text
					size={ 12 }
					weight={ 400 }
					color="primary"
					className="px-1"
				>
					{ createInterpolateElement(
						sprintf(
							/* translators: %1$s: coupon code, e.g. ONB10. */
							__(
								'Use code <code>%1$s</code> for 10%% off any SureForms Business plan.',
								'sureforms'
							),
							COUPON_CODE
						),
						{ code: <strong className="font-semibold" /> }
					) }
				</Text>
				<Button variant="link" size="xs" onClick={ handleCopy }>
					{ isCopied
						? __( 'Copied', 'sureforms' )
						: __( 'Copy', 'sureforms' ) }
				</Button>
			</div>

			<NavigationButtons
				backProps={ { onClick: navigateToPreviousRoute } }
				skipProps={ { onClick: handleSkip } }
				continueProps={ {
					onClick: handleUpgradeAndContinue,
					text: __( 'Upgrade', 'sureforms' ),
					icon: <ExternalLink />,
					className: '[&>svg]:size-4',
				} }
			/>
		</div>
	);
};

export default PremiumFeatures;
