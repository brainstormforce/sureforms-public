import { __ } from '@wordpress/i18n';
import ICONS from './icons.js';
import { Button, Container, Label } from '@bsf/force-ui';

const ErrorPopup = () => {
	return (
		<Container
			direction="column"
			justify="center"
			align="center"
			className="fixed inset-0 bg-overlay-background z-[99999999]"
		>
			<Container
				direction="column"
				className="bg-background-primary gap-5 px-5 py-4 rounded-lg max-w-sm shadow-lg"
			>
				<Container.Item className="pt-2">
					<Label variant="neutral" className="text-lg font-bold flex gap-3">
						<span className="pt-1">
							{ ICONS.warning }
						</span>
						{ __( 'Error Creating Form', 'sureforms' ) }
					</Label>
				</Container.Item>
				<Container.Item className="flex flex-col gap-4">
					<Label size="sm" className="text-text-secondary font-normal">
						{ __( 'There was an error mapping the AI response to Fields. Please try again.', 'sureforms' ) }
					</Label>
				</Container.Item>
				<Container.Item className="flex flex-col w-full gap-4 pb-2">
					<Button
						size="md"
						variant="primary"
						onClick={ () => {
							window.location.reload();
						} }
					>
						{ __( 'Try Again!', 'sureforms' ) }
					</Button>
				</Container.Item>
			</Container>
		</Container>
	);
};

export default ErrorPopup;
