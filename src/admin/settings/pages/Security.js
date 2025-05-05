import { useLocation } from 'react-router-dom';
import Recaptcha from '../components/security/Recaptcha';
import Hcaptcha from '../components/security/Hcaptcha';
import Turnstile from '../components/security/Turnstile';
import Honeypot from '../components/security/Honeypot';
import LoadingSkeleton from '@Admin/components/LoadingSkeleton';

const componentsMap = {
	recaptcha: Recaptcha,
	hcaptcha: Hcaptcha,
	turnstile: Turnstile,
	honeypot: Honeypot,
};

const SecurityPage = ( {
	loading,
	securitytabOptions,
	updateGlobalSettings,
} ) => {
	const location = useLocation();

	const getCurrentPage = () => {
		try {
			const searchParams = new URLSearchParams( location.search );
			return searchParams.get( 'subpage' );
		} catch ( error ) {
			return '';
		}
	};

	const CurrentComponent = componentsMap[ getCurrentPage() ];

	if ( ! CurrentComponent ) {
		return null;
	}

	return (
		<>
			{ loading ? (
				<div>
					<LoadingSkeleton count={ 6 } className="h-6 rounded-sm" />
				</div>
			) : (
				<CurrentComponent
					securitytabOptions={ securitytabOptions }
					updateGlobalSettings={ updateGlobalSettings }
				/>
			) }
		</>
	);
};

export default SecurityPage;
