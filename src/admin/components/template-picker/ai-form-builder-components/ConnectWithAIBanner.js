import { __ } from '@wordpress/i18n';
import { Container, Label } from '@bsf/force-ui';
import { initiateAuth } from '@Utils/Helpers';

export default () => {
	return (
		<Container
			className="mt-14 gap-2 w-full p-2 bg-brand-background-hover-100"
			direction="column"
			align="center"
			justify="center"
		>
			<Label variant="neutral" size="xs" className="font-normal">
				{ __(
					'Connect your website with SureForms AI to get 20 more AI form generations.',
					'sureforms'
				) }
				<Label
					variant="neutral"
					size="xs"
					className="!text-link-primary font-normal cursor-pointer underline"
					onClick={ initiateAuth }
				>
					{ __( 'Connect Now', 'sureforms' ) }
				</Label>
			</Label>
		</Container>
	);
};
