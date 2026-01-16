import { useSearchParams } from 'react-router-dom';
import ViewPayment from '../pages/viewPayment';
import ViewSubscription from '../pages/viewSubscription';

/**
 * PaymentDetailPage Component
 * Routes to either single payment or subscription view based on URL params
 */
const PaymentDetailPage = () => {
	const [ searchParams ] = useSearchParams();
	const type = searchParams.get( 'type' ) || 'payment';

	let content = null;

	// Render subscription view if type is subscription
	if ( type === 'subscription' ) {
		content = <ViewSubscription />;
	} else if ( type === 'payment' ) {
		content = <ViewPayment />;
	}

	return (
		<div className="min-h-screen px-8 py-8 bg-background-secondary flex flex-col gap-[24px] mx-auto max-w-[1500px]">
			{ content }
		</div>
	);
};

export default PaymentDetailPage;
