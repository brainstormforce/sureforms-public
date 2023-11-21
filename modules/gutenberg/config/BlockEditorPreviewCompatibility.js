function BlockEditorPreviewCompatibility( cssStyleID, styleTagId, styling ) {
	// Required CSS File.
	const WidgetCssFileTag = document.getElementById( cssStyleID );
	let cloneWidgetCssFileTag = false;

	if ( WidgetCssFileTag ) {
		cloneWidgetCssFileTag = WidgetCssFileTag.cloneNode( true );
	}

	// Desktop.
	const element = document.getElementById( styleTagId );

	if ( null === element || undefined === element ) {
		const $style = document.createElement( 'style' );
		$style.setAttribute( 'id', styleTagId );
		document.head.appendChild( $style );
	}

	if ( null !== element && undefined !== element ) {
		element.innerHTML = styling;
	}
	// Desktop ends.

	// Tablet / Mobile Starts.
	const tabletPreview =
		document.getElementsByClassName( 'is-tablet-preview' );
	const mobilePreview =
		document.getElementsByClassName( 'is-mobile-preview' );

	if ( 0 !== tabletPreview.length || 0 !== mobilePreview.length ) {
		const preview = tabletPreview[ 0 ] || mobilePreview[ 0 ];

		const iframe = preview.getElementsByTagName( 'iframe' )[ 0 ];
		const iframeDocument =
			iframe.contentWindow.document || iframe.contentDocument;

		let elementRes = iframeDocument.getElementById( styleTagId );
		if ( null === elementRes || undefined === elementRes ) {
			const $style = document.createElement( 'style' );
			$style.setAttribute( 'id', styleTagId );

			iframeDocument.head.appendChild( $style );
		}

		// Required CSS File.
		if ( cloneWidgetCssFileTag ) {
			iframeDocument.head.appendChild( cloneWidgetCssFileTag );
		}

		elementRes = iframeDocument.getElementById( styleTagId );

		if ( null !== elementRes && undefined !== elementRes ) {
			elementRes.innerHTML = styling;
		}
	}
}

export default BlockEditorPreviewCompatibility;
