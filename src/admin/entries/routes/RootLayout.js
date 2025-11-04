import { Outlet } from 'react-router-dom';
import Header from '@Admin/components/Header';
import { cn } from '@Utils/Helpers';

/**
 * FormPageHeader Component
 *
 * @param {Object} props           Component props
 * @param {string} props.className - Additional class names for styling
 * @return {JSX.Element}            FormPageHeader component
 */
const PageHeader = ( { className } ) => {
	return (
		<div className={ cn( 'z-50 relative', className ) }>
			<Header />
		</div>
	);
};

/**
 * RootLayout Component
 * Wrapper component for all routes that provides common layout and functionality
 */
const RootLayout = () => {
	return (
		<>
			<PageHeader />
			<Outlet />
		</>
	);
};

export default RootLayout;
