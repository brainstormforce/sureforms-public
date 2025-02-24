import { __ } from '@wordpress/i18n';
import { Container, Label } from '@bsf/force-ui';
import aiFormBuilderPlaceholder from '@Image/ai-form-builder.svg';
import { CircularProgressBar } from '@tomickigrzegorz/react-circular-progress-bar';

export default ( props ) => {
	const { message, percentBuild } = props;

	return (
		<Container
			containerType="flex"
			direction="column"
			className="mt-10 bg-background-secondary p-8 gap-6 w-[39%] ml-auto mr-auto"
		>
			<Container.Item
				className=""
			>
				<Container
					className="gap-6 items-center"
					containerType="flex"
					direction="row"
				>
					<Container.Item>
						<CircularProgressBar
							colorCircle="#eee3e1"
							colorSlice="#D54407"
							percent={ percentBuild }
							round
							speed={ 85 }
							fontColor="#0F172A"
							fontSize="18px"
							fontWeight={ 700 }
							size={ 72 }
						/>
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
							{ message }
						</Label>
					</Container.Item>
				</Container>
			</Container.Item>
			<img
				src={ aiFormBuilderPlaceholder }
				alt={ __( 'AI Form Builder', 'sureforms' ) }
			/>
		</Container>
	);
};
