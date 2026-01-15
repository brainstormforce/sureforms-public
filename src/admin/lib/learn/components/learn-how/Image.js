import { useState } from '@wordpress/element';
import { Skeleton } from '@bsf/force-ui';

/**
 * Image component with loading skeleton
 *
 * @param {Object} props     - Component props.
 * @param {string} props.src - Image source URL.
 * @param {string} props.alt - Image alt text.
 * @return {JSX.Element} Image component.
 */
const Image = ( { src, alt } ) => {
	const [ isLoaded, setIsLoaded ] = useState( false );

	return (
		<div className="relative w-full shadow-sm rounded-lg overflow-hidden">
			{ ! isLoaded && (
				<Skeleton className="w-full h-64" />
			) }
			<img
				src={ src }
				alt={ alt || '' }
				className={ `w-full h-auto border-0.5 border-solid border-border-subtle ${ isLoaded ? 'block' : 'hidden' }` }
				onLoad={ () => setIsLoaded( true ) }
			/>
		</div>
	);
};

export default Image;
