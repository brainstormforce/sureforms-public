import { Button, Input, Title } from '@bsf/force-ui';
import { __, sprintf } from '@wordpress/i18n';

const INPUT_FIELDS = [
	{
		id: 'site_key',
		label: __( 'Site Key', 'sureforms' ),
		s_key: 'srfm_hcaptcha_site_key',
	},
	{
		id: 'secret_key',
		label: __( 'Secret Key', 'sureforms' ),
		s_key: 'srfm_hcaptcha_secret_key',
	},
];

const Hcaptcha = ( { securitytabOptions, updateGlobalSettings } ) => {
	return (
		<div className="w-full space-y-6">
			<div className="space-y-2">
				<div className="space-y-0.5">
					<Title title={ __( 'hCaptcha', 'sureforms' ) } size="xs" />
					<p>
						{ __(
							'To enable hCAPTCHA, please add your site key and secret key. Configure these settings within the individual form.',
							'sureforms'
						) }
					</p>
				</div>
				<div className="divide-x divide-y-0 divide-solid divide-border-subtle">
					<div className="pr-2 inline-flex items-center">
						<Button
							tag="a"
							href="https://dashboard.hcaptcha.com/overview"
							className="[&>span]:p-0 no-underline hover:no-underline"
							variant="link"
							target="_blank"
							rel="noreferrer noopener"
						>
							{ __( 'Get Keys', 'sureforms' ) }
						</Button>
					</div>
					<div className="pl-2 inline-flex items-center">
						<Button
							tag="a"
							href="https://docs.hcaptcha.com/"
							className="[&>span]:p-0 no-underline hover:no-underline"
							variant="link"
							target="_blank"
							rel="noreferrer noopener"
						>
							{ __( 'Documentation', 'sureforms' ) }
						</Button>
					</div>
				</div>
			</div>

			{ INPUT_FIELDS.map( ( field ) => (
				<Input
					key={ field.id }
					type={ field.id === 'site_key' ? 'text' : 'password' }
					label={ field.label }
					name={ field.id }
					size="md"
					placeholder={ sprintf(
						// translators: %s is the label of the input field.
						__( 'Enter your %s here', 'sureforms' ),
						field.label
					) }
					value={ securitytabOptions[ field.s_key ] || '' }
					onChange={ ( value ) => {
						updateGlobalSettings(
							field.s_key,
							value,
							'security-settings'
						);
					} }
				/>
			) ) }
		</div>
	);
};

export default Hcaptcha;
