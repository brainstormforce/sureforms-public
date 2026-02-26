jQuery( document ).ready( function ( $ ) {
	// Check with the server if the pointer should be shown
	$.post(
		sureformsPointerData.ajaxurl,
		{
			action: 'should_show_pointer',
			pointer_nonce: sureformsPointerData.pointer_nonce,
		},
		function ( response ) {
			if ( ! response || ! response.show ) {
				return;
			}

			let $target = $( '#toplevel_page_sureforms_menu' );
			if ( ! $target.length ) {
				$target = $( '#menu-plugins' ); // fallback
			}

			const pointerContent =
				'<h3>' +
				response.title +
				'</h3>' +
				'<p>' +
				response.content +
				'</p>';

			let pointerClosedBy = null; // 'cta' or 'dismiss' or null

			$target
				.pointer( {
					content: pointerContent,
					position: {
						edge: 'left',
						align: 'center',
					},
					buttons( event, t ) {
						const dismissBtn = $(
							'<a class="close" href="#" style="margin-left:8px;"></a>'
						)
							.text( response.dismiss )
							.on( 'click.pointer', function ( e ) {
								e.preventDefault();
								pointerClosedBy = 'dismiss';
								t.element.pointer( 'close' );
								$.post( sureformsPointerData.ajaxurl, {
									action: 'sureforms_dismiss_pointer',
									pointer_nonce:
										sureformsPointerData.pointer_nonce,
								} );
							} );

						const ctaBtn = $(
							'<a class="button button-primary" href="' +
								response.button_url +
								'" style="margin-right:8px;">' +
								response.button_text +
								'</a>'
						).on( 'click.pointer', function () {
							pointerClosedBy = 'cta';
							t.element.pointer( 'close' );
							$.post( sureformsPointerData.ajaxurl, {
								action: 'sureforms_accept_cta',
								pointer_nonce:
									sureformsPointerData.pointer_nonce,
							} );
						} );

						// Wrap both buttons in a div and return as a jQuery object
						return $(
							'<div style="display:flex;justify-content:space-between;align-items:center;width:100%"></div>'
						)
							.append( ctaBtn )
							.append( dismissBtn );
					},
					close() {
						if ( ! pointerClosedBy ) {
							// Only run if closed by other means (not by our buttons)
							$.post( sureformsPointerData.ajaxurl, {
								action: 'sureforms_dismiss_pointer',
								pointer_nonce:
									sureformsPointerData.pointer_nonce,
							} );
						}
					},
				} )
				.pointer( 'open' );
		}
	);
} );
