import { __, _n, sprintf } from '@wordpress/i18n';
import {
	useCallback,
	useEffect,
	useLayoutEffect,
	useRef,
	useState,
} from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { ArrowRight, X } from 'lucide-react';

// Yellow from the BFCM banner calendar, used for the timer, prices and button.
const HIGHLIGHT = 'text-[#FEF08A]';

const PART_CLASSES = {
	plain: '',
	bold: 'font-bold',
	accent: `font-bold ${ HIGHLIGHT }`,
};

const SECOND_IN_MS = 1000;

// Scroll speed in px per second, the same as the SureCookie and SureDonation
// sale bars. The copy is a different length in each sale period, so the
// duration is worked out from the measured width rather than fixed.
export const SPEED = 21;

/**
 * Days, hours, minutes and seconds left until `endsAt`, never below zero.
 *
 * @since x.x.x
 * @param {number} endsAt End time in milliseconds.
 * @param {number} now    Current time in milliseconds.
 * @return {Array<number>} [ days, hours, minutes, seconds ].
 */
export const getTimeLeft = ( endsAt, now ) => {
	const total = Math.max( 0, Math.floor( ( endsAt - now ) / SECOND_IN_MS ) );
	return [
		Math.floor( total / 86400 ),
		Math.floor( ( total % 86400 ) / 3600 ),
		Math.floor( ( total % 3600 ) / 60 ),
		total % 60,
	];
};

/**
 * How many copies of the segments one run needs, and how long it takes to
 * slide by one run. The marquee slides by one run, so a run narrower than the
 * visible area shows a blank stretch before the loop restarts.
 *
 * @since x.x.x
 * @param {number} copyWidth     Width of one copy of the segments, in px.
 * @param {number} viewportWidth Width of the visible area, in px.
 * @return {{repeat: number, duration: number}|null} Null until both are measured.
 */
export const getMarqueeLayout = ( copyWidth, viewportWidth ) => {
	if ( ! copyWidth || ! viewportWidth ) {
		return null;
	}
	const repeat = Math.max( 1, Math.ceil( viewportWidth / copyWidth ) );
	return { repeat, duration: ( repeat * copyWidth ) / SPEED };
};

/**
 * Screen reader label for the countdown, with plural forms.
 *
 * @since x.x.x
 * @param {string} label   Label such as "Sale ends in".
 * @param {number} days    Days left.
 * @param {number} hours   Hours left.
 * @param {number} minutes Minutes left.
 * @return {string} Label such as "Sale ends in 1 day, 2 hours, 3 minutes".
 */
export const getTimerLabel = ( label, days, hours, minutes ) =>
	sprintf(
		/* translators: 1: label such as "Sale ends in", 2: number of days, 3: number of hours, 4: number of minutes, e.g. "2 days". */
		__( '%1$s %2$s, %3$s, %4$s', 'sureforms' ),
		label,
		/* translators: %d: number of days. */
		sprintf( _n( '%d day', '%d days', days, 'sureforms' ), days ),
		/* translators: %d: number of hours. */
		sprintf( _n( '%d hour', '%d hours', hours, 'sureforms' ), hours ),
		/* translators: %d: number of minutes. */
		sprintf( _n( '%d minute', '%d minutes', minutes, 'sureforms' ), minutes )
	);

/**
 * Countdown to the end of the sale, ticking every second. Calls `onEnd` once
 * the end time is reached. It stays mounted on narrow screens, where it is
 * only hidden with CSS, so `onEnd` fires there too.
 *
 * @since x.x.x
 * @param {Object}   props        Component props.
 * @param {number}   props.endsAt End time in milliseconds.
 * @param {string}   props.label  Screen reader label, e.g. "Sale ends in".
 * @param {Function} props.onEnd  Called when the countdown reaches zero.
 * @return {JSX.Element} The countdown.
 */
export const Countdown = ( { endsAt, label, onEnd } ) => {
	const [ now, setNow ] = useState( () => Date.now() );

	useEffect( () => {
		const timer = setInterval( () => setNow( Date.now() ), SECOND_IN_MS );
		return () => clearInterval( timer );
	}, [] );

	useEffect( () => {
		if ( now >= endsAt ) {
			onEnd();
		}
	}, [ now, endsAt, onEnd ] );

	const [ days, hours, minutes, seconds ] = getTimeLeft( endsAt, now );
	const pad = ( value ) => String( value ).padStart( 2, '0' );

	return (
		<span
			role="timer"
			// A clock reads left to right in every language; without this an
			// RTL page reorders the digit groups.
			dir="ltr"
			className={ `hidden shrink-0 font-bold tabular-nums tracking-wide sm:inline ${ HIGHLIGHT }` }
			aria-label={ getTimerLabel( label, days, hours, minutes ) }
		>
			{ [ days, hours, minutes, seconds ].map( pad ).join( ' : ' ) }
		</span>
	);
};

/**
 * One full run of the segments, each followed by a star, repeated `repeat`
 * times. Rendered twice so the marquee loops without a gap. Only the first
 * set of the first run is read by assistive tech. With reduced motion the
 * repeats are dropped and the single set scrolls by hand instead.
 *
 * @since x.x.x
 * @param {Object}  props         Component props.
 * @param {Array}   props.items   Segments, each a list of `{ text, style }` parts.
 * @param {number}  props.repeat  Copies of the segments in the run.
 * @param {Object}  props.copyRef Ref for the first copy, to measure it.
 * @param {boolean} props.hidden  Hide this run from assistive tech.
 * @return {JSX.Element} One run of segments.
 */
const Run = ( { items, repeat, copyRef, hidden = false } ) => (
	<div
		className={ `flex shrink-0 ${ hidden ? 'motion-reduce:hidden' : '' }` }
		aria-hidden={ hidden || undefined }
	>
		{ Array.from( { length: repeat }, ( _, copy ) => (
			<span
				key={ copy }
				ref={ copy ? undefined : copyRef }
				className={ `flex shrink-0 items-center gap-6 pr-6 ${
					copy ? 'motion-reduce:hidden' : ''
				}` }
				aria-hidden={ copy ? true : undefined }
			>
				{ items.map( ( parts, index ) => (
					<span
						key={ index }
						className="inline-flex items-center gap-6"
					>
						<span className="inline-flex items-center gap-1.5 whitespace-nowrap">
							{ parts.map( ( part, partIndex ) => (
								<span
									key={ partIndex }
									className={
										PART_CLASSES[ part.style ] || ''
									}
								>
									{ part.text }
								</span>
							) ) }
						</span>
						<span className={ HIGHLIGHT } aria-hidden="true">
							✦
						</span>
					</span>
				) ) }
			</span>
		) ) }
	</div>
);

/**
 * Measure one copy of the segments and the visible area, and keep the
 * marquee layout in step when either changes size (fonts loading, window
 * resizing).
 *
 * @since x.x.x
 * @return {Object} Refs for the visible area and the first copy, and the layout.
 */
const useMarqueeLayout = () => {
	const viewportRef = useRef( null );
	const copyRef = useRef( null );
	const [ layout, setLayout ] = useState( null );

	useLayoutEffect( () => {
		const viewport = viewportRef.current;
		const copy = copyRef.current;
		if ( ! viewport || ! copy ) {
			return;
		}

		const measure = () =>
			setLayout( ( previous ) => {
				const next = getMarqueeLayout(
					copy.offsetWidth,
					viewport.clientWidth
				);
				return previous?.repeat === next?.repeat &&
					previous?.duration === next?.duration
					? previous
					: next;
			} );

		measure();

		if ( typeof window.ResizeObserver === 'undefined' ) {
			return;
		}
		const observer = new window.ResizeObserver( measure );
		observer.observe( viewport );
		observer.observe( copy );
		return () => observer.disconnect();
	}, [] );

	return { viewportRef, copyRef, layout };
};

/**
 * Scrolling sale bar above the SureForms admin header.
 *
 * The server sends `promotion` only while the sale is live, to users who
 * manage SureForms without Pro and have not dismissed it, with the copy for
 * the running sale period. The bar counts itself as shown once it is on
 * screen, and in a tab left open past the end of the sale it goes away the
 * moment the countdown reaches zero.
 *
 * @since x.x.x
 * @param {Object}   props           Component props.
 * @param {Object}   props.promotion Promotion data from `srfm_admin.promotion`.
 * @param {Function} props.onHide    Called once the bar hides, after a dismissal or the end of the sale.
 * @return {JSX.Element|null} The sale bar, or nothing.
 */
const PromoBar = ( { promotion, onHide = () => {} } ) => {
	const endsAt = ( promotion?.ends_at || 0 ) * 1000;
	const [ isDismissed, setIsDismissed ] = useState( false );
	const [ isOver, setIsOver ] = useState( () => Date.now() >= endsAt );
	const sectionRef = useRef( null );
	const { viewportRef, copyRef, layout } = useMarqueeLayout();
	const [ reduceMotion ] = useState(
		() =>
			!! window.matchMedia?.( '(prefers-reduced-motion: reduce)' )
				?.matches
	);

	const isShowing =
		!! promotion?.items?.length &&
		!! promotion?.cta?.url &&
		! isDismissed &&
		! isOver;

	// Analytics and dismissal calls never block the bar or the link.
	const post = useCallback(
		( action ) =>
			apiFetch( {
				path: `/sureforms/v1/promotion/${ action }`,
				method: 'POST',
				data: { id: promotion?.id },
			} ).catch( () => {} ),
		[ promotion?.id ]
	);

	const handleEnd = useCallback( () => {
		setIsOver( true );
		onHide();
	}, [ onHide ] );

	// Counted once the bar is on screen, not when its data is built, so
	// admin pages without the bar count nothing.
	const isShowingOnMount = useRef( isShowing );
	useEffect( () => {
		if ( isShowingOnMount.current ) {
			post( 'shown' );
		}
	}, [ post ] );

	if ( ! isShowing ) {
		return null;
	}

	const handleDismiss = () => {
		// The button is about to unmount, which would drop keyboard focus to
		// the top of the page. Move it to the first visible control in the
		// header; the first ones in the DOM may belong to a hidden mobile menu.
		const next = Array.from(
			sectionRef.current?.nextElementSibling?.querySelectorAll(
				'a[href], button:not([disabled])'
			) || []
		).find( ( element ) => element.getClientRects().length > 0 );
		setIsDismissed( true );
		onHide();
		window.requestAnimationFrame( () => next?.focus() );
		post( 'dismiss' );
	};

	const repeat = layout?.repeat || 4;

	return (
		<section
			ref={ sectionRef }
			className="group relative flex min-w-0 items-center gap-3 bg-background-brand px-4 py-2.5 text-sm text-white"
			aria-label={ __( 'Promotion', 'sureforms' ) }
		>
			<Countdown
				endsAt={ endsAt }
				label={ promotion.timer_label }
				onEnd={ handleEnd }
			/>

			<div
				ref={ viewportRef }
				// Reduced motion leaves a single copy to scroll by hand, which
				// keyboard users can only do while the area can take focus.
				{ ...( reduceMotion && {
					tabIndex: 0,
					role: 'region',
					'aria-label': __( 'Sale details', 'sureforms' ),
				} ) }
				className="relative min-w-0 flex-1 overflow-hidden motion-reduce:overflow-x-auto [mask-image:linear-gradient(to_right,transparent,#000_24px,#000_calc(100%-24px),transparent)] motion-reduce:[mask-image:none]"
			>
				{ /*
				 * Animated only under motion-safe, so reduced motion never
				 * animates in either direction. RTL pages lay the track out
				 * from the right, so they slide the other way.
				 */ }
				<div
					className="flex w-max motion-safe:animate-srfm-marquee motion-safe:rtl:animate-srfm-marquee-rtl group-hover:[animation-play-state:paused] group-focus-within:[animation-play-state:paused]"
					style={
						layout
							? { animationDuration: `${ layout.duration }s` }
							: undefined
					}
				>
					<Run
						items={ promotion.items }
						repeat={ repeat }
						copyRef={ copyRef }
					/>
					<Run items={ promotion.items } repeat={ repeat } hidden />
				</div>
			</div>

			<a
				href={ promotion.cta.url }
				onClick={ () => post( 'click' ) }
				target="_blank"
				rel="noopener noreferrer"
				className="inline-flex shrink-0 items-center gap-1 rounded-md bg-[#FEF08A] px-3 py-1.5 text-sm font-bold text-text-primary no-underline hover:bg-[#FDE047] hover:text-text-primary focus:text-text-primary"
			>
				{ promotion.cta.label }
				<ArrowRight className="size-4" aria-hidden="true" />
			</a>

			<button
				type="button"
				onClick={ handleDismiss }
				className="inline-flex shrink-0 cursor-pointer items-center rounded-md border border-solid border-white/30 bg-transparent px-2 py-1.5 text-white hover:bg-white/10"
				aria-label={ __( 'Dismiss promotion', 'sureforms' ) }
			>
				<X className="size-4" aria-hidden="true" />
			</button>
		</section>
	);
};

export default PromoBar;
