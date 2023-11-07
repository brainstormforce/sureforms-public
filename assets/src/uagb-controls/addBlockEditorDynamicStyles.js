const addBlockEditorDynamicStyles = () => {
	setTimeout( () => {
		const getAllIFrames = document.getElementsByTagName( 'iframe' );
		if ( ! getAllIFrames?.length ) {
			return;
		}

		const cloneLinkTag = ( linkId ) => {
			const getTag = document.getElementById( linkId );
			return getTag ? getTag.cloneNode( true ) : false;
		};

		const cloneStyleTag = ( styleId ) => {
			const getStyleTag = document.getElementById( styleId );
			return getStyleTag ? getStyleTag.textContent : false;
		};

		const dashiconsCss = cloneLinkTag( 'dashicons-css' );
		const blockCssCss = cloneLinkTag( 'srfm-block-css-css' );
		const slickStyle = cloneLinkTag( 'srfm-slick-css-css' );
		const swiperStyle = cloneLinkTag( 'srfm-swiper-css-css' );
		const aosStyle = cloneLinkTag( 'srfm-aos-css-css' );

		const editorStyle = cloneStyleTag( 'srfm-editor-styles' );
		const editorProStyle = cloneStyleTag( 'spectra-pro-editor-styles' );
		const spacingStyle = cloneStyleTag(
			'srfm-blocks-editor-spacing-style'
		);
		const editorCustomStyle = cloneStyleTag(
			'srfm-blocks-editor-custom-css'
		);

		for ( const iterateIFrames of getAllIFrames ) {
			const iframeDocument =
				iterateIFrames?.contentWindow.document ||
				iterateIFrames?.contentDocument;
			if ( ! iframeDocument?.head ) {
				continue;
			}

			const copyLinkTag = ( clonedTag, tagId ) => {
				if ( ! clonedTag ) {
					return;
				}
				const isExistTag = iframeDocument.getElementById( tagId );
				if ( isExistTag ) {
					return;
				}
				iframeDocument.head.appendChild( clonedTag );
			};

			const copyStyleTag = ( clonedTag, tagId ) => {
				if ( ! clonedTag ) {
					return;
				}
				const isExistTag = iframeDocument.getElementById( tagId );
				if ( ! isExistTag ) {
					const node = document.createElement( 'style' );
					node.setAttribute( 'id', tagId );
					node.textContent = clonedTag;
					iframeDocument.head.appendChild( node );
				} else {
					isExistTag.textContent = clonedTag;
				}
			};

			copyLinkTag( blockCssCss, 'srfm-block-css-css' );
			copyLinkTag( dashiconsCss, 'dashicons-css' );
			copyLinkTag( slickStyle, 'srfm-slick-css-css' );
			copyLinkTag( swiperStyle, 'srfm-swiper-css-css' );
			copyLinkTag( aosStyle, 'srfm-aos-css-css' );

			copyStyleTag( editorStyle, 'srfm-editor-styles' );
			copyStyleTag( editorProStyle, 'spectra-pro-editor-styles' );
			copyStyleTag( spacingStyle, 'srfm-blocks-editor-spacing-style' );
			copyStyleTag( editorCustomStyle, 'srfm-blocks-editor-custom-css' );
		} // Loop end.
	} );
};

export default addBlockEditorDynamicStyles;
