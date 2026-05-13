import {
	useState,
	useEffect,
	useCallback,
	useMemo,
	useRef,
} from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import { toast } from '@bsf/force-ui';
import { useSelect, useDispatch } from '@wordpress/data';
import { cn } from '@Utils/Helpers';
import { getNavigation } from './Navigation';
import { STORE_NAME as SRFM_STORE_NAME } from '../../store/constants';
import { getTabSaveHandler } from './tab-save-adapter';
import GeneralPage from './pages/General';
import GlobalDefaultsPage from './pages/GlobalDefaults';
import ValidationsPage from './pages/Validations';
import SecurityPage from './pages/Security';
import IntegrationPage from './pages/Integrations';
import GoogleMapsPage from './pages/GoogleMaps';
import PaymentsPage from '../payment/global-setting-page';
import MCPPage from './pages/MCP';
import OttoKitPage from './pages/OttoKit';
import { applyFilters } from '@wordpress/hooks';
import PageTitleSection from '@Admin/components/PageTitleSection';

const Component = ( { path, subpage } ) => {
	const [ pageTitle, setPageTitle ] = useState( '' );
	const [ hidePageTitle, setHidePageTitle ] = useState( false );
	const [ loading, setLoading ] = useState( true );
	const [ helpText, setHelpText ] = useState( '' );

	// Global settings states — partitioned per save-tab id.
	const [ generalTabOptions, setGeneralTabOptions ] = useState( {
		srfm_ip_log: false,
		srfm_form_analytics: false,
		srfm_bsf_analytics: false,
		srfm_admin_notification: true,
	} );
	const [ emailTabOptions, setEmailTabOptions ] = useState( {
		srfm_email_summary: false,
		srfm_email_sent_to: srfm_admin.admin_email,
		srfm_schedule_report: 'Monday',
	} );
	const [ securitytabOptions, setSecurityTabOptions ] = useState( {
		srfm_v2_checkbox_site_key: '',
		srfm_v2_checkbox_secret_key: '',
		srfm_v2_invisible_site_key: '',
		srfm_v2_invisible_secret_key: '',
		srfm_v3_site_key: '',
		srfm_v3_secret_key: '',
		srfm_cf_appearance_mode: 'auto',
		srfm_cf_turnstile_site_key: '',
		srfm_cf_turnstile_secret_key: '',
		srfm_hcaptcha_site_key: '',
		srfm_hcaptcha_secret_key: '',
		srfm_honeypot: false,
	} );
	const [ mcpTabOptions, setMcpTabOptions ] = useState( {
		srfm_abilities_api: false,
		srfm_abilities_api_edit: false,
		srfm_abilities_api_delete: false,
		srfm_mcp_server: false,
	} );
	const [ dynamicBlockOptions, setDynamicBlockOptions ] = useState( {} );
	const [ paymentsSettings, setPaymentsSettings ] = useState( {} );
	const [ pluginConnected, setPluginConnected ] = useState(
		srfm_admin?.integrations?.sure_triggers?.connected ?? null
	);
	const [ localPluginStatus, setLocalPluginStatus ] = useState(
		srfm_admin?.integrations?.sure_triggers?.status
	);
	const [ formRestrictionOptions, setFormRestrictionOptions ] = useState( {
		max_entries: { status: false, maxEntries: 0, message: '' },
		ip_restriction: {
			status: false,
			mode: 'block',
			ips: '',
			includeDisallowedKeys: false,
			message: '',
		},
		country_restriction: {
			status: false,
			mode: 'block',
			countries: '',
			message: '',
		},
		keyword_restriction: {
			status: false,
			keywords: '',
			includeDisallowedKeys: false,
			message: '',
		},
	} );
	const [ complianceOptions, setComplianceOptions ] = useState( {
		gdpr: false,
		do_not_store_entries: false,
		auto_delete_entries: false,
		auto_delete_days: 30,
	} );
	const [ formConfirmationOptions, setFormConfirmationOptions ] = useState( {
		confirmation_type: 'same page',
		message:
			window.srfm_admin?.default_confirmation_message ||
			__(
				'Thank you for contacting us! We will be in touch with you shortly.',
				'sureforms'
			),
		submission_action: 'hide form',
		page_url: '',
		custom_url: '',
		enable_query_params: false,
		query_params: [],
	} );
	const [ emailNotificationOptions, setEmailNotificationOptions ] = useState( {
		email_to: '{admin_email}',
		subject: sprintf(
			/* translators: %s: {form_title} smart tag placeholder. */
			__( 'New Form Submission - %s', 'sureforms' ),
			'{form_title}'
		),
		email_body: '{all_data}',
		from_name: '{site_title}',
		from_email: '{admin_email}',
		email_cc: '{admin_email}',
		email_bcc: '{admin_email}',
		email_reply_to: '{admin_email}',
	} );

	// Per-save-tab baselines. Populated after the initial fetch resolves;
	// updated per-tab after a successful save. Used to compute dirty state
	// and to revert via window.__srfm_global_settings_discard on guard confirm.
	const [ savedBaselines, setSavedBaselines ] = useState( {
		'general-settings': null,
		'email-settings': null,
		'security-settings': null,
		'mcp-settings': null,
		'general-settings-dynamic-opt': null,
		'payments-settings': null,
		'form-restriction-settings': null,
		'compliance-settings': null,
		'form-confirmation-settings': null,
		'email-notification-settings': null,
	} );
	const [ isSaving, setIsSaving ] = useState( false );

	// Ref always holds the latest state slices so useCallback closures never go stale.
	const settingsStateRef = useRef( {} );
	settingsStateRef.current = {
		emailTabOptions,
		generalTabOptions,
		securitytabOptions,
		mcpTabOptions,
		dynamicBlockOptions,
		paymentsSettings,
		formRestrictionOptions,
		complianceOptions,
		formConfirmationOptions,
		emailNotificationOptions,
	};

	// Stable refs to setter functions.
	const tabSetters = useRef( {
		'email-settings': setEmailTabOptions,
		'general-settings': setGeneralTabOptions,
		'security-settings': setSecurityTabOptions,
		'mcp-settings': setMcpTabOptions,
		'general-settings-dynamic-opt': setDynamicBlockOptions,
		'payments-settings': setPaymentsSettings,
		'form-restriction-settings': setFormRestrictionOptions,
		'compliance-settings': setComplianceOptions,
		'form-confirmation-settings': setFormConfirmationOptions,
		'email-notification-settings': setEmailNotificationOptions,
	} );

	// Map from save-tab slug to the state key in settingsStateRef.
	const stateKeys = useRef( {
		'email-settings': 'emailTabOptions',
		'general-settings': 'generalTabOptions',
		'security-settings': 'securitytabOptions',
		'mcp-settings': 'mcpTabOptions',
		'general-settings-dynamic-opt': 'dynamicBlockOptions',
		'payments-settings': 'paymentsSettings',
		'form-restriction-settings': 'formRestrictionOptions',
		'compliance-settings': 'complianceOptions',
		'form-confirmation-settings': 'formConfirmationOptions',
		'email-notification-settings': 'emailNotificationOptions',
	} );

	const optionsToFetch = [
		'srfm_general_settings_options',
		'srfm_email_summary_settings_options',
		'srfm_security_settings_options',
		'srfm_default_dynamic_block_option',
		'srfm_mcp_settings_options',
		'srfm_form_restriction_settings_options',
		'srfm_compliance_settings_options',
		'srfm_form_confirmation_settings_options',
		'srfm_email_notification_settings_options',
	];

	// Set page title and icon based on the path / subpage.
	useEffect( () => {
		if ( path ) {
			getNavigation().forEach( ( single ) => {
				const slug = single?.slug && single.slug ? single.slug : '';
				let title = single?.name && single.name ? single.name : '';
				// eslint-disable-next-line no-shadow
				const helpText =
					single?.helpText && single.helpText ? single.helpText : '';
				const hideTitle =
					slug === 'google-maps-settings' && srfm_admin?.is_pro_active
						? false
						: !! single?.hidePageTitle;
				if ( slug && slug === path ) {
					if ( single.submenu ) {
						const currentSubpage =
							subpage || single.submenu[ 0 ]?.slug;
						const submenuItem = single.submenu.find(
							( item ) => item.slug === currentSubpage
						);
						if ( submenuItem ) {
							title = submenuItem.name;
						}
					}
					setPageTitle( title );
					setHidePageTitle( hideTitle );
					setHelpText( helpText );
				}
			} );
		}
	}, [ path, subpage ] );

	// Fetch global settings + seed baselines.
	useEffect( () => {
		const fetchData = async () => {
			setLoading( true );
			try {
				const data = await apiFetch( {
					path: `sureforms/v1/srfm-global-settings?options_to_fetch=${ optionsToFetch }`,
					method: 'GET',
					headers: {
						'content-type': 'application/json',
						'X-WP-Nonce': srfm_admin.global_settings_nonce,
					},
				} );

				const nextBaselines = {
					'general-settings': null,
					'email-settings': null,
					'security-settings': null,
					'mcp-settings': null,
					'general-settings-dynamic-opt': null,
					'payments-settings': null,
					'form-restriction-settings': null,
					'compliance-settings': null,
					'form-confirmation-settings': null,
					'email-notification-settings': null,
				};

				if ( data.srfm_general_settings_options ) {
					const {
						srfm_ip_log,
						srfm_form_analytics,
						srfm_bsf_analytics,
						srfm_admin_notification,
					} = data.srfm_general_settings_options;
					const snap = {
						srfm_ip_log,
						srfm_form_analytics,
						srfm_bsf_analytics,
						srfm_admin_notification,
					};
					setGeneralTabOptions( snap );
					nextBaselines[ 'general-settings' ] = snap;
				}

				if ( data.srfm_email_summary_settings_options ) {
					const {
						srfm_email_summary,
						srfm_email_sent_to,
						srfm_schedule_report,
					} = data.srfm_email_summary_settings_options;
					const snap = {
						srfm_email_summary,
						srfm_email_sent_to,
						srfm_schedule_report,
					};
					setEmailTabOptions( snap );
					nextBaselines[ 'email-settings' ] = snap;
				}

				if ( data.srfm_security_settings_options ) {
					const snap = { ...data.srfm_security_settings_options };
					setSecurityTabOptions( snap );
					nextBaselines[ 'security-settings' ] = snap;
				}

				if ( data.srfm_mcp_settings_options ) {
					const snap = { ...data.srfm_mcp_settings_options };
					setMcpTabOptions( snap );
					nextBaselines[ 'mcp-settings' ] = snap;
				}

				if ( data.srfm_default_dynamic_block_option ) {
					const snap = { ...data.srfm_default_dynamic_block_option };
					setDynamicBlockOptions( snap );
					nextBaselines[ 'general-settings-dynamic-opt' ] = snap;
				}

				if ( data.payment_settings ) {
					// Clone before storing as baseline so an in-place
					// mutation downstream can't poison the comparison
					// reference used by `isTabDirty`. Matches the
					// spread-on-assign pattern every other branch uses.
					const snap = { ...data.payment_settings };
					setPaymentsSettings( snap );
					nextBaselines[ 'payments-settings' ] = snap;
				}

				if ( data.srfm_form_restriction_settings_options ) {
					const snap = {
						...data.srfm_form_restriction_settings_options,
					};
					setFormRestrictionOptions( snap );
					nextBaselines[ 'form-restriction-settings' ] = snap;
				}

				if ( data.srfm_compliance_settings_options ) {
					const snap = { ...data.srfm_compliance_settings_options };
					setComplianceOptions( snap );
					nextBaselines[ 'compliance-settings' ] = snap;
				}

				if ( data.srfm_form_confirmation_settings_options ) {
					const snap = {
						...data.srfm_form_confirmation_settings_options,
					};
					setFormConfirmationOptions( snap );
					nextBaselines[ 'form-confirmation-settings' ] = snap;
				}

				if ( data.srfm_email_notification_settings_options ) {
					const snap = {
						...data.srfm_email_notification_settings_options,
					};
					setEmailNotificationOptions( snap );
					nextBaselines[ 'email-notification-settings' ] = snap;
				}

				setSavedBaselines( nextBaselines );
				setLoading( false );
			} catch ( error ) {
				toast.error(
					__(
						'Failed to load settings. Please refresh and try again.',
						'sureforms'
					)
				);
			}
		};

		fetchData();
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// Update only local state on every change. Persistence is deferred to
	// the explicit Save button (saveAllDirtyTabs) so users can review and
	// undo before committing.
	const updateGlobalSettings = useCallback( ( setting, value, tab ) => {
		const setter = tabSetters.current[ tab ];
		const stateKey = stateKeys.current[ tab ];
		if ( ! setter || ! stateKey ) {
			return;
		}
		const changes =
			setting !== null && typeof setting === 'object'
				? setting
				: { [ setting ]: value };
		const updatedTabOptions = {
			...settingsStateRef.current[ stateKey ],
			...changes,
		};
		setter( updatedTabOptions );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [] );

	// Map save-tab slug → current local state slice.
	const tabValues = {
		'general-settings': generalTabOptions,
		'email-settings': emailTabOptions,
		'security-settings': securitytabOptions,
		'mcp-settings': mcpTabOptions,
		'general-settings-dynamic-opt': dynamicBlockOptions,
		'payments-settings': paymentsSettings,
		'form-restriction-settings': formRestrictionOptions,
		'compliance-settings': complianceOptions,
		'form-confirmation-settings': formConfirmationOptions,
		'email-notification-settings': emailNotificationOptions,
	};

	// Shallow JSON-equality diff against the saved baseline.
	const isTabDirty = ( tab ) => {
		const baseline = savedBaselines[ tab ];
		if ( ! baseline ) {
			return false;
		}
		const current = tabValues[ tab ];
		const keys = new Set( [
			...Object.keys( baseline ),
			...Object.keys( current ),
		] );
		for ( const key of keys ) {
			if (
				JSON.stringify( baseline[ key ] ) !==
				JSON.stringify( current[ key ] )
			) {
				return true;
			}
		}
		return false;
	};

	const dirtyTabs = useMemo(
		() => Object.keys( tabValues ).filter( ( t ) => isTabDirty( t ) ),
		// eslint-disable-next-line react-hooks/exhaustive-deps
		[
			generalTabOptions,
			emailTabOptions,
			securitytabOptions,
			mcpTabOptions,
			dynamicBlockOptions,
			paymentsSettings,
			formRestrictionOptions,
			complianceOptions,
			formConfirmationOptions,
			emailNotificationOptions,
			savedBaselines,
		]
	);
	const isAnyTabDirty = dirtyTabs.length > 0;

	// Save every dirty tab in parallel.
	const saveAllDirtyTabs = useCallback( async () => {
		if ( ! isAnyTabDirty || isSaving ) {
			return;
		}

		// Validations tab disallows blank strings — surface a targeted error
		// rather than partially committing.
		if ( dirtyTabs.includes( 'general-settings-dynamic-opt' ) ) {
			const hasEmptyValue = Object.values( dynamicBlockOptions ).some(
				( value ) => typeof value === 'string' && value.trim() === ''
			);
			if ( hasEmptyValue ) {
				toast.error(
					__(
						'Form Validation fields cannot be left blank.',
						'sureforms'
					)
				);
				return;
			}
		}

		// Email Summary recipient required + email-regex when summaries are on.
		if (
			dirtyTabs.includes( 'email-settings' ) &&
			emailTabOptions?.srfm_email_summary
		) {
			const recipient = (
				emailTabOptions?.srfm_email_sent_to || ''
			).trim();
			if ( '' === recipient ) {
				toast.error(
					__(
						'Recipient email is required when email summaries are enabled.',
						'sureforms'
					)
				);
				return;
			}
			if ( ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( recipient ) ) {
				toast.error(
					__(
						'Please enter a valid recipient email.',
						'sureforms'
					)
				);
				return;
			}
		}

		setIsSaving( true );

		const requests = dirtyTabs.map( ( tab ) => {
			// `srfm_tab` is a backward-compat marker the legacy save
			// handler uses to decide which option group to write. Safe
			// to remove once the backend dispatches purely on the
			// option keys present in the body.
			const body = { ...tabValues[ tab ], srfm_tab: tab };
			return apiFetch( {
				path: 'sureforms/v1/srfm-global-settings',
				method: 'POST',
				body: JSON.stringify( body ),
				headers: {
					'content-type': 'application/json',
					'X-WP-Nonce': srfm_admin.global_settings_nonce,
				},
			} )
				.then( () => ( {
					tab,
					ok: true,
					snapshot: tabValues[ tab ],
				} ) )
				.catch( ( error ) => ( { tab, ok: false, error } ) );
		} );

		const results = await Promise.all( requests );

		const succeeded = results.filter( ( r ) => r.ok );
		const failed = results.filter( ( r ) => ! r.ok );

		if ( succeeded.length > 0 ) {
			setSavedBaselines( ( prev ) => {
				const next = { ...prev };
				succeeded.forEach( ( { tab, snapshot } ) => {
					next[ tab ] = snapshot;
				} );
				return next;
			} );
		}

		if ( failed.length === 0 ) {
			toast.success( __( 'Settings saved.', 'sureforms' ) );
		} else if ( succeeded.length === 0 ) {
			toast.error( __( 'Failed to save settings.', 'sureforms' ) );
		} else {
			toast.error(
				__(
					'Some settings failed to save. Please retry.',
					'sureforms'
				)
			);
		}

		setIsSaving( false );
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [
		dirtyTabs,
		isAnyTabDirty,
		isSaving,
		dynamicBlockOptions,
		emailTabOptions,
	] );

	// Native browser confirmation when closing the tab/window with unsaved
	// changes.
	useEffect( () => {
		if ( ! isAnyTabDirty ) {
			return undefined;
		}
		const handler = ( e ) => {
			e.preventDefault();
			e.returnValue = '';
		};
		window.addEventListener( 'beforeunload', handler );
		return () => window.removeEventListener( 'beforeunload', handler );
	}, [ isAnyTabDirty ] );

	// Push the free-tab dirty signal into Redux so the tab-switch guard
	// (`UnsavedChangesGuard`) and any other observer can react without
	// holding a reference to this component.
	const {
		setGlobalSettingsPageDirty,
		requestGlobalSettingsExtensionSave,
	} = useDispatch( SRFM_STORE_NAME );
	useEffect( () => {
		setGlobalSettingsPageDirty( isAnyTabDirty );
	}, [ isAnyTabDirty, setGlobalSettingsPageDirty ] );

	// Aggregate dirty + isSaving contributions from filter-driven
	// extension panels (pro IP/Country/Keyword Defaults under Form
	// Restrictions, etc.). OR-folded into the header Save button's
	// `isDirty` / `isSaving` below so the button activates on extension
	// edits AND shows its spinner while an extension panel is
	// mid-POST. Extensions push booleans via
	// `setGlobalSettingsExtensionDirty(slot, dirty)` /
	// `setGlobalSettingsExtensionSaving(slot, saving)`.
	const extensionsDirty = useSelect(
		( select ) =>
			select( SRFM_STORE_NAME )
				?.selectGlobalSettingsExtensionsDirtyAggregate?.() || false,
		[]
	);
	const extensionsSaving = useSelect(
		( select ) =>
			select( SRFM_STORE_NAME )
				?.selectGlobalSettingsExtensionsSavingAggregate?.() || false,
		[]
	);

	// Expose a discard callback on `window` so the guard can revert
	// free-tab state to baselines before navigating. Pro tabs don't need
	// this — they unmount on tab-switch and lose state implicitly.
	useEffect( () => {
		window.__srfm_global_settings_discard = () => {
			if ( savedBaselines[ 'general-settings' ] ) {
				setGeneralTabOptions( savedBaselines[ 'general-settings' ] );
			}
			if ( savedBaselines[ 'email-settings' ] ) {
				setEmailTabOptions( savedBaselines[ 'email-settings' ] );
			}
			if ( savedBaselines[ 'security-settings' ] ) {
				setSecurityTabOptions( savedBaselines[ 'security-settings' ] );
			}
			if ( savedBaselines[ 'mcp-settings' ] ) {
				setMcpTabOptions( savedBaselines[ 'mcp-settings' ] );
			}
			if ( savedBaselines[ 'general-settings-dynamic-opt' ] ) {
				setDynamicBlockOptions(
					savedBaselines[ 'general-settings-dynamic-opt' ]
				);
			}
			if ( savedBaselines[ 'payments-settings' ] ) {
				setPaymentsSettings( savedBaselines[ 'payments-settings' ] );
			}
			if ( savedBaselines[ 'form-restriction-settings' ] ) {
				setFormRestrictionOptions(
					savedBaselines[ 'form-restriction-settings' ]
				);
			}
			if ( savedBaselines[ 'compliance-settings' ] ) {
				setComplianceOptions( savedBaselines[ 'compliance-settings' ] );
			}
			if ( savedBaselines[ 'form-confirmation-settings' ] ) {
				setFormConfirmationOptions(
					savedBaselines[ 'form-confirmation-settings' ]
				);
			}
			if ( savedBaselines[ 'email-notification-settings' ] ) {
				setEmailNotificationOptions(
					savedBaselines[ 'email-notification-settings' ]
				);
			}
		};
		return () => {
			delete window.__srfm_global_settings_discard;
		};
	}, [ savedBaselines ] );

	// If a tab component (e.g. a pro tab) has registered its own save
	// adapter, route the header Save button to it instead of the free
	// auto-save flow. The adapter exposes primitive isDirty/isSaving via
	// Redux and the onSave function via the tab-save-adapter registry.
	const tabAdapter = useSelect(
		( select ) =>
			select( SRFM_STORE_NAME )?.selectGlobalSettingsTabAdapter
				? select( SRFM_STORE_NAME ).selectGlobalSettingsTabAdapter(
					path
				  )
				: null,
		[ path ]
	);

	// Wrap the base save handler so a single header click also pings
	// every registered extension panel (via the
	// `extensionSaveCounter` Redux counter). Pro panels subscribe and
	// fire their own POSTs in parallel with whatever the host runs —
	// no need for the host to know each panel's REST surface or
	// payload shape.
	const baseHeaderOnSave = tabAdapter
		? () => {
			const handler = getTabSaveHandler( path );
			if ( handler ) {
				handler();
			}
		  }
		: saveAllDirtyTabs;
	const headerOnSave = () => {
		// Scope the bump to the currently-active page so extensions
		// registered to a different tab don't fire their POST. Extensions
		// read `selectGlobalSettingsExtensionSaveTab` and bail out when
		// the slug doesn't match.
		requestGlobalSettingsExtensionSave( path );
		baseHeaderOnSave();
	};
	const headerIsDirty = tabAdapter
		? tabAdapter.isDirty || extensionsDirty
		: isAnyTabDirty || extensionsDirty;
	const headerIsSaving =
		( tabAdapter ? tabAdapter.isSaving : isSaving ) || extensionsSaving;

	const pathsForFullWidth = [ 'ottokit-settings', 'integration-settings' ];
	const isFullWidth =
		pathsForFullWidth.includes( path ) ||
		( 'google-maps-settings' === path && ! srfm_admin?.is_pro_active );

	return (
		<>
			{ pageTitle && (
				<PageTitleSection
					title={ pageTitle }
					hidePageTitle={ hidePageTitle }
					helpText={ helpText }
					onSave={ headerOnSave }
					isDirty={ headerIsDirty }
					isSaving={ headerIsSaving }
				/>
			) }
			<div
				className={ cn(
					'mx-auto p-4 rounded-xl bg-background-primary shadow-sm',
					isFullWidth ? 'w-full' : 'max-w-content-container'
				) }
			>
				{ 'general-settings' === path && (
					<GeneralPage
						loading={ loading }
						emailTabOptions={ emailTabOptions }
						generalTabOptions={ generalTabOptions }
						updateGlobalSettings={ updateGlobalSettings }
					/>
				) }
				{ 'global-defaults' === path && (
					<GlobalDefaultsPage
						loading={ loading }
						formRestrictionOptions={ formRestrictionOptions }
						complianceOptions={ complianceOptions }
						emailNotificationOptions={ emailNotificationOptions }
						formConfirmationOptions={ formConfirmationOptions }
						updateGlobalSettings={ updateGlobalSettings }
						toast={ toast }
					/>
				) }
				{ 'validation-settings' === path && (
					<ValidationsPage
						loading={ loading }
						dynamicBlockOptions={ dynamicBlockOptions }
						updateGlobalSettings={ updateGlobalSettings }
					/>
				) }
				{ 'security-settings' === path && (
					<SecurityPage
						loading={ loading }
						securitytabOptions={ securitytabOptions }
						generalTabOptions={ generalTabOptions }
						updateGlobalSettings={ updateGlobalSettings }
					/>
				) }

				{ 'mcp-settings' === path && (
					<MCPPage
						loading={ loading }
						mcpTabOptions={ mcpTabOptions }
						updateGlobalSettings={ updateGlobalSettings }
					/>
				) }
				{ 'ottokit-settings' === path && (
					<OttoKitPage
						loading={ loading }
						pluginConnected={ pluginConnected }
						setPluginConnected={ setPluginConnected }
						localPluginStatus={ localPluginStatus }
						setLocalPluginStatus={ setLocalPluginStatus }
					/>
				) }

				{ 'integration-settings' === path && (
					<IntegrationPage loading={ loading } />
				) }
				{ 'google-maps-settings' === path && (
					<GoogleMapsPage loading={ loading } toast={ toast } />
				) }
				{ 'payments-settings' === path && (
					<PaymentsPage
						loading={ loading }
						paymentsSettings={ paymentsSettings }
						updateGlobalSettings={ updateGlobalSettings }
						setPaymentsSettings={ setPaymentsSettings }
						subpage={ subpage }
					/>
				) }
				{ applyFilters(
					'srfm.settings.page.content',
					[],
					path,
					loading,
					toast
				) }
			</div>
		</>
	);
};

export default Component;
