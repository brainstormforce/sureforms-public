/** @jsx jsx */
import { __ } from '@wordpress/i18n';
import { jsx } from '@emotion/react';
import { Button, Container, Label, Title } from '@bsf/force-ui';
import { Plus, SquareArrowOutUpRight } from 'lucide-react';

export default () => {
	const siteUrl = srfm_admin.site_url;

	return (
		<>
			<Container
				className="bg-background-primary p-5 xl:p-6 shadow-sm rounded-xl border border-solid border-border-subtle gap-y-8 md:gap-x-8"
				containerType="grid"
				cols={ 12 }
				align="center"
			>
				<Container.Item
					colSpan={ 8 }
				>
					<Container
						containerType="grid"
						cols={ 8 }
						gap="sm"
					>

						<Container.Item
							colSpan={ 8 }
						>
							<Title
								description=""
								icon={ null }
								iconPosition="right"
								size="lg"
								tag="h4"
								title={ __( 'Welcome to SureForms!', 'sureforms' ) }
								className="p-2"
							/>
							<Label
								size="sm"
								variant="neutral"
								className="p-2 font-[400] text-text-secondary"
							>
								{ __( 'SureForms is a WordPress plugin that enables users to create beautiful looking forms through a drag-and-drop interface, without needing to code. It integrates with the WordPress block editor.', 'sureforms' ) }
							</Label>

							<Container
								className="p-2 gap-3"
								containerType="flex"
								gap="sm"
							>
								<Container.Item
								>
									<Button
										className="bg-button-primary text-white shadow-sm-blur-1 gap-1 hover:bg-button-primary focus:bg-button-primary"
										icon={ <Plus aria-label="icon" role="img" /> }
										iconPosition="right"
										size="md"
										tag="button"
										type="button"
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
										className="gap-1"
										icon={ <SquareArrowOutUpRight aria-label="icon" role="img" /> }
										iconPosition="right"
										size="md"
										tag="button"
										type="button"
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

				<Container.Item
					className="p-2 gap-8 bg-gray-100"
					colSpan={ 4 }
				>
				Image
				</Container.Item>

			</Container>
		</>
	);
};
