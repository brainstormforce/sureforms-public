const getSRFMEditorStateLocalStorage = ( key = false ) => {
	if ( ! window.localStorage ) {
		return null;
	}

	if ( ! key ) {
		return localStorage;
	}

	const srfmLastOpenedSettingState = localStorage.getItem( key );

	if ( srfmLastOpenedSettingState ) {
		return JSON.parse( srfmLastOpenedSettingState );
	}

	return null;
};

export default getSRFMEditorStateLocalStorage;
