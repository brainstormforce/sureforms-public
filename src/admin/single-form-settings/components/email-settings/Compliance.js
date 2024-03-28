import { __ } from '@wordpress/i18n';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch } from '@wordpress/data';
import { ToggleControl } from '@wordpress/components';

const Compliance = ( { complianceData } ) => {
	const { editPost } = useDispatch( editorStore );

	const handleToggle = ( data ) => {
		const updatedData = complianceData.map( ( item ) => {
			return {
				...item,
				[ data.id ]: data.status,
			};
		} );

		editPost( {
			meta: {
				_srfm_compliance: updatedData,
			},
		} );
	};

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
							help={ __(
								'When enabled this form will not store User IP, Browser Name and the Device Name in the Entries.',
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
						{ complianceData[ 0 ].gdpr && (
							<>
								<ToggleControl
									label={ __(
										'Delete entry data after form submission',
										'sureforms'
									) }
									help={ __(
										'When enabled this form will never store Entries',
										'sureforms'
									) }
									checked={
										complianceData[ 0 ].do_not_store_entries
									}
									onChange={ ( value ) =>
										handleToggle( {
											id: 'do_not_store_entries',
											status: value,
										} )
									}
								/>
							</>
						) }
					</div>
				</div>
			</div>
		</div>
	);
};

export default Compliance;
