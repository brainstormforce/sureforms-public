import { Outlet } from '@tanstack/react-router';
import { Toaster } from '@bsf/force-ui';

/**
 * RootLayout Component
 * Wrapper component for all routes that provides common layout and functionality
 */
const RootLayout = () => {
	return (
		<>
			<Outlet />
			<Toaster className="z-999999" />
		</>
	);
};

export default RootLayout;
