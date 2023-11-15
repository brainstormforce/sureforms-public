function BlockEditorPreviewCompatibility ( cssStyleID, styleTagId, styling ) {


	// Required CSS File.
		const WidgetCssFileTag = document.getElementById( cssStyleID );
		let cloneWidgetCssFileTag = false;

		if ( WidgetCssFileTag ) {

			cloneWidgetCssFileTag = WidgetCssFileTag.cloneNode( true );
		}

    	// Desktop.
		let element = document.getElementById(
			styleTagId
		);

		if ( null === element || undefined === element ) {

			const $style = document.createElement( 'style' );
			$style.setAttribute(
				'id',
				styleTagId
			);
			document.head.appendChild( $style );
		}

		if ( null !== element && undefined !== element ) {
			element.innerHTML = styling;
		}
		// Desktop ends.

		// Tablet / Mobile Starts.
		let tabletPreview = document.getElementsByClassName('is-tablet-preview');
		let mobilePreview = document.getElementsByClassName('is-mobile-preview');

		if ( 0 !== tabletPreview.length || 0 !== mobilePreview.length ) {

			let preview = tabletPreview[0] || mobilePreview[0];

			let iframe = preview.getElementsByTagName('iframe')[0];
			let iframeDocument = iframe.contentWindow.document || iframe.contentDocument;

			let element = iframeDocument.getElementById(
				styleTagId
			);
			if ( null === element || undefined === element ) {

				const $style = document.createElement( 'style' );
				$style.setAttribute(
					'id',
					styleTagId
					);

				iframeDocument.head.appendChild( $style );
			}

			// Required CSS File.
            if ( cloneWidgetCssFileTag ) {
                iframeDocument.head.appendChild( cloneWidgetCssFileTag );
            }

			element = iframeDocument.getElementById(
				styleTagId
			);

			if ( null !== element && undefined !== element ) {
				element.innerHTML = styling;
			}
		}
}

export default BlockEditorPreviewCompatibility;
