import { __, sprintf } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
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
const MINUTE_IN_MS = 60 * SECOND_IN_MS;

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
 * Countdown to the end of the sale, ticking every second.
 *
 * @since x.x.x
 * @param {Object} props        Component props.
 * @param {number} props.endsAt End time in milliseconds.
 * @param {string} props.label  Screen reader label, e.g. "Sale ends in".
 * @return {JSX.Element} The countdown.
 */
const Countdown = ( { endsAt, label } ) => {
	const [ now, setNow ] = useState( () => Date.now() );

	useEffect( () => {
		const timer = setInterval( () => setNow( Date.now() ), SECOND_IN_MS );
		return () => clearInterval( timer );
	}, [] );

	const [ days, hours, minutes, seconds ] = getTimeLeft( endsAt, now );
	const pad = ( value ) => String( value ).padStart( 2, '0' );

	return (
		<span
			role="timer"
			className={ `hidden shrink-0 font-bold tabular-nums tracking-wide sm:inline ${ HIGHLIGHT }` }
			aria-label={ sprintf(
				/* translators: 1: label such as "Sale ends in", 2: days, 3: hours, 4: minutes. */
				__( '%1$s %2$d days, %3$d hours, %4$d minutes', 'sureforms' ),
				label,
				days,
				hours,
				minutes
			) }
		>
			{ [ days, hours, minutes, seconds ].map( pad ).join( ' : ' ) }
		</span>
	);
};

// How many times the segments repeat inside one run. The marquee slides by
// one run, so a run narrower than the bar shows a gap before the loop restarts.
// ponytail: fixed count covers bars up to ~4x the copy width (about 2800px with
// today's copy); measure with a ResizeObserver if longer bars or shorter copy
// show a gap.
const REPEAT = 4;

/**
 * One full run of the segments, each followed by a star. Rendered twice so the
 * marquee loops without a gap. Only the first set of the first run is read by
 * assistive tech. With reduced motion the repeats are dropped and the single
 * set scrolls by hand instead.
 *
 * @since x.x.x
 * @param {Object}  props        Component props.
 * @param {Array}   props.items  Segments, each a list of `{ text, style }` parts.
 * @param {boolean} props.hidden Hide this run from assistive tech.
 * @return {JSX.Element} One run of segments.
 */
const Run = ( { items, hidden = false } ) => (
	<div
		className={ `flex shrink-0 ${ hidden ? 'motion-reduce:hidden' : '' }` }
		aria-hidden={ hidden || undefined }
	>
		{ Array.from( { length: REPEAT }, ( _, copy ) => (
			<span
				key={ copy }
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
 * Scrolling sale bar above the SureForms admin header.
 *
 * The server sends `promotion` only while the sale is live, to users who
 * manage SureForms without Pro and have not dismissed it, with the copy for
 * the running sale period. The end time is checked again every minute, so the
 * bar also goes away from a tab left open past the end of the sale.
 *
 * @since x.x.x
 * @param {Object} props           Component props.
 * @param {Object} props.promotion Promotion data from `srfm_admin.promotion`.
 * @return {JSX.Element|null} The sale bar, or nothing.
 */
const PromoBar = ( { promotion } ) => {
	const endsAt = ( promotion?.ends_at || 0 ) * 1000;
	const [ isDismissed, setIsDismissed ] = useState( false );
	const [ now, setNow ] = useState( () => Date.now() );

	useEffect( () => {
		if ( ! endsAt ) {
			return;
		}
		const timer = setInterval( () => setNow( Date.now() ), MINUTE_IN_MS );
		return () => clearInterval( timer );
	}, [ endsAt ] );

	if (
		! promotion?.items?.length ||
		! promotion?.cta?.url ||
		isDismissed ||
		now >= endsAt
	) {
		return null;
	}

	const handleDismiss = () => {
		setIsDismissed( true );
		apiFetch( {
			path: '/sureforms/v1/promotion/dismiss',
			method: 'POST',
			data: { id: promotion.id },
		} ).catch( () => {} );
	};

	return (
		<section
			className="group relative flex min-w-0 items-center gap-3 bg-background-brand px-4 py-2 text-sm text-white"
			aria-label={ __( 'Promotion', 'sureforms' ) }
		>
			<Countdown endsAt={ endsAt } label={ promotion.timer_label } />

			<div className="relative min-w-0 flex-1 overflow-hidden motion-reduce:overflow-x-auto [mask-image:linear-gradient(to_right,transparent,#000_24px,#000_calc(100%-24px),transparent)]">
				<div className="flex w-max animate-srfm-marquee group-hover:[animation-play-state:paused] group-focus-within:[animation-play-state:paused] motion-reduce:animate-none">
					<Run items={ promotion.items } />
					<Run items={ promotion.items } hidden />
				</div>
			</div>

			<a
				href={ promotion.cta.url }
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
				className="inline-flex shrink-0 cursor-pointer items-center rounded-md border border-solid border-white/30 bg-transparent p-1.5 text-white hover:bg-white/10"
				aria-label={ __( 'Dismiss promotion', 'sureforms' ) }
			>
				<X className="size-4" aria-hidden="true" />
			</button>
		</section>
	);
};

export default PromoBar;
