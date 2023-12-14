import { __ } from '@wordpress/i18n';

const GbsNotice = ( { globalBlockStyleId, globalBlockStyleName } ) => {
	if (
		'enabled' === srfm_spec_blocks_info?.uag_enable_gbs_extension &&
		globalBlockStyleId &&
		globalBlockStyleName
	) {
		return (
			<div className="spectra-gbs-notice">
				<span className="spectra-gbs-notice-text">
					{ __( 'Global block style added', 'sureforms' ) }
				</span>
			</div>
		);
	}
	return null;
};

export default GbsNotice;
