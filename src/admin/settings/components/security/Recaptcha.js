import { Button, Input, Title, Tabs } from '@bsf/force-ui';
import { __, sprintf } from '@wordpress/i18n';
import { useState, useEffect } from '@wordpress/element';

// Mirror Global_Settings::sanitize_recaptcha_v3_threshold(): clamp to 0-1, round to 2 decimals, 0.5 when not a finite number.
const normalizeThreshold = ( value ) => {
	const parsed = parseFloat( value );
	if ( ! Number.isFinite( parsed ) ) {
		return 0.5;
	}
	return (
		Math.round(
			( Math.min( 1, Math.max( 0, parsed ) ) + Number.EPSILON ) * 100
		) / 100
	);
};

const RECAPTCHA_TYPES = [
	{
		label: __( 'reCAPTCHA v2', 'sureforms' ),
		value: 'v2',
		site_key: 'srfm_v2_checkbox_site_key',
		secret_key: 'srfm_v2_checkbox_secret_key',
	},
	{
		label: __( 'reCAPTCHA v2 Invisible', 'sureforms' ),
		value: 'invisible',
		site_key: 'srfm_v2_invisible_site_key',
		secret_key: 'srfm_v2_invisible_secret_key',
	},
	{
		label: __( 'reCAPTCHA v3', 'sureforms' ),
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
	const storedThreshold = securitytabOptions.srfm_v3_score_threshold ?? 0.5;
	// The typed text, kept apart from the stored number so "0." survives while typing.
	const [ thresholdInput, setThresholdInput ] = useState(
		String( storedThreshold )
	);

	// Follow external changes (settings load, discard) without clobbering text that already parses to the stored value.
	useEffect( () => {
		setThresholdInput( ( input ) =>
			Number( input ) === storedThreshold ? input : String( storedThreshold )
		);
	}, [ storedThreshold ] );

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
							href="https://docs.cloud.google.com/recaptcha/docs"
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
									__( 'Enter your %s here', 'sureforms' ),
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
						{ 'v3' === type.value && (
							<div className="space-y-1.5">
								<Input
									type="number"
									label={ __( 'Score Threshold', 'sureforms' ) }
									name="srfm_v3_score_threshold"
									size="md"
									min={ 0 }
									max={ 1 }
									step={ 0.1 }
									aria-describedby="srfm-v3-score-threshold-help"
									value={ thresholdInput }
									onChange={ ( value ) => {
										setThresholdInput( value );
										// Store a number, never the typed string, so retyping the saved value leaves Save inactive.
										const parsed = Number( value );
										if ( '' !== value && Number.isFinite( parsed ) ) {
											updateGlobalSettings(
												'srfm_v3_score_threshold',
												parsed,
												'security-settings'
											);
										}
									} }
									onBlur={ () => {
										const normalized =
											normalizeThreshold( thresholdInput );
										setThresholdInput( String( normalized ) );
										if ( normalized !== storedThreshold ) {
											updateGlobalSettings(
												'srfm_v3_score_threshold',
												normalized,
												'security-settings'
											);
										}
									} }
								/>
								<p
									id="srfm-v3-score-threshold-help"
									className="text-xs text-text-tertiary m-0"
								>
									{ __(
										'Submissions that Google scores below this value are blocked. Scores run from 0.0 (likely a bot) to 1.0 (likely a human). Default is 0.5. Set 0 to accept any score.',
										'sureforms'
									) }
								</p>
							</div>
						) }
					</Tabs.Panel>
				) ) }
			</Tabs>
		</div>
	);
};

export default Recaptcha;
