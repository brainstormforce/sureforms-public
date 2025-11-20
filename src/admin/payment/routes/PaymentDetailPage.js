import { useSearchParams } from 'react-router-dom';
import ViewPayment from '../pages/viewPayment';
import ViewSubscription from '../pages/viewSubscription';
import { cn } from '@Utils/Helpers';
import Header from '@Admin/components/Header';

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
		<>
			<div className={ cn( 'z-50 relative' ) }>
				<Header />
				<div className="min-h-screen px-8 py-8 bg-background-secondary flex flex-col gap-[24px] mx-auto max-w-[1500px]">
					{ content }
				</div>
			</div>
		</>
	);
};

export default PaymentDetailPage;
