import { Skeleton } from '@bsf/force-ui';
import { cn } from '@Utils/Helpers';
import { Fragment } from '@wordpress/element';

const LoadingSkeleton = ( { count, variant = 'rectangular', className } ) => {
	return Array.from( { length: count }, ( _, index ) => (
		<Fragment key={ index }>
			<Skeleton
				variant={ variant }
				className={ cn( 'w-full inline-flex', className ) }
			/>
			<br />
		</Fragment>
	) );
};

export default LoadingSkeleton;
