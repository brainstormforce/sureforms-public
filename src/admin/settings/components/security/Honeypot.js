import { Switch } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';

const Honeypot = ( { securitytabOptions, updateGlobalSettings } ) => {
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
				checked={ securitytabOptions.srfm_honeypot }
				onChange={ ( value ) =>
					updateGlobalSettings(
						'srfm_honeypot',
						value,
						'security-settings'
					)
				}
			/>
		</div>
	);
};

export default Honeypot;
