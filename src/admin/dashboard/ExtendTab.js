import { __ } from '@wordpress/i18n';
import { Button, Container, Label, Toaster } from '@bsf/force-ui';
import {
	cn,
	getPluginStatusText,
	handlePluginActionTrigger,
} from '@Utils/Helpers';

export default () => {
	const integrations = Object.entries( srfm_admin?.integrations );

	return (
		<Container
			className="bg-background-primary border-0.5 border-solid rounded-xl border-border-subtle p-3 gap-2 shadow-sm-blur-1"
			direction="column"
		>
			<Toaster
				position="bottom-right"
				design="stack"
				theme="light"
				autoDismiss={ true }
				dismissAfter={ 5000 }
			/>
			<Container.Item>
				<Label size="sm" className="font-semibold p-1">
					{ __( 'Extend Your Website', 'sureforms' ) }
				</Label>
			</Container.Item>
			<Container.Item>
				<Container
					containerType="grid"
					className="gap-1 p-1 grid-cols-2 md:grid-cols-4 xl:grid-cols-2 bg-background-secondary rounded-lg p-1 gap-1"
				>
					{ integrations?.map( ( plugin, index ) => (
						<Container.Item
							key={ index }
							className="flex shadow-sm-blur-2 rounded-md bg-background-primary flex-1 min-w-[calc(50%-0.5rem)]"
						>
							<Container direction="column" className="gap-1 p-2">
								<Container.Item className="flex flex-col gap-1 pb-1">
									<Container
										align="center"
										className="gap-1.5 p-1"
									>
										<img
											className="w-5 h-5"
											src={ plugin[ 1 ].logo }
											alt={ plugin[ 1 ].title }
										/>
										<Label size="sm">
											{ plugin[ 1 ].title }
										</Label>
									</Container>
									<Label
										size="sm"
										variant="help"
										className="font-normal p-1 gap-0.5"
									>
										{ plugin[ 1 ].subtitle }
									</Label>
								</Container.Item>
								<Container.Item className="flex gap-0.5 pt-2 pb-1 px-1 mt-auto">
									<Button
										className={ cn(
											'w-fit focus:[box-shadow:none]',
											plugin[ 1 ].status ===
												'Activated' &&
												'bg-badge-background-green hover:bg-badge-background-green'
										) }
										variant="outline"
										onClick={ ( event ) =>
											handlePluginActionTrigger( {
												plugin: plugin[ 1 ],
												event,
											} )
										}
										size="xs"
									>
										{ getPluginStatusText( plugin[ 1 ] ) }
									</Button>
								</Container.Item>
							</Container>
						</Container.Item>
					) ) }
				</Container>
			</Container.Item>
		</Container>
	);
};
