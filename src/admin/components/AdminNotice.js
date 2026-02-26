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
import { TriangleAlert, Info, CheckCircle, CircleAlert } from 'lucide-react';

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
 * @param {Object} props         - Component props
 * @param {string} props.variant - Notice type (error, warning, info, success)
 * @param {string} props.message - Notice message text
 * @param {string} props.title   - Optional notice title
 * @param {Array}  props.actions - Optional array of action buttons
 * @return {JSX.Element} - The rendered notice
 */
const SingleNotice = ( { variant = 'info', message, title, actions = [] } ) => {
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
								onClick={ () => {
									if ( action.url ) {
										if ( action.target === '_blank' ) {
											window.open(
												action.url,
												'_blank',
												'noopener,noreferrer'
											);
										} else {
											window.location.href = action.url;
										}
									}
									if ( action.onClick ) {
										action.onClick();
									}
								} }
								variant="link"
								size="xs"
								className="inline-flex text-link-primary p-0 [&>span]:p-0"
							>
								{ action.label }
							</Button>
						</span>
					) ) }
			</span>
		</span>
	);

	return (
		<Alert
			content={ content }
			icon={ getNoticeIcon( variant ) }
			title={ title || null }
			variant={ variant }
			className={ getNoticeClassName( variant ) }
		/>
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
	// Get notices from localized data
	const notices = window.srfm_admin?.notices || [];

	if ( ! notices || notices.length === 0 ) {
		return null;
	}

	// Filter notices based on current page
	const filteredNotices = notices.filter( ( notice ) => {
		if ( ! notice.pages || notice.pages.includes( 'all' ) ) {
			return true;
		}
		return notice.pages.includes( currentPage );
	} );

	if ( filteredNotices.length === 0 ) {
		return null;
	}

	return (
		<div className="flex flex-col gap-4">
			{ filteredNotices.map( ( notice ) => (
				<SingleNotice
					key={ notice.id }
					variant={ notice.variant || 'info' }
					message={ notice.message }
					title={ notice.title }
					actions={ notice.actions || [] }
				/>
			) ) }
		</div>
	);
};

export default AdminNotice;
