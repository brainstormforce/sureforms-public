import { useEffect, render } from '@wordpress/element';
import Header from './components/Header.js';
import { __ } from '@wordpress/i18n';
import StartingPoint from './components/StartingPoint.js';
import ICONS from './components/icons';
import { BrowserRouter as Router, useLocation } from 'react-router-dom';
// import TemplateScreen from './components/TemplateScreen.js';
import AiFormBuilder from './components/AiFormBuilder.js';
import AiAuthScreen from './components/AiAuthScreen.js';

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
			/* case 'template':
				return (
					<>
						<Header />
						<TemplateScreen />
					</>
				); */
			case 'ai':
				return (
					<>
						{ srfm_admin.is_authorized ? (
							<AiFormBuilder />
						) : (
							<AiAuthScreen />
						) }
					</>
				);

			default:
				return (
					<div className="srfm-tp-sp-container srfm-content-section">
						<Header />
						<div className="srfm-tp-sp-methods-container">
							<div className="srfm-tp-sp-methods-inner-wrap">
								<div className="srfm-tp-sp-heading-container">
									<h1 className="srfm-tp-sp-heading">
										{ __( 'How would you like to create a new form?', 'sureforms' ) }
									</h1>
								</div>
								<div className="srfm-tp-sp-methods">
									<div className="srfm-single-card">
										<StartingPoint
											icon={ ICONS.scratch }
											title={ __(
												'Build Form From Scratch',
												'sureforms'
											) }
											description={ __(
												'Tailor your form precisely to your unique needs. No coding skills required—just unleash your creativity.',
												'sureforms'
											) }
											isSecondary={ true }
											btnText={ __( 'Build From Scratch', 'sureforms' ) }
											btnLink={ `${ srfm_admin.site_url }/wp-admin/post-new.php?post_type=sureforms_form` }
										/>
									</div>
									{ /* <Link
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
									</Link> */ }
									<div className="srfm-single-card">
										<StartingPoint
											icon={ ICONS.ai }
											title={ __(
												'Generate Form with AI',
												'sureforms'
											) }
											description={ __(
												'Experience the future of form building with AI-powered forms. Use AI to build your form 10x faster.',
												'sureforms'
											) }
											btnText={
												<>
													<span>{ __( 'Try the AI FormBuilder', 'sureforms' ) }</span>
													<span className="srfm-btn-arrow">{ ICONS.arrowRight }</span>
												</>
											}
											btnLink={ `${ srfm_admin.site_url }/wp-admin/admin.php?page=add-new-form&method=ai` }
										/>
									</div>
								</div>
								<div className="srfm-exit-link-container">
									<div className="srfm-exit-link-content" onClick={ () => {
										window.location.href = '/wp-admin/admin.php?page=sureforms_menu';
									} }>
										{ ICONS.exit }
										<span>{ __( 'Exit to Dashboard', 'sureforms' ) }</span>
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
