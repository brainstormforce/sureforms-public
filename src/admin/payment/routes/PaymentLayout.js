import { __, sprintf } from '@wordpress/i18n';
import { Link, Outlet, useParams, useSearchParams } from 'react-router-dom';
import Header from '@Admin/components/Header';
import { cn } from '@Utils/Helpers';

/**
 * PageHeader Component
 * Displays breadcrumb navigation for payment/subscription details
 * @param {Object} props - Component props
 */
const PageHeader = ( props ) => {
	const { className } = props;
	const { id } = useParams( { strict: false } );
	const [ searchParams ] = useSearchParams();
	const type = searchParams.get( 'type' ) || 'payment';

	let breadcrumbTitle = '';
	if ( type === 'subscription' ) {
		breadcrumbTitle = sprintf(
			// translators: %s: Subscription ID
			__( 'Subscription #%s', 'sureforms' ),
			id || ''
		);
	} else {
		// translators: %s: Payment ID
		breadcrumbTitle = sprintf( __( 'Payment #%s', 'sureforms' ), id || '' );
	}

	return (
		<div className={ cn( 'z-50 relative', className ) }>
			<Header
				breadCrumb={ [
					{ type: 'separator' },
					{
						text: __( 'Payments', 'sureforms' ),
						linkProps: { as: Link, to: '/' },
					},
					{ type: 'separator' },
					{ text: breadcrumbTitle, type: 'page' },
				] }
			/>
		</div>
	);
};

/**
 * PaymentLayout Component
 * Layout wrapper for payment detail pages (single payment & subscription)
 */
const PaymentLayout = () => {
	return (
		<>
			<PageHeader />
			<Outlet />
		</>
	);
};

export default PaymentLayout;
