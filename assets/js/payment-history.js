/**
 * Payment History Frontend JavaScript.
 *
 * Handles overlay panels for subscription details, payment details,
 * and multi-step subscription cancellation flow.
 *
 * @package
 * @since x.x.x
 */

( function () {
	'use strict';

	const config = window.srfm_payment_history || {};
	const i18n = config.i18n || {};
	const subs = window.srfmDashboardSubs || [];
	const txs = window.srfmDashboardTxs || [];

	let cancelState = { name: '', next: '', opt: 'eop', paymentId: null };

	/**
	 * Initialize event listeners.
	 */
	function init() {
		const subRows = document.querySelectorAll( '.srfm-pd-sub-row' );
		subRows.forEach( function ( row ) {
			row.addEventListener( 'click', function () {
				openSubDetail( parseInt( row.dataset.index, 10 ) );
			} );
			row.addEventListener( 'keydown', function ( e ) {
				if ( 'Enter' === e.key || ' ' === e.key ) {
					e.preventDefault();
					openSubDetail( parseInt( row.dataset.index, 10 ) );
				}
			} );
		} );

		const payRows = document.querySelectorAll( '.srfm-pd-pay-row' );
		payRows.forEach( function ( row ) {
			row.addEventListener( 'click', function () {
				openTxDetail( parseInt( row.dataset.index, 10 ) );
			} );
			row.addEventListener( 'keydown', function ( e ) {
				if ( 'Enter' === e.key || ' ' === e.key ) {
					e.preventDefault();
					openTxDetail( parseInt( row.dataset.index, 10 ) );
				}
			} );
		} );

		document
			.querySelectorAll( '.srfm-pd-overlay' )
			.forEach( function ( overlay ) {
				overlay.addEventListener( 'click', function ( e ) {
					if ( e.target === overlay ) {
						closeOverlay( overlay.id );
					}
				} );
			} );

		document.addEventListener( 'keydown', function ( e ) {
			if ( 'Escape' === e.key ) {
				closeOverlay( 'srfm-pd-cancel-overlay' );
				closeOverlay( 'srfm-pd-sub-overlay' );
				closeOverlay( 'srfm-pd-tx-overlay' );
			}
		} );
	}

	/**
	 * Open subscription detail overlay.
	 *
	 * @param {number} index Subscription index.
	 */
	function openSubDetail( index ) {
		const s = subs[ index ];
		if ( ! s ) {
			return;
		}

		const isActive = s.canCancel;
		const statusMap = {
			active: 'Active',
			trialing: 'Trialing',
			canceled: 'Cancelled',
			past_due: 'Past Due',
			paused: 'Paused',
		};
		const statusLabel = statusMap[ s.status ] || s.status;
		const badgeClass = isActive
			? 'srfm-pd-badge--active'
			: 'srfm-pd-badge--cancelled';

		let html =
			'<div class="srfm-pd-panel-header">' +
			'<div>' +
			'<div class="srfm-pd-panel-id">' +
			esc( i18n.subscription || 'Subscription' ) +
			'</div>' +
			'<div class="srfm-pd-panel-title">' +
			esc( s.name ) +
			'</div>' +
			'<div class="srfm-pd-panel-subtitle">' +
			esc( s.form ) +
			'</div>' +
			'</div>' +
			'<div style="text-align:right;">' +
			'<button type="button" class="srfm-pd-panel-close" onclick="srfmPH.closeSub()">&times;</button>' +
			'<div style="margin-top:8px;"><span class="srfm-pd-badge ' +
			badgeClass +
			'"><span class="srfm-pd-badge-dot"></span>' +
			esc( statusLabel ) +
			'</span></div>' +
			'</div></div>';

		html += panelRow( i18n.amount || 'Amount', s.amount );

		if ( isActive && s.next ) {
			html += panelRow( i18n.next_payment || 'Next Payment', s.next );
		} else if ( ! isActive ) {
			if ( s.cancelledOn ) {
				html += panelRow(
					i18n.cancelled_on || 'Cancelled On',
					s.cancelledOn
				);
			}
			if ( s.accessUntil ) {
				html += panelRow(
					i18n.access_until || 'Access Until',
					s.accessUntil
				);
			}
		}

		html += panelRow( i18n.gateway || 'Gateway', s.gateway );
		html += panelRow( i18n.started || 'Started', s.started );

		html += '<div class="srfm-pd-panel-footer">';
		html +=
			'<button type="button" class="srfm-pd-btn" onclick="srfmPH.closeSub()">' +
			svgBack() +
			' ' +
			esc( i18n.back || 'Back' ) +
			'</button>';

		if ( isActive ) {
			html +=
				'<button type="button" class="srfm-pd-btn srfm-pd-btn--danger" onclick="srfmPH.closeSub();srfmPH.openCancel(\'' +
				escAttr( s.name ) +
				"'," +
				s.paymentId +
				')">' +
				svgCancel() +
				' ' +
				esc( i18n.cancel_subscription || 'Cancel Subscription' ) +
				'</button>';
		}

		html += '</div>';

		document.getElementById( 'srfm-pd-sub-panel' ).innerHTML = html;
		openOverlay( 'srfm-pd-sub-overlay' );
	}

	/**
	 * Open transaction detail overlay.
	 *
	 * @param {number} index Transaction index.
	 */
	function openTxDetail( index ) {
		const t = txs[ index ];
		if ( ! t ) {
			return;
		}

		const statusMap = {
			succeeded: 'Paid',
			pending: 'Pending',
			failed: 'Failed',
			canceled: 'Cancelled',
			refunded: 'Refunded',
			partially_refunded: 'Partially Refunded',
			processing: 'Processing',
			active: 'Active',
		};
		const statusLabel = statusMap[ t.status ] || t.status;
		const badgeClass =
			'refunded' === t.status || 'partially_refunded' === t.status
				? 'srfm-pd-badge--refunded'
				: 'succeeded' === t.status || 'active' === t.status
					? 'srfm-pd-badge--paid'
					: 'srfm-pd-badge--pending';

		let html =
			'<div class="srfm-pd-panel-header">' +
			'<div>' +
			'<div class="srfm-pd-panel-id">' +
			esc( t.id ) +
			'</div>' +
			'<div class="srfm-pd-panel-title">' +
			esc( t.amount ) +
			'</div>' +
			'<div class="srfm-pd-panel-subtitle">' +
			esc( t.date ) +
			'</div>' +
			'</div>' +
			'<div style="text-align:right;">' +
			'<button type="button" class="srfm-pd-panel-close" onclick="srfmPH.closeTx()">&times;</button>' +
			'<div style="margin-top:8px;"><span class="srfm-pd-badge ' +
			badgeClass +
			'"><span class="srfm-pd-badge-dot"></span>' +
			esc( statusLabel ) +
			'</span></div>' +
			'</div></div>';

		html += panelRow( i18n.form || 'Form', t.form );
		html += panelRow(
			i18n.type || 'Type',
			'subscription' === t.type
				? i18n.subscription_payment || 'Subscription Payment'
				: i18n.one_time_payment || 'One-time Payment'
		);
		html += panelRow( i18n.gateway || 'Gateway', t.gateway );
		html += panelRow(
			i18n.transaction_id || 'Transaction ID',
			'<span style="font-family:monospace;font-size:11px;">' +
				esc( t.txn ) +
				'</span>',
			true
		);

		if ( 'subscription' === t.type && t.sub ) {
			html +=
				'<div class="srfm-pd-sub-info-box">' +
				'<div class="srfm-pd-sub-info-title">' +
				esc( i18n.parent_subscription || 'Parent Subscription' ) +
				'</div>' +
				'<div class="srfm-pd-sub-info-grid">' +
				'<div><div class="srfm-pd-sub-info-label">' +
				esc( i18n.plan || 'Plan' ) +
				'</div><div class="srfm-pd-sub-info-value">' +
				esc( t.sub.name ) +
				'</div></div>' +
				'<div><div class="srfm-pd-sub-info-label">' +
				esc( i18n.amount || 'Amount' ) +
				'</div><div class="srfm-pd-sub-info-value">' +
				esc( t.sub.interval ) +
				'</div></div>' +
				( t.sub.next
					? '<div><div class="srfm-pd-sub-info-label">' +
					  esc( i18n.next_payment || 'Next Payment' ) +
					  '</div><div class="srfm-pd-sub-info-value">' +
					  esc( t.sub.next ) +
					  '</div></div>'
					: '' ) +
				'<div><div class="srfm-pd-sub-info-label">' +
				esc( i18n.status || 'Status' ) +
				'</div><div class="srfm-pd-sub-info-value">' +
				esc( t.sub.status ) +
				'</div></div>' +
				'</div></div>';
		} else {
			html +=
				'<div class="srfm-pd-single-info-box">' +
				esc(
					i18n.one_time_note ||
						'One-time payment. No recurring subscription associated.'
				) +
				'</div>';
		}

		html += '<div class="srfm-pd-panel-footer">';
		html +=
			'<button type="button" class="srfm-pd-btn" onclick="srfmPH.closeTx()">' +
			svgBack() +
			' ' +
			esc( i18n.back || 'Back' ) +
			'</button>';
		html += '</div>';

		document.getElementById( 'srfm-pd-tx-panel' ).innerHTML = html;
		openOverlay( 'srfm-pd-tx-overlay' );
	}

	// =========================================================================
	// Cancel Flow
	// =========================================================================

	function openCancel( name, paymentId ) {
		cancelState = { name, paymentId };
		showCancelStep( 1 );
		openOverlay( 'srfm-pd-cancel-overlay' );
	}

	function showCancelStep( step ) {
		const panel = document.getElementById( 'srfm-pd-cancel-panel' );
		let html = '';

		if ( 1 === step ) {
			const msg = (
				i18n.cancel_confirm_now ||
				'Your "%s" will be cancelled immediately. You will lose access right away.'
			).replace( '%s', cancelState.name );

			html =
				'<div class="srfm-pd-cancel-body" style="padding-top:32px;">' +
				'<div class="srfm-pd-cancel-icon" style="background:#fef3c7;">' +
				svgWarning() +
				'</div>' +
				'<h4>' +
				esc( i18n.are_you_sure || 'Are you sure?' ) +
				'</h4>' +
				'<p>' +
				esc( msg ) +
				'</p></div>';

			html +=
				'<div class="srfm-pd-panel-footer">' +
				'<button type="button" class="srfm-pd-btn" onclick="srfmPH.closeCancel()">' +
				esc( i18n.keep_subscription || 'Keep Subscription' ) +
				'</button>' +
				'<button type="button" class="srfm-pd-btn srfm-pd-btn--danger-fill" id="srfm-pd-confirm-cancel" onclick="srfmPH.confirmCancel()">' +
				esc( i18n.yes_cancel || 'Yes, Cancel' ) +
				'</button>' +
				'</div>';
		} else if ( 2 === step ) {
			html =
				'<div class="srfm-pd-cancel-body" style="padding:40px 24px;">' +
				'<div class="srfm-pd-cancel-icon" style="background:#dcfce7;">' +
				svgCheck() +
				'</div>' +
				'<h4>' +
				esc( i18n.subscription_cancelled || 'Subscription Cancelled' ) +
				'</h4>' +
				'<p>' +
				esc(
					i18n.cancel_success ||
						'The subscription has been cancelled successfully.'
				) +
				'</p></div>';

			html +=
				'<div class="srfm-pd-panel-footer srfm-pd-panel-footer--center">' +
				'<button type="button" class="srfm-pd-btn srfm-pd-btn--primary" onclick="srfmPH.finishCancel()">' +
				esc( i18n.done || 'Done' ) +
				'</button>' +
				'</div>';
		}

		panel.innerHTML = html;
	}

	function confirmCancel() {
		const btn = document.getElementById( 'srfm-pd-confirm-cancel' );
		if ( btn ) {
			btn.disabled = true;
			btn.textContent = i18n.processing || 'Processing...';
		}

		const formData = new FormData();
		formData.append( 'action', 'srfm_frontend_cancel_subscription' );
		formData.append( 'nonce', config.nonce );
		formData.append( 'payment_id', cancelState.paymentId );

		fetch( config.ajax_url, {
			method: 'POST',
			body: formData,
			credentials: 'same-origin',
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( data ) {
				if ( data.success ) {
					showCancelStep( 2 );
				} else {
					if ( btn ) {
						btn.disabled = false;
						btn.textContent = i18n.yes_cancel || 'Yes, Cancel';
					}
					window.alert(
						data.data || i18n.error || 'Something went wrong.'
					);
				}
			} )
			.catch( function () {
				if ( btn ) {
					btn.disabled = false;
					btn.textContent = i18n.yes_cancel || 'Yes, Cancel';
				}
				window.alert(
					i18n.error || 'Something went wrong. Please try again.'
				);
			} );
	}

	function finishCancel() {
		closeOverlay( 'srfm-pd-cancel-overlay' );
		window.location.reload();
	}

	// =========================================================================
	// Overlay Helpers
	// =========================================================================

	function openOverlay( id ) {
		const el = document.getElementById( id );
		if ( el ) {
			el.classList.add( 'srfm-pd-overlay--open' );
			document.body.style.overflow = 'hidden';
		}
	}

	function closeOverlay( id ) {
		const el = document.getElementById( id );
		if ( el ) {
			el.classList.remove( 'srfm-pd-overlay--open' );
		}
		if ( ! document.querySelector( '.srfm-pd-overlay--open' ) ) {
			document.body.style.overflow = '';
		}
	}

	// =========================================================================
	// HTML Helpers
	// =========================================================================

	function panelRow( label, value, isHtml ) {
		return (
			'<div class="srfm-pd-panel-row"><span class="srfm-pd-panel-label">' +
			esc( label ) +
			'</span><span class="srfm-pd-panel-value">' +
			( isHtml ? value : esc( value ) ) +
			'</span></div>'
		);
	}

	function svgBack() {
		return '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>';
	}

	function svgCancel() {
		return '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
	}

	function svgWarning() {
		return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#d97706" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>';
	}

	function svgCheck() {
		return '<svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
	}

	function esc( str ) {
		if ( ! str ) {
			return '';
		}
		const div = document.createElement( 'div' );
		div.appendChild( document.createTextNode( str ) );
		return div.innerHTML;
	}

	function escAttr( str ) {
		return esc( str ).replace( /'/g, '&#39;' ).replace( /"/g, '&quot;' );
	}

	// =========================================================================
	// Public API
	// =========================================================================

	window.srfmPH = {
		closeSub() {
			closeOverlay( 'srfm-pd-sub-overlay' );
		},
		closeTx() {
			closeOverlay( 'srfm-pd-tx-overlay' );
		},
		closeCancel() {
			closeOverlay( 'srfm-pd-cancel-overlay' );
		},
		openCancel,
		confirmCancel,
		finishCancel,
	};

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
