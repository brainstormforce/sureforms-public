import { Outlet } from '@tanstack/react-router';
import { toast, Toaster } from '@bsf/force-ui';

// Expose toast globally for easy access across the admin interface
window.srfm_toast = toast;

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
