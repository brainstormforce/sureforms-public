import { useCallback } from '@wordpress/element';
import { useNavigate } from 'react-router-dom';

/**
 * Opens the template library (`?page=add-new-form&view=templates`) in place,
 * through the Add New Form screen's router, so no page reload is needed.
 *
 * @since 2.13.0
 * @return {Function} Opens the library.
 */
const useOpenTemplateLibrary = () => {
	const navigate = useNavigate();
	return useCallback(
		() => navigate( { search: '?page=add-new-form&view=templates' } ),
		[ navigate ]
	);
};

export default useOpenTemplateLibrary;
