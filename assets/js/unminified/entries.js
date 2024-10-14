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

		const textarea = document.querySelector('.add-notes-field #srfm-entry-note');
		const submitNoteBtn = document.querySelector('.add-notes-field button');

		submitNoteBtn.addEventListener('click', async function(e) {
			e.preventDefault();

			const formData = new FormData();

			formData.append( 'note', textarea.value );
			formData.append( 'entryID', srfm_entries.entryID );

			await fetch( srfm_entries.ajaxURL, {
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

	window.addEventListener("load", function() {
		handleEntryNotes();
	});
}());