import { __ } from '@wordpress/i18n';
import { Button, Container, Label } from '@bsf/force-ui';
import { Ticket, CircleHelp, MessagesSquare, Star } from 'lucide-react';

export default () => {
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
				{ /* Support Ticket */ }
				<Container
					align="center"
					className="gap-1 p-1 rounded-md bg-background-primary shadow-sm-blur-1"
					containerType="flex"
					direction="row"
				>
					<Container.Item className="flex">
						<Button
							className="font-medium cursor-pointer text-sm text-text-primary"
							icon={ <Ticket className="w-12 text-icon-primary" /> }
							iconPosition="left"
							size="xs"
							tag="button"
							type="button"
							variant="ghost"
							onClick={ () => {
								window.location.href = '#';
							} }
						>
							{ __( 'Open Support Ticket', 'sureforms' ) }
						</Button>
					</Container.Item>
				</Container>
				{ /* Help center */ }
				<Container
					align="center"
					className="gap-1 p-1 rounded-md bg-background-primary shadow-sm-blur-1"
					containerType="flex"
					direction="row"
				>
					<Container.Item className="flex">
						<Button
							className="font-medium cursor-pointer text-sm text-text-primary"
							icon={ <CircleHelp className="w-12 text-icon-primary" /> }
							iconPosition="left"
							size="xs"
							tag="button"
							type="button"
							variant="ghost"
							onClick={ () => {
								window.location.href = '#';
							} }
						>
							{ __( 'Help Center', 'sureforms' ) }
						</Button>
					</Container.Item>
				</Container>
				{ /* Join the Community */ }
				<Container
					align="center"
					className="gap-1 p-1 rounded-md bg-background-primary shadow-sm-blur-1"
					containerType="flex"
					direction="row"
				>
					<Container.Item className="flex">
						<Button
							className="font-medium cursor-pointer text-sm text-text-primary"
							icon={ <MessagesSquare className="w-12 text-icon-primary" /> }
							iconPosition="left"
							size="xs"
							tag="button"
							type="button"
							variant="ghost"
							onClick={ () => {
								window.location.href = '#';
							} }
						>
							{ __( 'Join the Community', 'sureforms' ) }
						</Button>
					</Container.Item>
				</Container>
				{ /* Rate Us */ }
				<Container
					align="center"
					className="gap-1 p-1 rounded-md bg-background-primary shadow-sm-blur-1"
					containerType="flex"
					direction="row"
				>
					<Container.Item className="flex">
						<Button
							className="font-medium cursor-pointer text-sm text-text-primary"
							icon={ <Star className="w-12 text-icon-primary" /> }
							iconPosition="left"
							size="xs"
							tag="button"
							type="button"
							variant="ghost"
							onClick={ () => {
								window.location.href = '#';
							} }
						>
							{ __( 'Rate Us', 'sureforms' ) }
						</Button>
					</Container.Item>
				</Container>
			</Container.Item>
		</Container>
	);
};
