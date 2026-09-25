import { useRef, useState } from '@wordpress/element';
import { __, sprintf } from '@wordpress/i18n';
import { ChevronsLeftRight } from 'lucide-react';

/**
 * Before/after comparison. `before` fills the box; `after` is revealed from
 * the right edge up to a divider that follows the pointer -- move across the
 * card and the two versions wipe between each other, with nothing to click.
 *
 * A transparent native range input sits over the box so the comparison is
 * reachable by keyboard. It takes no pointer events, so mouse and touch are
 * driven only by the move handler below and the two cannot compute slightly
 * different positions and fight over the divider.
 *
 * Pointer rather than mouse events: touch has no hover, but a finger dragged
 * across the card still emits pointermove, so the wipe works there too.
 *
 * @param {Object} props
 * @param {Node}   props.before  Full-size "before" card.
 * @param {Node}   props.after   Full-size "after" card (same dimensions).
 * @param {number} props.width   Box width in px.
 * @param {number} props.height  Box height in px.
 * @param {number} props.initial Initial divider position, 0–100 (% from the left).
 * @param {string} props.label   Accessible name for the drag handle.
 * @return {JSX.Element} The slider.
 */
const BeforeAfterSlider = ( {
	before,
	after,
	width = 283,
	height = 246,
	initial = 50,
	label = __( 'Compare free and premium', 'sureforms' ),
} ) => {
	const [ position, setPosition ] = useState( initial );
	const trackRef = useRef( null );

	// Measured per event rather than cached: the card is inside a tab panel that
	// can be scrolled or re-laid-out between renders, and a stale rect silently
	// offsets the divider from the cursor.
	const followPointer = ( event ) => {
		const rect = trackRef.current?.getBoundingClientRect();

		if ( ! rect?.width ) {
			return;
		}

		const percent = ( ( event.clientX - rect.left ) / rect.width ) * 100;

		setPosition( Math.min( 100, Math.max( 0, Math.round( percent ) ) ) );
	};

	// The ring is keyed to :focus-visible, not :focus-within. The range input
	// covers the whole card, so focus-within also fires on click and drew a
	// border around the card every time someone used the slider with a mouse.
	// Keyboard focus still needs the ring: the input itself is invisible, so
	// without it there is nothing on screen to show where focus is.
	return (
		<div style={ { width } }>
			<div
				ref={ trackRef }
				onPointerMove={ followPointer }
				className="relative cursor-ew-resize select-none overflow-hidden rounded-[13px] has-[:focus-visible]:outline has-[:focus-visible]:outline-2 has-[:focus-visible]:outline-offset-2 has-[:focus-visible]:outline-button-primary"
				style={ { width, height } }
			>
				<div className="absolute inset-0">{ before }</div>
				<div
					className="absolute inset-y-0 right-0 overflow-hidden"
					style={ { width: `${ 100 - position }%` } }
				>
					<div className="absolute right-0 top-0" style={ { width } }>
						{ after }
					</div>
				</div>
				<div
					className="pointer-events-none absolute inset-y-0 w-0.5 -translate-x-1/2 bg-button-primary"
					style={ { left: `${ position }%` } }
				>
					<span className="absolute left-1/2 top-1/2 flex size-6 -translate-x-1/2 -translate-y-1/2 items-center justify-center rounded-full bg-button-primary text-white shadow-md">
						<ChevronsLeftRight className="size-3.5" />
					</span>
				</div>
				<input
					type="range"
					min={ 0 }
					max={ 100 }
					value={ position }
					onChange={ ( event ) =>
						setPosition( Number( event.target.value ) )
					}
					aria-label={ label }
					aria-valuetext={ sprintf(
						/* translators: %s: percentage, already formatted, e.g. "20%". */
						__( '%s of the free version shown', 'sureforms' ),
						`${ position }%`
					) }
					className="pointer-events-none absolute inset-0 m-0 h-full w-full appearance-none bg-transparent opacity-0"
				/>
			</div>
		</div>
	);
};

export default BeforeAfterSlider;
