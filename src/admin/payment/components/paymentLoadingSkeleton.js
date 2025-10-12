import { Container } from '@bsf/force-ui';
import LoadingSkeleton from '@Admin/components/LoadingSkeleton';

const PaymentLoadingSkeleton = () => {
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
};

export default PaymentLoadingSkeleton;
