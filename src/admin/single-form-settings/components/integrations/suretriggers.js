import { __ } from '@wordpress/i18n';
import svgIcons from '@Image/single-form-logo.json';
import parse from 'html-react-parser';
import { useEffect } from '@wordpress/element';

const Suretriggers = ( { setSelectedTab } ) => {
	const backArrow = parse( svgIcons.leftArrow );

	useEffect( () => {
		document.dispatchEvent( new Event( 'InitSureTriggers' ) );
	}, [] );

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
				</div>
				<div className="srfm-modal-inner-box" style={ { height: '100%' } }>
					<div className="srfm-modal-inner-box-text">
						<h5>{ __( 'SureTriggers Integrations', 'sureforms' ) }</h5>
					</div>

					<div className="srfm-modal-separator" />
					<div id="suretriggers-iframe-wrapper" style={ { width: '100%', height: '100%' } } ></div>
				</div>
			</div>
		</div>
	);
};

export default Suretriggers;
