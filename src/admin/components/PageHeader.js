import { cn } from '@Utils/Helpers';
import Header from './Header';

const FormPageHeader = ( { className } ) => {
	return (
		<div className={ cn( 'z-50 relative', className ) }>
			<Header />
		</div>
	);
};

export default FormPageHeader;
