// import apiFetch from '@wordpress/api-fetch';
// import { dispatch } from '@wordpress/data';
// import { addQueryArgs } from '@wordpress/url';
// import { store } from './store';

const setInitialState = () => {
	// const queryParams = {};
	// // If post page.
	// if ( surerank_seo_popup?.post_id ) {
	// 	queryParams.post_id = surerank_seo_popup.post_id;
	// }
	// // If term page.
	// if ( surerank_seo_popup?.term_id ) {
	// 	queryParams.term_id = surerank_seo_popup.term_id;
	// }

	// apiFetch( {
	// 	path: addQueryArgs( '/surerank/v1/editor', queryParams ),
	// } ).then( ( response ) => {
	// 	if ( response?.success ) {
	// 		let initialState = {
	// 			variables: response.variables,
	// 		};

	// 		if ( response.other ) {
	// 			initialState = { ...initialState, ...response.other };
	// 		}

	// 		dispatch( store ).updateInitialState( initialState );
	// 	}
	// } );
};

export default setInitialState;
