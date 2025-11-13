import { Container, Button } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { ArrowRight } from 'lucide-react';
import paymentPlaceHolder from '@Image/payment-list-placeholder.svg';

/**
 * PaymentListPlaceHolder Component
 *
 * @param {Object} props
 * @param {string} props.paymentMode - Current payment mode ('live' or 'test')
 */
const PaymentListPlaceHolder = ( { paymentMode = 'test' } ) => {
	return (
		<div className="srfm-single-payment-wrapper min-h-screen bg-background-secondary p-8">
			<Container
				containerType="flex"
				direction="column"
				gap="xs"
				className="w-full bg-background-primary border-0.5 border-solid rounded-xl border-border-subtle p-4 gap-2 shadow-sm"
			>
				<h1 className="text-xl font-semibold text-text-primary">
					{ __( 'Payments', 'sureforms' ) }
				</h1>
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
								{ __( 'No payments yet', 'sureforms' ) }
							</p>
							<p className="text-sm text-text-secondary">
								{ 'live' === paymentMode
									? __(
										'No live payments received yet.',
										'sureforms'
									  )
									: __(
										'No test payments received yet.',
										'sureforms'
									  ) }
							</p>
							<p className="text-sm text-text-secondary">
								{ __(
									"It's quiet here because no one has made a payment yet. Once you receive payments, this page will let you:",
									'sureforms'
								) }
							</p>
							<ul className="list-disc list-inside m-0">
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
							{ ! window.srfm_admin?.payments
								?.stripe_connected && (
								<Button
									onClick={ () => {
										const paymentSettingsUrl =
											window.srfm_admin.payments
												.stripe_connect_url;
										window.open(
											paymentSettingsUrl,
											'_blank',
											'noopener,noreferrer'
										);
									} }
									variant="primary"
									size="md"
									className="w-fit flex"
									icon={ <ArrowRight className="!size-4" /> }
									iconPosition="right"
								>
									{ __( 'Configure Payment', 'sureforms' ) }
								</Button>
							) }
						</div>
					</Container>
				</Container>
			</Container>
		</div>
	);
};

export default PaymentListPlaceHolder;
