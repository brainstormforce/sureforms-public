import { __ } from '@wordpress/i18n';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch } from '@wordpress/data';
import { ToggleControl } from '@wordpress/components';

const Compliance = ( { complianceData } ) => {
	const { editPost } = useDispatch( editorStore );

	const handleToggle = ( data ) => {
		const updatedData = { ...complianceData };

		updatedData[ 0 ][ data.id ] = data.status;

		updateMeta( '_srfm_compliance', updatedData );
	};

	console.log( complianceData );

	function updateMeta( option, value ) {
		const option_array = {};
		option_array[ option ] = value;
		editPost( {
			meta: option_array,
		} );
	}
	return (
		<div className="srfm-modal-content">
			<div className="srfm-modal-inner-content">
				<div className="srfm-modal-inner-heading">
					<span className="srfm-modal-inner-heading-text">
						<h4>{ __( 'Compliance Settings', 'sureforms' ) }</h4>
					</span>
				</div>
				<div className="srfm-modal-inner-box">
					<div className="srfm-modal-inner-box-text">
						<h5>{ __( 'GDPR Settings', 'sureforms' ) }</h5>
					</div>
					<div className="srfm-modal-separator"></div>
					<div className="srfm-modal-inner-box-table">
						<ToggleControl
							label={ __(
								'Enable GDPR Compliance',
								'sureforms'
							) }
							checked={ complianceData[ 0 ].gdpr }
							onChange={ ( value ) =>
								handleToggle( {
									id: 'gdpr',
									status: value,
								} )
							}
						/>
					</div>
				</div>
			</div>
		</div>
	);
};

export default Compliance;
