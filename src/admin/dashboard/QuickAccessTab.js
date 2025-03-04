import { __ } from '@wordpress/i18n';
import { Button, Container, Label } from '@bsf/force-ui';
import { Ticket, CircleHelp, MessagesSquare, Star } from 'lucide-react';

export default () => {
	const quickAccessOptions = [
		{
			key: 'support-ticket',
			label: __( 'Open Support Ticket', 'sureforms' ),
			icon: <Ticket className="w-12 text-icon-primary" />,
			link: 'https://sureforms.com/contact/',
		},
		{
			key: 'help-center',
			label: __( 'Help Center', 'sureforms' ),
			icon: <CircleHelp className="w-12 text-icon-primary" />,
			link: 'https://sureforms.com/docs/',
		},
		{
			key: 'join-community',
			label: __( 'Join the Community', 'sureforms' ),
			icon: <MessagesSquare className="w-12 text-icon-primary" />,
			link: 'https://www.facebook.com/groups/surecrafted',
		},
		{
			key: 'rate-us',
			label: __( 'Rate Us', 'sureforms' ),
			icon: <Star className="w-12 text-icon-primary" />,
			link: 'https://wordpress.org/support/plugin/sureforms/reviews/?rate=5#new-post',
		},
	];

	const QuickAccessButtons = () => {
		return (
			quickAccessOptions.map( ( { key, label, icon, link } ) => (
				<Container
					key={ key }
					align="center"
					className="gap-1 p-1 rounded-md bg-background-primary shadow-sm-blur-1 hover:bg-background-primary"
					containerType="flex"
					direction="row"
				>
					<Container.Item className="flex">
						<Button
							className="font-medium cursor-pointer text-sm text-text-primary focus:[box-shadow:none]"
							icon={ icon }
							iconPosition="left"
							size="xs"
							variant="ghost"
							onClick={ () => window.open( link, '_blank' ) }
						>
							{ label }
						</Button>
					</Container.Item>
				</Container>
			) )
		);
	};

	return (
		<Container
			className="bg-background-primary border border-solid rounded-xl border-border-subtle p-3 shadow-sm"
			containerType="flex"
			direction="column"
			gap="xs"
		>
			<Container.Item className="p-1 gap-8">
				<Label className="font-semibold text-text-primary text-sm">
					{ __( 'Quick Access', 'sureforms' ) }
				</Label>
			</Container.Item>
			<Container.Item className="flex flex-col bg-background-secondary gap-1 p-2 rounded-lg">
				<QuickAccessButtons />
			</Container.Item>
		</Container>
	);
};
