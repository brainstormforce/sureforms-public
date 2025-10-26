import { Container, Button, Title } from '@bsf/force-ui';
import { ArrowLeft, ArrowUpRight } from 'lucide-react';
import { __, sprintf } from '@wordpress/i18n';
import { formatOrderId } from './utils';

const PaymentHeader = ( { title, onBack, paymentData, handleViewEntry } ) => {
	const { entry_id } = paymentData;

	// Generate order ID in the same format as payment list
	const orderId = formatOrderId( paymentData );

	return (
		<Container
			containerType="flex"
			direction="row"
			gap="xs"
			className="w-full justify-between items-center"
		>
			<Title
				icon={ null }
				size="lg"
				tag="h2"
				title={ sprintf(
					/* translators: 1: title, 2: Order ID */
					__( '%1$s %2$s', 'sureforms' ),
					title,
					orderId
				) }
			/>
			<div className="flex gap-2 items-center">
				<Button
					icon={ <ArrowUpRight className="!size-4" /> }
					iconPosition="right"
					size="s"
					variant="outline"
					onClick={ handleViewEntry }
					disabled={ ! entry_id }
					className="text-text-primary rounded-[4px] p-2 gap-0.5"
				>
					{ __( 'View Entry', 'sureforms' ) }
				</Button>
				<Button
					variant="outline"
					size="s"
					icon={ <ArrowLeft className="!size-4" /> }
					onClick={ onBack }
					className="text-text-primary rounded-[4px] p-2 gap-0.5"
				>
					{ __( 'Back', 'sureforms' ) }
				</Button>
			</div>
		</Container>
	);
};

export default PaymentHeader;
