import { __ } from '@wordpress/i18n';
import { Container, Label } from '@bsf/force-ui';
import aiFormBuilderPlaceholder from '@Image/ai-form-builder.svg';
import { CircularProgressBar } from '@tomickigrzegorz/react-circular-progress-bar';

export default ( props ) => {
	const { message, percentBuild } = props;

	return (
		<Container
			direction="column"
			className="mt-8 bg-background-secondary mx-auto p-8 gap-8"
			align="center"
			justify="center"
		>
			<Container.Item>
				<Container
					className="gap-6"
					align="center"
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
							size="md"
							className="font-bold"
						>
							{ __( 'We are building your form…', 'sureforms' ) }
						</Label>
						<Label variant="help" size="sm" className="font-normal">
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
