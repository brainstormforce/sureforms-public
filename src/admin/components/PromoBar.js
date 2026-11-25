import { __ } from '@wordpress/i18n';
import { useEffect, useState } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { ArrowRight, X } from 'lucide-react';

const PART_CLASSES = {
	plain: '',
	bold: 'font-bold',
	accent: 'font-bold text-brand-200',
	strike: 'line-through opacity-70',
};

const MINUTE_IN_MS = 60 * 1000;

/**
 * One full run of the segments, each followed by a star. Rendered twice so the
 * marquee loops without a gap. With reduced motion the copy is dropped and the
 * single run scrolls by hand instead.
 *
 * @since x.x.x
 * @param {Object}  props        Component props.
 * @param {Array}   props.items  Segments, each a list of `{ text, style }` parts.
 * @param {boolean} props.hidden Hide this copy from assistive tech.
 * @return {JSX.Element} One run of segments.
 */
const Run = ( { items, hidden = false } ) => (
	<div
		className={ `flex shrink-0 items-center gap-6 pr-6 ${
			hidden ? 'motion-reduce:hidden' : ''
		}` }
		aria-hidden={ hidden || undefined }
	>
		{ items.map( ( parts, index ) => (
			<span key={ index } className="inline-flex items-center gap-6">
				<span className="inline-flex items-center gap-1.5 whitespace-nowrap">
					{ parts.map( ( part, partIndex ) => (
						<span
							key={ partIndex }
							className={ PART_CLASSES[ part.style ] || '' }
						>
							{ part.text }
						</span>
					) ) }
				</span>
				<span className="text-brand-200" aria-hidden="true">
					✦
				</span>
			</span>
		) ) }
	</div>
);

/**
 * Scrolling sale bar above the SureForms admin header.
 *
 * The server sends `promotion` only while the sale is live, to users who
 * manage SureForms without Pro and have not dismissed it. The end time is
 * checked again every minute, so the bar also goes away from a tab left open
 * past the end of the sale.
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
			{ promotion.badge && (
				<span className="hidden shrink-0 items-center gap-1.5 rounded-full border border-solid border-white/70 px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider text-white sm:inline-flex">
					<span className="size-1.5 rounded-full bg-white" />
					{ promotion.badge }
				</span>
			) }

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
				className="inline-flex shrink-0 items-center gap-1 rounded-md bg-white px-3 py-1.5 text-sm font-bold text-brand-primary-600 no-underline hover:bg-brand-background-50 hover:text-brand-primary-600 focus:text-brand-primary-600"
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
