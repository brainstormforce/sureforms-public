import { useState, useEffect } from '@wordpress/element';
import { Text } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';

const AUTO_ADVANCE_MS = 2000;

/**
 * Illustration carousel for the welcome step: one slide at a time, dot
 * pagination, the active slide's title as a caption. Advances on its own
 * every few seconds, pauses while hovered, and hides the dots when there is
 * a single slide.
 *
 * @param {Object} props
 * @param {Array}  props.slides Array of { title, illustration } where
 *                              illustration is a React node.
 * @return {JSX.Element|null} The carousel, or null without slides.
 */
const FeatureCarousel = ( { slides = [] } ) => {
	const [ active, setActive ] = useState( 0 );
	const [ paused, setPaused ] = useState( false );
	const count = slides.length;

	useEffect( () => {
		// Auto-advancing content needs a pause mechanism (WCAG 2.2.2); hover
		// gives one to pointer users, and reduced-motion opts everyone else out.
		const prefersReducedMotion = window.matchMedia?.(
			'(prefers-reduced-motion: reduce)'
		)?.matches;

		if ( paused || prefersReducedMotion || count < 2 ) {
			return;
		}
		const timer = setInterval(
			() => setActive( ( index ) => ( index + 1 ) % count ),
			AUTO_ADVANCE_MS
		);
		return () => clearInterval( timer );
	}, [ paused, count ] );

	const slide = slides[ active ];

	if ( ! slide ) {
		return null;
	}

	return (
		<div
			className="space-y-3"
			onMouseEnter={ () => setPaused( true ) }
			onMouseLeave={ () => setPaused( false ) }
		>
			{ /* All slides share one grid cell and cross-fade via opacity. */ }
			<div className="grid justify-items-center">
				{ slides.map( ( item, index ) => (
					<div
						key={ index }
						aria-hidden={ index !== active }
						className={ cn(
							'col-start-1 row-start-1 transition-opacity duration-500 ease-in-out',
							index === active
								? 'opacity-100'
								: 'pointer-events-none opacity-0'
						) }
					>
						{ item.illustration }
					</div>
				) ) }
			</div>
			{ count > 1 && (
				// Pagination dots, not tabs: there is no tabpanel for them to
				// own, so aria-current is the honest role here.
				<div className="flex items-center justify-center gap-1">
					{ slides.map( ( item, index ) => (
						<button
							key={ index }
							type="button"
							aria-current={ index === active }
							aria-label={ item.title }
							onClick={ () => setActive( index ) }
							className={ cn(
								'h-1.5 cursor-pointer rounded-full border-0 p-0 transition-all focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-button-primary',
								index === active
									? 'w-4 bg-button-primary'
									: 'w-1.5 bg-border-subtle'
							) }
						/>
					) ) }
				</div>
			) }
			{ /* Keyed so the caption re-mounts and fades in with each slide. */ }
			<Text
				key={ active }
				as="p"
				size={ 12 }
				weight={ 500 }
				className="srfm-onboarding-fade-in text-center text-button-primary"
			>
				{ slide.title }
			</Text>
		</div>
	);
};

export default FeatureCarousel;
