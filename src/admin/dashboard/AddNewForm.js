import { __ } from '@wordpress/i18n';
import { Button, Container, Label } from '@bsf/force-ui';
import { ArrowLeft } from 'lucide-react';
import ForceUiIcons from './ForceUiIcons';

export default () => {
	return (
		<Container
			className="p-4 gap-2 bg-background-primary border-0.5 border-solid border-border-subtle rounded-xl shadow-sm-blur-2 w-[680px] ml-auto mr-auto"
			containerType="flex"
			direction="column"
		>
			<Container.Item
				className="p-2 gap-6"
			>
				<Label
					variant="neutral"
					className="text-text-primary font-semibold text-2xl"
				>
					{ __( 'How would you like to create a new form?', 'sureforms' ) }
				</Label>
			</Container.Item>
			<Container.Item
				className="p-2 gap-6"
			>
				<Container className="flex flex-col md:flex-row">
					<Container.Item className="flex flex-col flex-1 bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm-blur-2">
						<Container className="flex flex-col flex-1">
							<Container.Item className="gap-2 p-3">
								<Container containerType="flex" direction="column">
									<Container.Item>{ ForceUiIcons.filePlus }</Container.Item>
									<Container.Item className="flex flex-col gap-1 p-1">
										<Label
											size="md"
											variant="neutral"
											className="font-medium text-text-primary text-lg"
										>
											{ __( 'Build Form From Scratch', 'sureforms' ) }
										</Label>
										<Label
											size="sm"
											variant="help"
											className="text-text-tertiary font-normal text-xs"
										>
											{ __( 'Tailor your form precisely to your unique needs. No coding skills required—just unleash your creativity.', 'sureforms' ) }
										</Label>
									</Container.Item>
								</Container>
							</Container.Item>
							<Container.Item className="mt-auto p-3">
								<Button
									className="gap-0.5 w-full bg-button-secondary border border-solid border-button-secondary shadow-sm-blur-2 hover:bg-button-secondary font-semibold rounded-lg text-text-on-color text-xs"
									iconPosition="left"
									size="md"
									tag="button"
									type="button"
									variant="ghost"
									onClick={ () => {
										window.location.href = `${ srfm_admin.site_url }/wp-admin/post-new.php?post_type=sureforms_form`;
									} }
								>
									{ __( 'Build From Scratch', 'sureforms' ) }
								</Button>
							</Container.Item>
						</Container>
					</Container.Item>
					<Container.Item className="flex flex-col flex-1 bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm-blur-2">
						<Container className="flex flex-col flex-1">
							<Container.Item className="gap-2 p-3">
								<Container containerType="flex" direction="column">
									<Container.Item>{ ForceUiIcons.wandSparkles }</Container.Item>
									<Container.Item className="flex flex-col gap-1 p-1">
										<Label
											size="md"
											variant="neutral"
											className="font-medium text-text-primary text-lg"
										>
											{ __( 'Generate Form with AI', 'sureforms' ) }
										</Label>
										<Label
											size="sm"
											variant="help"
											className="text-text-tertiary font-normal text-xs"
										>
											{ __( 'Experience the future of form building with AI-powered forms. Use AI to build your form 10x faster.', 'sureforms' ) }
										</Label>
									</Container.Item>
								</Container>
							</Container.Item>
							<Container.Item className="mt-auto p-3">
								<Button
									className="gap-0.5 w-full bg-button-primary border border-solid border-button-primary shadow-sm-blur-2 hover:bg-button-primary font-semibold rounded-lg text-text-on-color text-xs"
									iconPosition="left"
									size="md"
									tag="button"
									type="button"
									variant="ghost"
									onClick={ () => {
										window.location.href = `${ srfm_admin.site_url }/wp-admin/admin.php?page=add-new-form&method=ai`;
									} }
								>
									{ __( 'Try the AI FormBuilder', 'sureforms' ) }
								</Button>
							</Container.Item>
						</Container>
					</Container.Item>
				</Container>
			</Container.Item >
			<Container.Item
				className="flex p-2 gap-6 justify-center"
			>
				<Button
					className="text-text-tertiary font-semibold text-xs hover:cursor-pointer"
					icon={ <ArrowLeft size={ 16 } /> }
					iconPosition="left"
					size="xs"
					tag="button"
					type="button"
					variant="ghost"
					onClick={ () => {
						window.location.href = '/wp-admin/admin.php?page=sureforms_menu';
					} }
				>
					{ __( 'Exit to Dashboard', 'sureforms' ) }
				</Button>
			</Container.Item >
		</Container >
	);
};
