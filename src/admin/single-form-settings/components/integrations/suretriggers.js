import { __ } from '@wordpress/i18n';
import { Title } from '@bsf/force-ui';

const Suretriggers = () => {
	// Adding validation for the SureTriggersConfig and SureTriggers object
	if ( window?.SureTriggers && window?.SureTriggersConfig ) {
		window.SureTriggers.init( window.SureTriggersConfig );
	}

	return (
		<div className="h-full bg-background-primary rounded-xl p-4 mb-6 flex flex-col gap-2">
			<Title
				tag="h4"
				title={ __( 'OttoKit Settings', 'sureforms' ) }
				size="md"
				className="p-2"
			/>
			<div className="h-full flex flex-col gap-1 bg-background-secondary rounded-lg p-2">
				<div
					id="suretriggers-iframe-wrapper"
					className="size-full [&>iframe]:rounded-md"
				/>
			</div>
		</div>
	);
};

export default Suretriggers;
