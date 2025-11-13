import { Container, Button, Text } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { ArrowRight } from 'lucide-react';
import paymentPlaceHolder from '@Image/payment-list-placeholder.svg';

/**
 * PaymentListPlaceHolder Component
 *
 */
const PaymentListPlaceHolder = () => {
	const isConfigured = window.srfm_admin?.payments?.stripe_connected;
	const subHeading = isConfigured
		? __( 'No payments yet', 'sureforms' )
		: __( 'Configure Payment', 'sureforms' );
	const textDescription = isConfigured
		? __(
			"It's quiet here because no one has made a payment yet. Once you receive payments, this page will let you:",
			'sureforms'
		  )
		: __(
			'Configure your payment gateway to start accepting payments.',
			'sureforms'
		  );
	const FEATURES = [
		__( 'Track all transactions in real time', 'sureforms' ),
		__( 'View details and statuses easily', 'sureforms' ),
		__( 'Manage refunds or partial refunds', 'sureforms' ),
	];

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
								className="w-60 h-60"
							/>
						</div>
						<div className="flex flex-col gap-2">
							<Text
								size={ 20 }
								lineHeight={ 30 }
								weight={ 600 }
								letterSpacing={ -0.5 }
								color="primary"
							>
								{ subHeading }
							</Text>
							<div className="flex flex-col">
								{ /* Description */ }
								<Text
									size={ 16 }
									lineHeight={ 24 }
									weight={ 400 }
									color="secondary"
								>
									{ textDescription }
								</Text>
								{ /* Bullet Points */ }
								<ul className="flex flex-col list-disc list-inside leading-7 ml-2.5 mt-2 mb-0 mr-0">
									{ FEATURES.map( ( point, index ) => (
										<li key={ index } className="m-0">
											<Text
												className="inline-block"
												size={ 16 }
												lineHeight={ 28 }
												weight={ 400 }
												color="secondary"
											>
												{ point }
											</Text>
										</li>
									) ) }
								</ul>
							</div>
							{ ! isConfigured && (
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
