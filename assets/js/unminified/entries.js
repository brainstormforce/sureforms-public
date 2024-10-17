(function() {
	/**
	 * This function follow below steps:
	 * 1. Check if entry notes div exists or not.
	 * 2. Toggle on/off note textarea and submit notes button.
	 * 3. Do the ajax request to save the added notes and display the response.
	 */
	function handleEntryNotes() {
		const entryNotes = document.querySelector( '.entry-notes' );

		if ( ! entryNotes ) {
			return;
		}

		const addNoteBtn = document.querySelector( '.srfm-add-entry-note-button' );
		const entryNotesContainer = document.querySelector( '.srfm-entry-note-wrapper .entry-notes-container' );

		const notesField = document.querySelector('.add-notes-field');
		const textarea = notesField.querySelector('#srfm-entry-note');
		const submitNoteBtn = notesField.querySelector('button');

		addNoteBtn.addEventListener('click', function(e) {
			e.preventDefault();
			addNoteBtn.classList.add('hidden');
			notesField.classList.remove('hidden');
		});

		submitNoteBtn.addEventListener('click', async function(e) {
			e.preventDefault();

			const formData = new FormData();

			formData.append( 'note', textarea.value );
			formData.append( 'entryID', srfm_entries.entryID );

			await fetch( srfm_entries.ajaxURLs.saveNotes, {
				method: 'POST',
				body: formData
			} )
			.then( ( res ) => res.json() )
			.then( ( res ) => {
				entryNotesContainer.innerHTML = res.data;
				textarea.value = '';
			} );
		});

	}

	function handleEntryResendNotification() {
		const dialog = document.getElementById('srfm-resend-notification-modal');
		const resendNotificationTriggerBtn = document.querySelector( '.srfm-resend-notification-trigger-btn' );

		if ( ! dialog && ! resendNotificationTriggerBtn) {
			return;
		}

		// Set bulk entries ID.
		const entryIds = {};
		const bulkCheckboxes = document.querySelectorAll('.sureforms-table-container tbody .check-column input');

		const bulkSelectCheckboxes = document.querySelectorAll( '.sureforms-table-container .column-cb input' );

		bulkCheckboxes.forEach(function(bulkCheckbox) {
			bulkCheckbox.addEventListener('input', function() {
				entryIds[this.value] = this.checked;
				hideShowTriggerBtn();
			});
		});

		bulkSelectCheckboxes.forEach(function(bulkSelectCheckbox) {
			bulkSelectCheckbox.addEventListener('input', function() {
				bulkCheckboxes.forEach(function(bulkCheckbox) {
					entryIds[bulkCheckbox.value] = bulkCheckbox.checked;
				});

				hideShowTriggerBtn();
			});
		});

		function hideShowTriggerBtn() {
			if (Object.keys(entryIds).filter(key => entryIds[key] === true).length > 0) {
				resendNotificationTriggerBtn.classList.remove('hidden');
			} else {
				resendNotificationTriggerBtn.classList.add('hidden');
			}
		}

		// Hide/show recipient field.
		const recipientFieldGroup = dialog.querySelector('.recipient-field-group');
		dialog.querySelector('[name="send_to"]')
		?.addEventListener('change',function() {
			if ( 'default' === this.value ) {
				recipientFieldGroup.classList.add('hidden');
			} else {
				recipientFieldGroup.classList.remove('hidden');
			}
		});

		const fields    = dialog.querySelectorAll('.srfm-resend-notification-field');
		const resendBtn = dialog.querySelector('.srfm-resend-notification');
		const cancelBtn = dialog.querySelector('.srfm-cancel-resend-notification');

		// Display modal on trigger button click
		resendNotificationTriggerBtn.addEventListener('click', function(e) {
			e.preventDefault();
			dialog.showModal();
		});

		resendBtn?.addEventListener('click',async function(e) {
			e.preventDefault();

			const formData = new FormData();

			fields.forEach(function(field) {
				formData.append( field.getAttribute('name'), field.value );
			});

			if ( Object.keys(entryIds).length ) {
				formData.append('entry_ids', Object.keys(entryIds).filter(key => entryIds[key] === true).join(','));
			}

			await fetch( srfm_entries.ajaxURLs.resendNotification, {
				method: 'POST',
				body: formData
			} )
			.then( res => res.json() )
			.then( ( res ) => {
				console.log(res);
			} );
		});

		cancelBtn.addEventListener('click', function(e) {
			e.preventDefault();
			dialog.close();
		});

	}

	window.addEventListener("load", function() {
		handleEntryNotes();
		handleEntryResendNotification();
	});
}());