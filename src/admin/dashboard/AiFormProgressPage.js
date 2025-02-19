import { __ } from '@wordpress/i18n';
import { Container, Label } from '@bsf/force-ui';

export default () => {
	return (
		<Container
			containerType="flex"
			direction="column"
			className="bg-background-secondary p-8 gap-6 w-[39%] ml-auto mr-auto"
		>
			<Container.Item
				className=""
			>
				<Container
					className="gap-6"
					containerType="flex"
					direction="row"
				>
					<Container.Item>
						<div className="relative w-[72px] h-[72px]">
							<svg className="w-full h-full" viewBox="0 0 100 100">
								<circle cx="50" cy="50" r="40" stroke="#fce8df" strokeWidth="10" fill="none" />
								<circle cx="50" cy="50" r="40" stroke="#d7460b" strokeWidth="10" fill="none"
									strokeDasharray="251.2" strokeDashoffset="175.8" strokeLinecap="round"
									transform="rotate(-90 50 50)" />
							</svg>
							<div className="absolute inset-0 flex items-center justify-center text-black text-[20px] font-bold">
								30%
							</div>
						</div>
					</Container.Item>
					<Container.Item className="flex flex-col gap-0.5">
						<Label
							variant="neutral"
							className="text-text-primary font-bold text-[20px] leading-[28px]"
						>
							{ __( 'We are building your form…', 'sureforms' ) }
						</Label>
						<Label
							variant="neutral"
							className="text-text-tertiary font-normal text-sm"
						>
							{ __( 'Adding form fields', 'sureforms' ) }
						</Label>
					</Container.Item>
				</Container>
			</Container.Item>
			<Container.Item className="bg-gray-100 w-[384px] h-[274px] border-0.5 border-solid border-brand-800 rounded-lg shadow-sm">
				image
			</Container.Item>
		</Container>
	);
};
