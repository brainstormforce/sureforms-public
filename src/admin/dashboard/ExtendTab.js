import { __ } from '@wordpress/i18n';
import { Button, Container, Label, Toaster } from '@bsf/force-ui';
import {
	cn,
	getPluginStatusText,
	handlePluginActionTrigger,
} from '@Utils/Helpers';

export default () => {
	const plugin = srfm_admin?.rotating_plugin_banner;
	const isRTL = srfm_admin?.is_rtl;
	const toasterPosition = isRTL ? 'bottom-left' : 'bottom-right';

	// Don't render if no plugins are shown/active.
	if ( ! plugin ) {
		return null;
	}

	return (
		<Container
			className="w-full bg-background-primary border-0.5 border-solid rounded-xl border-border-subtle p-3 gap-2 shadow-sm-blur-1"
			direction="column"
		>
			<Toaster
				position={ toasterPosition }
				design="stack"
				theme="light"
				autoDismiss={ true }
				dismissAfter={ 5000 }
				className={ cn(
					'z-[999999]',
					isRTL
						? '[&>li>div>div.absolute]:right-auto [&>li>div>div.absolute]:left-[0.75rem!important]'
						: ''
				) }
			/>
			<Container.Item>
				<Label size="sm" className="font-semibold p-1">
					{ __( 'Super Charge Your Workflow', 'sureforms' ) }
				</Label>
			</Container.Item>
			<Container.Item>
				<Container
					containerType="grid"
					className="gap-1 p-1 grid-cols-2 md:grid-cols-1 xl:grid-cols-1 bg-background-secondary rounded-lg p-1 gap-1"
				>
					<Container.Item className="flex shadow-sm-blur-2 rounded-md bg-background-primary flex-1 min-w-[calc(50%-0.5rem)]">
						<Container direction="column" className="gap-1 p-2">
							<Container.Item className="flex flex-col gap-1 pb-1">
								<Container
									align="start"
									className="gap-1.5 p-1"
								>
									<img
										className="w-5 h-5"
										src={ plugin.logo }
										alt={ plugin.title }
									/>
									<Label size="sm">
										{ plugin.title }
									</Label>
								</Container>
								<Label
									size="sm"
									variant="help"
									className="font-normal p-1 gap-0.5"
								>
									{ plugin.subtitle }
								</Label>
							</Container.Item>
							<Container.Item className="flex gap-0.5 pt-2 pb-1 px-1 mt-auto">
								<Button
									className={ cn(
										'w-fit focus:[box-shadow:none]',
										plugin.status === 'Activated' &&
											'bg-badge-background-green hover:bg-badge-background-green'
									) }
									variant="outline"
									onClick={ ( event ) =>
										handlePluginActionTrigger( {
											plugin,
											event,
										} )
									}
									size="xs"
								>
									{ getPluginStatusText( plugin ) }
								</Button>
							</Container.Item>
						</Container>
					</Container.Item>
				</Container>
			</Container.Item>
		</Container>
	);
};
