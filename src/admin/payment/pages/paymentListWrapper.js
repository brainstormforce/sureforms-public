import PaymentTable from './paymentList.js';
import { PaymentContext } from '../components/context.js';
import { useContext } from '@wordpress/element';
import ViewPayment from './viewPayment.js';
import ViewSubscription from './viewSubscription.js';
import Header from '@Admin/components/Header.js';

const PaymentListWrapper = () => {
	const { viewSinglePayment, singlePaymentType } =
		useContext( PaymentContext );

	let showViewPayment = null;
	let wrapperClassName = '';

	if ( viewSinglePayment ) {
		wrapperClassName = 'max-w-[1550px] m-auto';
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
			<div className={ `sureforms-payments-app ${ wrapperClassName }` }>
				{ showViewPayment }
			</div>
		</>
	);
};

export default PaymentListWrapper;
