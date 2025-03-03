import { __ } from '@wordpress/i18n';
import ICONS from './icons.js';
import { Button, Container, Label } from '@bsf/force-ui';

const ErrorPopup = () => {
	return (
		<Container
			containerType="flex"
			direction="column"
			className="fixed inset-0 w-full h-full bg-overlay-background z-[99999999] flex justify-center items-center"
		>
			<Container
				containerType="flex"
				direction="column"
				className="bg-white gap-5 px-5 py-4 rounded-lg w-full max-w-[400px] h-auto shadow-lg"
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
					<Label className="text-text-secondary font-normal text-sm">
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
