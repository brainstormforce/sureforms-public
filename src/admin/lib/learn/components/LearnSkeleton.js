import { Skeleton } from '@bsf/force-ui';

/**
 * Module header skeleton component
 */
const ModuleHeaderSkeleton = () => {
	return (
		<div className="p-4 sm:p-6 flex items-center gap-4 flex-wrap sm:flex-nowrap">
			<Skeleton
				variant="rectangular"
				className="w-6 h-6 bg-background-secondary rounded"
			/>
			<div className="flex-1 min-w-0">
				<Skeleton
					variant="rectangular"
					className="w-48 h-6 bg-background-secondary rounded"
				/>
			</div>
			<div className="flex items-center gap-2">
				<Skeleton
					variant="rectangular"
					className="w-24 h-4 bg-background-secondary rounded"
				/>
				<Skeleton
					variant="rectangular"
					className="w-32 h-2 bg-background-secondary rounded"
				/>
			</div>
			<Skeleton
				variant="rectangular"
				className="w-36 h-9 bg-background-secondary rounded"
			/>
		</div>
	);
};

/**
 * Expanded module skeleton with lessons
 *
 * @param {Object} props             - Component props
 * @param {number} props.lessonCount - Number of lessons to show
 */
const ExpandedModuleSkeleton = ( { lessonCount = 4 } ) => {
	return (
		<div className="bg-background-primary rounded-xl shadow-sm border border-solid border-border-strong">
			<ModuleHeaderSkeleton />

			{ /* Lessons Section */ }
			<div className="px-4 sm:px-6 pb-4 sm:pb-6 flex flex-col gap-3">
				{ /* First lesson expanded */ }
				<div className="border border-solid border-border-subtle rounded-lg">
					<div className="p-4 flex items-center gap-3">
						<Skeleton
							variant="circular"
							className="w-6 h-6 bg-background-secondary"
						/>
						<div className="flex-1">
							<Skeleton
								variant="rectangular"
								className="w-2/3 h-5 bg-background-secondary rounded"
							/>
						</div>
						<Skeleton
							variant="rectangular"
							className="w-20 h-6 bg-background-secondary rounded hidden sm:block"
						/>
						<Skeleton
							variant="rectangular"
							className="w-6 h-6 bg-background-secondary rounded"
						/>
					</div>
					<div className="p-4 pt-0 flex flex-col gap-4">
						<Skeleton
							variant="rectangular"
							className="w-full h-48 sm:h-64 bg-background-secondary rounded-lg"
						/>
						<Skeleton
							variant="rectangular"
							className="w-full h-4 bg-background-secondary rounded"
						/>
						<Skeleton
							variant="rectangular"
							className="w-3/4 h-4 bg-background-secondary rounded"
						/>
						<div className="flex flex-col gap-3 mt-2">
							<Skeleton
								variant="rectangular"
								className="w-full h-10 bg-background-secondary rounded"
							/>
							<Skeleton
								variant="rectangular"
								className="w-full h-10 bg-background-secondary rounded"
							/>
						</div>
					</div>
				</div>

				{ /* Remaining collapsed lessons */ }
				{ Array( lessonCount - 1 )
					.fill( 0 )
					.map( ( _, index ) => (
						<div
							key={ `lesson-skeleton-${ index }` }
							className="border border-solid border-border-subtle rounded-lg p-4 flex items-center gap-3"
						>
							<Skeleton
								variant="circular"
								className="w-6 h-6 bg-background-secondary"
							/>
							<div className="flex-1">
								<Skeleton
									variant="rectangular"
									className="w-2/3 h-5 bg-background-secondary rounded"
								/>
							</div>
							<Skeleton
								variant="rectangular"
								className="w-20 h-6 bg-background-secondary rounded hidden sm:block"
							/>
							<Skeleton
								variant="rectangular"
								className="w-6 h-6 bg-background-secondary rounded"
							/>
						</div>
					) ) }
			</div>
		</div>
	);
};

/**
 * Collapsed module skeleton (header only)
 */
const CollapsedModuleSkeleton = () => {
	return (
		<div className="bg-background-primary rounded-xl shadow-sm border border-solid border-border-subtle">
			<ModuleHeaderSkeleton />
		</div>
	);
};

/**
 * Skeleton loading component for BsfLearn
 * Shows loading state while fetching chapters data from API
 *
 * @since 1.0.0
 */
const LearnSkeleton = () => {
	return (
		<div className="flex flex-col gap-4">
			{ /* First module expanded with lessons */ }
			<ExpandedModuleSkeleton lessonCount={ 6 } />

			{ /* Second module collapsed */ }
			<CollapsedModuleSkeleton />

			{ /* Third module collapsed */ }
			<CollapsedModuleSkeleton />
		</div>
	);
};

export default LearnSkeleton;
