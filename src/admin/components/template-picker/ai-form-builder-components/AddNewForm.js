import { __ } from '@wordpress/i18n';
import { Button, Container, Label, Title } from '@bsf/force-ui';
import { ArrowLeft, ArrowRight } from 'lucide-react';
import ICONS from '../components/icons';

export default () => {
	const FormOption = ( {
		icon,
		title,
		description,
		buttonText,
		iconPosition,
		onClick,
		btnIcon,
		variant,
	} ) => (
		<Container.Item className="flex flex-col justify-between gap-0 flex-1 min-h-[260px] border-0.5 border-solid border-border-subtle rounded-lg shadow-sm-blur-2">
			<Container
				direction="column"
				className="flex flex-col gap-0 p-3 flex-grow"
			>
				<Container.Item>{ icon }</Container.Item>
				<Container.Item className="flex flex-col gap-1 p-1">
					<Title size="sm" title={ title } className="font-medium" />
					<Label size="sm" variant="help">
						{ description }
					</Label>
				</Container.Item>
			</Container>

			<div className="border-t-0.5 border-b-0 border-y-0 border-solid border-border-subtle"></div>

			<Container.Item className="flex gap-3 p-3">
				<Button
					className="w-full"
					size="sm"
					iconPosition={ iconPosition }
					icon={ btnIcon }
					variant={ variant }
					onClick={ onClick }
				>
					{ buttonText }
				</Button>
			</Container.Item>
		</Container.Item>
	);

	const formOptions = {
		buildFromScratch: {
			icon: ICONS.filePlus,
			title: __( 'Build Form From Scratch', 'sureforms' ),
			description: __(
				'Tailor your form precisely to your unique needs. No coding skills required—just unleash your creativity.',
				'sureforms'
			),
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
			description: __(
				'Experience the future of form building with AI-powered forms. Use AI to build your form 10x faster.',
				'sureforms'
			),
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
		<Container className="flex flex-col sm:flex-row p-2 gap-6">
			{ Object.keys( formOptions ).map( ( key ) => (
				<FormOption key={ key } { ...formOptions[ key ] } />
			) ) }
		</Container>
	);

	return (
		<Container
			className="w-full h-screen p-8 gap-8"
			justify="center"
			align="center"
		>
			<Container
				className="p-4 gap-2 bg-background-primary border-0.5 border-solid border-border-subtle rounded-xl shadow-sm-blur-2 max-w-[42.5rem]"
				direction="column"
			>
				<Container.Item className="p-2 gap-6">
					<Title
						size="lg"
						tag="h3"
						title={ __(
							'How would you like to create a new form?',
							'sureforms'
						) }
					/>
				</Container.Item>
				<Container.Item>
					<FormSelection />
				</Container.Item>
				<Container.Item className="flex p-2 gap-6 justify-center">
					<Button
						className="text-text-tertiary hover:cursor-pointer"
						icon={ <ArrowLeft size={ 16 } /> }
						iconPosition="left"
						size="xs"
						variant="ghost"
						onClick={ () => {
							window.location.href =
								'/wp-admin/admin.php?page=sureforms_menu';
						} }
					>
						{ __( 'Exit to Dashboard', 'sureforms' ) }
					</Button>
				</Container.Item>
			</Container>
		</Container>
	);
};
