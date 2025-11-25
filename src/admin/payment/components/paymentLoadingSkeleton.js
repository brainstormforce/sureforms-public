import { Skeleton, Text, Button } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';

const PaymentLoadingSkeleton = ( props ) => {
	const { loading, error, notFound, setViewSinglePayment } = props;

	if ( loading ) {
		return (
			<div className="p-8 bg-background-secondary min-h-screen space-y-6">
				{ /* Header Skeleton */ }
				<div className="flex items-center gap-3 mx-auto">
					<Skeleton className="w-8 h-8 rounded-md" />
					<Skeleton className="h-8 w-48" />
				</div>

				<div className="mx-auto">
					<div className="space-y-6">
						{ /* Main Content Grid Skeleton */ }
						<div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
							{ /* Left Column */ }
							<div className="lg:col-span-2 space-y-6">
								{ /* Billing Details Skeleton */ }
								<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
									<div className="pb-0 px-4 pt-4">
										<Skeleton className="h-5 w-32" />
									</div>
									<div className="p-4 space-y-1 relative before:content-[''] before:block before:absolute before:inset-3 before:bg-background-secondary before:rounded-lg">
										<div className="bg-background-primary rounded-md shadow-sm p-3">
											<div className="space-y-2">
												<div className="flex gap-4">
													<Skeleton className="w-24 h-4" />
													<Skeleton className="w-24 h-4" />
													<Skeleton className="w-32 h-4" />
													<Skeleton className="flex-1 h-4" />
												</div>
												<div className="flex gap-4">
													<Skeleton className="w-20 h-4" />
													<Skeleton className="w-16 h-4" />
													<Skeleton className="w-28 h-4" />
													<Skeleton className="flex-1 h-4" />
												</div>
											</div>
										</div>
									</div>
								</div>

								{ /* Payment Information Skeleton */ }
								<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
									<div className="pb-0 px-4 pt-4">
										<div className="flex items-center justify-between">
											<Skeleton className="h-5 w-40" />
											<Skeleton className="w-24 h-6 rounded-md" />
										</div>
									</div>
									<div className="p-4 space-y-1 relative before:content-[''] before:block before:absolute before:inset-3 before:bg-background-secondary before:rounded-lg">
										{ /* Simulate info rows */ }
										{ Array.from( { length: 8 } ).map(
											( _, index ) => (
												<div
													key={ index }
													className="bg-background-primary rounded-md shadow-sm p-3"
												>
													<div className="flex gap-4">
														<Skeleton className="w-32 h-4" />
														<Skeleton className="flex-1 h-4" />
													</div>
												</div>
											)
										) }
									</div>
								</div>
							</div>

							{ /* Right Column */ }
							<div className="space-y-4">
								{ /* Notes Section Skeleton */ }
								<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
									<div className="pb-0 px-4 pt-4">
										<div className="flex items-center justify-between">
											<Skeleton className="h-5 w-24" />
											<Skeleton className="w-20 h-6 rounded-md" />
										</div>
									</div>
									<div className="p-4 space-y-1 relative before:content-[''] before:block before:absolute before:inset-3 before:bg-background-secondary before:rounded-lg">
										<div className="bg-background-primary rounded-md shadow-sm p-3">
											<Skeleton className="w-full h-16" />
										</div>
									</div>
								</div>

								{ /* Payment Logs Section Skeleton */ }
								<div className="bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm">
									<div className="pb-0 px-4 pt-4">
										<Skeleton className="h-5 w-28" />
									</div>
									<div className="p-4 space-y-1 relative before:content-[''] before:block before:absolute before:inset-3 before:bg-background-secondary before:rounded-lg">
										{ /* Simulate log entries */ }
										{ Array.from( { length: 3 } ).map(
											( _, index ) => (
												<div
													key={ index }
													className="bg-background-primary rounded-md shadow-sm p-3"
												>
													<div className="space-y-2">
														<Skeleton className="h-4 w-3/4" />
														<Skeleton className="h-3 w-1/2" />
													</div>
												</div>
											)
										) }
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		);
	} else if ( error ) {
		return (
			<div className="p-8 bg-background-secondary min-h-screen">
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
			<div className="p-8 bg-background-secondary min-h-screen">
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
