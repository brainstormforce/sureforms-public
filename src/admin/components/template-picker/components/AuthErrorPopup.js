import { __ } from '@wordpress/i18n';
import ICONS from './icons';
import Header from './Header.js';
import { Button, Container, Label } from '@bsf/force-ui';

export const AuthErrorPopup = ( { initiateAuth } ) => {
	return (
		<>
			<Header />
			<Container
				direction="column"
				justify="center"
				align="center"
				className="fixed inset-0 bg-overlay-background z-[99999999]"
			>
				<Container
					direction="column"
					className="bg-background-primary gap-4 py-4 px-5 rounded-lg max-w-md shadow-lg"
				>
					<Container.Item>
						<Label variant="neutral" className="text-lg font-bold flex gap-3">
							<span className="pt-1">
								{ ICONS.warning }
							</span>
							{ __( 'Authentication Failed', 'sureforms' ) }
						</Label>
					</Container.Item>
					<Container.Item>
						<Label size="sm" className="inline text-text-secondary font-normal">
							{ __(
								'Please check your username and password for the account, and try to reconnect again. Need help?',
								'sureforms'
							) } { ' ' }
							<Label
								onClick={ () => {
									window.open( 'https://sureforms.com/contact/', '_blank' );
								} }
								size="sm"
								className="cursor-pointer inline underline font-normal !text-link-primary"
							>
								{ __( 'Contact Support', 'sureforms' ) }
							</Label>
						</Label>
					</Container.Item>
					<Container.Item className="flex flex-col gap-4 sm:flex-row">
						<Button
							size="md"
							variant="primary"
							onClick={
								initiateAuth
							}
							className="text-sm font-semibold bg-brand-800 hover:bg-brand-800"
						>
							{ __( 'Click Here to Retry', 'sureforms' ) }
						</Button>
						<Button
							size="md"
							variant="outline"
							className="text-sm font-semibold"
							onClick={ () => {
								window.location.href = '/wp-admin/admin.php?page=sureforms_menu';
							} }>
							{ __( 'Exit to Dashboard', 'sureforms' ) }
						</Button>
					</Container.Item>
				</Container>
			</Container>
		</>
	);
};

