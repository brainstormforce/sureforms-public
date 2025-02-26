import { Switch } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';

const Honeypot = () => {
	return (
		<div className="w-full">
			<Switch
				label={ {
					heading: __( 'Enable Honeypot Security', 'sureforms' ),
					description: __(
						'Enable Honeypot Security for better spam protection',
						'sureforms'
					),
				} }
				size="sm"
			/>
		</div>
	);
};

export default Honeypot;
