import PaymentTable from './paymentList.js';
import { PaymentContext } from '../components/context.js';
import { useContext } from '@wordpress/element';
import ViewPayment from './viewPayment.js';
import ViewSubscription from './viewSubscription.js';

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

	return <div className="sureforms-payments-app">{ showViewPayment }</div>;
};

export default PaymentListWrapper;
