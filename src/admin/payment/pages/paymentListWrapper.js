import PaymentTable from './paymentList.js';
import { PaymentContext } from '../components/context.js';
import { useContext } from '@wordpress/element';
import ViewPayment from './viewPayment.js';
import ViewSubscription from './viewSubscription.js';
import Header from '@Admin/components/Header.js';

const PaymentListWrapper = () => {
	const { viewSinglePayment, singlePaymentType } =
		useContext( PaymentContext );
	console.log( { viewSinglePayment } );

	let showViewPayment = null;

	if ( viewSinglePayment ) {
		if ( singlePaymentType === 'subscription' ) {
			showViewPayment = <ViewSubscription />;
		} else {
			showViewPayment = <ViewPayment />;
		}
	} else {
		showViewPayment = <PaymentTable />;
	}

	return (
		<>
			<Header />
			<div className="sureforms-payments-app max-w-[1550px] m-auto">
				{ showViewPayment }
			</div>
		</>
	);
};

export default PaymentListWrapper;
