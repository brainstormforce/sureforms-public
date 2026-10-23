import { useEffect } from '@wordpress/element';

const FOCUSABLE = [
	'a[href]',
	'button:not([disabled])',
	'input:not([disabled]):not([type="hidden"])',
	'select:not([disabled])',
	'textarea:not([disabled])',
	'iframe',
	'[tabindex]',
].join( ',' );

// Open traps, innermost last. Only the innermost one acts, so a preview opened
// over the library keeps focus without the library's trap fighting it.
const openTraps = [];

/**
 * Elements inside `node` reachable with Tab, in order.
 *
 * @param {HTMLElement} node Container.
 * @return {HTMLElement[]} Focusable elements.
 */
const getTabbable = ( node ) =>
	Array.from( node.querySelectorAll( FOCUSABLE ) ).filter(
		( element ) =>
			element.tabIndex >= 0 &&
			element.getClientRects().length > 0 &&
			! element.closest( '[inert]' )
	);

/**
 * Keeps keyboard focus inside a dialog while it is open: Tab and Shift+Tab
 * wrap around, and focus that escapes (e.g. tabbing out of a preview iframe)
 * is brought back. Focus moving into another dialog, such as a confirmation
 * opened on top, is left alone.
 *
 * @since 2.13.0
 * @param {HTMLElement|null} node   Dialog element.
 * @param {boolean}          active Whether the trap is on.
 */
const useFocusTrap = ( node, active = true ) => {
	useEffect( () => {
		if ( ! node || ! active ) {
			return;
		}

		const trap = { node };
		openTraps.push( trap );
		const isInnermost = () => openTraps[ openTraps.length - 1 ] === trap;
		const { ownerDocument } = node;

		const inOtherDialog = ( element ) => {
			const dialog = element?.closest?.(
				'[role="dialog"], [role="alertdialog"], [aria-modal="true"]'
			);
			return Boolean( dialog && ! node.contains( dialog ) );
		};

		const onKeyDown = ( event ) => {
			if ( 'Tab' !== event.key || ! isInnermost() ) {
				return;
			}
			const current = ownerDocument.activeElement;
			if (
				current &&
				! node.contains( current ) &&
				inOtherDialog( current )
			) {
				return;
			}
			const tabbable = getTabbable( node );
			if ( ! tabbable.length ) {
				event.preventDefault();
				node.focus();
				return;
			}
			const first = tabbable[ 0 ];
			const last = tabbable[ tabbable.length - 1 ];
			const outside = ! node.contains( current ) || current === node;
			if ( event.shiftKey && ( outside || current === first ) ) {
				event.preventDefault();
				last.focus();
			} else if ( ! event.shiftKey && ( outside || current === last ) ) {
				event.preventDefault();
				first.focus();
			}
		};

		const onFocusIn = ( event ) => {
			const { target, relatedTarget } = event;
			if (
				! isInnermost() ||
				node.contains( target ) ||
				inOtherDialog( target )
			) {
				return;
			}
			// Focus that came from somewhere else outside (a toast, a
			// confirmation that just closed) is not ours to move.
			if ( relatedTarget && ! node.contains( relatedTarget ) ) {
				return;
			}
			const tabbable = getTabbable( node );
			const next =
				relatedTarget && relatedTarget === tabbable[ 0 ]
					? tabbable[ tabbable.length - 1 ]
					: tabbable[ 0 ];
			( next || node ).focus();
		};

		ownerDocument.addEventListener( 'keydown', onKeyDown );
		ownerDocument.addEventListener( 'focusin', onFocusIn );
		return () => {
			ownerDocument.removeEventListener( 'keydown', onKeyDown );
			ownerDocument.removeEventListener( 'focusin', onFocusIn );
			openTraps.splice( openTraps.indexOf( trap ), 1 );
		};
	}, [ node, active ] );
};

export default useFocusTrap;
