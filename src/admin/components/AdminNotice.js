/**
 * AdminNotice component for displaying admin notices in React pages.
 *
 * This component bridges PHP admin notices into React admin pages.
 * PHP notices are localized via window.srfm_admin.notices and rendered
 * using the same Alert design pattern as existing notices (e.g., payment/utils.js).
 *
 * @since 2.5.0
 */

import { Alert, Button } from '@bsf/force-ui';
import { TriangleAlert, Info, CheckCircle, CircleAlert, X } from 'lucide-react';
import { useRef, useState } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';

/**
 * Handlers for notice actions that have to call the server.
 *
 * PHP supplies an opaque identifier in `action`, never a URL or an endpoint — the
 * server does not get to name an address for the browser to POST to. The endpoint,
 * method and error handling live here, in reviewable source.
 *
 * A `url` on the same action stays as the no-JS fallback: if no handler matches the
 * identifier, the existing URL behaviour runs instead.
 */
const NOTICE_ACTION_HANDLERS = {
	'repair-entries-table': () =>
		apiFetch( {
			path: '/sureforms/v1/database/repair-entries-table',
			method: 'POST',
		} ),
};

/**
 * Tell the server which button was pressed.
 *
 * The same srfm_notice_response call the dashboard and the classic notices make,
 * so the click is counted and Contact Support retires the fault it reports. The
 * button name comes from PHP, which also holds the allowlist it is checked
 * against. keepalive, because the action may navigate this tab straight after.
 *
 * @param {string} noticeId - Notice id, as registered.
 * @param {string} button   - Button name from the action's `response`.
 */
const recordResponse = ( noticeId, button ) => {
	const ajaxUrl = window.srfm_admin?.ajax_url;
	const nonce = window.srfm_admin?.notice_response_nonce;

	if ( ! button || ! ajaxUrl || ! nonce ) {
		return;
	}

	fetch( ajaxUrl, {
		method: 'POST',
		credentials: 'same-origin',
		keepalive: true,
		body: new URLSearchParams( {
			action: 'srfm_notice_response',
			nonce,
			notice_id: noticeId,
			button,
		} ),
	} ).catch( () => {} );
};

/**
 * Get the appropriate icon for each notice variant.
 *
 * @param {string} variant - The notice variant (error, warning, info, success)
 * @return {JSX.Element} - The icon component
 */
const getNoticeIcon = ( variant ) => {
	switch ( variant ) {
		case 'error':
			return <CircleAlert className="!size-6" />;
		case 'warning':
			return <TriangleAlert className="!size-6" />;
		case 'success':
			return <CheckCircle className="!size-6" />;
		case 'info':
		default:
			return <Info className="!size-6" />;
	}
};

/**
 * Get the appropriate className for each notice variant.
 * Matches the design pattern from payment/components/utils.js
 *
 * @param {string} variant - The notice variant (error, warning, info, success)
 * @return {string} - The className string
 */
const getNoticeClassName = ( variant ) => {
	switch ( variant ) {
		case 'error':
			return 'shadow-none bg-alert-background-danger';
		case 'warning':
			return 'shadow-none bg-alert-background-warning';
		case 'success':
			return 'shadow-none bg-alert-background-success';
		case 'info':
		default:
			return 'shadow-none bg-alert-background-info';
	}
};

/**
 * SingleNotice component - renders an individual notice.
 *
 * @param {Object}   props             - Component props
 * @param {string}   props.id          - Notice id, sent with any action response
 * @param {string}   props.variant     - Notice type (error, warning, info, success)
 * @param {string}   props.message     - Notice message text
 * @param {string}   props.title       - Optional notice title
 * @param {Array}    props.actions     - Optional array of action buttons
 * @param {boolean}  props.dismissible - Whether the notice can be dismissed
 * @param {Function} props.onDismiss   - Callback when notice is dismissed
 * @return {JSX.Element} - The rendered notice
 */
const SingleNotice = ( {
	id,
	variant = 'info',
	message,
	title,
	actions = [],
	dismissible = true,
	onDismiss,
} ) => {
	// Only ever set by a server-backed action; a plain link action never touches it.
	const [ status, setStatus ] = useState( { busy: false, error: '' } );
	// Synchronous guard: `disabled={status.busy}` only takes effect after the next
	// render commits, so two clicks dispatched before that commit would both run.
	// The ref flips immediately, so the second click is dropped.
	const inFlight = useRef( false );

	const runAction = async ( action ) => {
		const handler = NOTICE_ACTION_HANDLERS[ action.action ];

		// No handler for this identifier — fall back to the URL, which is why PHP
		// ships both.
		if ( ! handler ) {
			recordResponse( id, action.response );

			if ( action.url ) {
				if ( action.target === '_blank' ) {
					window.open( action.url, '_blank', 'noopener,noreferrer' );
				} else {
					window.location.href = action.url;
				}
			}
			return;
		}

		if ( inFlight.current ) {
			return;
		}
		inFlight.current = true;

		setStatus( { busy: true, error: '' } );

		try {
			await handler();
			// Reload rather than mutating local state: the notice is rendered from a
			// PHP-localized array, so the server is the only thing that can say it is
			// resolved.
			//
			// The flag has to be added by hand. PHP registers the confirmation off
			// `srfm_db_repair`, and a bare reload would drop the warning with nothing
			// in its place — the same silent outcome as a failure. The admin-post
			// fallback redirects with this exact arg, so both paths land identically.
			const next = new URL( window.location.href );
			next.searchParams.set( 'srfm_db_repair', 'done' );
			window.location.assign( next.toString() );
		} catch ( error ) {
			inFlight.current = false;
			setStatus( {
				busy: false,
				error:
					error?.message ||
					__( 'Something went wrong. Please try again.', 'sureforms' ),
			} );
		}
	};

	// Build content with message and inline action buttons (matching WebhookConfigure pattern)
	const content = (
		<span className="flex flex-col gap-3.5">
			<span>
				{ message }
				{ actions.length > 0 &&
					actions.map( ( action, index ) => (
						<span key={ index }>
							{ ' ' }
							<Button
								onClick={ () => runAction( action ) }
								disabled={ status.busy }
								aria-busy={ status.busy }
								variant="link"
								size="xs"
								className="inline-flex text-link-primary p-0 [&>span]:p-0"
							>
								{ status.busy && action.action
									? __( 'Working…', 'sureforms' )
									: action.label }
							</Button>
						</span>
					) ) }
			</span>
			<span role="status" aria-live="polite" className="sr-only">
				{ status.busy ? __( 'Working\u2026', 'sureforms' ) : '' }
			</span>
			{ status.error && (
				<span role="alert" className="text-text-error">
					{ status.error }
				</span>
			) }
		</span>
	);

	return (
		<div className="relative">
			<Alert
				content={ content }
				icon={ getNoticeIcon( variant ) }
				title={ title || null }
				variant={ variant }
				className={ getNoticeClassName( variant ) }
			/>
			{ /* Not Alert's own onClose: its close button is labelled with an
			     untranslated "Close alert". */ }
			{ dismissible && onDismiss && (
				<Button
					variant="ghost"
					size="xs"
					icon={ <X className="size-4" /> }
					onClick={ onDismiss }
					className="absolute top-2 end-2 p-1 text-icon-secondary hover:text-icon-primary"
					aria-label={ __( 'Dismiss notice', 'sureforms' ) }
				/>
			) }
		</div>
	);
};

/**
 * AdminNotice component - renders all admin notices.
 *
 * This component reads notices from window.srfm_admin.notices and renders them
 * at the top of React admin pages. It maintains the same design as existing
 * notices (e.g., WebhookConfigure in payment/components/utils.js).
 *
 * Notice Structure (from PHP):
 * {
 *   id: 'unique-notice-id',
 *   variant: 'error' | 'warning' | 'info' | 'success',
 *   message: 'The notice message (can contain HTML)',
 *   title: 'Optional notice title',
 *   actions: [
 *     {
 *       label: 'Button text',
 *       url: 'https://example.com',
 *       target: '_blank' | '_self',
 *       response: 'contact_support', // Optional srfm_notice_response button name
 *       variant: 'primary' | 'secondary' | 'link',
 *       size: 'sm' | 'md' | 'lg',
 *       className: 'custom-classes',
 *       onClick: function() {} // Optional custom handler
 *     }
 *   ],
 *   dismissible: true/false,
 *   pages: ['all'] or ['sureforms_entries', 'sureforms_forms'] // Which pages to show on
 * }
 *
 * @param {Object} props             - Component props
 * @param {string} props.currentPage - Current page slug to filter notices
 * @return {JSX.Element|null} - The rendered notices or null
 */
const AdminNotice = ( { currentPage = 'all' } ) => {
	const [ dismissedIds, setDismissedIds ] = useState( [] );

	// Get notices from localized data
	const notices = window.srfm_admin?.notices || [];

	if ( ! notices || notices.length === 0 ) {
		return null;
	}

	// Filter notices based on current page and dismissed state
	const filteredNotices = notices.filter( ( notice ) => {
		if ( dismissedIds.includes( notice.id ) ) {
			return false;
		}
		if ( ! notice.pages || notice.pages.includes( 'all' ) ) {
			return true;
		}
		return notice.pages.includes( currentPage );
	} );

	if ( filteredNotices.length === 0 ) {
		return null;
	}

	const handleDismiss = ( id ) => {
		setDismissedIds( ( prev ) => [ ...prev, id ] );
	};

	return (
		<div className="flex flex-col gap-4">
			{ filteredNotices.map( ( notice ) => (
				<SingleNotice
					key={ notice.id }
					id={ notice.id }
					variant={ notice.variant || 'info' }
					message={ notice.message }
					title={ notice.title }
					actions={ notice.actions || [] }
					dismissible={ notice.dismissible !== false }
					onDismiss={ () => handleDismiss( notice.id ) }
				/>
			) ) }
		</div>
	);
};

export default AdminNotice;
