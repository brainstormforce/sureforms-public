import { Container, Text, Button } from '@bsf/force-ui';
import LoadingSkeleton from '@Admin/components/LoadingSkeleton';
import { __ } from '@wordpress/i18n';

const PaymentLoadingSkeleton = ( props ) => {
	const { loading, error, notFound, setViewSinglePayment } = props;

	if ( loading ) {
		return (
			<div className="srfm-single-payment-wrapper min-h-screen bg-background-secondary p-8">
				<Container
					containerType="flex"
					direction="column"
					gap="xs"
					className="w-full h-full"
				>
					<LoadingSkeleton count={ 1 } className="min-h-[44px]" />
					<Container
						className="w-full gap-8"
						containerType="grid"
						cols={ 12 }
					>
						<div className="flex flex-col gap-8 col-span-12 xl:col-span-8">
							<LoadingSkeleton
								count={ 1 }
								className="min-h-[186px]"
							/>
							<LoadingSkeleton
								count={ 1 }
								className="min-h-[450px]"
							/>
						</div>
						<div className="flex flex-col gap-8 col-span-12 xl:col-span-4">
							<LoadingSkeleton
								count={ 1 }
								className="min-h-[200px]"
							/>
							<LoadingSkeleton
								count={ 1 }
								className="min-h-[260px]"
							/>
						</div>
					</Container>
				</Container>
			</div>
		);
	} else if ( error ) {
		return (
			<div className="srfm-single-payment-wrapper min-h-screen bg-background-secondary p-8">
				<div className="flex items-center justify-center h-96">
					<div className="text-center">
						<Text className="text-red-600 mb-4">
							{ __(
								'Error loading payment details',
								'sureforms'
							) }
						</Text>
						<Button
							variant="outline"
							onClick={ () => setViewSinglePayment( false ) }
						>
							{ __( 'Back to Payments', 'sureforms' ) }
						</Button>
					</div>
				</div>
			</div>
		);
	} else if ( notFound ) {
		return (
			<div className="srfm-single-payment-wrapper min-h-screen bg-background-secondary p-8">
				<div className="flex items-center justify-center h-96">
					<div className="text-center">
						<Text className="mb-4">
							{ __( 'Payment not found', 'sureforms' ) }
						</Text>
						<Button
							variant="outline"
							onClick={ () => setViewSinglePayment( false ) }
						>
							{ __( 'Back to Payments', 'sureforms' ) }
						</Button>
					</div>
				</div>
			</div>
		);
	}

	return null;
};

export default PaymentLoadingSkeleton;
