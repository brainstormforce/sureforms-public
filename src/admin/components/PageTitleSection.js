import { Button, Title } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';
import { applyFilters } from '@wordpress/hooks';

const PageTitleSection = ( { title, hidePageTitle, helpText } ) => {
	if ( ! title ) {
		return null;
	}

	const getCurrentPage = () => {
		try {
			const searchParams = new URLSearchParams( window.location.search );
			return searchParams.get( 'tab' );
		} catch ( error ) {
			return '';
		}
	};

	const exclusionList = applyFilters( 'srfm.settings.exclusionList', [
		'account-settings',
		'integration-settings',
		'general-settings',
		'validation-settings',
		'security-settings',
		'ottokit-settings',
	] );

	return (
		// Do not render the title section if hidePageTitle is true.
		! hidePageTitle && (
			<div className="max-w-content-container mx-auto flex justify-between mb-6 flex-col gap-1">
				<Title
					tag="h4"
					className="inline-block"
					title={ title }
					size="md"
				/>
				{ ! exclusionList.includes( getCurrentPage() ) && (
					<Button>{ __( 'Save', 'sureforms' ) }</Button>
				) }
				{ helpText && (
					<span className="text-[14px] text-[#6B7280] leading-5">
						{ helpText }
					</span>
				) }
			</div>
		)
	);
};

export default PageTitleSection;
