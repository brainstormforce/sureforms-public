import { __ } from '@wordpress/i18n';
import { Container, Label } from '@bsf/force-ui';
import { initiateAuth } from '@Utils/Helpers';

export default () => {
	return (
		<Container
			className="mt-14 gap-2 w-full p-2 bg-badge-border-yellow"
			direction="column"
			align="center"
			justify="center"
		>
			<Label variant="neutral" size="xs" className="font-normal">
				{ __(
					'You have reached the maximum number of form generations in your Free Plan.',
					'sureforms'
				) }
				<Label
					variant="neutral"
					size="xs"
					className="!text-link-primary font-normal cursor-pointer underline"
					onClick={ initiateAuth }
				>
					{ __( 'Upgrade Now', 'sureforms' ) }
				</Label>
			</Label>
		</Container>
	);
};
