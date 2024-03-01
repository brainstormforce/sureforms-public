import { useEffect, render } from '@wordpress/element';
import Header from './components/Header.js';
import { __ } from '@wordpress/i18n';
import StartingPoint from './components/StartingPoint.js';
import ICONS from './components/icons';
import { BrowserRouter as Router, useLocation, Link } from 'react-router-dom';
import TemplateScreen from './components/TemplateScreen.js';

const TemplatePicker = () => {
	// Remove admin bar padding.
	useEffect( () => {
		document.querySelector( 'html.wp-toolbar' ).style.paddingTop = 0;
	}, [] );

	// Starting screen navigation
	function useQuery() {
		return new URLSearchParams( useLocation().search );
	}

	function QueryScreen() {
		const query = useQuery();
		const method = query.get( 'method' );
		switch ( method ) {
			case 'template':
				return (
					<>
						<Header />
						<TemplateScreen />
					</>
				);
			default:
				return (
					<div className="srfm-tp-sp-container srfm-content-section">
						<Header />
						<div className="srfm-tp-sp-methods-container">
							<div className="srfm-tp-sp-methods-inner-wrap">
								<h1 className="srfm-tp-sp-heading">
									{ __( 'Create a New Form', 'sureforms' ) }
								</h1>
								<div className="srfm-tp-sp-methods">
									<Link
										className="srfm-single-card"
										to={ `${ srfm_admin.site_url }/wp-admin/post-new.php?post_type=sureforms_form` }
										reloadDocument
									>
										<StartingPoint
											icon={ ICONS.scratch }
											title={ __(
												'Start from Scratch',
												'sureforms'
											) }
											description={ __(
												'Tailoring your form precisely to your unique needs. No coding skills required—just unleash your creativity.',
												'sureforms'
											) }
										/>
									</Link>
									<Link
										className="srfm-single-card"
										to={ {
											location: `${ srfm_admin.site_url }/wp-admin/admin.php`,
											search: `?page=add-new-form&method=template`,
										} }
									>
										<StartingPoint
											icon={ ICONS.template }
											title={ __(
												'Use a Template',
												'sureforms'
											) }
											description={ __(
												'Save time and jumpstart your form creation process with our extensive library of professionally designed templates.',
												'sureforms'
											) }
										/>
									</Link>
									<div className="srfm-single-card">
										<StartingPoint
											icon={ ICONS.ai }
											title={ __(
												'Create with AI',
												'sureforms'
											) }
											description={ __(
												'Experience the future of form building. AI-powered algorithms analyze your requirements and generate a tailor-made form.',
												'sureforms'
											) }
											isComingSoon={ true }
										/>
									</div>
								</div>
							</div>
						</div>
					</div>
				);
		}
	}

	return (
		<>
			<Router>
				<QueryScreen />
			</Router>
		</>
	);
};

export default TemplatePicker;

( function () {
	const app = document.getElementById( 'srfm-add-new-form-container' );

	function renderApp() {
		if ( null !== app ) {
			render( <TemplatePicker />, app );
		}
	}

	document.addEventListener( 'DOMContentLoaded', renderApp );
}() );
