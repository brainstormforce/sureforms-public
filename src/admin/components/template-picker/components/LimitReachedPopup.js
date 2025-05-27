import { __ } from '@wordpress/i18n';
import Header from './Header.js';
import ICONS from './icons.js';
import AiFormBuilder from './AiFormBuilder.js';
import { Button, Container, Label } from '@bsf/force-ui';

const LimitReachedPopup = ( {
	title = __( 'Limit Reached', 'sureforms' ),
	paraOne,
	paraTwo,
	buttonText,
	onclick,
} ) => {
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
					className="bg-background-primary gap-6 py-4 px-5 rounded-lg max-w-md shadow-lg"
				>
					<Container.Item className="relative pt-2">
						<Label variant="neutral" className="text-lg font-bold flex gap-3">
							<span className="pt-1">
								{ ICONS.warning }
							</span>
							{ title }
							<span
								className="absolute top-[-10px] right-[-15px] cursor-pointer"
								onClick={
									() => window.location.href = srfm_admin.site_url + '/wp-admin/admin.php?page=add-new-form'
								}
							>
								{ ICONS.close }
							</span>
						</Label>
					</Container.Item>
					<Container.Item className="flex flex-col gap-4">
						<Label size="md" className="text-text-secondary font-normal">
							{ paraOne }
						</Label>
						<Label size="md" className="text-text-secondary font-normal">
							{ paraTwo }
						</Label>
					</Container.Item>
					<Container.Item className="flex flex-col w-full gap-4 pb-2">
						<Button
							size="md"
							variant="primary"
							onClick={ onclick }
						>
							{ buttonText ?? __( 'Connect Now', 'sureforms' ) }
						</Button>
					</Container.Item>
				</Container>
			</Container>
			<AiFormBuilder />
		</>
	);
};

export default LimitReachedPopup;
