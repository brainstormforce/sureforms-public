import { __, sprintf } from '@wordpress/i18n';
import { Button } from '@bsf/force-ui';
import { useEffect } from '@wordpress/element';
import { ExternalLink } from 'lucide-react';
import { useOnboardingNavigation } from '../hooks';
import { useOnboardingState } from '../onboarding-state';
import { Divider, Header, FeatureList, HERO_PANEL_CLASS } from '../components';
import NavigationButtons from '../components/navigation-buttons';
// Rendered via <object>, not <img> or an inlined component: the artwork
// animates with CSS keyframes, which svgr/svgo strips when inlining, and
// <object> gives the SVG its own document where they run untouched.
import cacheConflictIllustration from '@Image/onboarding/cache-conflict.svg';

const tips = [
	__( 'Exclude SureForms pages from the cache completely', 'sureforms' ),
	__(
		'Clear your browser cache after changing plugin settings',
		'sureforms'
	),
	__(
		'Prevent failed form submissions and unexpected caching errors',
		'sureforms'
	),
];

/**
 * Shown only when a recognised caching plugin is active (see
 * getVisibleRoutes in ../hooks.js). "View full guide" opens the plugin's
 * setup guide without leaving the wizard; "I've fixed this" records the
 * acknowledgement and moves on.
 */
const CacheConflict = () => {
	const { navigateToNextRoute, navigateToPreviousRoute } =
		useOnboardingNavigation();
	const [ onboardingState, actions ] = useOnboardingState();
	const acknowledged =
		onboardingState?.analytics?.cacheConflictAcknowledged ?? null;

	// Flip null -> false on render so the analytics blob distinguishes
	// "never saw this step" from "saw it and did not acknowledge".
	//
	// Only from null. The effect re-runs on every mount, and this step is
	// reachable again with browser Back, so an unconditional reset would turn a
	// recorded acknowledgement back into "did not acknowledge".
	useEffect( () => {
		if ( null === acknowledged ) {
			actions.setCacheConflictAcknowledged( false );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	const pluginName = srfm_admin?.caching_plugin || '';
	const guideUrl = srfm_admin?.caching_plugin_doc_url || '';

	const handleViewGuide = () => {
		if ( guideUrl ) {
			window.open( guideUrl, '_blank', 'noopener' );
		}
	};

	const handleFixed = () => {
		actions.setCacheConflictAcknowledged( true );
		navigateToNextRoute();
	};

	return (
		<div className="space-y-4">
			<div className={ HERO_PANEL_CLASS }>
				<object
					type="image/svg+xml"
					data={ cacheConflictIllustration }
					className="pointer-events-none block h-auto w-full"
					aria-label={ __(
						'Illustration comparing a form with and without a cache exclusion',
						'sureforms'
					) }
				/>
			</div>

			<Header
				title={ sprintf(
					/* translators: %s: caching plugin name, e.g. LiteSpeed Cache. */
					__( '%s might be blocking your forms', 'sureforms' ),
					pluginName
				) }
				description={ __(
					"Without an exclusion, visitors may see outdated pages or submissions that don't go through as expected, leading to failed form entries and frustrated users.",
					'sureforms'
				) }
			/>

			<FeatureList
				heading={ __( 'Keep Your Forms Working', 'sureforms' ) }
				items={ tips }
			/>

			<Button
				variant="outline"
				size="sm"
				icon={ <ExternalLink /> }
				iconPosition="right"
				className="[&>svg]:size-4"
				onClick={ handleViewGuide }
				disabled={ ! guideUrl }
			>
				{ __( 'View full guide', 'sureforms' ) }
			</Button>

			<Divider />

			{ /*
			  * The primary button is the one that moves the wizard on. It used to
			  * be "View full guide", which leaves the wizard for a docs tab, with
			  * muted ghost text as the only way forward -- so people clicked the
			  * primary, read the docs, came back and had to find the real control.
			  *
			  * There is deliberately no skip here: acknowledging is the only way
			  * past this step. That makes cacheConflictAcknowledged an abandonment
			  * signal rather than a measure of intent -- 'yes' for everyone who
			  * finishes, 'no' only alongside exited_early. admin/analytics.php says
			  * the same thing where the property is written, so nobody reads it as
			  * "how many people ignored the warning".
			  */ }
			<NavigationButtons
				backProps={ { onClick: navigateToPreviousRoute } }
				continueProps={ {
					onClick: handleFixed,
					text: __( "I've fixed this, continue setup", 'sureforms' ),
				} }
			/>
		</div>
	);
};

export default CacheConflict;
