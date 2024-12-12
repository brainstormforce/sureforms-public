( function () {
	/**
	 * Handles the entry notes functionality, including showing the notes field
	 * and submitting new notes via AJAX.
	 *
	 * @since x.x.x
	 */
	function handleEntryNotes() {
		const entryNotes = document.querySelector( '.entry-notes' );

		if ( ! entryNotes ) {
			return;
		}

		const addNoteBtn = document.querySelector(
			'.srfm-add-entry-note-button'
		);
		const notesContainer = document.querySelector(
			'.srfm-entry-note-wrapper .entry-notes-container'
		);

		const notesField = document.querySelector( '.add-notes-field' );
		const textarea = notesField.querySelector( '#srfm-entry-note' );
		const submitNoteBtn = notesField.querySelector( 'button' );
		const spinner = entryNotes.querySelector( '.in-progress-overlay' );

		// Function to handle notes navigation work.
		const navigateNotes = () => {
			let lastResponse = {};
			const navBtns = document.querySelectorAll( '.entry-notes-nav-btn' );

			const fetchMarkup = async ( args = {} ) => {
				spinner.classList.remove( 'hidden' );

				const formData = new FormData();

				formData.append( 'entryID', srfm_entries.entryID );

				// Use last response as payload.
				Object.keys( lastResponse ).forEach( ( responseKey ) => {
					if ( 'markup' === responseKey ) {
						return;
					}
					formData.append( responseKey, lastResponse[ responseKey ] );
				} );

				// If any additional arguments provided.
				Object.keys( args ).forEach( ( argKey ) => {
					formData.append( argKey, args[ argKey ] );
				} );

				await fetch( srfm_entries.ajaxURLs.navigateNotes, {
					method: 'POST',
					body: formData,
				} )
					.then( ( res ) => res.json() )
					.then( ( res ) => {
						if ( ! res?.success ) {
							return;
						}

						notesContainer.innerHTML = res?.data?.markup;
						lastResponse = res?.data || {};

						if ( false === lastResponse?.nextPage ) {
							document
								.querySelector(
									'.entry-notes-nav-btn[data-type="next"]'
								)
								.classList.add( 'disabled' );
						} else {
							document
								.querySelector(
									'.entry-notes-nav-btn[data-type="next"]'
								)
								.classList.remove( 'disabled' );
						}

						if ( false === lastResponse?.prevPage ) {
							document
								.querySelector(
									'.entry-notes-nav-btn[data-type="prev"]'
								)
								.classList.add( 'disabled' );
						} else {
							document
								.querySelector(
									'.entry-notes-nav-btn[data-type="prev"]'
								)
								.classList.remove( 'disabled' );
						}
					} )
					.finally( () => {
						spinner.classList.add( 'hidden' );
					} );
			};

			// Fetch logs on first page load.
			fetchMarkup();

			navBtns.forEach( ( navBtn ) => {
				navBtn.addEventListener( 'click', function ( e ) {
					e.preventDefault();

					fetchMarkup( {
						type: this.getAttribute( 'data-type' ),
					} );
				} );
			} );
		};

		/**
		 * Handles the click event for the add note button,
		 * showing the notes input field.
		 *
		 * @param {Event} e - The click event.
		 * @since x.x.x
		 */
		addNoteBtn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			addNoteBtn.classList.add( 'hidden' );
			notesField.classList.remove( 'hidden' );
		} );

		/**
		 * Handles the click event for submitting a new note,
		 * sends the note data to the server via AJAX.
		 *
		 * @param {Event} e - The click event.
		 * @since x.x.x
		 */
		submitNoteBtn.addEventListener( 'click', async function ( e ) {
			e.preventDefault();

			spinner.classList.remove( 'hidden' );

			const formData = new FormData();

			formData.append( 'note', textarea.value );
			formData.append( 'entryID', srfm_entries.entryID );

			await fetch( srfm_entries.ajaxURLs.saveNotes, {
				method: 'POST',
				body: formData,
			} )
				.then( ( res ) => res.json() )
				.then( () => {
					textarea.value = '';

					// Refetch notes on submit.
					navigateNotes();
				} );
		} );

		navigateNotes();
	}

	/**
	 * Handles the functionality for resending notifications, including displaying
	 * the modal, managing selected entries, and submitting the resend request.
	 *
	 * @since x.x.x
	 */
	function handleEntryResendNotification() {
		const dialog = document.getElementById(
			'srfm-resend-notification-modal'
		);
		const resendNotificationTriggerBtn = document.querySelector(
			'.srfm-resend-notification-trigger-btn'
		);

		if ( ! dialog && ! resendNotificationTriggerBtn ) {
			return;
		}

		// Set bulk entries ID.
		const entryIds = {};
		const bulkCheckboxes = document.querySelectorAll(
			'.sureforms-table-container tbody .check-column input'
		);

		const bulkSelectCheckboxes = document.querySelectorAll(
			'.sureforms-table-container .column-cb input'
		);

		/**
		 * Handles the input event for bulk checkboxes, updating entry IDs
		 * and showing/hiding the trigger button.
		 *
		 * @since x.x.x
		 */
		bulkCheckboxes.forEach( function ( bulkCheckbox ) {
			bulkCheckbox.addEventListener( 'input', function () {
				entryIds[ this.value ] = this.checked;
				hideShowTriggerBtn();
			} );
		} );

		/**
		 * Handles the input event for bulk select checkboxes, updating
		 * the state of all bulk checkboxes and showing/hiding the trigger button.
		 *
		 * @since x.x.x
		 */
		bulkSelectCheckboxes.forEach( function ( bulkSelectCheckbox ) {
			bulkSelectCheckbox.addEventListener( 'input', function () {
				bulkCheckboxes.forEach( function ( bulkCheckbox ) {
					entryIds[ bulkCheckbox.value ] = bulkCheckbox.checked;
				} );

				hideShowTriggerBtn();
			} );
		} );

		/**
		 * Shows or hides the resend notification trigger button based on
		 * selected entries.
		 *
		 * @since x.x.x
		 */
		function hideShowTriggerBtn() {
			if (
				Object.keys( entryIds ).filter(
					( key ) => entryIds[ key ] === true
				).length > 0
			) {
				resendNotificationTriggerBtn.classList.remove( 'hidden' );
			} else {
				resendNotificationTriggerBtn.classList.add( 'hidden' );
			}
		}

		let resendNotificationMsgTimeOut = 0;

		/**
		 * Handle the resend notification message box.
		 *
		 * @param {Object} response Response from server.
		 */
		function handleResendNotificationMessage( response ) {
			const wpBodyContent = document.getElementById( 'wpbody-content' );
			wpBodyContent.insertAdjacentHTML( 'beforeend', response.data );

			wpBodyContent
				.querySelector( '.srfm-resend-notification-message .close' )
				?.addEventListener( 'click', function () {
					document
						.querySelector( '.srfm-resend-notification-message' )
						?.remove();
				} );

			// Clear previous running timeout (if any).
			clearTimeout( resendNotificationMsgTimeOut );

			// Auto close notification after 3 seconds.
			resendNotificationMsgTimeOut = setTimeout(
				() =>
					document
						.querySelector( '.srfm-resend-notification-message' )
						?.remove(),
				5000
			);

			dialog.close();
		}

		// Hide/show recipient field.
		const recipientFieldGroup = dialog.querySelector(
			'.recipient-field-group'
		);
		dialog
			.querySelector( '[name="send_to"]' )
			?.addEventListener( 'change', function () {
				if ( 'default' === this.value ) {
					recipientFieldGroup.classList.add( 'hidden' );
				} else {
					recipientFieldGroup.classList.remove( 'hidden' );
				}
			} );

		const fields = dialog.querySelectorAll(
			'.srfm-resend-notification-field'
		);
		const resendBtn = dialog.querySelector( '.srfm-resend-notification' );
		const cancelBtn = dialog.querySelector(
			'.srfm-cancel-resend-notification'
		);

		const inProgressOverlay = dialog.querySelector(
			'.in-progress-overlay'
		);

		// Display modal on trigger button click
		/**
		 * Handles the click event for the trigger button to display the
		 * resend notification modal.
		 *
		 * @param {Event} e - The click event.
		 * @since x.x.x
		 */
		resendNotificationTriggerBtn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			document
				.querySelector( '.srfm-resend-notification-message' )
				?.remove();
			dialog.showModal();
		} );

		/**
		 * Handles the click event for the resend button, submitting the
		 * notification resend request via AJAX.
		 *
		 * @param {Event} e - The click event.
		 * @since x.x.x
		 */
		resendBtn?.addEventListener( 'click', async function ( e ) {
			e.preventDefault();

			document
				.querySelector( '.srfm-resend-notification-message' )
				?.remove();
			inProgressOverlay.classList.remove( 'hidden' );

			const formData = new FormData();

			fields.forEach( function ( field ) {
				formData.append( field.getAttribute( 'name' ), field.value );
			} );

			if ( Object.keys( entryIds ).length ) {
				formData.append(
					'entry_ids',
					Object.keys( entryIds )
						.filter( ( key ) => entryIds[ key ] === true )
						.join( ',' )
				);
			}

			await fetch( srfm_entries.ajaxURLs.resendNotification, {
				method: 'POST',
				body: formData,
			} )
				.then( ( res ) => res.json() )
				.then( handleResendNotificationMessage )
				.finally( () => inProgressOverlay.classList.add( 'hidden' ) );
		} );

		/**
		 * Handles the click event for the cancel button to close the modal.
		 *
		 * @param {Event} e - The click event.
		 * @since x.x.x
		 */
		cancelBtn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			dialog.close();
		} );
	}

	function handleEditEntry() {
		const dialog = document.getElementById( 'srfm-edit-entry-modal' );

		if ( ! dialog ) {
			return;
		}

		const editEntryBtn = document.querySelector(
			'.button.srfm-edit-entry'
		);
		const cancelBtns = document.querySelectorAll( '.srfm-cancel-entry-btn' );

		editEntryBtn.addEventListener( 'click', function ( e ) {
			e.preventDefault();
			dialog.showModal();

			document.body.style.overflow = 'hidden';
		} );

		cancelBtns.forEach((cancelBtn) => {
			cancelBtn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				dialog.close();

				document.body.style.overflow = 'scroll';
			} );
		});
	}

	function handleLogsNavigation() {
		let lastResponse = {};
		let lastPayload = {};
		const spinner = document.querySelector(
			'.srfm-entry-logs .in-progress-overlay'
		);
		const logsWrapper = document.querySelector(
			'.srfm-entry-logs .inside'
		);
		const navBtns = document.querySelectorAll(
			'.entry-logs-navigation-btn'
		);

		const fetchMarkup = async ( args = {} ) => {
			spinner.classList.remove( 'hidden' );

			const formData = new FormData();

			formData.append( 'entryID', srfm_entries.entryID );

			// Use last response as payload.
			Object.keys( lastResponse ).forEach( ( responseKey ) => {
				if ( 'markup' === responseKey ) {
					return;
				}
				formData.append( responseKey, lastResponse[ responseKey ] );
			} );

			// If any additional arguments provided.
			Object.keys( args ).forEach( ( argKey ) => {
				formData.append( argKey, args[ argKey ] );
			} );

			lastPayload = lastResponse;

			await fetch( srfm_entries.ajaxURLs.navigateLogs, {
				method: 'POST',
				body: formData,
			} )
				.then( ( res ) => res.json() )
				.then( ( res ) => {
					if ( ! res?.success ) {
						return;
					}
					lastResponse = res?.data || {};

					logsWrapper.innerHTML = lastResponse?.markup;

					const deleteBtns =
						document.querySelectorAll( '.btn-delete-log' );

					deleteBtns.forEach( ( deleteBtn ) => {
						deleteBtn.addEventListener( 'click', function ( e ) {
							e.preventDefault();

							if (
								confirm(
									wp.i18n.__(
										'Are you sure you want to delete this log?',
										'sureforms'
									)
								)
							) {
								fetchMarkup( {
									deleteLog:
										this.getAttribute( 'data-log-key' ),
									...lastPayload,
								} );
							}
						} );
					} );

					if ( false === lastResponse?.nextPage ) {
						document
							.querySelector(
								'.entry-logs-navigation-btn[data-type="next"]'
							)
							.classList.add( 'disabled' );
					} else {
						document
							.querySelector(
								'.entry-logs-navigation-btn[data-type="next"]'
							)
							.classList.remove( 'disabled' );
					}

					if ( false === lastResponse?.prevPage ) {
						document
							.querySelector(
								'.entry-logs-navigation-btn[data-type="prev"]'
							)
							.classList.add( 'disabled' );
					} else {
						document
							.querySelector(
								'.entry-logs-navigation-btn[data-type="prev"]'
							)
							.classList.remove( 'disabled' );
					}
				} )
				.finally( () => {
					spinner.classList.add( 'hidden' );
				} );
		};

		// Fetch logs on first page load.
		fetchMarkup();

		navBtns.forEach( ( navBtn ) => {
			navBtn.addEventListener( 'click', function ( e ) {
				e.preventDefault();
				fetchMarkup( {
					type: this.getAttribute( 'data-type' ),
				} );
			} );
		} );
	}

	window.addEventListener( 'load', function () {
		handleEditEntry();
		handleEntryNotes();
		handleLogsNavigation();
		handleEntryResendNotification();
	} );
}() );
