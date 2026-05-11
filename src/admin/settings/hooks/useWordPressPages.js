import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { toast } from '@bsf/force-ui';

/**
 * Fetches published WordPress pages for use in page-selector dropdowns.
 *
 * @return {{ pageOptions: Array, loadingPages: boolean }} Page options and loading state.
 */
const useWordPressPages = () => {
	const [ pageOptions, setPageOptions ] = useState( [] );
	const [ loadingPages, setLoadingPages ] = useState( true );

	useEffect( () => {
		apiFetch( { path: '/wp/v2/pages?per_page=100&status=publish' } )
			.then( ( pages ) =>
				setPageOptions(
					pages.map( ( page ) => ( {
						label:
							page.title.rendered ||
							__( '(no title)', 'sureforms' ),
						value: page.link,
					} ) )
				)
			)
			.catch( () =>
				toast.error(
					__(
						'Failed to load pages. Please refresh and try again.',
						'sureforms'
					)
				)
			)
			.finally( () => setLoadingPages( false ) );
	}, [] );

	return { pageOptions, loadingPages };
};

export default useWordPressPages;
