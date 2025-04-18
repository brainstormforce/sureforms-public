const siteUrl = srfm_backend.site_url;

document.addEventListener( 'DOMContentLoaded', function () {
	changeAddNewUrl();
} );

function changeAddNewUrl() {
	const addNewBtn = document.querySelector( '.page-title-action' );
	if ( addNewBtn ) {
		addNewBtn.classList.add( 'button' );
		addNewBtn.classList.add( 'button-secondary' );
		addNewBtn.classList.add( 'button-large' );
		addNewBtn.classList.add( 'srfm-add-new-btn' );
		addNewBtn.setAttribute(
			'href',
			`${ siteUrl }/wp-admin/admin.php?page=add-new-form`
		);
		addNewBtn.classList.remove( 'page-title-action' );
	}
}
