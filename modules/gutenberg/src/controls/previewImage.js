const PreviewImage = ( { image, isChildren = false } ) => {
	if ( ! image ) {
		console.error( __( 'Please add preview image.', 'sureforms' ) ); // eslint-disable-line
	}

	let imgUrl = srfm_spec_blocks_info.uagb_url;
	imgUrl += '/assets/images/block-previews/';
	if ( isChildren ) {
		imgUrl += 'children/';
	}
	imgUrl += image + '.svg';
	return <img width="100%" src={ imgUrl } alt="" />;
};

export default PreviewImage;
