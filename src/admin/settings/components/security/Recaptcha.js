import { Button, Input, Title, Tabs } from '@bsf/force-ui';
import { __, sprintf } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

const RECAPTCHA_TYPES = [
	{
		label: __( 'reCaptcha v2', 'sureforms' ),
		value: 'v2',
		site_key: 'srfm_v2_checkbox_site_key',
		secret_key: 'srfm_v2_checkbox_secret_key',
	},
	{
		label: __( 'reCaptcha v2 Invisible', 'sureforms' ),
		value: 'invisible',
		site_key: 'srfm_v2_invisible_site_key',
		secret_key: 'srfm_v2_invisible_secret_key',
	},
	{
		label: __( 'reCaptcha v3', 'sureforms' ),
		value: 'v3',
		site_key: 'srfm_v3_site_key',
		secret_key: 'srfm_v3_secret_key',
	},
];

const INPUT_FIELDS = [
	{
		id: 'site_key',
		label: __( 'Site Key', 'sureforms' ),
	},
	{
		id: 'secret_key',
		label: __( 'Secret Key', 'sureforms' ),
	},
];

const Recaptcha = ( { securitytabOptions, updateGlobalSettings } ) => {
	const [ activeTab, setActiveTab ] = useState( RECAPTCHA_TYPES[ 0 ].value );

	return (
		<div className="w-full space-y-6">
			<div className="space-y-2">
				<div className="space-y-0.5">
					<Title
						title={ __( 'Google reCAPTCHA', 'sureforms' ) }
						size="xs"
					/>
					<p>
						{ __(
							'To enable reCAPTCHA feature on your SureForms Please enable reCAPTCHA option on your blocks setting and select version. Add google reCAPTCHA secret and site key here. reCAPTCHA will be added to your page on front-end.',
							'sureforms'
						) }
					</p>
				</div>
				<div className="divide-x divide-y-0 divide-solid divide-border-subtle">
					<div className="pr-2 inline-flex items-center">
						<Button
							tag="a"
							href="https://www.google.com/recaptcha/admin/create"
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
							href="https://developers.google.com/recaptcha/intro"
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

			<Tabs activeItem={ activeTab }>
				<Tabs.Group
					variant="rounded"
					onChange={ ( { value: { slug } } ) => setActiveTab( slug ) }
				>
					{ RECAPTCHA_TYPES.map( ( type ) => (
						<Tabs.Tab
							key={ type.value }
							slug={ type.value }
							text={ type.label }
						/>
					) ) }
				</Tabs.Group>
				{ RECAPTCHA_TYPES.map( ( type ) => (
					<Tabs.Panel key={ type.value } slug={ type.value }>
						{ INPUT_FIELDS.map( ( field ) => (
							<Input
								key={ field.id }
								type={
									field.id === 'site_key'
										? 'text'
										: 'password'
								}
								label={ field.label }
								name={ field.id }
								size="md"
								placeholder={ sprintf(
									// translators: %s is the label of the input field.
									__( 'Enter your %s key here', 'sureforms' ),
									field.label
								) }
								value={
									field.id === 'site_key'
										? securitytabOptions[ type.site_key ]
										: securitytabOptions[ type.secret_key ]
								}
								onChange={ ( value ) => {
									updateGlobalSettings(
										field.id === 'site_key'
											? `${ type.site_key }`
											: `${ type.secret_key }`,
										value,
										'security-settings'
									);
								} }
							/>
						) ) }
					</Tabs.Panel>
				) ) }
			</Tabs>
		</div>
	);
};

export default Recaptcha;
