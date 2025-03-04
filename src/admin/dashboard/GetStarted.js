import { __ } from '@wordpress/i18n';
import { Button, Container, Label, Title } from '@bsf/force-ui';
import { Plus, SquareArrowOutUpRight } from 'lucide-react';

export default () => {
	const siteUrl = srfm_admin.site_url;

	return (
		<Container
			className="bg-background-primary p-4 shadow-sm rounded-xl border border-solid border-border-subtle gap-y-8 md:gap-x-8"
			containerType="grid"
			cols={ 12 }
			align="center"
		>
			<Container.Item className="flex flex-col gap-6 col-span-12 md:col-span-7 lg:col-span-7">
				<div>
					<Title
						size="lg"
						tag="h3"
						title={ __( 'Welcome to SureForms!', 'sureforms' ) }
						className="mb-1 px-2"
					/>
					<Label
						size="md"
						variant="neutral"
						className="px-2 font-normal text-text-secondary"
					>
						{ __(
							'SureForms is a WordPress plugin that enables users to create beautiful looking forms through a drag-and-drop interface, without needing to code. It integrates with the WordPress block editor.',
							'sureforms'
						) }
					</Label>
				</div>
				<div className="flex flex-row flex-wrap gap-3">
					<Button
						className="text-white shadow-sm-blur-1 gap-1"
						icon={ <Plus aria-label="icon" role="img" /> }
						iconPosition="right"
						size="md"
						variant="primary"
						onClick={ () => {
							window.location.href = `${ siteUrl }/wp-admin/admin.php?page=add-new-form`;
						} }
					>
						{ __( 'Create New Form', 'sureforms' ) }
					</Button>
					<Button
						className="gap-1 focus:[box-shadow:none]"
						icon={
							<SquareArrowOutUpRight
								aria-label="icon"
								role="img"
							/>
						}
						iconPosition="right"
						size="md"
						variant="ghost"
						onClick={ () => {
							window.open(
								'https://sureforms.com/docs/',
								'_blank'
							);
						} }
					>
						{ __( 'Read Full Guide', 'sureforms' ) }
					</Button>
				</div>
			</Container.Item>
			<Container className="col-span-12 md:col-span-5 lg:col-span-5 aspect-video">
				<iframe
					className="w-full h-full rounded border border-border-subtle"
					src="https://www.youtube.com/embed/it16jGnZBus"
					title="SureForms: Custom WordPress Forms MADE SIMPLE"
					allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
					referrerPolicy="strict-origin-when-cross-origin"
					allowFullScreen
				></iframe>
			</Container>
		</Container>
	);
};
