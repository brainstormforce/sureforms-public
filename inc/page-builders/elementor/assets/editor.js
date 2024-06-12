document.addEventListener( 'DOMContentLoaded', function () {
	if ( typeof elementor === 'undefined' ) {
		return;
	}

	// Form edit link
	elementor.channels.editor.on( 'sureforms:form:edit', function ( view ) {
		const block_id = view.elementSettingsModel.get( 'srfm_form_block' );
		if ( ! block_id ) {
			return;
		}

		const win = window.open(
			srfmElementorData.admin_url +
				`post.php?post=${ block_id }&action=edit`,
			'_blank'
		);
		win.focus();
	} );

	// Form create link
	elementor.channels.editor.on( 'sureforms:form:create', function () {
		const win = window.open( srfmElementorData.add_new_form_url, '_blank' );
		win.focus();
	} );
} );
