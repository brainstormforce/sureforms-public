import { __ } from '@wordpress/i18n';
import { Button, Container, Label, Title } from '@bsf/force-ui';
import { Plus, SquareArrowOutUpRight } from 'lucide-react';

export default () => {
	const siteUrl = srfm_admin.site_url;

	return (
		<>
			<Container
				className="bg-background-primary p-4 shadow-sm rounded-xl border border-solid border-border-subtle gap-y-8 md:gap-x-8"
				containerType="grid"
				cols={ 12 }
				align="center"
			>
				<Container.Item
					className="flex flex-col gap-6 col-span-12 md:col-span-7 lg:col-span-7"
				>
					<Container
						containerType="grid"
						cols={ 8 }
					>

						<Container.Item
							colSpan={ 8 }
							className="flex flex-col gap-1"
						>
							<Title
								description=""
								icon={ null }
								iconPosition="right"
								size="lg"
								tag="h4"
								title={ __( 'Welcome to SureForms!', 'sureforms' ) }
								className="px-2"
							/>
							<Label
								size="sm"
								variant="neutral"
								className="px-2 font-normal text-text-secondary"
							>
								{ __( 'SureForms is a WordPress plugin that enables users to create beautiful looking forms through a drag-and-drop interface, without needing to code. It integrates with the WordPress block editor.', 'sureforms' ) }
							</Label>

							<Container
								className="p-2 gap-3 flex-col sm:flex-row"
								containerType="flex"
							>
								<Container.Item
								>
									<Button
										className="bg-button-primary text-white shadow-sm-blur-1 gap-1 hover:bg-button-primary focus:bg-button-primary focus:[box-shadow:none]"
										icon={ <Plus aria-label="icon" role="img" /> }
										iconPosition="right"
										size="md"
										variant="ghost"
										onClick={ () => {
											window.location.href = `${ siteUrl }/wp-admin/admin.php?page=add-new-form`;
										} }

									>
										{ __( 'Create New Form', 'sureforms' ) }
									</Button>
								</Container.Item>

								<Container.Item
								>
									<Button
										className="gap-1 focus:[box-shadow:none]"
										icon={ <SquareArrowOutUpRight aria-label="icon" role="img" /> }
										iconPosition="right"
										size="md"
										variant="ghost"
										onClick={ () => {
											window.open( 'https://sureforms.com/docs/', '_blank' );
										} }
									>
										{ __( 'Read Full Guide', 'sureforms' ) }
									</Button>
								</Container.Item>
							</Container>
						</Container.Item>

					</Container>
				</Container.Item>

				<Container.Item className="col-span-12 md:col-span-5 lg:col-span-5">
					<iframe
						width="100%"
						src="https://www.youtube.com/embed/it16jGnZBus"
						title="SureForms: Custom WordPress Forms MADE SIMPLE"
						allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
						referrerPolicy="strict-origin-when-cross-origin"
						allowFullScreen
					></iframe>
				</Container.Item>

			</Container>
		</>
	);
};
