import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';
import { Button, Text, Container, Badge, Loader, Skeleton } from '@bsf/force-ui';
import { useOnboardingNavigation } from './hooks';
import { useOnboardingState } from './onboarding-state';
import NavigationButtons from './navigation-buttons';
import { handlePluginActionTrigger } from '@Utils/Helpers';
import { Header, Divider } from './components'

const RecommendedPlugins = () => {
	const [ isPluginsDataLoading, setIsPluginsDataLoading ] = useState( false );
	const [ , actions ] = useOnboardingState();
	const { navigateToNextRoute, navigateToPreviousRoute } = useOnboardingNavigation();

	// Get integrations from localized data
	let integrations = Object.entries( srfm_admin?.integrations ?? {} );
	integrations = integrations.filter(
		( plugin ) =>
			plugin[ 0 ] === 'sure_mails' || plugin[ 0 ] === 'sure_triggers'
	);

	const handleContinue = async () => {
		navigateToNextRoute();
	};

	const handleBack = async () => {
		navigateToPreviousRoute();
	};

	return (
		<div className="space-y-6">
			<Header
				title={ __(
					'Add More Power to Your Website',
					'sureforms'
				) }
				description={ __(
					'These tools can help you build your website faster and easier. Try them out and see how they can help your website grow.',
					'sureforms'
				) }
			/>

			<div className="grid grid-cols-1 sm:grid-cols-2 gap-1 p-1 rounded-lg bg-background-secondary">
				{ integrations?.map( ( plugin, index ) => (
					<Container
						key={ index }
						gap="none"
						direction="column"
						className="p-3 bg-background-primary rounded-md shadow-sm w-full"
					>
						<Container align="start" className="p-1 w-full">
							<img
								className="w-5 h-5"
								src={ plugin[ 1 ].logo }
								alt={ plugin[ 1 ].title }
							/>
							<Badge
								label={ plugin[ 1 ].badge_text ?? __( 'Free', 'sureforms' ) }
								variant="green"
								className="ml-auto"
							/>
						</Container>
						<Container
							direction="column"
							className="gap-1.5 flex-1"
						>
							<div className="space-y-0.5">
								<Text
									size={ 14 }
									weight={ 500 }
									color="primary"
								>
									{ plugin[ 1 ].title }
								</Text>
								<Text
									size={ 14 }
									weight={ 400 }
									color="tertiary"
								>
									{ plugin[ 1 ].subtitle }
								</Text>
							</div>
							{ isPluginsDataLoading ? (
								<Skeleton className="w-28 h-6" variant="rectangular" />
							) : (
								<Button
									className={ `w-fit focus:[box-shadow:none] ${ 
										plugin[ 1 ].status === 'Activated' &&
										'bg-badge-background-green hover:bg-badge-background-green'
									}` }
									variant="outline"
									onClick={ ( event ) =>
										handlePluginActionTrigger( {
											plugin: plugin[ 1 ],
											event,
										} )
									}
									size="xs"
								>
									{ plugin[ 1 ].status === 'Activated'
										? __( 'Activated', 'sureforms' )
										: plugin[ 1 ].status === 'Installed'
										? __( 'Activate', 'sureforms' )
										: __( 'Install & Activate', 'sureforms' )
									}
								</Button>
							) }
						</Container>
					</Container>
				) ) }
			</div>

			<Divider />

			<NavigationButtons
				backProps={ {
					onClick: handleBack,
				} }
				continueProps={ {
					onClick: handleContinue,
				} }
			/>
		</div>
	);
};

export default RecommendedPlugins;
