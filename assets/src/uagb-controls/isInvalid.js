const isInvalid = ( block ) => {
	const { name, isValid, validationIssues } = block;

	if ( ! name || ! name.match( /^srfm\// ) ) {
		return false;
	}

	if ( isValid || ! validationIssues.length ) {
		return false;
	}

	return true;
};

export default isInvalid;
