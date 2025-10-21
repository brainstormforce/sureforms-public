import { Container } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import paymentPlaceHolder from '@Image/payment-list-placeholder.svg';

const PaymentListPlaceHolder = () => {
	return (
		<div className="srfm-single-payment-wrapper min-h-screen bg-background-secondary p-8">
			<Container
				containerType="flex"
				direction="column"
				gap="xs"
				className="w-full bg-background-primary border-0.5 border-solid rounded-xl border-border-subtle p-4 gap-2 shadow-sm"
			>
				<Container
					containerType="flex"
					gap="xs"
					className="w-full bg-background-secondary rounded-xl p-2 shadow-sm"
				>
					<Container
						containerType="flex"
						gap="xs"
						className="w-full bg-background-primary border-0.5 border-solid rounded-xl border-border-subtle p-6 shadow-sm flex items-center gap-6"
					>
						<div className="min-h-[240px] min-w-[240px] bg-background-secondary">
							<img
								src={ paymentPlaceHolder }
								alt={ __( 'Payment Placeholder', 'sureforms' ) }
								className="w-[240px] h-[240px]"
							/>
						</div>
						<div className="flex flex-col gap-2">
							<p className="text-xl font-bold">
								{ __(
									'No payments yet—but they’ll start rolling in soon!',
									'sureforms'
								) }
							</p>
							<p>
								{ __(
									'It’s quiet here because no one has submitted a payment yet. Once your forms go live, this page will let you:',
									'sureforms'
								) }
							</p>
							<ul className="list-disc list-inside">
								<li>
									{ __(
										'Track all transactions in real time',
										'sureforms'
									) }
								</li>
								<li>
									{ __(
										'View details and statuses easily',
										'sureforms'
									) }
								</li>
								<li>
									{ __(
										'Manage refunds or partial refunds',
										'sureforms'
									) }
								</li>
								<li>
									{ __(
										'Download invoices for each transaction',
										'sureforms'
									) }
								</li>
							</ul>
						</div>
					</Container>
				</Container>
			</Container>
		</div>
	);
};

export default PaymentListPlaceHolder;
