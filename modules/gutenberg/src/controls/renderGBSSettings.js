import GlobalBlockStyles from '@Components/global-block-link';

const renderGBSSettings = ( styling, setAttributes, attributes ) => {
	if (
		! srfm_spec_blocks_info?.spectra_pro_status ||
		'enabled' !== srfm_spec_blocks_info?.uag_enable_gbs_extension
	) {
		return null;
	}

	return <GlobalBlockStyles { ...{ setAttributes, styling, attributes } } />;
};

export default renderGBSSettings;
