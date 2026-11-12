import { createRoot, useEffect, useState } from '@wordpress/element';
import { BrowserRouter as Router, useLocation } from 'react-router-dom';
import FormPageHeader from '../components/PageHeader';
import '../tw-base.scss';

import Navigation, { isHiddenSettingsTab } from './Navigation';
import Component from './Component';
import UnsavedChangesGuard from './UnsavedChangesGuard';
import { Toaster } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';

function useQuery() {
	return new URLSearchParams( useLocation().search );
}

function QueryScreen() {
	const query = useQuery();
	const tab = query.get( 'tab' );
	return (
		<Component
			path={ isHiddenSettingsTab( tab ) ? 'general-settings' : tab }
			subpage={ query.get( 'subpage' ) }
		/>
	);
}

function useAdminMenuState() {
	const [ isMenuFolded, setIsMenuFolded ] = useState( false );
	const [ adminMenuWidth, setAdminMenuWidth ] = useState( 160 );

	useEffect( () => {
		const checkMenuState = () => {
			const bodyEl = document.body;
			const isFolded = bodyEl.classList.contains( 'folded' );
			const isAutoFold = bodyEl.classList.contains( 'auto-fold' );
			const isSmallScreen = window.innerWidth <= 960;

			setIsMenuFolded( isFolded || ( isAutoFold && isSmallScreen ) );

			// Get actual admin menu width from the DOM element to support plugins that modify it.
			const adminMenu = document.getElementById( 'adminmenuback' );
			const actualWidth = adminMenu
				? adminMenu.getBoundingClientRect().width
				: 0;

			// Use measured width if valid, otherwise fall back to defaults.
			if ( actualWidth > 0 ) {
				setAdminMenuWidth( actualWidth );
			} else if ( isFolded || ( isAutoFold && isSmallScreen ) ) {
				setAdminMenuWidth( 36 );
			} else {
				setAdminMenuWidth( 160 );
			}
		};

		checkMenuState();

		const observer = new MutationObserver( checkMenuState );
		observer.observe( document.body, {
			attributes: true,
			attributeFilter: [ 'class' ],
		} );

		// Also observe the admin menu for style/width changes from plugins.
		const adminMenu = document.getElementById( 'adminmenuback' );
		let menuObserver;
		if ( adminMenu ) {
			menuObserver = new MutationObserver( checkMenuState );
			menuObserver.observe( adminMenu, {
				attributes: true,
				attributeFilter: [ 'style', 'class' ],
			} );
		}

		window.addEventListener( 'resize', checkMenuState );

		return () => {
			observer.disconnect();
			if ( menuObserver ) {
				menuObserver.disconnect();
			}
			window.removeEventListener( 'resize', checkMenuState );
		};
	}, [] );

	return { isMenuFolded, adminMenuWidth };
}

const Settings = () => {
	const isRTL = srfm_admin?.is_rtl;
	const { adminMenuWidth } = useAdminMenuState();
	const toasterPosition = isRTL ? 'top-left' : 'top-right';

	const backgroundStyle = {
		'--bg-width': `${ adminMenuWidth + 240 }px`,
	};

	return (
		<>
			<Router>
				<FormPageHeader />
				<div
					className="grid grid-cols-[15rem_1fr] auto-rows-fr min-h-screen bg-background-secondary before:content-['_'] before:fixed before:inset-0 before:h-full before:w-[var(--bg-width)] before:bg-background-primary before:shadow-sm rtl:before:right-0 rtl:before:left-auto"
					style={ backgroundStyle }
				>
					<Navigation />
					<div className="max-h-full h-full overflow-y-auto">
						<div className="p-8">
							<QueryScreen />
						</div>
					</div>
				</div>
				<UnsavedChangesGuard />
			</Router>
			<Toaster
				className={ cn(
					'z-[999999]',
					isRTL
						? '[&>li>div>div.absolute]:right-auto [&>li>div>div.absolute]:left-[0.75rem!important]'
						: ''
				) }
				position={ toasterPosition }
			/>
		</>
	);
};

export default Settings;

( function () {
	const app = document.getElementById( 'srfm-settings-container' );

	function renderApp() {
		if ( null !== app ) {
			const root = createRoot( app );
			root.render( <Settings /> );
		}
	}

	// Pro (and third-party) bundles register their settings-page filters
	// (`addFilter( 'srfm.settings.navigation', ... )`, page content, etc.) at
	// module-eval time. Those bundles are classic, parser-blocking footer
	// scripts that load AFTER this one, so we must not render until every one
	// of them has executed — otherwise pro-injected tabs are missing from the
	// first (and only) paint.
	//
	// `setTimeout( 0 )` is NOT a safe barrier here: it fires as soon as the
	// parser yields (e.g. while a later footer script is still downloading),
	// which can be before those scripts run. That race is why an early bundle
	// like the License tab always appears while a later one — Login/Registration
	// — intermittently does not.
	//
	// `DOMContentLoaded` fires only after the document is fully parsed and all
	// parser-blocking scripts have executed, so every dependent filter is
	// guaranteed to be registered by then. Fall back to `setTimeout( 0 )` if the
	// document has already finished parsing (defensive; not expected for a
	// footer script).
	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', renderApp );
	} else {
		setTimeout( renderApp, 0 );
	}
}() );
