import { Button, Title } from '@bsf/force-ui';
import { __ } from '@wordpress/i18n';

const PageTitleSection = ( { title } ) => {
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

	const exclusionList = [ 'account-settings', 'integration-settings' ];

	return (
		<div className="max-w-content-container mx-auto flex items-center justify-between mb-6">
			<Title
				tag="h4"
				className="inline-block"
				title={ title }
			/>
			{ ! exclusionList.includes( getCurrentPage() ) && (
				<Button>
					{ __( 'Save', 'sureforms' ) }
				</Button>
			) }
		</div>
	);
};

export default PageTitleSection;
