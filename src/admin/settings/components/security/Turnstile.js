import { Button, Input, Label, RadioButton, Title } from '@bsf/force-ui';
import { __, sprintf } from '@wordpress/i18n';

const APPEARANCE_MODES = [
	{
		label: __( 'Auto', 'sureforms' ),
		value: 'auto',
	},
	{
		label: __( 'Light', 'sureforms' ),
		value: 'light',
	},
	{
		label: __( 'Dark', 'sureforms' ),
		value: 'dark',
	},
];

const INPUT_FIELDS = [
	{
		id: 'site_key',
		label: __( 'Site Key', 'sureforms' ),
		s_key: 'srfm_cf_turnstile_site_key',
	},
	{
		id: 'secret_key',
		label: __( 'Secret Key', 'sureforms' ),
		s_key: 'srfm_cf_turnstile_secret_key',
	},
];

const Turnstile = ( { securitytabOptions, updateGlobalSettings } ) => {
	return (
		<div className="w-full space-y-6">
			<div className="space-y-2">
				<div className="space-y-0.5">
					<Title
						title={ __( 'Cloudflare Turnstile', 'sureforms' ) }
						size="xs"
					/>
					<p>
						{ __(
							'To enable Cloudflare Turnstile, please add your site key and secret key. Configure these settings within the individual form.',
							'sureforms'
						) }
					</p>
				</div>
				<div className="divide-x divide-y-0 divide-solid divide-border-subtle">
					<div className="pr-2 inline-flex items-center">
						<Button
							tag="a"
							href="https://www.cloudflare.com/en-gb/products/turnstile/"
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
							href="https://developers.cloudflare.com/turnstile/get-started/"
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

			<div className="space-y-1.5">
				<Label tag="p" size="sm">
					{ __( 'Appearance Mode', 'sureforms' ) }
				</Label>
				<RadioButton.Group size="sm" columns={ 3 } className="flex flex-wrap gap-2">
					{ APPEARANCE_MODES.map( ( mode ) => (
						<RadioButton.Button
							key={ mode.value }
							value={ mode.value }
							label={ { heading: mode.label } }
							borderOn
							borderOnActive
							onClick={ () => {
								updateGlobalSettings(
									'srfm_cf_appearance_mode',
									mode.value,
									'security-settings'
								);
							} }
							checked={
								securitytabOptions.srfm_cf_appearance_mode ===
								mode.value
							}
						/>
					) ) }
				</RadioButton.Group>
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

export default Turnstile;
