import { __ } from '@wordpress/i18n';
import { Button, Container, Label } from '@bsf/force-ui';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import ICONS from '../components/icons';

export default () => {
	const FormOption = ( { icon, title, description, buttonText, buttonClass, iconPosition, onClick, btnIcon } ) => (
		<Container.Item className="flex flex-col flex-1 bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm-blur-2">
			<Container className="flex flex-col flex-1">
				<Container.Item className="gap-2 p-3">
					<Container containerType="flex" direction="column">
						<Container.Item>{ icon }</Container.Item>
						<Container.Item className="flex flex-col gap-1 p-1">
							<Label size="md" variant="neutral" className="font-medium text-text-primary text-lg">
								{ title }
							</Label>
							<Label size="sm" variant="help" className="text-text-tertiary font-normal text-xs">
								{ description }
							</Label>
						</Container.Item>
					</Container>
				</Container.Item>
				<Container.Item className="mt-auto p-3">
					<Button
						className={ `gap-0.5 w-full ${ buttonClass } text-text-on-color text-xs focus:[box-shadow:none]` }
						size="sm"
						iconPosition={ iconPosition }
						icon={ btnIcon }
						variant="ghost"
						onClick={ onClick }
					>
						{ buttonText }
					</Button>
				</Container.Item>
			</Container>
		</Container.Item>
	);

	const formOptions = {
		buildFromScratch: {
			icon: ICONS.filePlus,
			title: __( 'Build Form From Scratch', 'sureforms' ),
			description: __( 'Tailor your form precisely to your unique needs. No coding skills required—just unleash your creativity.', 'sureforms' ),
			buttonText: __( 'Build From Scratch', 'sureforms' ),
			buttonClass: 'bg-button-secondary hover:bg-button-secondary',
			iconPosition: 'left',
			onClick: () => {
				window.location.href = `${ srfm_admin.site_url }/wp-admin/post-new.php?post_type=sureforms_form`;
			},
			btnIcon: <ArrowRight size={ 12 } stroke="transparent" />,
		},
		aiFormBuilder: {
			icon: ICONS.wandSparkles,
			title: __( 'Generate Form with AI', 'sureforms' ),
			description: __( 'Experience the future of form building with AI-powered forms. Use AI to build your form 10x faster.', 'sureforms' ),
			buttonText: __( 'Build With AI', 'sureforms' ),
			buttonClass: 'bg-button-primary hover:bg-button-primary',
			iconPosition: 'right',
			onClick: () => {
				window.location.href = `${ srfm_admin.site_url }/wp-admin/admin.php?page=add-new-form&method=ai`;
			},
			btnIcon: <ArrowRight size={ 12 } />,
		},
	};

	const FormSelection = () => (
		<Container className="flex flex-col sm:flex-row">
			{ Object.keys( formOptions ).map( ( key ) => (
				<FormOption key={ key } { ...formOptions[ key ] } />
			) ) }
		</Container>
	);

	return (
		<Container
			className="flex w-full h-screen justify-center items-center mx-auto"
		>
			<Container
				className="p-4 gap-2 bg-background-primary border-0.5 border-solid border-border-subtle rounded-xl shadow-sm-blur-2 max-w-[680px]"
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
				<Container.Item className="p-2 gap-6">
					<FormSelection />
				</Container.Item >
				<Container.Item
					className="flex p-2 gap-6 justify-center"
				>
					<Button
						className="text-text-tertiary hover:cursor-pointer focus:[box-shadow:none]"
						icon={ <ArrowLeft size={ 12 } /> }
						iconPosition="left"
						size="xs"
						variant="ghost"
						onClick={ () => {
							window.location.href = '/wp-admin/admin.php?page=sureforms_menu';
						} }
					>
						{ __( 'Exit to Dashboard', 'sureforms' ) }
					</Button>
				</Container.Item >
			</Container>
		</Container >
	);
};
