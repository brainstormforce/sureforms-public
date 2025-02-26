import { useLocation } from 'react-router-dom';
import Recaptcha from '../components/security/Recaptcha';
import Hcaptcha from '../components/security/Hcaptcha';
import Turnstile from '../components/security/Turnstile';
import Honeypot from '../components/security/Honeypot';

const SecurityPage = () => {
	const location = useLocation();

	const getCurrentPage = () => {
		try {
			const searchParams = new URLSearchParams( location.search );
			return searchParams.get( 'subpage' );
		} catch ( error ) {
			return '';
		}
	};

	switch ( getCurrentPage() ) {
		case 'recaptcha':
			return <Recaptcha />;
		case 'hcaptcha':
			return <Hcaptcha />;
		case 'turnstile':
			return <Turnstile />;
		case 'honeypot':
			return <Honeypot />;
		default:
			return null;
	}
};

export default SecurityPage;
