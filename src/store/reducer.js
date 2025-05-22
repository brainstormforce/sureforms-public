const defaultState = {
	a: 1,
};

// Add filter to add default state. so we can extend the default state from the "sureforms-pro" plugin.
function reducer( state = defaultState ) {
	return state;
}

export default reducer;
