import { __ } from '@wordpress/i18n';
import { store as editorStore } from '@wordpress/editor';
import { useDispatch } from '@wordpress/data';
import { ToggleControl, TextControl, BaseControl } from '@wordpress/components';

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
						) }
						{ ! complianceData[ 0 ].do_not_store_entries &&
							complianceData[ 0 ].gdpr && (
								<>
									<ToggleControl
										label={ __(
											'Automatically delete entries',
											'sureforms'
										) }
										help={ __(
											'When enabled this form will automatically delete entries after a certain period of time.',
											'sureforms'
										) }
										checked={
											complianceData[ 0 ]
												.auto_delete_entries
										}
										onChange={ ( value ) =>
											handleToggle( {
												id: 'auto_delete_entries',
												status: value,
											} )
										}
									/>
									{ complianceData[ 0 ]
										.auto_delete_entries && (
										<div>
											<label class="components-flex-item components-flex-block components-toggle-control__label">
												{ __(
													'Specify how many days old entries will be deleted for this form',
													'sureforms'
												) }
											</label>
											<TextControl
												type="number"
												style={ {
													width: '17%',
													marginTop: 'calc(8px)',
													fontSize: '12px',
												} }
												value={
													complianceData[ 0 ]
														.auto_delete_days
												}
												onChange={ ( value ) =>
													handleToggle( {
														id: 'auto_delete_days',
														status: value,
													} )
												}
											/>
										</div>
									) }
								</>
							) }
					</div>
				</div>
			</div>
		</div>
	);
};

export default Compliance;
