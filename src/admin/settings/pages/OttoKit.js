import { __ } from '@wordpress/i18n';
import { Button, Container, Text, Title } from '@bsf/force-ui';
import { getPluginStatusText, handlePluginActionTrigger } from '@Utils/Helpers';
import { Dot } from 'lucide-react';
import ottoKitImage from '@Image/ottokit-integration.svg';

const OttoKitPage = ( { loading } ) => {
	const features = [
		__( 'Instantly send form entries to Slack, Mailchimp, or other apps', 'sureforms' ),
		__( 'Set up alerts, notifications, or actions based on form submissions', 'sureforms' ),
		__( 'Automate tasks like follow-ups, lead assignments, or data sync', 'sureforms' ),
	];
	const plugin = srfm_admin?.integrations?.sure_triggers;

	return (
		<Container className="flex bg-background-primary rounded-xl">
			<Container className='p-2 rounded-lg bg-background-secondary gap-2 w-full'>
				<Container className='p-6 gap-6 rounded-md bg-background-primary w-full'>
					<Container>
						<img src={ ottoKitImage } alt={ __( 'OttoKit', 'sureforms' ) } className="w-[280px] h-[280px]" />
					</Container>
					<Container className='gap-8'>
						<div className="space-y-2">
							<Title
								tag="h3"
								title={ __( 'Setup Integration via OttoKit', 'sureforms' ) }
								size="md"
							/>
							<Text size={ 16 } weight={ 400 } color="secondary">
								{ __(
									'OttoKit connects with SureForms to help you send form data to your favorite tools and trigger automated workflows, no code needed.',
									'sureforms'
								) }
							</Text>
							{ features.map( ( feature, index ) => (
								<Container
									key={ index }
									className="flex items-center gap-1.5"
								>
									<Dot className="text-icon-secondary" />
									<Text size={ 16 } weight={ 400 } color="secondary">
										{ feature }
									</Text>
								</Container>
							) ) }
							<Container className='p-2 gap-3'>
								<Button
									size="md"
									className={
										plugin.status === 'Activated'
											? 'bg-badge-background-green hover:bg-badge-background-green'
											: ''
									}
									variant={
										plugin.status === 'Activated'
											? 'outline'
											: 'primary'
									}
									onClick={ ( event ) =>
										handlePluginActionTrigger( {
											plugin,
											event,
										} )
									}
								>
									{ getPluginStatusText( plugin ) }
								</Button>
							</Container>
						</div>
					</Container>
				</Container>
			</Container>
		</Container>
	);
};

export default OttoKitPage;
