import { __ } from '@wordpress/i18n';
import { Container, Label } from '@bsf/force-ui';
import { initiateAuth } from '@Utils/Helpers';

export default () => {
	return (
		<Container
			className="mt-20 gap-2 w-full p-2 bg-brand-background-hover-100 justify-center items-center"
			containerType="flex"
			direction="column"
		>
			<Label
				variant="neutral"
				size="xs"
			>
				{ __( 'Connect your website with SureForms AI to get 20 more AI form generations.', 'sureforms' ) }
				<Label
					variant="neutral"
					size="xs"
					className="!text-link-primary cursor-pointer underline focus:cursor-pointer"
					onClick={ initiateAuth }
				>
					{ __( 'Connect Now', 'sureforms' ) }
				</Label>
			</Label>
		</Container>
	);
};
