import { __ } from '@wordpress/i18n';
import { Button, Container, Title } from '@bsf/force-ui';
import { ArrowLeftIcon } from 'lucide-react';

const Suretriggers = ( { setSelectedTab } ) => {
	// Adding validation for the SureTriggersConfig and SureTriggers object
	if ( window?.SureTriggers && window?.SureTriggersConfig ) {
		window.SureTriggers.init( window.SureTriggersConfig );
	}

	return (
		<div className="space-y-7 h-full pb-10">
			<Container align="center" className="gap-2">
				<Button
					className="p-0"
					size="md"
					variant="ghost"
					onClick={ () => setSelectedTab( 'integrations' ) }
					icon={ <ArrowLeftIcon /> }
				/>
				<Title
					tag="h5"
					title={ __(
						'SureTriggers',
						'sureforms'
					) }
				/>
			</Container>
			<div className="h-full bg-background-primary rounded-xl p-4 shadow-sm mb-6">
				<div className="h-full flex flex-col gap-1 bg-background-secondary rounded-lg p-2">
					<div
						id="suretriggers-iframe-wrapper"
						className="size-full [&>iframe]:border [&>iframe]:border-solid [&>iframe]:border-border-subtle [&>iframe]:rounded-md"
					/>
				</div>
			</div>
		</div>
	);
};

export default Suretriggers;
