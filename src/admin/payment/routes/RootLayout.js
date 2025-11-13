import { Outlet } from 'react-router-dom';
import Header from '@Admin/components/Header';
import { cn } from '@Utils/Helpers';

/**
 * PageHeader Component
 *
 * @param {Object} props           Component props
 * @param {string} props.className - Additional class names for styling
 * @return {JSX.Element}            PageHeader component
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
 * Layout wrapper for payment listing page
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
