import { __ } from '@wordpress/i18n';
import { Button, Container, Label } from '@bsf/force-ui';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import ICONS from '../components/icons';

export default () => {
	const FormOption = ( { icon, title, description, buttonText, iconPosition, onClick, btnIcon, variant } ) => (
		<Container.Item className="flex flex-col flex-1 bg-background-primary border-0.5 border-solid border-border-subtle rounded-lg shadow-sm-blur-2">
			<Container className="flex flex-col flex-1">
				<Container.Item className="gap-2 p-3">
					<Container containerType="flex" direction="column">
						<Container.Item>{ icon }</Container.Item>
						<Container.Item className="flex flex-col gap-1 p-1">
							<Label size="md" variant="neutral" className="text-lg">
								{ title }
							</Label>
							<Label size="xs" variant="help">
								{ description }
							</Label>
						</Container.Item>
					</Container>
				</Container.Item>
				<Container.Item className="mt-auto p-3">
					<Button
						className={ `gap-0.5 w-full text-xs` }
						size="sm"
						iconPosition={ iconPosition }
						icon={ btnIcon }
						variant={ variant }
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
			iconPosition: 'left',
			onClick: () => {
				window.location.href = `${ srfm_admin.site_url }/wp-admin/post-new.php?post_type=sureforms_form`;
			},
			btnIcon: <ArrowRight size={ 12 } stroke="transparent" />,
			variant: 'secondary',
		},
		aiFormBuilder: {
			icon: ICONS.wandSparkles,
			title: __( 'Generate Form with AI', 'sureforms' ),
			description: __( 'Experience the future of form building with AI-powered forms. Use AI to build your form 10x faster.', 'sureforms' ),
			buttonText: __( 'Build With AI', 'sureforms' ),
			iconPosition: 'right',
			onClick: () => {
				window.location.href = `${ srfm_admin.site_url }/wp-admin/admin.php?page=add-new-form&method=ai`;
			},
			btnIcon: <ArrowRight size={ 12 } />,
			variant: 'primary',
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
						className="font-semibold text-2xl"
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
						className="text-text-tertiary hover:cursor-pointer"
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
