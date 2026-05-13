import { Button, Title } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';
import { LoaderCircle, Save } from 'lucide-react';

const PageTitleSection = ( {
	title,
	hidePageTitle,
	helpText,
	onSave,
	isDirty = false,
	isSaving = false,
} ) => {
	if ( ! title ) {
		return null;
	}

	// Returns both the tab slug and the compound `tab/subpage` slug so
	// the exclusion list below can target either an entire tab or one
	// specific sub-tab (e.g. only `payments-settings/payment-methods`
	// without affecting `payments-settings/general`).
	const getCurrentPageSlugs = () => {
		try {
			const searchParams = new URLSearchParams( window.location.search );
			const tab = searchParams.get( 'tab' ) || '';
			const subpage = searchParams.get( 'subpage' ) || '';
			return subpage ? [ tab, `${ tab }/${ subpage }` ] : [ tab ];
		} catch ( error ) {
			return [];
		}
	};

	// Tabs in this list have no Save semantics at the page level — either
	// they don't persist anything (OttoKit) or they manage their own save UX
	// inside the page (Integrations drawer per-row, License action buttons,
	// Payment Methods per-gateway Connect/Save inside each Stripe / PayPal
	// panel). The header Save button is hidden for them. Tabs that register
	// a save adapter via `useGlobalSettingsTabSaveAdapter` (e.g. Google
	// Maps, User Registration) DO show the header button — `Component.js`
	// routes it to the registered handler.
	// Entries may be either a top-level `tab` slug or a compound
	// `tab/subpage` slug for sub-tab-scoped exclusion.
	const exclusionList = applyFilters( 'srfm.settings.exclusionList', [
		'account-settings',
		'integration-settings',
		'ottokit-settings',
		'payments-settings/payment-methods',
	] );

	const currentSlugs = getCurrentPageSlugs();
	const showSaveButton = ! exclusionList.some( ( slug ) =>
		currentSlugs.includes( slug )
	);

	return (
		// Do not render the title section if hidePageTitle is true.
		! hidePageTitle && (
			<div className="max-w-content-container mx-auto flex justify-between items-center mb-6 gap-3">
				<div className="flex flex-col gap-1">
					<Title
						tag="h4"
						className="inline-block"
						title={ title }
						size="md"
					/>
					{ helpText && (
						<span className="text-[14px] text-[#6B7280] leading-5">
							{ helpText }
						</span>
					) }
				</div>
				{ showSaveButton && (
					<Button
						size="md"
						variant="primary"
						onClick={ onSave }
						disabled={ ! isDirty || isSaving }
						iconPosition="left"
						icon={
							isSaving ? (
								<LoaderCircle className="animate-spin size-4" />
							) : (
								<Save />
							)
						}
					>
						{ isSaving
							? __( 'Saving…', 'sureforms' )
							: __( 'Save', 'sureforms' ) }
					</Button>
				) }
			</div>
		)
	);
};

export default PageTitleSection;
