import { __ } from '@wordpress/i18n';
import { Container, Label } from '@bsf/force-ui';
import { Ticket, CircleHelp, MessagesSquare, Star } from 'lucide-react';

export default () => {
	const quickAccessOptions = [
		{
			key: 'support-ticket',
			label: __( 'Open Support Ticket', 'sureforms' ),
			icon: <Ticket size={ 16 } />,
			link: 'https://sureforms.com/contact/',
		},
		{
			key: 'help-center',
			label: __( 'Help Center', 'sureforms' ),
			icon: <CircleHelp size={ 16 } />,
			link: 'https://sureforms.com/docs/',
		},
		{
			key: 'join-community',
			label: __( 'Join the Community', 'sureforms' ),
			icon: <MessagesSquare size={ 16 } />,
			link: 'https://www.facebook.com/groups/surecrafted',
		},
		{
			key: 'rate-us',
			label: __( 'Rate Us', 'sureforms' ),
			icon: <Star size={ 16 } />,
			link: 'https://wordpress.org/support/plugin/sureforms/reviews/?rate=5#new-post',
		},
	];

	const QuickAccessButtons = () => {
		return quickAccessOptions.map( ( { key, label, icon, link } ) => (
			<Container
				key={ key }
				align="center"
				className="gap-1 p-1 rounded-md bg-background-primary shadow-sm-blur-1"
				containerType="flex"
				direction="row"
			>
				<Container.Item className="flex">{ icon }</Container.Item>
				<Container.Item className="flex">
					<a
						className="no-underline"
						href={ link }
						target="_blank"
						rel="noreferrer"
					>
						<Label className="py-0 px-1 font-normal cursor-pointer">
							{ label }
						</Label>
					</a>
				</Container.Item>
			</Container>
		) );
	};

	return (
		<Container
			className="bg-background-primary border-0.5 border-solid rounded-xl border-border-subtle p-3 shadow-sm"
			containerType="flex"
			direction="column"
			gap="xs"
		>
			<Container.Item className="p-1 gap-8">
				<Label size="md" className="font-semibold">
					{ __( 'Quick Access', 'sureforms' ) }
				</Label>
			</Container.Item>
			<Container.Item className="flex flex-col bg-background-secondary gap-1 p-2 rounded-lg">
				<QuickAccessButtons />
			</Container.Item>
		</Container>
	);
};
