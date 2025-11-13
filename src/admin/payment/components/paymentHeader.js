import { Button, Text } from '@bsf/force-ui';
import { ArrowLeft, ArrowUpRight } from 'lucide-react';
import { __, sprintf } from '@wordpress/i18n';
import { formatOrderId } from './utils';

const PaymentHeader = ( { title, onBack, paymentData, handleViewEntry } ) => {
	const { entry_id } = paymentData;

	// Generate order ID in the same format as payment list
	const orderId = formatOrderId( paymentData );

	return (
		<div className="flex items-center justify-between gap-3">
			<div className="flex items-center gap-3">
				<Button
					onClick={ onBack }
					variant="ghost"
					size="md"
					className="p-1"
					icon={ <ArrowLeft /> }
				/>
				<Text size={ 24 } color="primary" weight={ 600 }>
					{ sprintf(
						/* translators: 1: title, 2: Order ID */
						__( '%1$s %2$s', 'sureforms' ),
						title,
						orderId
					) }
				</Text>
			</div>
			<Button
				variant="primary"
				size="md"
				icon={ <ArrowUpRight className="!size-4" /> }
				iconPosition="right"
				onClick={ handleViewEntry }
				disabled={ ! entry_id }
			>
				{ __( 'View Form Data', 'sureforms' ) }
			</Button>
		</div>
	);
};

export default PaymentHeader;
