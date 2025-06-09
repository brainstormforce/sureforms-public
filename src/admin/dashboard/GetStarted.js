import { __ } from '@wordpress/i18n';
import { Button, Container, Label, Title } from '@bsf/force-ui';
import { Plus } from 'lucide-react';
import ExternalLinkIcon from '@Image/external-link-icon.js';

export default () => {
	const siteUrl = srfm_admin.site_url;

	return (
		<Container
			className="w-full bg-background-primary p-4 gap-8 shadow-sm-blur-1 rounded-xl border-0.5 border-solid border-border-subtle"
			containerType="grid"
			cols={ 12 }
			align="center"
		>
			<Container.Item className="flex flex-col gap-6 p-2 col-span-12 md:col-span-7 lg:col-span-7">
				<Container direction="column" className="gap-1">
					<Title
						size="md"
						tag="h3"
						title={ __( 'Welcome to SureForms!', 'sureforms' ) }
					/>
					<Label
						size="md"
						variant="neutral"
						className="font-normal text-text-secondary"
					>
						{ __(
							'SureForms is a WordPress plugin that enables users to create beautiful looking forms through a drag-and-drop interface, without needing to code. It integrates with the WordPress block editor.',
							'sureforms'
						) }
					</Label>
				</Container>
				<Container className="flex-wrap gap-3">
					<Button
						className="shadow-sm-blur-1 gap-1"
						icon={
							<Plus
								aria-label={ __(
									'Create New Form',
									'sureforms'
								) }
							/>
						}
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
						className="gap-1"
						icon={
							<ExternalLinkIcon
								aria-label={ __(
									'Read Full Guide',
									'sureforms'
								) }
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
				</Container>
			</Container.Item>
			<Container className="p-2 gap-2 col-span-12 md:col-span-5 lg:col-span-5 aspect-video">
				<iframe
					className="w-full h-full rounded border border-solid border-border-subtle"
					src="https://www.youtube.com/embed/it16jGnZBus"
					title={ __(
						'SureForms: Custom WordPress Forms MADE SIMPLE',
						'sureforms'
					) }
					allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
					referrerPolicy="strict-origin-when-cross-origin"
					allowFullScreen
				></iframe>
			</Container>
		</Container>
	);
};
