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
		const entryNotesContainer = document.querySelector(
			'.srfm-entry-note-wrapper .entry-notes-container'
		);

		const notesField = document.querySelector( '.add-notes-field' );
		const textarea = notesField.querySelector( '#srfm-entry-note' );
		const submitNoteBtn = notesField.querySelector( 'button' );

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

			const formData = new FormData();

			formData.append( 'note', textarea.value );
			formData.append( 'entryID', srfm_entries.entryID );

			await fetch( srfm_entries.ajaxURLs.saveNotes, {
				method: 'POST',
				body: formData,
			} )
				.then( ( res ) => res.json() )
				.then( ( res ) => {
					entryNotesContainer.innerHTML = res.data;
					textarea.value = '';
				} );
		} );
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
			dialog.querySelector( 'details' )?.remove();
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

			dialog.querySelector( 'details' )?.remove();
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
				.then( ( res ) => {
					if ( ! res?.success ) {
						alert( res?.data );
						return;
					}

					dialog
						.querySelector( '.modal-content' )
						?.insertAdjacentHTML( 'beforeend', res?.data );
				} )
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

	window.addEventListener( 'load', function () {
		handleEntryNotes();
		handleEntryResendNotification();
	} );
}() );
