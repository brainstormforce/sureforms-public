import { __ } from '@wordpress/i18n';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';
import apiFetch from '@wordpress/api-fetch';
import { useState } from '@wordpress/element';

const Suretriggers = ( props ) => {
	const { setSelectedTab, iframeUrl, setIframeUrl } = props;
	const backArrow = parse( svgIcons.leftArrow );
	const [ showAddIntegration, setShowAddIntegration ] = useState( true );

	const addIntegrations = () => {
		const formData = new window.FormData();
		formData.append( 'action', 'sureforms_test_integration' );
		formData.append( 'formId', srfm_admin.form_id );
		formData.append( 'force', true );

		apiFetch( {
			url: srfm_admin.ajax_url,
			method: 'POST',
			body: formData,
		} ).then( ( data ) => {
			if ( data.success ) {
				console.log( data.data );
				setIframeUrl( data.data.iframe_url );
				setShowAddIntegration( false );
			}
		} );
	};

	window.addEventListener(
		'message',
		function ( event ) {
			if ( event.data.payload.page === 'listing' && ! showAddIntegration ) {
				setShowAddIntegration( true );
			} else if ( event.data.payload.page === 'build' && showAddIntegration ) {
				setShowAddIntegration( false );
			}
		} );

	return (
		<div className="srfm-modal-content" style={ { height: '100%' } }>
			<div className="srfm-modal-inner-content" style={ { height: '100%' } }>
				<div className="srfm-modal-inner-heading">
					<div className="srfm-modal-inner-heading-text">
						<span onClick={ () => {
							setSelectedTab( 'integrations' );
						} } className="srfm-back-btn">{ backArrow }</span>
						<h4>{ __( 'Integrations', 'sureforms' ) }</h4>
					</div>
					{ showAddIntegration && (
						<button onClick={ addIntegrations } className="srfm-modal-inner-heading-button">
							{ __( 'Add Integration', 'sureforms' ) }
						</button>
					) }
				</div>
				<div className="srfm-modal-inner-box" style={ { height: '100%' } }>
					<div className="srfm-modal-inner-box-text">
						<h5>{ __( 'SureTriggers Integrations', 'sureforms' ) }</h5>
					</div>

					<div className="srfm-modal-separator" />
					<div style={ { width: '100%', height: '100%' } } >
						<iframe
							src={ iframeUrl }
							title="Embedded Content"
							style={ { width: '100%', height: '100%', border: 'none' } }
						/>
					</div>
				</div>
			</div>
		</div>
	);
};

export default Suretriggers;
